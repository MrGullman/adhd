# NPF-familjen – Drift

Så sätts servern upp och så driftsätts nya versioner. Appen körs på https://npf-familjen.duckdns.org.

| Del | Hur |
| --- | --- |
| Server | Debian 12 hos en leverantör, delad med andra projekt |
| Användare | `npf-familjen` äger filerna och kör appen, precis som de andra projekten har egna användare |
| Webbserver | Serverns Nginx (egen sajt) + PHP 8.4 från packages.sury.org (egen PHP-FPM-pool) |
| Databas | Serverns MariaDB, med egen databas och egen databasanvändare |
| HTTPS | Let's Encrypt via Certbot, förnyas automatiskt |
| Deploy | GitHub Actions + Deployer 8, utan driftstopp |
| Kö och schemaläggare | systemd-tjänsten npf-familjen-queue och /etc/cron.d/npf-familjen |
| Backup | spatie/laravel-backup varje natt 02:30 till /var/backups/npf-familjen, 14 dagar |
| E-post | Gmail med applösenord |

Konfigurationsfilerna finns i [deploy/server/](../deploy/server/) och Deployer i [deploy.php](../deploy.php).

---

## 1. Innan du börjar

- Du kan logga in på servern med ditt eget konto och köra `sudo`.
- Nginx, MariaDB (10.10 eller senare) och paketförrådet packages.sury.org finns redan på servern.
- DuckDNS-adressen pekar på serverns IP-adress (`dig +short npf-familjen.duckdns.org`).
- Port 22, 80 och 443 är öppna i leverantörens brandvägg.

## 2. Skapa en deploy-nyckel (på din dator)

GitHub Actions loggar in på servern med en egen nyckel, aldrig med ditt lösenord.

```bash
ssh-keygen -t ed25519 -C "github-actions-npf-familjen" -f ~/.ssh/npf_deploy -N ""
cat ~/.ssh/npf_deploy.pub
```

Kopiera den publika nyckeln (raden som börjar med `ssh-ed25519`).

## 3. Kör installationsskriptet (på servern, med sudo)

```bash
git clone https://github.com/MrGullman/adhd.git ~/npf-setup
sudo bash ~/npf-setup/deploy/server/setup.sh "ssh-ed25519 AAAA… github-actions-npf-familjen" "din@epost.se"
```

Skriptet lägger bara till NPF-familjens egna delar: användaren `npf-familjen`, en PHP-FPM-pool, en Nginx-sajt, en databas med egen användare, `.env`, köworker, schemaläggare och HTTPS-certifikat. Det uppgraderar inte systemet och ändrar inte brandväggen, MariaDB:s inställningar eller de andra projekten. Om Nginx- eller PHP-FPM-konfigurationen inte går igenom kopplas NPF-familjens del bort igen innan något laddas om.

Det tar några minuter och kan köras igen utan att något skrivs över. När det är klart kan du ta bort `~/npf-setup`.

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
2. När den är klar, starta köworkern på servern: `sudo systemctl start npf-familjen-queue`
3. Öppna https://npf-familjen.duckdns.org.

Efter detta driftsätts varje push till `main` automatiskt när testerna har gått igenom.

## 6. Skapa ditt konto och stäng registreringen

1. Skapa ditt konto på https://npf-familjen.duckdns.org/register och skapa familjen.
2. Stäng registreringen, så att ingen annan kan skapa konton. Andra vuxna bjuds in via e-post.

```bash
sudo sed -i 's/^REGISTRATION_ENABLED=.*/REGISTRATION_ENABLED=false/' /var/www/npf-familjen/shared/.env
sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan optimize
```

## Adminkonto för bildgalleriet

Adminportalen finns på https://npf-familjen.duckdns.org/admin. Där laddar du upp bilder till galleriet som alla familjer kan välja från.

Skapa ett eget adminkonto. Det är skilt från familjens konton och tillhör ingen familj. Kommandot frågar efter namn, e-post och lösenord:

```bash
sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan admin:create
```

