# NPF-familjen – Roadmap

## Projektmål

NPF-familjen är ett hjälpmedel för barn och familjer med NPF-problematik (t.ex. ADHD och autism) i familjen.

En familjedelad webapp för:

- Att-göra listor
- Handlingslista
- Familjens veckomatsedel, receptbank och skafferi (skild från skolmaten)
- Dagbok för vårdnadshavare
- Belöningssystem
- Kalender (Google, iPhone, Outlook)
- Påminnelser (PWA)
- Familjedelning med egna enkla konton för barn (telefon och läsplatta)
- Andra vuxna (t.ex. mormor) som kan se eller ändra
- Bildstöd (pictogram och egna foton)
- Aktiviteter och händelser med packlistor i bildstöd, Imorgon och Veckan
- Rutiner steg för steg med bildstöd och synlig tid
- Hemskärm som vårdnadshavarna själva ställer in
- Community för vårdnadshavare

Systemet byggs med:

- Laravel 13
- MariaDB 12.3 LTS
- Laravels Vue starter kit (Fortify-auth)
- Inertia.js 3
- Vue 3 + TypeScript
- Tailwind CSS 4 + shadcn-vue
- Filament 5 (adminportal)
- Egen server, deploy från GitHub (GitHub Actions + Deployer 8)

Appen är på svenska.

Mobilvänlig design är prioritet.

Familjen äger sin data. Data delas aldrig mellan familjer.

Appen är gratis till en början men kan bli en betaltjänst längre fram.

---

# Versioner

## Version 1

Används bara av vår familj.

Milestone 0–12.

Rekommenderad byggordning, så att familjen kan börja använda appen tidigt:

1. Milestone 0 – Setup
2. Milestone 1 – Familjer och konton
3. Milestone 12 – Drift (tidigt, så att servern och Raspberry Pi:n testas från början)
4. Milestone 2 – Uppgifter och listor
5. Milestone 3 – Återkommande uppgifter
6. Milestone 4 – Bildstöd
7. Milestone 5 – Idag-vy
8. Milestone 7 – Rutiner
9. Milestone 11 – Hemskärm
10. Milestone 6 – Aktiviteter, händelser och packlistor
11. Milestone 8 – Skolmat
12. Milestone 9 – Receptbank och skafferi
13. Milestone 10 – Veckomatsedel och handlingslista

Barnen testar varje vy när den är klar, och vi justerar innan vi bygger vidare.

## Version 2

Milestone 13–16: dagbok, belöningssystem, kalender, PWA och påminnelser.

## Innan andra familjer bjuds in

Milestone 17–18: datakontroll och integritetspolicy, community.

---

# Milestone 0 – Setup

## Mål

Få en fungerande Laravel-app lokalt, på svenska och mot MariaDB.

## Tasks

- [x] Skapa appen med `laravel new` (Laravel 13, Vue starter kit utan Teams, Pest, MariaDB) i en tom mapp och flytta in den i repot. Behåll README.md, docs/, .ai/ och resources/images/.
- [x] Skapa databaserna npf_familjen och npf_familjen_test (utf8mb4, utf8mb4_uca1400_swedish_ai_ci)
- [x] Tester körs mot npf_familjen_test
- [x] Sätt APP_NAME="NPF-familjen"
- [x] Logotyp från resources/images/logo.png i appen, plus favicon (tillfällig ikon tills en förenklad logotyp utan text finns)
- [x] Stilguidens tokens (docs/design/tokens.json) till resources/css/app.css: Tailwind 4 @theme, shadcn-vue-variabler, ljust och mörkt tema, typsnitten Fredoka och Atkinson Hyperlegible Next
- [x] Svenska: APP_LOCALE=sv, APP_FAKER_LOCALE=sv_SE, installera laravel-lang/lang
- [x] Översätt startkitets sidor (inloggning, inställningar) till svenska
- [x] Servera appen via Laravel Herd
- [x] Installera Laravel Boost (`composer require laravel/boost --dev` + `php artisan boost:install`, välj Claude Code och Copilot). Projektets regler i .ai/guidelines/ tas med automatiskt.
- [x] Committa Boosts genererade filer (CLAUDE.md, AGENTS.md, boost.json och skills) så att AI-verktyg på GitHub också hittar dem
- [x] Kör migrations
- [x] Verifiera login fungerar
- [x] GitHub Actions: tester vid varje push (Pest mot MariaDB 12.3, Pint, Larastan, frontend-build)

