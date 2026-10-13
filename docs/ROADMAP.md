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

1. Milestone 0 – Setup (klar)
2. Milestone 1 – Familjer och konton (klar)
3. Milestone 12 – Drift (tidigt, så att servern och Raspberry Pi:n testas från början) (i drift, några tester och Raspberry Pi kvar)
4. Milestone 2 – Uppgifter och listor (klar)
5. Milestone 3 – Återkommande uppgifter (klar)
6. Milestone 4 – Bildstöd (klar)
7. Milestone 5 – Idag-vy (klar)
8. Milestone 7 – Rutiner (klar)
9. Milestone 11 – Hemskärm (klar, Raspberry Pi provas när den finns)
10. Milestone 6 – Aktiviteter, händelser och packlistor (klar)
11. Milestone 8 – Skolmat (klar)
12. Milestone 9 – Receptbank och skafferi (klar)
13. Milestone 10 – Veckomatsedel och handlingslista (klar)

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

- [x] lists tabell (t.ex. handlingslista)
- [x] list_items tabell (med added_by)
- [x] tasks tabell med del av dagen och/eller klockslag
- [x] Dagsdelar per familj (standard: morgon 06, förmiddag 09, eftermiddag 12, kväll 17)
- [x] Lists UI
- [x] Tasks UI
- [x] Tilldela uppgift till familjemedlem
- [x] Ändra och ta bort uppgifter
- [x] Barn kan lägga till på handlingslistan

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

- [x] recurring_tasks tabell (vem, del av dagen och/eller klockslag, upprepning: varje dag, vissa veckodagar eller varje vecka)
- [x] Schemalagt kommando som skapar dagens uppgifter varje natt (routes/console.php, Europe/Stockholm)
- [x] Ändring av en återkommande uppgift påverkar bara kommande dagar
- [x] Tester för veckodagar och sommartid/vintertid

## Definition of Done

- Återkommande uppgifter dyker upp automatiskt de dagar de ska
- Inga dubbletter om kommandot körs två gånger

---

# Milestone 4 – Bildstöd

## Mål

Allt viktigt ska gå att förstå utan att läsa: uppgifter, listor och familjemedlemmar har bilder.

## Tasks

- [x] pictures och picture_categories tabeller
- [x] picture_id på tasks, recurring_tasks, list_items och users (avatar)
- [x] is_admin på users
- [x] Installera Filament 5, adminportal på /admin (bara admin)
- [x] Admin: massuppladdning till galleriet, kategorier, etiketter, sökord
- [x] Köjobb: skala ner och konvertera till WebP
- [x] Bildväljare: galleri och familjens foton
- [x] Ladda upp eget foto (mobilkamera)
- [ ] Admin: importera Mulberry Symbols med svenska etiketter (valfritt, inte gjort)
- [x] Sida med bildkällor
- [x] Visa bild + kort text i listor och uppgifter

## Definition of Done

- Admin kan ladda upp många bilder på en gång och sortera dem i kategorier
- Familjen kan välja bild från galleriet till en uppgift
- Familjens egna foton är inte publikt åtkomliga

---

# Milestone 5 – Idag-vy

## Mål

Visa dagens uppgifter. Barnen ser vad de ska göra idag, vårdnadshavarna ser hela familjens dag.

## Tasks

- [x] TodayController
- [x] pages/today/Index.vue
- [x] Filtrera uppgifter för idag (Europe/Stockholm)
- [x] Gruppera efter del av dagen, sortera på klockslag
- [x] Barnets vy: dagens uppgifter med bildstöd och stora knappar
- [x] Barnets vy på läsplatta: "Nu" och dagens lista bredvid varandra i liggande läge
- [x] Vårdnadshavarnas dashboard: hela familjens dag

## Definition of Done

- Idag visar uppgifter korrekt
- Barnet ser sin egen dag med bildstöd, på telefon och läsplatta
- Vårdnadshavare ser alla barns dag

---

# Milestone 6 – Aktiviteter, händelser och packlistor

## Mål

Barnen ser vad som händer idag, imorgon och i veckan, inklusive när en förälder är borta eller någon annan hämtar, och får hjälp att packa med bildstöd.

## Tasks

- [x] activities (med type, end_date, visible_to_children, unusual), activity_participants och activity_items tabeller
- [x] activity_item_checks och activity_confirmations tabeller
- [x] Vuxen skapar aktivitet (en gång eller varje vecka) och packlista med bilder
- [x] Händelser för vuxna och barn: bortrest, hämtning, annorlunda dag
- [x] "Visa för barnen" på händelser som rör vuxna
- [x] Nedräkning i nätter när någon är bortrest ("2 sömnar kvar")
- [x] Dagen innan: "Packa till simskolan imorgon" i barnets Idag-vy, bocka av varje sak
- [x] Samma dag: "Har du packat till simskolan?" med samma bilder
- [x] Vuxna ser om barnet har packat
- [x] Imorgon-vy och Veckan-vy (bara det stora och det som är annorlunda)

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

