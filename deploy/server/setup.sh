#!/usr/bin/env bash
#
# Lägger till NPF-familjen på en Debian 12-server där andra projekt redan körs. Se docs/DRIFT.md.
#
# Skriptet lägger bara till sådant som hör till NPF-familjen: en egen användare, en egen PHP-FPM-pool,
# en egen Nginx-sajt, en egen databas och en egen databasanvändare. Det uppgraderar inte systemet och
# ändrar inte brandväggen, andra Nginx-sajter, andra PHP-pooler eller MariaDB:s inställningar.
#
# Kör med sudo från en klon av repot:
#   sudo bash deploy/server/setup.sh "<publik SSH-nyckel för GitHub Actions>" "<din e-post>"
#
# Skriptet kan köras flera gånger. Det skriver aldrig över en befintlig .env.

set -euo pipefail

DOMAIN="npf-familjen.duckdns.org"
APP_USER="npf-familjen"
APP_DIR="/var/www/npf-familjen"
BACKUP_DIR="/var/backups/npf-familjen"
DB_NAME="npf_familjen"
DB_USER="npf_familjen"
DB_COLLATION="utf8mb4_uca1400_swedish_ai_ci"
PHP="8.4"
HERE="$(cd "$(dirname "$0")" && pwd)"

DEPLOY_KEY="${1:-}"
EMAIL="${2:-}"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
fail() { printf '\033[1;31m%s\033[0m\n' "$1"; exit 1; }

[ "$(id -u)" -eq 0 ] || fail "Kör skriptet med sudo."
grep -q 'VERSION_CODENAME=bookworm' /etc/os-release || fail "Skriptet är gjort för Debian 12 (bookworm)."
[ -n "$DEPLOY_KEY" ] || fail "Ange den publika SSH-nyckeln för GitHub Actions som första argument."
[ -n "$EMAIL" ] || fail "Ange din e-post som andra argument."
command -v nginx >/dev/null || fail "Nginx saknas. Skriptet förutsätter att Nginx redan körs på servern."
command -v mariadb >/dev/null || fail "MariaDB saknas. Skriptet förutsätter att MariaDB redan körs på servern."
[ -f /etc/apt/sources.list.d/php.list ] || fail "Paketförrådet packages.sury.org saknas, så PHP ${PHP} kan inte installeras."

export DEBIAN_FRONTEND=noninteractive

step "Installerar det som saknas (befintliga paket uppgraderas inte)"
apt-get update
apt-get -y install --no-upgrade git unzip curl acl certbot python3-certbot-nginx \
    "php${PHP}-fpm" "php${PHP}-cli" "php${PHP}-mysql" "php${PHP}-mbstring" "php${PHP}-xml" \
    "php${PHP}-curl" "php${PHP}-zip" "php${PHP}-bcmath" "php${PHP}-intl" "php${PHP}-gd" "php${PHP}-opcache"

step "Kontrollerar MariaDB"
mariadb -N -e "SELECT _utf8mb4'a' COLLATE ${DB_COLLATION}" >/dev/null 2>&1 \
    || fail "MariaDB saknar sorteringen ${DB_COLLATION} (kräver MariaDB 10.10 eller senare)."
mariadb -N -e "SELECT VERSION()"