## Definition of Done

- App kör lokalt mot MariaDB
- Login fungerar
- Inloggning, inställningar och felmeddelanden är på svenska
- Testerna går igenom mot MariaDB, lokalt och på GitHub

---

# Milestone 1 – Familjer och konton

## Mål

All privat data ska vara kopplad till en familj. Vårdnadshavare, andra vuxna och barn har egna konton.

## Tasks

- [x] Skapa families tabell
- [x] Skapa family_user pivot tabell med role (parent, editor, viewer, child)
- [x] Lägg till active_family_id i users tabellen (ingen family_id på users, så att växelvis boende kan läggas till senare)
- [x] Create Family page
- [x] Middleware för aktuell familj
- [x] BelongsToFamily trait (global scope + family_id vid create)
- [x] Bjuda in andra vuxna via e-post med roll (family_invitations)
- [x] Byta familj i menyn för vuxna som tillhör flera familjer
- [x] Barnkonton: vårdnadshavare skapar barn (namn, avatar, kod på 4–6 siffror), email nullable
- [x] Koden lagras krypterad. Vårdnadshavare kan visa, byta och återställa den.
- [x] Neka enkla koder (0000, 1234, 4321)
- [x] devices tabell
- [x] Familjeenhet: barnet väljer sig själv och skriver koden
- [x] Barnets egen enhet kopplas via QR-kod
- [x] Barnens inloggning för både telefon och läsplatta
- [x] Felaktiga försök: vänta en minut efter 5, stäng av inloggningen efter 10
- [x] Stänga av ett barns inloggning: alla sessioner loggas ut (SESSION_DRIVER=database) och barnets enheter kopplas bort
- [x] Inloggningshistorik för barnen (login_events)
- [x] Policies för alla fyra roller
- [x] Isolationstester mellan familjer

## Definition of Done

- User kan skapa familj
- Vårdnadshavare kan bjuda in en vuxen som kan se eller ändra
- Vårdnadshavare kan skapa barnkonto och visa, byta och återställa koden
- Barn kan logga in med sin kod, men bara på en betrodd enhet
- Ett avstängt barnkonto loggas ut överallt direkt
- Aktiv familj används överallt
- Tester visar att familj A aldrig ser familj B:s data

---

# Milestone 2 – Uppgifter och listor

## Mål

Familjen ska kunna dela uppgifter och listor.

## Tasks

- [ ] lists tabell (t.ex. handlingslista)
- [ ] list_items tabell (med added_by)
- [ ] tasks tabell med del av dagen och/eller klockslag
- [ ] Dagsdelar per familj (standard: morgon 06, förmiddag 09, eftermiddag 12, kväll 17)
- [ ] Lists UI
- [ ] Tasks UI
- [ ] Tilldela uppgift till familjemedlem
- [ ] Ändra och ta bort uppgifter
- [ ] Barn kan lägga till på handlingslistan

## Definition of Done

- Familjen kan hantera listor
- Familjen kan hantera uppgifter
- En uppgift kan ha del av dagen, klockslag eller båda
- Barn kan lägga till varor på handlingslistan

---

# Milestone 3 – Återkommande uppgifter

## Mål

Uppgifter som återkommer (t.ex. borsta tänderna varje morgon) skapas automatiskt.

## Tasks

- [ ] recurring_tasks tabell (vem, del av dagen och/eller klockslag, upprepning: varje dag, vissa veckodagar eller varje vecka)
- [ ] Schemalagt kommando som skapar dagens uppgifter varje natt (routes/console.php, Europe/Stockholm)
- [ ] Ändring av en återkommande uppgift påverkar bara kommande dagar
- [ ] Tester för veckodagar och sommartid/vintertid

