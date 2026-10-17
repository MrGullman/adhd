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

## Snabbguide: jobba på servern

Logga in med ditt eget konto: `ssh jeppe@62.119.70.55`.

Användaren `npf-familjen` har **inget lösenord**, med flit (`adduser --disabled-password`). Ingen kan logga in som appen med lösenord, bara deployen med sin nyckel. Därför fungerar inte `su - npf-familjen`. Använd `sudo`, som frågar efter ditt eget lösenord:

| Vill du … | Skriv |
| --- | --- |
| bli `npf-familjen` en stund (`exit` för att gå tillbaka) | `sudo -iu npf-familjen` |
| köra ett artisan-kommando | `sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan <kommando>` |
| ändra inställningar (`.env`) | `sudo nano /var/www/npf-familjen/shared/.env` |
| läsa in ändrad `.env` | `sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan optimize` och `sudo systemctl restart npf-familjen-queue` |
| se appens logg | `sudo tail -n 100 /var/www/npf-familjen/shared/storage/logs/laravel-*.log` |
| se nginx fel (t.ex. 502 Bad Gateway) | `sudo tail -n 50 /var/log/nginx/error.log` |
| se om kön går | `sudo systemctl status npf-familjen-queue` |

Sätt aldrig ett lösenord på `npf-familjen` med `passwd`. Då försvinner skyddet, och `sudo` räcker till allt.

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

Registreringen öppnas och stängs i adminportalen: Konton → Familjer → **Stäng registreringen** / **Öppna registreringen**. Ändringen gäller direkt och syns i Konton → Logg. Inbjudna vuxna kan alltid gå med.

`REGISTRATION_ENABLED` i `.env` gäller bara tills knappen har använts första gången.

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

Integritetspolicyn på `/integritet` ska visa vem som ansvarar för familjernas uppgifter (GDPR). Det fylls i på servern, aldrig i koden:

- `PRIVACY_CONTROLLER`: den som är personuppgiftsansvarig, alltså den som bestämmer över uppgifterna. Driver du appen själv är det ditt namn. Står ett företag eller en förening bakom: dess namn (gärna med organisationsnummer).
- `PRIVACY_CONTACT`: dit familjer vänder sig med frågor, rättelser eller klagomål. En e-postadress räcker, gärna en separat adress i stället för din privata.

Båda syns publikt på `/integritet`.

```bash
sudo nano /var/www/npf-familjen/shared/.env
```

```
PRIVACY_CONTROLLER="Förnamn Efternamn"
PRIVACY_CONTACT="integritet@exempel.se"
```

```bash
sudo -u npf-familjen php8.4 /var/www/npf-familjen/current/artisan optimize
```

Tomma fält visar "Uppgift om personuppgiftsansvarig saknas ännu". Fyll i dem innan andra familjer bjuds in.

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

Skärmen är en vanlig webbsida på en hemlig länk. Raspberry Pi:n startar direkt i den, i helskärm och utan skrivbord. Provat och fungerar på **Raspberry Pi 3 Model B (1 GB)** med Raspberry Pi OS Lite (Debian 13 Trixie), oktober 2026.

**Det här behövs:** Raspberry Pi 3, 4 eller 5, ett minneskort på minst 8 GB (16 GB är bättre), en strömadapter som räcker (Pi 3: micro-USB 5 V, minst 2,5 A), en skärm eller tv med HDMI och wifi eller nätverkskabel.

**Uppmätt på Pi 3:** cirka 500 MB minne ledigt med hemskärmen igång, belastning under 1, 54 °C och ingen strypning (`vcgencmd get_throttled` ger `0x0`). Pi 3 räcker.

### 1. Skapa skärmen i appen

Familjen → Hemskärmar → Skapa hemskärm. Kopiera länken som visas (den visas bara en gång, annars "Skapa ny länk"). Välj widgets och storlek i redigeraren.

### 2. Skriv systemet till minneskortet

I Raspberry Pi Imager på datorn:

- **Enhet:** din Pi. **Operativsystem:** Raspberry Pi OS (other) → **Raspberry Pi OS Lite (64-bit)**, utan skrivbord, så att webbläsaren får så mycket minne som möjligt.
- **Redigera inställningar:** värdnamn `npf-skarm`, användare (t.ex. `npf`) och lösenord, wifi, land **SE**, tidszon **Europe/Stockholm**, tangentbord **se**, och slå på **SSH**.

Sätt kortet i Pi:n, koppla in HDMI och sist strömmen. Efter 3–5 minuter: `ssh npf@npf-skarm.local` från datorn.

### 3. Installera och spara länken

```bash
sudo apt update && sudo apt full-upgrade -y
sudo apt install -y labwc chromium wlopm wlrctl
nano ~/hemskarm-url          # klistra in länken från steg 1, spara med Ctrl+O, stäng med Ctrl+X
```

### 4. Webbläsaren i helskärm, på svenska och utan översättning

```bash
sudo mkdir -p /etc/chromium/policies/managed
echo '{"TranslateEnabled": false}' | sudo tee /etc/chromium/policies/managed/npf-familjen.json
cat > ~/kiosk.sh <<'EOF'
#!/bin/sh
# Visar hemskärmen i helskärm. Startar om webbläsaren om den skulle stänga.
URL="$(cat "$HOME/hemskarm-url")"
while true; do
  chromium \
    --kiosk --lang=sv --accept-lang=sv-SE --noerrdialogs --disable-infobars \
    --disable-session-crashed-bubble --disable-features=Translate \
    --overscroll-history-navigation=0 --check-for-update-interval=31536000 \
    --ozone-platform=wayland \
    "$URL"
  sleep 5
done
EOF
chmod +x ~/kiosk.sh
```

### 5. Starta direkt i hemskärmen, utan skrivbord

labwc är en liten skärmhanterare (samma som Raspberry Pi OS använder) som bara visar webbläsaren. Muspekaren flyttas till hörnet där den inte syns. (`cage` fungerar också för kiosk, men kan inte släcka skärmen.)

```bash
mkdir -p ~/.config/labwc
cat > ~/.config/labwc/autostart <<'EOF'
# Muspekaren till hörnet längst ner till höger, där den inte syns.
wlrctl pointer move 10000 10000
"$HOME/kiosk.sh" &
EOF
sudo raspi-config nonint do_boot_behaviour B2
cat >> ~/.bash_profile <<'EOF'

# NPF-familjen: hemskärmen startar på skärmen (inte när man loggar in med ssh).
if [ "$(tty)" = "/dev/tty1" ]; then
  exec labwc
fi
EOF
sudo reboot
```

Efter 1–2 minuter visas hemskärmen.

### 6. Skärmen på och av, styrd från appen

Tiderna ställs in i appen: Familjen → hemskärmen → Skärmen på och av (vardagar och helger, omstart på morgonen och tv:n via HDMI). Pi:n frågar servern varje minut och släcker eller tänder skärmen. Även "Släck skärmen" under Hemskärmen i menyn stänger då av skärmen på riktigt.

Skriptet körs som root av cron, en gång i minuten:

```bash
sudo tee /usr/local/bin/npf-skarm >/dev/null <<'EOF'
#!/bin/sh
# NPF-familjen: frågar servern om skärmen ska vara på eller av, och om det är dags att starta om.
USER_NAME=npf
URL="$(cat /home/$USER_NAME/hemskarm-url)"
STATE=/run/npf-skarm.state
RUNTIME="/run/user/$(id -u $USER_NAME)"
WAYLAND="$(ls "$RUNTIME" 2>/dev/null | grep -m1 '^wayland-[0-9]*$')"
CURRENT="$(cat $STATE 2>/dev/null || echo on)"
UPTIME="$(cut -d. -f1 /proc/uptime)"

ANSWER="$(curl -fsS --max-time 20 "$URL/status?power=$CURRENT&uptime=$UPTIME")" || exit 0
POWER="$(echo "$ANSWER" | grep -o '"power":"[a-z]*"' | cut -d'"' -f4)"
CEC="$(echo "$ANSWER" | grep -o '"cec":true')"
REBOOT="$(echo "$ANSWER" | grep -o '"reboot":"[0-9:]*"' | cut -d'"' -f4)"

if [ -n "$POWER" ] && [ "$POWER" != "$CURRENT" ] && [ -n "$WAYLAND" ]; then
  runuser -u $USER_NAME -- env XDG_RUNTIME_DIR="$RUNTIME" WAYLAND_DISPLAY="$WAYLAND" wlopm --"$POWER" '*'
  # Tv:n i viloläge eller på via HDMI-CEC, om det är valt i appen.
  if [ -n "$CEC" ] && command -v cec-ctl >/dev/null; then
    cec-ctl -d /dev/cec0 --playback >/dev/null 2>&1
    if [ "$POWER" = off ]; then
      cec-ctl -d /dev/cec0 --to 0 --standby >/dev/null 2>&1
    else
      cec-ctl -d /dev/cec0 --to 0 --image-view-on >/dev/null 2>&1
    fi
  fi
  echo "$POWER" > $STATE
fi

# Omstart på morgonen, en gång: när klockan stämmer och Pi:n har varit igång en stund.
if [ -n "$REBOOT" ] && [ "$(date +%H:%M)" = "$REBOOT" ] && [ "$UPTIME" -gt 600 ]; then
  /sbin/reboot
fi
EOF
sudo chmod +x /usr/local/bin/npf-skarm
sudo apt install -y v4l-utils    # cec-ctl, för att styra tv:n
crontab -r 2>/dev/null           # de gamla tiderna i cron behövs inte längre
echo '* * * * * /usr/local/bin/npf-skarm' | sudo crontab -
```

Prova direkt med `sudo /usr/local/bin/npf-skarm`. Under Hemskärmen i appen syns sedan "Raspberry Pi: I kontakt nyss". Användarnamnet `npf` i skriptets andra rad ska vara samma som på Pi:n.

Utan skriptet (t.ex. en läsplatta som hemskärm) blir sidan mörk enligt tiderna, men själva skärmen släcks inte.

### Kontrollera hur Pi:n mår

```bash
free -h; uptime; vcgencmd measure_temp; vcgencmd get_throttled
```

Mindre än 100 MB under "available", belastning över 4, över 70 °C eller `throttled` annat än `0x0` betyder att något behöver ses över (oftast strömadaptern).

### Bra att veta

- Skärmen uppdateras varje minut och byter till mörkt tema på kvällen.
- Ny länk i appen? Byt innehållet i `~/hemskarm-url` och starta om Pi:n (`sudo reboot`).
- Står Pi:n på ett nätverk som blockerar duckdns.org (t.ex. ett jobbnätverk) syns ingenting.
- Ta bort skärmen i appen så slutar länken fungera direkt.

## Felsökning

### 502 Bad Gateway

Står det `upstream sent too big header` i `/var/log/nginx/error.log` är nginx buffert för appens svarshuvuden (kakor) för liten. Lägg till två rader i appens `location ~ ^/index\.php` i `/etc/nginx/sites-available/npf-familjen` (samma som i [deploy/server/nginx.conf](../deploy/server/nginx.conf)):

```
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Filen ändrades av Certbot när HTTPS lades till, så skriv in raderna för hand i stället för att kopiera hela filen. Ändringen gäller bara NPF-familjens sajt, inte de andra projekten.

Står det något om `connect() ... failed` eller `No such file` på `npf-familjen.sock` är PHP-FPM nere: `sudo systemctl restart php8.4-fpm`.

| Var | Kommando |
| --- | --- |
| Appens logg | `tail -f /var/www/npf-familjen/shared/storage/logs/laravel-*.log` |
| Nginx | `tail -f /var/log/nginx/error.log` |
| Köworkern | `journalctl -u npf-familjen-queue -f` |
| PHP-FPM | `journalctl -u php8.4-fpm -f` |