Logga sedan in som vanligt på https://npf-familjen.duckdns.org/login. Adminkontot hamnar direkt i adminportalen. Slå på tvåstegsverifiering under Inställningar.

Ett befintligt konto kan också göras till admin, eller tas bort som admin:

```bash
sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan users:admin <e-post>
sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan users:admin <e-post> --remove
```

## 7. E-post via Gmail

Använd ett separat Gmail-konto för appen. Ett applösenord ger åtkomst till hela brevlådan.

1. Skapa Gmail-kontot och slå på tvåstegsverifiering.
2. Skapa ett applösenord: Google-konto → Säkerhet → Applösenord.
3. Fyll i `MAIL_USERNAME`, `MAIL_PASSWORD` (applösenordet) och `MAIL_FROM_ADDRESS` (samma Gmail-adress) i `/var/www/npf-familjen/shared/.env`.
4. Kör `sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan optimize`.
5. Testa:

```bash
cd /var/www/npf-familjen/current
sudo -u npf-familjen php8.4 artisan tinker --execute="Mail::raw('Test från NPF-familjen.', fn (\$m) => \$m->to('din@epost.se')->subject('Test'));"
```

## Integritetspolicyn

Fyll i vem som är personuppgiftsansvarig i `/var/www/npf-familjen/shared/.env` och kör `artisan optimize`:

```
PRIVACY_CONTROLLER="Namn"
PRIVACY_CONTACT="e-post"
```

Policyn finns på `/integritet`.

## Push-notiser (påminnelser)

Påminnelser skickas som push-notiser och kräver ett nyckelpar (VAPID). Gör detta en gång, efter första driftsättningen med Milestone 16:

```bash
cd /var/www/npf-familjen/current
sudo -u npf-familjen php8.4 artisan webpush:vapid
sudo -u npf-familjen php8.4 artisan optimize
sudo -u npf-familjen php8.4 artisan queue:restart
```

Kommandot skriver `VAPID_PUBLIC_KEY` och `VAPID_PRIVATE_KEY` i `/var/www/npf-familjen/shared/.env`. Kör det aldrig igen på en server som används: nya nycklar gör att alla enheter måste slå på påminnelser på nytt. Spara nycklarna tillsammans med `APP_KEY`.

Påminnelserna skickas av schemaläggaren varje minut (`reminders:send`) och går via kön.

## 8. Backup

Backupen tas varje natt kl. 02:30 och sparas i 14 dagar i `/var/backups/npf-familjen`. Den innehåller databasen och uppladdade filer. Koden finns på GitHub.

Ta en backup direkt:

```bash
cd /var/www/npf-familjen/current && sudo -u npf-familjen php8.4 artisan backup:run
```

Återställ:

```bash
sudo ls /var/backups/npf-familjen/NPF-familjen/
sudo unzip /var/backups/npf-familjen/NPF-familjen/<datum>.zip -d /tmp/aterstall
sudo mariadb npf_familjen < /tmp/aterstall/db-dumps/mariadb-npf_familjen.sql
```

Uppladdade filer ligger i zippen under en sökväg som slutar på `storage/app`. Kopiera innehållet till `/var/www/npf-familjen/shared/storage/app/` och kör `sudo chown -R npf-familjen:npf-familjen /var/www/npf-familjen/shared/storage`. Ta bort `/tmp/aterstall` efteråt.

Testa återställningen en gång när servern är klar, så vet du att den fungerar.

Backupen ligger på samma server. Den skyddar mot misstag och trasiga uppdateringar, men inte om servern går sönder. Ladda gärna ner en kopia ibland.

### Spara APP_KEY säkert

Dagboken, kalenderlänkarna och barnens koder är krypterade med appens nyckel, `APP_KEY` i `/var/www/npf-familjen/shared/.env`. Backupen innehåller databasen men inte `.env`. Försvinner nyckeln går dagboken inte att läsa, inte ens från en backup.

Spara nyckeln på ett säkert ställe utanför servern, t.ex. i din lösenordshanterare:

```bash
sudo grep ^APP_KEY= /var/www/npf-familjen/shared/.env
```