## Definition of Done

- Återkommande uppgifter dyker upp automatiskt de dagar de ska
- Inga dubbletter om kommandot körs två gånger

---

# Milestone 4 – Bildstöd

## Mål

Allt viktigt ska gå att förstå utan att läsa: uppgifter, listor och familjemedlemmar har bilder.

## Tasks

- [ ] pictures och picture_categories tabeller
- [ ] picture_id på tasks, recurring_tasks, list_items och users (avatar)
- [ ] is_admin på users
- [ ] Installera Filament 5, adminportal på /admin (bara admin)
- [ ] Admin: massuppladdning till galleriet, kategorier, etiketter, sökord
- [ ] Köjobb: skala ner och konvertera till WebP
- [ ] Bildväljare: galleri och familjens foton
- [ ] Ladda upp eget foto (mobilkamera)
- [ ] Admin: importera Mulberry Symbols med svenska etiketter (valfritt)
- [ ] Sida med bildkällor
- [ ] Visa bild + kort text i listor och uppgifter

## Definition of Done

- Admin kan ladda upp många bilder på en gång och sortera dem i kategorier
- Familjen kan välja bild från galleriet till en uppgift
- Familjens egna foton är inte publikt åtkomliga

---

# Milestone 5 – Idag-vy

## Mål

Visa dagens uppgifter. Barnen ser vad de ska göra idag, vårdnadshavarna ser hela familjens dag.

## Tasks

- [ ] TodayController
- [ ] pages/today/Index.vue
- [ ] Filtrera uppgifter för idag (Europe/Stockholm)
- [ ] Gruppera efter del av dagen, sortera på klockslag
- [ ] Barnets vy: dagens uppgifter med bildstöd och stora knappar
- [ ] Barnets vy på läsplatta: "Nu" och dagens lista bredvid varandra i liggande läge
- [ ] Vårdnadshavarnas dashboard: hela familjens dag

## Definition of Done

- Idag visar uppgifter korrekt
- Barnet ser sin egen dag med bildstöd, på telefon och läsplatta
- Vårdnadshavare ser alla barns dag

---

# Milestone 6 – Aktiviteter, händelser och packlistor

## Mål

Barnen ser vad som händer idag, imorgon och i veckan, inklusive när en förälder är borta eller någon annan hämtar, och får hjälp att packa med bildstöd.

## Tasks

- [ ] activities (med type, end_date, visible_to_children, unusual), activity_participants och activity_items tabeller
- [ ] activity_item_checks och activity_confirmations tabeller
- [ ] Vuxen skapar aktivitet (en gång eller varje vecka) och packlista med bilder
- [ ] Händelser för vuxna och barn: bortrest, hämtning, annorlunda dag
- [ ] "Visa för barnen" på händelser som rör vuxna
- [ ] Nedräkning i nätter när någon är bortrest ("2 sömnar kvar")
- [ ] Dagen innan: "Packa till simskolan imorgon" i barnets Idag-vy, bocka av varje sak
- [ ] Samma dag: "Har du packat till simskolan?" med samma bilder
- [ ] Vuxna ser om barnet har packat
- [ ] Imorgon-vy och Veckan-vy (bara det stora och det som är annorlunda)

## Definition of Done

- Barnet får packlistan med bilder dagen innan
- Barnet får frågan om packningen samma dag
- Packlistan återanvänds varje gång aktiviteten sker
- Barnet ser vem som hämtar och när en förälder kommer hem
- Vuxnas händelser syns bara för barnen när "Visa för barnen" är valt

---

# Milestone 7 – Rutiner

## Mål

Barnen klarar sina rutiner (morgon, efter skolan, kväll) steg för steg med bildstöd och synlig tid.

## Tasks

