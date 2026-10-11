#!/usr/bin/env bash
#
# Förbereder en Debian 12-server för NPF-familjen. Se docs/DRIFT.md.
#
# Kör som root från en klon av repot:
#   bash deploy/server/setup.sh "<publik SSH-nyckel för GitHub Actions>" "<din e-post för Let's Encrypt>"
#
# Skriptet kan köras flera gånger. Det skriver aldrig över en befintlig .env.

set -euo pipefail

DOMAIN="npf-familjen.duckdns.org"
APP_DIR="/var/www/npf-familjen"
BACKUP_DIR="/var/backups/npf-familjen"
DB_NAME="npf_familjen"
DB_USER="npf_familjen"
PHP="8.4"
HERE="$(cd "$(dirname "$0")" && pwd)"

DEPLOY_KEY="${1:-}"
CERT_EMAIL="${2:-}"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
fail() { printf '\033[1;31m%s\033[0m\n' "$1"; exit 1; }

[ "$(id -u)" -eq 0 ] || fail "Kör skriptet som root."
grep -q 'VERSION_CODENAME=bookworm' /etc/os-release || fail "Skriptet är gjort för Debian 12 (bookworm)."
[ -n "$DEPLOY_KEY" ] || fail "Ange den publika SSH-nyckeln för GitHub Actions som första argument."
[ -n "$CERT_EMAIL" ] || fail "Ange din e-post för Let's Encrypt som andra argument."

export DEBIAN_FRONTEND=noninteractive

step "Uppdaterar systemet och installerar grundpaket"
apt-get update
apt-get -y upgrade
apt-get -y install ca-certificates curl gnupg lsb-release git unzip acl \
    nginx certbot python3-certbot-nginx ufw fail2ban unattended-upgrades

step "Lägger till PHP-förrådet (packages.sury.org)"
if [ ! -f /etc/apt/sources.list.d/php.list ]; then
    curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
    dpkg -i /tmp/debsuryorg-archive-keyring.deb
    echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ bookworm main" \
        > /etc/apt/sources.list.d/php.list
fi

step "Lägger till MariaDB-förrådet (12.3 LTS)"
if ! ls /etc/apt/sources.list.d/ | grep -q mariadb; then
    curl -LsS https://r.mariadb.com/downloads/mariadb_repo_setup \
        | bash -s -- --mariadb-server-version="mariadb-12.3" --skip-maxscale
fi

apt-get update

step "Installerar PHP ${PHP} och MariaDB"
apt-get -y install \
    "php${PHP}-fpm" "php${PHP}-cli" "php${PHP}-mysql" "php${PHP}-mbstring" "php${PHP}-xml" \
    "php${PHP}-curl" "php${PHP}-zip" "php${PHP}-bcmath" "php${PHP}-intl" "php${PHP}-gd" "php${PHP}-opcache" \
    mariadb-server mariadb-client

step "Installerar Composer"
if ! command -v composer >/dev/null; then
    EXPECTED="$(curl -sS https://composer.github.io/installer.sig)"
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [ "$EXPECTED" = "$ACTUAL" ] || fail "Composer-installationens kontrollsumma stämmer inte."
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm /tmp/composer-setup.php
fi

step "Skapar användaren deploy"
if ! id deploy >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" deploy
fi
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
touch /home/deploy/.ssh/authorized_keys
grep -qxF "$DEPLOY_KEY" /home/deploy/.ssh/authorized_keys || echo "$DEPLOY_KEY" >> /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
chmod 600 /home/deploy/.ssh/authorized_keys

step "Skapar mappar"
install -d -o deploy -g deploy "$APP_DIR" "$APP_DIR/shared" "$APP_DIR/shared/storage"
install -d -m 700 -o deploy -g deploy "$BACKUP_DIR"

step "Skapar databasen"
if [ ! -f "$APP_DIR/shared/.env" ]; then
    DB_PASSWORD="$(openssl rand -hex 24)"
    mariadb <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_swedish_ai_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

    step "Skriver .env"
    APP_KEY="base64:$(openssl rand -base64 32)"
    cat > "$APP_DIR/shared/.env" <<ENV
APP_NAME="NPF-familjen"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://${DOMAIN}

APP_LOCALE=sv
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=sv_SE

# Öppen tills du har skapat ditt konto och familjen. Sätt sedan till false (se docs/DRIFT.md).
REGISTRATION_ENABLED=true

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_COLLATION=utf8mb4_uca1400_swedish_ai_ci

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

# Gmail med applösenord från ett separat Gmail-konto för appen (se docs/DRIFT.md).
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="NPF-familjen"

BACKUP_PATH=${BACKUP_DIR}
ENV
    chown deploy:deploy "$APP_DIR/shared/.env"
    chmod 600 "$APP_DIR/shared/.env"
else
    echo "Det finns redan en .env. Databasen och .env lämnas orörda."
fi

step "Ställer in PHP-FPM, Nginx, köworker och schemaläggare"
install -m 644 "$HERE/php-fpm.conf" "/etc/php/${PHP}/fpm/pool.d/npf-familjen.conf"
install -m 644 "$HERE/nginx.conf" /etc/nginx/sites-available/npf-familjen
ln -sf /etc/nginx/sites-available/npf-familjen /etc/nginx/sites-enabled/npf-familjen
rm -f /etc/nginx/sites-enabled/default
install -m 644 "$HERE/npf-familjen-queue.service" /etc/systemd/system/npf-familjen-queue.service
install -m 644 "$HERE/cron" /etc/cron.d/npf-familjen
systemctl daemon-reload
systemctl enable "php${PHP}-fpm" nginx mariadb npf-familjen-queue
systemctl restart "php${PHP}-fpm"
nginx -t
systemctl reload nginx

step "Brandvägg, fail2ban och automatiska säkerhetsuppdateringar"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
systemctl enable --now fail2ban
echo 'unattended-upgrades unattended-upgrades/enable_auto_updates boolean true' | debconf-set-selections
dpkg-reconfigure -f noninteractive unattended-upgrades

step "Hämtar HTTPS-certifikat från Let's Encrypt"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$CERT_EMAIL" --redirect

step "Klart"
cat <<DONE

Servern är förberedd. Nästa steg står i docs/DRIFT.md:
  1. Lägg in serverns host key och deploy-nyckeln som GitHub Secrets.
  2. Sätt GitHub-variabeln DEPLOY_ENABLED=true och kör deploy-flödet.
  3. Starta köworkern: systemctl start npf-familjen-queue
  4. Skapa ditt konto och familjen, och stäng sedan registreringen.
  5. Fyll i Gmail-uppgifterna i ${APP_DIR}/shared/.env.

Databaslösenordet finns bara i ${APP_DIR}/shared/.env.
DONE