- [x] routines, routine_child och routine_steps tabeller
- [x] routine_runs och routine_step_checks tabeller
- [x] Vuxen skapar rutin: steg med bild, uppskattad tid, valfria steg, steg bara vissa dagar
- [x] Rutin med "klar senast" (t.ex. 07:45)
- [x] Fokusläge för barnet: ett steg i taget, stor Klar-knapp, nästa steg syns litet (först–sen)
- [x] Synlig tid: kvartsprickar (1 prick = 15 min) fram till "klar senast"
- [x] Om tiden inte räcker: föreslå att hoppa över valfria steg
- [x] Steg "Kolla packningen" visar dagens packlistor
- [x] Uppläsning av steget (webbläsarens talsyntes, svenska)
- [x] Rätt rutin visas överst i barnets Idag-vy när det är dags
- [x] Vuxna ser läget live i sin dashboard
- [x] Lugnt firande när rutinen är klar
- [x] Färdiga rutinmallar i adminportalen (morgon, efter skolan, kväll, läggdags)

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

- [x] school_menus tabell
- [x] school_meals tabell
- [x] Lägg till skola via Matilda-länk
- [x] Koppla familjemedlem till skola (family_user.school_menu_id)
- [x] MealProvider interface + MatildaMealProvider
- [x] Schemalagt kommando som hämtar denna och nästa vecka
- [x] Visa dagens skollunch per barn i Idag-vyn
- [x] Tester med Http::fake()

## Definition of Done

- Dagens och morgondagens skollunch visas per barn
- Barn på olika skolor ser sin egen skolas lunch
- Appen fungerar även när Matilda inte svarar

---

# Milestone 9 – Receptbank och skafferi

## Mål

Familjen har en egen receptbank, skriver in vad som finns hemma och får förslag på vad de kan laga.

## Tasks

- [x] ingredients tabell (gemensam grundlista + familjens egna)
- [x] Admin: grundlista med vanliga ingredienser och butiksavdelningar
- [x] recipes och recipe_ingredients tabeller
- [x] Receptbank UI: namn, bild, portioner, gör så här, ingredienser med autocomplete
- [x] pantry_items tabell
- [x] Skafferi UI: snabbt lägga till och ta bort det som finns hemma, markera basvaror
- [x] Matförslag: "Kan lagas nu" och "Saknar 1–2 saker"

## Definition of Done

- Familjen kan spara recept med ingredienser
- Familjen kan skriva in vad som finns hemma
- Förslagen visar recept som går att laga med det som finns hemma

---

# Milestone 10 – Veckomatsedel och handlingslista

## Mål

Familjen planerar veckans mat och får en handlingslista med det som behöver kompletteringshandlas.

## Tasks

- [x] meal_plan_entries tabell
- [x] Veckomatsedel UI: lägg recept (eller fritext) på en dag, ändra portioner
- [x] Lägg till recept i veckomatsedeln direkt från matförslagen
- [x] Skapa handlingslista: veckans ingredienser minus det som finns hemma
- [x] Slå ihop samma vara och räkna om enheter inom vikt och volym
- [x] Handlingslistan sorteras efter butiksavdelning
- [x] Avbockad vara läggs i skafferiet
- [x] Uppdatera handlingslistan när veckomatsedeln ändras (lägg till nytt, ta bort ej avbockade varor från strukna måltider, rör aldrig det familjen lagt till själv)
- [x] "Middag ikväll" med bild i Idag-vyn
- [x] Tester för portioner, enheter och avdrag mot skafferiet

## Definition of Done

- Veckomatsedeln är tydligt skild från skolmaten
- Handlingslistan innehåller bara det som saknas hemma
- Det som handlas hamnar i skafferiet

---

# Milestone 11 – Hemskärm

## Mål

En separat skärm hemma, likt DAKboard. Vårdnadshavarna väljer själva vad som visas.

## Tasks