- [ ] routines, routine_child och routine_steps tabeller
- [ ] routine_runs och routine_step_checks tabeller
- [ ] Vuxen skapar rutin: steg med bild, uppskattad tid, valfria steg, steg bara vissa dagar
- [ ] Rutin med "klar senast" (t.ex. 07:45)
- [ ] Fokusläge för barnet: ett steg i taget, stor Klar-knapp, nästa steg syns litet (först–sen)
- [ ] Synlig tid: kvartsprickar (1 prick = 15 min) fram till "klar senast"
- [ ] Om tiden inte räcker: föreslå att hoppa över valfria steg
- [ ] Steg "Kolla packningen" visar dagens packlistor
- [ ] Uppläsning av steget (webbläsarens talsyntes, svenska)
- [ ] Rätt rutin visas överst i barnets Idag-vy när det är dags
- [ ] Vuxna ser läget live i sin dashboard
- [ ] Lugnt firande när rutinen är klar
- [ ] Färdiga rutinmallar i adminportalen (morgon, efter skolan, kväll, läggdags)

## Definition of Done

- Barnet kan göra sin morgonrutin på egen hand, ett steg i taget
- Barnet ser hur mycket tid som är kvar utan att läsa klockan
- Vuxna ser hur långt barnet har kommit
- Familjen kan utgå från en mall och anpassa den

---

# Milestone 8 – Skolmat

## Mål

Visa barnens skollunch från Matilda Menu.

## Tasks

- [ ] school_menus tabell
- [ ] school_meals tabell
- [ ] Lägg till skola via Matilda-länk
- [ ] Koppla familjemedlem till skola (family_user.school_menu_id)
- [ ] MealProvider interface + MatildaMealProvider
- [ ] Schemalagt kommando som hämtar denna och nästa vecka
- [ ] Visa dagens skollunch per barn i Idag-vyn
- [ ] Tester med Http::fake()

## Definition of Done

- Dagens och morgondagens skollunch visas per barn
- Barn på olika skolor ser sin egen skolas lunch
- Appen fungerar även när Matilda inte svarar

---

# Milestone 9 – Receptbank och skafferi

## Mål

Familjen har en egen receptbank, skriver in vad som finns hemma och får förslag på vad de kan laga.

## Tasks

- [ ] ingredients tabell (gemensam grundlista + familjens egna)
- [ ] Admin: grundlista med vanliga ingredienser och butiksavdelningar
- [ ] recipes och recipe_ingredients tabeller
- [ ] Receptbank UI: namn, bild, portioner, gör så här, ingredienser med autocomplete
- [ ] pantry_items tabell
- [ ] Skafferi UI: snabbt lägga till och ta bort det som finns hemma, markera basvaror
- [ ] Matförslag: "Kan lagas nu" och "Saknar 1–2 saker"

## Definition of Done

- Familjen kan spara recept med ingredienser
- Familjen kan skriva in vad som finns hemma
- Förslagen visar recept som går att laga med det som finns hemma

---

# Milestone 10 – Veckomatsedel och handlingslista

## Mål

Familjen planerar veckans mat och får en handlingslista med det som behöver kompletteringshandlas.

## Tasks

- [ ] meal_plan_entries tabell
- [ ] Veckomatsedel UI: lägg recept (eller fritext) på en dag, ändra portioner
- [ ] Lägg till recept i veckomatsedeln direkt från matförslagen
- [ ] Skapa handlingslista: veckans ingredienser minus det som finns hemma
- [ ] Slå ihop samma vara och räkna om enheter inom vikt och volym
- [ ] Handlingslistan sorteras efter butiksavdelning
- [ ] Avbockad vara läggs i skafferiet
- [ ] Uppdatera handlingslistan när veckomatsedeln ändras (lägg till nytt, ta bort ej avbockade varor från strukna måltider, rör aldrig det familjen lagt till själv)
- [ ] "Middag ikväll" med bild i Idag-vyn
- [ ] Tester för portioner, enheter och avdrag mot skafferiet

## Definition of Done

- Veckomatsedeln är tydligt skild från skolmaten
- Handlingslistan innehåller bara det som saknas hemma
- Det som handlas hamnar i skafferiet

---

# Milestone 11 – Hemskärm

## Mål