step "Installerar Composer"
if ! command -v composer >/dev/null; then
    EXPECTED="$(curl -sS https://composer.github.io/installer.sig)"
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$("php${PHP}" -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [ "$EXPECTED" = "$ACTUAL" ] || fail "Composer-installationens kontrollsumma stämmer inte."
    "php${PHP}" /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm /tmp/composer-setup.php
else
    echo "Composer finns redan."
fi

step "Skapar användaren ${APP_USER}"
if ! id "$APP_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "NPF-familjen" "$APP_USER"
fi
install -d -m 700 -o "$APP_USER" -g "$APP_USER" "/home/${APP_USER}/.ssh"
touch "/home/${APP_USER}/.ssh/authorized_keys"
grep -qxF "$DEPLOY_KEY" "/home/${APP_USER}/.ssh/authorized_keys" \
    || echo "$DEPLOY_KEY" >> "/home/${APP_USER}/.ssh/authorized_keys"
chown "$APP_USER:$APP_USER" "/home/${APP_USER}/.ssh/authorized_keys"
chmod 600 "/home/${APP_USER}/.ssh/authorized_keys"

step "Skapar mappar"
install -d -o "$APP_USER" -g "$APP_USER" "$APP_DIR" "$APP_DIR/shared" "$APP_DIR/shared/storage"
install -d -m 700 -o "$APP_USER" -g "$APP_USER" "$BACKUP_DIR"

step "Skapar databasen och databasanvändaren"
if [ ! -f "$APP_DIR/shared/.env" ]; then
    DB_PASSWORD="$(openssl rand -hex 24)"
    mariadb <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE ${DB_COLLATION};
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
DB_COLLATION=${DB_COLLATION}

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
# Hit skickas mejl om backupen misslyckas.
BACKUP_NOTIFICATION_EMAIL=${EMAIL}
ENV
    chown "$APP_USER:$APP_USER" "$APP_DIR/shared/.env"
    chmod 600 "$APP_DIR/shared/.env"
else
    echo "Det finns redan en .env. Databasen och .env lämnas orörda."
fi

step "Lägger till PHP-FPM-pool, köworker och schemaläggare"
install -m 644 "$HERE/php-fpm.conf" "/etc/php/${PHP}/fpm/pool.d/npf-familjen.conf"
install -m 644 "$HERE/npf-familjen-queue.service" /etc/systemd/system/npf-familjen-queue.service
install -m 644 "$HERE/cron" /etc/cron.d/npf-familjen
if ! "php-fpm${PHP}" -t; then
    rm -f "/etc/php/${PHP}/fpm/pool.d/npf-familjen.conf"
    fail "PHP-FPM-konfigurationen är fel. NPF-familjens pool är borttagen igen och inget har laddats om."
fi
systemctl daemon-reload
systemctl enable "php${PHP}-fpm" npf-familjen-queue
# reload i stället för restart, så att andra projekts pågående anrop inte avbryts.
systemctl reload-or-restart "php${PHP}-fpm"

step "Lägger till Nginx-sajten"
if [ ! -f /etc/nginx/sites-available/npf-familjen ]; then
    install -m 644 "$HERE/nginx.conf" /etc/nginx/sites-available/npf-familjen
else
    echo "Sajten finns redan (Certbot kan ha lagt till HTTPS i den). Den lämnas orörd."
fi
ln -sf /etc/nginx/sites-available/npf-familjen /etc/nginx/sites-enabled/npf-familjen
if ! nginx -t; then
    rm -f /etc/nginx/sites-enabled/npf-familjen
    fail "Nginx-konfigurationen är fel. NPF-familjens sajt är bortkopplad igen och inget har laddats om."
fi
systemctl reload nginx

step "Hämtar HTTPS-certifikat från Let's Encrypt"
certbot --nginx -d "$DOMAIN" --cert-name "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect --keep-until-expiring

step "Klart"
cat <<DONE

NPF-familjen är tillagd på servern. Nästa steg står i docs/DRIFT.md:
  1. Lägg in serverns host key och deploy-nyckeln som GitHub Secrets.
  2. Sätt GitHub-variabeln DEPLOY_ENABLED=true och kör deploy-flödet.
  3. Starta köworkern: sudo systemctl start npf-familjen-queue
  4. Skapa ditt konto och familjen, och stäng sedan registreringen.
  5. Fyll i Gmail-uppgifterna i ${APP_DIR}/shared/.env.

Databaslösenordet finns bara i ${APP_DIR}/shared/.env.
DONE