- [x] Hemskärm som enhet (devices, type display)
- [x] Skapa och återkalla skärmlänk i inställningar
- [x] Display-middleware (token → familj)
- [x] display_widgets tabell
- [x] DisplayWidget-klass per widget-typ
- [x] Redigerare i vårdnadshavarnas dashboard: dra och släpp med gridstack, ändra storlek, inställningar per widget
- [x] Förhandsvisning i redigeraren
- [x] Täthet per skärm: stor, mellan, kompakt (devices.settings.density)
- [x] DisplayLayout utan navigation, pages/display/Index.vue
- [x] Widgets: klocka/datum (alltid överst), barnets dag (rutinens steg eller nästa uppgift), dagens uppgifter, aktiviteter och packning, Imorgon, Veckan, handlingslista
- [x] Widget för skollunch
- [x] Widget för middag ikväll och kommande dagar
- [x] Byte till nästa dag för skollunch vid tid familjen ställer in (förval 15:00)
- [x] Byte till nästa dag för middag (förval 19:00)
- [x] Mörkt tema kvällstid
- [x] Automatisk uppdatering med usePoll
- [x] Veckoschema som whiteboarden: rad per vald person, kolumn per dag, valbara rader Middag och Skolmat (tillagd efter version 1)

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
- [x] Debian 12 med PHP 8.4 från Ondřej Surýs paketförråd (fanns redan på servern)
- [x] Stäng registreringen: REGISTRATION_ENABLED=false (andra vuxna går med via inbjudan)
- [x] Server: egen användare npf-familjen (servern delas med andra projekt), inloggning bara med SSH-nyckel
- [x] Nginx och PHP 8.4 (egen sajt och egen PHP-FPM-pool på den delade servern)
- [ ] MariaDB: servern har 10.11, som delas med andra projekt. Beslut: köra på 10.11 eller uppgradera till 12.3
- [x] DuckDNS-adress (servern står hos en leverantör med fast IP, så ingen uppdatering eller NAT loopback behövs)
- [x] HTTPS med Let's Encrypt
- [x] GitHub Actions: deploy vid push till main med Deployer 8, om testerna går igenom
- [x] Zero-downtime-deploy
- [ ] Testa rollback (dep rollback)
- [x] Köworker (systemd) och scheduler (cron)
- [x] E-post via Gmail SMTP med applösenord (separat Gmail-konto för appen)
- [x] Bilder på privat disk i delad mapp som överlever deployer
- [x] Backup varje natt till en egen mapp på servern (spatie/laravel-backup, 14 dagar)
- [ ] Testa återställning av en backup
- [ ] Raspberry Pi: Chromium i kioskläge mot hemskärmslänken, skärmen av på natten (instruktioner i DRIFT.md, inte provat på en Pi än)

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

- [x] journal_entries tabell
- [x] journal_entry_child tabell (vilka barn anteckningen gäller)
- [x] Kryptera innehållet (encrypted cast)
- [x] JournalPolicy: bara vårdnadshavare (parent), enligt ARCHITECTURE.md
- [x] Journal page
- [x] Historik, filtrera per barn

## Definition of Done

- Vuxna kan skriva anteckningar om ett eller flera barn
- Barn har ingen åtkomst till dagboken

---

# Milestone 14 – Belöningssystem

## Mål

Motivera användare genom poäng.

## Tasks

- [x] ~~task_completions tabell~~ behövs inte: uppgifter sparar redan vem som bockade av och när
- [ ] daily_goals tabell (väntar, inget behov än)
- [x] Poängbok: point_transactions (en rad per poäng som ges eller används)
- [x] rewards tabell (med picture_id)
- [x] reward_redemptions tabell
- [x] Rewards UI (Belöningar för vårdnadshavare, Mina poäng för barn)
- [x] Bara vårdnadshavare kan ge och dra poäng, enligt ARCHITECTURE.md
- [x] Barn kan önska belöning, vårdnadshavare godkänner
- [x] Avklarad uppgift, rutin och packning kan ge poäng (valbart per familj)
- [x] Poäng-widget på hemskärmen

## Definition of Done

- Uppgifter ger poäng
- Belöningar kan lösas in
- Barn kan inte ge sig själva poäng

---

# Milestone 15 – Kalender

## Mål

Familjens kalendrar från Google, iPhone (iCloud) och Outlook visas i appen och på hemskärmen.

## Tasks

- [x] calendars tabell (iCal-länk lagras krypterad)
- [x] calendar_events tabell
- [x] Lägg till kalender via iCal-länk, med guide för Google, iPhone och Outlook
- [x] Synk med sabre/vobject, inklusive återkommande händelser
- [x] Schemalagt kommando som synkar var 15:e minut
- [x] Visa dagens händelser i Idag-vyn (och barnens Min dag)
- [x] Kalender-widget på hemskärmen, och händelserna i Veckoschema
- [x] Koppla aktiviteter till kalenderhändelser via namn (t.ex. "Simskola")
- [x] Tester med Http::fake() och exempel-filer från alla tre

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