En separat skärm hemma, likt DAKboard. Vårdnadshavarna väljer själva vad som visas.

## Tasks

- [ ] Hemskärm som enhet (devices, type display)
- [ ] Skapa och återkalla skärmlänk i inställningar
- [ ] Display-middleware (token → familj)
- [ ] display_widgets tabell
- [ ] DisplayWidget-klass per widget-typ
- [ ] Redigerare i vårdnadshavarnas dashboard: dra och släpp med gridstack, ändra storlek, inställningar per widget
- [ ] Förhandsvisning i redigeraren
- [ ] Täthet per skärm: stor, mellan, kompakt (devices.settings.density)
- [ ] DisplayLayout utan navigation, pages/display/Index.vue
- [ ] Widgets: klocka/datum, skollunch, middag ikväll/veckans mat, dagens uppgifter, rutiner (varje barns pågående steg), aktiviteter och packning, Imorgon, Veckan, handlingslista
- [ ] Byte till nästa dag vid tider familjen ställer in (förval: skollunch 15:00, middag 19:00)
- [ ] Mörkt tema kvällstid
- [ ] Automatisk uppdatering med usePoll

Widgets för kalender och poäng läggs till när de delarna byggs (version 2). Utkast: docs/mockups/.

## Definition of Done

- Vårdnadshavare kan välja och placera widgets med dra och släpp
- Varje skärm har sin egen layout och täthet
- Skärmen visar bara de valda widgetarna, utan inloggning
- Ändringar syns på skärmen inom en minut
- Skärmlänken kan återkallas

---

# Milestone 12 – Drift

## Mål

Appen körs på en server så att familjen kan använda den på telefonerna och hemskärmen.

## Tasks

- [x] Driftpaket i repot: deploy.php, deploy-flöde i GitHub Actions, serverskript, backupschema och runbook ([DRIFT.md](DRIFT.md))
- [ ] Debian 12: lägg till Ondřej Surýs paketförråd för PHP 8.4 och MariaDB:s paketförråd för 12.3
- [ ] Stäng registreringen: REGISTRATION_ENABLED=false (andra vuxna går med via inbjudan)
- [ ] Server: egen användare npf-familjen (servern delas med andra projekt), inloggning bara med SSH-nyckel
- [ ] Nginx, PHP 8.4, MariaDB 12.3 LTS
- [x] DuckDNS-adress (servern står hos en leverantör med fast IP, så ingen uppdatering eller NAT loopback behövs)
- [ ] HTTPS med Let's Encrypt
- [ ] GitHub Actions: deploy vid push till main med Deployer 8, om testerna går igenom
- [ ] Zero-downtime-deploy och rollback
- [ ] Köworker (systemd) och scheduler (cron)
- [ ] E-post via Gmail SMTP med applösenord (separat Gmail-konto för appen)
- [ ] Bilder på privat disk i delad mapp som överlever deployer
- [ ] Backup varje natt till en egen mapp på servern (spatie/laravel-backup, 14 dagar)
- [ ] Raspberry Pi: Chromium i kioskläge mot hemskärmslänken, skärmen av på natten

## Definition of Done

- En push till main deployar automatiskt utan driftstopp
- Rollback fungerar
- Familjen använder appen på telefonerna och hemskärmen
- Backup tas automatiskt och återställning är testad

---

# Milestone 13 – Dagbok

## Mål

Vårdnadshavarnas anteckningar om ett eller flera barn.

## Tasks

- [ ] journal_entries tabell
- [ ] journal_entry_child tabell (vilka barn anteckningen gäller)
- [ ] Kryptera innehållet (encrypted cast)
- [ ] JournalPolicy: bara vuxna
- [ ] Journal page
- [ ] Historik, filtrera per barn

## Definition of Done

- Vuxna kan skriva anteckningar om ett eller flera barn
- Barn har ingen åtkomst till dagboken

---

# Milestone 14 – Belöningssystem

## Mål

Motivera användare genom poäng.

## Tasks