Byt aldrig nyckeln på en server som redan används.

## 9. Gå tillbaka till förra versionen

Från din dator, med deploy-nyckeln:

```bash
ssh-add ~/.ssh/npf_deploy
vendor/bin/dep rollback
```

## 10. Rekommenderat efteråt

Det här gäller hela servern och alla projekt på den, så skriptet gör det inte:

- MariaDB lyssnar på alla nätverk (port 3306). Om inget projekt behöver nå databasen utifrån: sätt `bind-address = 127.0.0.1` i `/etc/mysql/mariadb.conf.d/50-server.cnf` och starta om MariaDB.
- En brandvägg (ufw eller leverantörens) som bara släpper in 22, 80 och 443.
- fail2ban mot inloggningsförsök på SSH.
- Stäng av lösenordsinloggning för SSH när alla som behöver logga in använder nyckel: `PasswordAuthentication no` i `/etc/ssh/sshd_config` och sedan `sudo systemctl reload ssh`.

## Hemskärm på Raspberry Pi

Skärmen är en vanlig webbsida på en hemlig länk. Raspberry Pi:n visar den i Chromium i helskärm. Det här är förberett men inte provat på en riktig Pi än.

**Det här behövs:** Raspberry Pi 4 eller 5 (2 GB räcker), ett minneskort på minst 16 GB, en strömadapter, en skärm eller tv med HDMI och wifi eller nätverkskabel.

### 1. Skapa skärmen i appen

Familjen → Hemskärmar → Skapa hemskärm. Kopiera länken som visas (den visas bara en gång, annars "Skapa ny länk"). Välj widgets och storlek i redigeraren.

### 2. Installera Raspberry Pi OS

Använd Raspberry Pi Imager på datorn:

- Välj **Raspberry Pi OS (64-bit)**, med skrivbord.
- Under inställningar (kugghjulet): sätt användarnamn och lösenord, wifi, land **SE** och tidszon **Europe/Stockholm**, och slå på SSH.

### 3. Starta Chromium i helskärm vid uppstart

Logga in på Pi:n (med skärm och tangentbord, eller `ssh användare@raspberrypi.local`) och kör:

```bash
sudo apt-get update && sudo apt-get -y install chromium unclutter
mkdir -p ~/.config/autostart
cat > ~/.config/autostart/hemskarm.desktop <<'EOF2'
[Desktop Entry]
Type=Application
Name=Hemskärm
Exec=sh -c 'sleep 10; chromium --kiosk --noerrdialogs --disable-infobars --disable-session-crashed-bubble --check-for-update-interval=31536000 "LÄNKEN"'
EOF2
```

Byt `LÄNKEN` mot länken från steg 1, med citattecknen kvar. Starta om med `sudo reboot`. Skärmen startar efter en kort stund. Muspekaren döljs av `unclutter`.

### 4. Skärmen av på natten (valfritt)

Stänger av bilden 22:00 och slår på den 06:00:

```bash
(crontab -l 2>/dev/null; echo "0 22 * * * wlr-randr --output HDMI-A-1 --off"; echo "0 6 * * * wlr-randr --output HDMI-A-1 --on") | crontab -
```

Namnet på utgången (`HDMI-A-1`) visas med `wlr-randr`. Fungerar på Raspberry Pi OS Bookworm och senare (Wayland).

### Bra att veta

- Skärmen uppdateras varje minut och byter till mörkt tema på kvällen.
- Står Pi:n på ett nätverk som blockerar duckdns.org (t.ex. ett jobbnätverk) syns ingenting.
- Ta bort skärmen i appen så slutar länken fungera direkt.

## Felsökning

| Var | Kommando |
| --- | --- |
| Appens logg | `tail -f /var/www/npf-familjen/shared/storage/logs/laravel-*.log` |
| Nginx | `tail -f /var/log/nginx/error.log` |
| Köworkern | `journalctl -u npf-familjen-queue -f` |
| PHP-FPM | `journalctl -u php8.4-fpm -f` |
