# NPF-familjen – Drift

Så sätts servern upp och så driftsätts nya versioner. Appen körs på https://npf-familjen.duckdns.org.

| Del | Hur |
| --- | --- |
| Server | Debian 12 hos en leverantör |
| Webbserver | Nginx + PHP 8.4 (packages.sury.org), PHP-FPM kör som användaren deploy |
| Databas | MariaDB 12.3 LTS |
| HTTPS | Let's Encrypt via Certbot, förnyas automatiskt |
| Deploy | GitHub Actions + Deployer 8, utan driftstopp |
| Kö och schemaläggare | systemd-tjänsten npf-familjen-queue och /etc/cron.d/npf-familjen |
| Backup | spatie/laravel-backup varje natt 02:30 till /var/backups/npf-familjen, 14 dagar |
| E-post | Gmail med applösenord |

Konfigurationsfilerna finns i [deploy/server/](../deploy/server/) och Deployer i [deploy.php](../deploy.php).

---

## 1. Innan du börjar

- Du kan logga in på servern som root med SSH.
- DuckDNS-adressen pekar på serverns IP-adress (`dig +short npf-familjen.duckdns.org`).
- Port 22, 80 och 443 är öppna i leverantörens brandvägg.

## 2. Skapa en deploy-nyckel (på din dator)

GitHub Actions loggar in på servern med en egen nyckel, aldrig med ditt lösenord.

```bash
ssh-keygen -t ed25519 -C "github-actions-npf-familjen" -f ~/.ssh/npf_deploy -N ""
cat ~/.ssh/npf_deploy.pub
```

Kopiera den publika nyckeln (raden som börjar med `ssh-ed25519`).

## 3. Kör installationsskriptet (på servern, som root)

```bash
apt-get update && apt-get -y install git
git clone https://github.com/MrGullman/adhd.git /root/npf-setup
bash /root/npf-setup/deploy/server/setup.sh "ssh-ed25519 AAAA… github-actions-npf-familjen" "din@epost.se"
```

Skriptet installerar allt, skapar databasen och `.env`, ställer in brandväggen och hämtar HTTPS-certifikatet. Det tar några minuter och kan köras igen utan att något skrivs över.

## 4. Lägg in nycklarna i GitHub

På GitHub: **Settings → Secrets and variables → Actions**.

Secrets:

| Namn | Värde |
| --- | --- |
| `DEPLOY_SSH_KEY` | Innehållet i `~/.ssh/npf_deploy` (den privata nyckeln, hela filen) |
| `DEPLOY_KNOWN_HOSTS` | Utskriften av `ssh-keyscan npf-familjen.duckdns.org` |

Variables:

| Namn | Värde |
| --- | --- |
| `DEPLOY_ENABLED` | `true` |

## 5. Första driftsättningen

1. På GitHub: **Actions → deploy → Run workflow** på `main`.
2. När den är klar, starta köworkern på servern: `systemctl start npf-familjen-queue`
3. Öppna https://npf-familjen.duckdns.org.

Efter detta driftsätts varje push till `main` automatiskt när testerna har gått igenom.

## 6. Skapa ditt konto och stäng registreringen

1. Skapa ditt konto på https://npf-familjen.duckdns.org/register och skapa familjen.
2. Stäng registreringen, så att ingen annan kan skapa konton. Andra vuxna bjuds in via e-post.

```bash
sed -i 's/^REGISTRATION_ENABLED=.*/REGISTRATION_ENABLED=false/' /var/www/npf-familjen/shared/.env
sudo -u deploy php8.4 /var/www/npf-familjen/current/artisan optimize
```

## 7. E-post via Gmail

Använd ett separat Gmail-konto för appen. Ett applösenord ger åtkomst till hela brevlådan.

1. Skapa Gmail-kontot och slå på tvåstegsverifiering.
2. Skapa ett applösenord: Google-konto → Säkerhet → Applösenord.
3. Fyll i `MAIL_USERNAME`, `MAIL_PASSWORD` (applösenordet) och `MAIL_FROM_ADDRESS` (samma Gmail-adress) i `/var/www/npf-familjen/shared/.env`.
4. Kör `sudo -u deploy php8.4 /var/www/npf-familjen/current/artisan optimize`.
5. Testa:

```bash
cd /var/www/npf-familjen/current
sudo -u deploy php8.4 artisan tinker --execute="Mail::raw('Test från NPF-familjen.', fn (\$m) => \$m->to('din@epost.se')->subject('Test'));"
```

## 8. Backup

Backupen tas varje natt kl. 02:30 och sparas i 14 dagar i `/var/backups/npf-familjen`. Den innehåller databasen och uppladdade filer. Koden finns på GitHub.

Ta en backup direkt:

```bash
cd /var/www/npf-familjen/current && sudo -u deploy php8.4 artisan backup:run
```

Återställ (som root):

```bash
ls /var/backups/npf-familjen/NPF-familjen/
unzip /var/backups/npf-familjen/NPF-familjen/<datum>.zip -d /tmp/aterstall
mariadb npf_familjen < /tmp/aterstall/db-dumps/mariadb-npf_familjen.sql
```

Uppladdade filer ligger i zippen under en sökväg som slutar på `storage/app`. Kopiera innehållet till `/var/www/npf-familjen/shared/storage/app/` och kör `chown -R deploy:deploy /var/www/npf-familjen/shared/storage`. Ta bort `/tmp/aterstall` efteråt.

Testa återställningen en gång när servern är klar, så vet du att den fungerar.

Backupen ligger på samma server. Den skyddar mot misstag och trasiga uppdateringar, men inte om servern går sönder. Ladda gärna ner en kopia ibland.

## 9. Gå tillbaka till förra versionen

Från din dator, med deploy-nyckeln:

```bash
ssh-add ~/.ssh/npf_deploy
vendor/bin/dep rollback
```

## 10. Rekommenderat efteråt

När du har kontrollerat att du kommer in med SSH-nyckel kan du stänga av lösenordsinloggning för SSH: sätt `PasswordAuthentication no` i `/etc/ssh/sshd_config` och kör `systemctl reload ssh`.

## Felsökning

| Var | Kommando |
| --- | --- |
| Appens logg | `tail -f /var/www/npf-familjen/shared/storage/logs/laravel-*.log` |
| Nginx | `tail -f /var/log/nginx/error.log` |
| Köworkern | `journalctl -u npf-familjen-queue -f` |
| PHP-FPM | `journalctl -u php8.4-fpm -f` |