- [ ] task_completions tabell
- [ ] daily_goals tabell
- [ ] family_points_ledger tabell
- [ ] rewards tabell (med picture_id)
- [ ] reward_redemptions tabell
- [ ] Rewards UI
- [ ] Bara vuxna kan ge och dra poäng
- [ ] Barn kan önska belöning, vuxen godkänner
- [ ] Avklarad rutin och packning kan ge poäng (valbart)
- [ ] Poäng-widget på hemskärmen

## Definition of Done

- Uppgifter ger poäng
- Belöningar kan lösas in
- Barn kan inte ge sig själva poäng

---

# Milestone 15 – Kalender

## Mål

Familjens kalendrar från Google, iPhone (iCloud) och Outlook visas i appen och på hemskärmen.

## Tasks

- [ ] calendars tabell (iCal-länk lagras krypterad)
- [ ] calendar_events tabell
- [ ] Lägg till kalender via iCal-länk, med guide för Google, iPhone och Outlook
- [ ] Synk med sabre/vobject, inklusive återkommande händelser
- [ ] Schemalagt kommando som synkar var 15:e minut
- [ ] Visa dagens händelser i Idag-vyn
- [ ] Kalender-widget på hemskärmen
- [ ] Koppla aktiviteter till kalenderhändelser via namn (t.ex. "Simskola")
- [ ] Tester med Http::fake() och exempel-filer från alla tre

## Definition of Done

- Kalendrar från Google, iPhone och Outlook visas
- Återkommande händelser visas rätt
- Tider visas i svensk tid, även över sommartid/vintertid

---

# Milestone 16 – PWA och påminnelser

## Mål

Appen kan installeras på telefonen och skickar påminnelser.

## Tasks

- [ ] PWA med vite-plugin-pwa (manifest, service worker, ikon)
- [ ] Web Push med laravel-notification-channels/webpush (VAPID-nycklar)
- [ ] Guide: lägg till på hemskärmen (krävs för push på iPhone)
- [ ] Påminnelser för uppgifter med tid, kalenderhändelser och när en rutin ska starta
- [ ] Påminnelse om packning dagen innan en aktivitet
- [ ] Barn får påminnelser på sin egen enhet
- [ ] Tysta timmar per användare

## Definition of Done

- Appen går att installera på iPhone och Android
- Påminnelser kommer i tid som push-notis
- Inga påminnelser under tysta timmar

---

# Milestone 17 – Datakontroll

## Mål

Familjen har full kontroll över sin data.

## Tasks

- [ ] Exportera familjens data (JSON + bilder i ZIP, köjobb)
- [ ] Radera barnkonto
- [ ] Radera hela familjen (all data och alla filer, köjobb)
- [ ] Integritetspolicy

## Definition of Done

- En vuxen kan ladda ner all familjens data
- Efter radering finns ingen data eller fil kvar från familjen

---

# Milestone 18 – Community

## Mål

Vårdnadshavare i olika familjer kan diskutera problem och tips.

## Tasks

- [ ] groups tabell
- [ ] group_members tabell
- [ ] threads tabell
- [ ] posts tabell
- [ ] Community UI
- [ ] Bara vuxna har åtkomst

## Definition of Done

- Diskussion mellan familjer fungerar
- Barnkonton kommer inte åt Community

---

# Framtida funktioner

## Möjliga features

- Realtime chat (Laravel Reverb + Echo)
- Statistik
- Betalversion (Laravel Cashier, familjen betalar)
- Kalender: tvåvägssynk med Google och Outlook
- Hemskärm: väder, foton
- Hemskärm: bocka av tasks via pekskärm
- Egen domän och e-posttjänst inom EU (t.ex. Brevo eller Mailgun EU)
- Backup även utanför servern
- Flytta servern till en nyare Debian innan Debian 12:s LTS-stöd tar slut (juni 2028)
- Importera recept från länk (schema.org/Recipe, Laravel AI SDK för att tolka ingredienser)
- Dra av ingredienser från skafferiet när en måltid är lagad

Dark mode ingår redan i starter kit (inställningar → Appearance).
