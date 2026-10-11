# NPF-familjen – Architecture

## Stack

Projektet utgår från Laravels officiella Vue starter kit.

Backend:

- Laravel 13 (kräver PHP 8.3+)

Frontend:

- Inertia.js 3
- Vue 3 (Composition API + TypeScript)
- Tailwind CSS 4
- shadcn-vue (UI-komponenter)
- Laravel Wayfinder (typsäkra routes i frontend)
- gridstack (dra och släpp i hemskärmens redigerare)
- Vite

Database:

- SQLite (development)
- MySQL/Postgres (production)

Auth:

- Laravel Fortify (ingår i starter kit; login, registrering, lösenordsåterställning, e-postverifiering, 2FA)

Lokal utvecklingsmiljö:

- Laravel Herd

Test och kodkvalitet:

- Pest
- Laravel Pint
- Larastan

Adminportal:

- Filament 5 (på /admin)

AI-stöd:

- Laravel Boost (MCP-server + AI-riktlinjer för Copilot m.fl.)

---

# Grundprinciper

## Family Scoped Data

ALL privat data ska innehålla:

family_id

Exempel:

- lists
- tasks
- journal_entries
- rewards
- recurring_tasks

User får bara se data där user är medlem i familjen.

Policies används alltid.

## Data delas aldrig mellan familjer

- En familjs data syns aldrig för en annan familj
- Enda undantaget: inlägg som en vuxen själv publicerar i Community
- Bildgalleriet (från adminportalen) är inte familjedata

## Automatisk familjefiltrering

Modeller med family_id använder traiten BelongsToFamily:

- Global scope som filtrerar på aktuell familj
- family_id sätts automatiskt vid create

Aktuell familj sätts av middleware:

- Inloggad användare: users.active_family_id
- Enhet (t.ex. hemskärm): devices.family_id

Det skyddar även när någon glömmer where('family_id', ...).

---

# Konton och roller

## Roller i familjen

family_user.role

- parent (vuxen/vårdnadshavare)
- child (barn)

Vuxen kan:

- hantera familjen, barnkonton och enheter
- skapa och ändra uppgifter, listor och belöningar
- ge och dra poäng, godkänna inlösen av belöningar
- skriva och läsa dagboken
- lägga till kalendrar
- skapa aktiviteter och packlistor
- ställa in hemskärmen
- använda Community
- exportera och radera familjens data

Barn kan:

- se vad de ska göra idag
- se och bocka av sina uppgifter
- bocka av packlistor
- se sina poäng
- önska en belöning (en vuxen godkänner)

Rollen kontrolleras i policies.

## Vuxenkonton

Fortify: e-post + lösenord, 2FA.

## Barnkonton

Barn har enkla konton utan e-post och lösenord.

- En vuxen skapar barnkontot (namn, avatar, PIN-kod)
- users.email är nullable, users.pin lagras hashad
- Barnet loggar in på en betrodd enhet: väljer sig själv och skriver sin PIN
- PIN fungerar bara på betrodda enheter och rate-limitas
- En vuxen kan byta PIN och logga ut barnets enheter

## Enheter

devices

- family_id
- user_id (nullable: barnets egen enhet)
- type (family, child, display)
- name
- token (lagras hashad)
- last_seen_at

Typer:

- family: delad enhet (t.ex. surfplatta i köket) där barnen loggar in med PIN
- child: barnets egen telefon/platta, kopplas genom att en vuxen visar en QR-kod
- display: hemskärmen

En vuxen aktiverar och återkallar enheter. Token sparas i en långlivad cookie (hemskärmen: i länken).

---

# Dagbok

Dagboken är för vårdnadshavare: anteckningar om ett eller flera av familjens barn.

- Bara vuxna (parent) kan läsa och skriva
- Barn har ingen åtkomst
- Innehållet krypteras i databasen (encrypted cast)

journal_entries

- family_id
- author_id
- date
- content (krypterad)

journal_entry_child

- journal_entry_id
- user_id (barnet)

---

# Community Data

Community är separat från familjedata.

Community använder:

groups
threads
posts

Community använder inte family_id.

Behörighet styrs via group_members.

Bara vuxna (parent) har åtkomst. Barnkonton kommer aldrig åt Community.

---

# Hemskärm (Display)

En separat helskärmsvy för en skärm hemma (likt DAKboard). Hemskärmen är bara en visning, inte en dashboard: ingen meny, inga knappar.

## Widgets

Vårdnadshavarna väljer själva vad som visas. Familjer prioriterar olika: vissa vill ha kalender, andra skolmat eller vad som ska göras.

Tillgängliga widgets:

- Klocka och datum
- Kalender (dagens händelser)
- Skollunch per barn
- Dagens uppgifter per familjemedlem (med bildstöd och avatar)
- Aktiviteter och packning (t.ex. "Imorgon: simskola")
- Shoppinglistor
- Poäng och belöningar

Dagbok kan aldrig visas på hemskärmen.

## Redigera hemskärmen

Vårdnadshavarnas dashboard har en redigerare för hemskärmen:

- Dra och släpp widgets i ett rutnät och ändra storlek (gridstack)
- Inställningar per widget (t.ex. vilka barn, vilka kalendrar, vilken lista)
- Förhandsvisning som ser ut som skärmen
- Varje skärm har sin egen layout (t.ex. kök och hall)

display_widgets

- family_id
- device_id (skärmen)
- type (clock, calendar, school_meals, tasks, activities, shopping_list, points)
- x, y, w, h (position i rutnätet)
- settings (json)

Rutnät: 12 kolumner, liggande 16:9.

## Widgets i koden

- Backend: en klass per widget-typ som implementerar DisplayWidget (typ, validering av settings, data för familjen)
- Frontend: en Vue-komponent per widget i resources/js/components/display/widgets/
- DisplayController skickar bara data för de widgets som finns på skärmen

## Åtkomst

Hemskärmen har ingen inloggad användare. Den är en enhet (devices, type = display) med en hemlig länk.

Regler:

- En vuxen skapar en skärm och får länken /display/{token}
- Middleware slår upp enheten via token och sätter aktuell familj
- Länken kan återkallas genom att enheten tas bort
- Hemskärmen är skrivskyddad

All data hämtas via enhetens family_id:

Task::where('family_id', $device->family_id)

## Uppdatering

- Inertia usePoll (t.ex. var 60:e sekund, background: 'continue')
- Senare: Laravel Reverb för realtid

## Design

- Liggande format, ingen navigation, ingen hover
- Läsbar på flera meters avstånd: stora typsnitt, hög kontrast
- Mörkt tema kvällstid

## Hårdvara (förslag)

- Raspberry Pi med Chromium i kioskläge
- Eller en surfplatta med kioskapp

---

# Bildstöd

Uppgifter, listor, belöningar och familjemedlemmar ska kunna visas med bild + kort text, så att den som inte läser (eller inte orkar läsa) förstår ändå.

## Bildkällor

1. Bildgalleri (gemensamt): bilder som admin laddar upp i adminportalen. Alla familjer väljer från galleriet.
2. Familjens egna foton: t.ex. barnets egen tandborste. Syns bara för familjen.

Galleriet kan kompletteras med Mulberry Symbols (CC BY-SA 4.0, kommersiell användning tillåten). De importeras via adminportalen och får svenska etiketter där. Källan anges på en sida med bildkällor.

ARASAAC används inte: licensen (CC BY-NC-SA) förbjuder kommersiell användning, och appen kan bli en betaltjänst.

## Tabeller

pictures

- family_id (nullable: null = gemensamt galleri)
- picture_category_id (nullable)
- source (gallery, upload)
- path
- label (kort text, används även som alt-text)
- keywords (sökord)

picture_categories

- name (t.ex. Morgon, Mat, Skola, Hygien, Känslor)
- sort_order

Modeller med bild får picture_id (nullable):

- tasks
- list_items
- rewards
- recurring_tasks
- users (avatar)

## Regler

- Galleriet (family_id = null) kan läsas av alla familjer men bara ändras av admin
- Familjens bilder hämtas med: where family_id = aktiv familj OR family_id IS NULL
- Familjens foton sparas på privat disk och visas via en route som kontrollerar behörighet (PicturePolicy, hemskärmen via enhetens token)
- Uppladdade bilder skalas ner och konverteras till WebP i ett köjobb
- Bildväljaren söker i galleriet och familjens foton

---

# Aktiviteter och packlistor

Barnen ska se vad som händer idag och få hjälp att förbereda sig. Exempel: simskola.

- Dagen innan: "Packa till simskolan imorgon" med bildstöd för det som ska packas
- Samma dag: "Har du packat till simskolan?" med samma bilder

## Tabeller

activities

- family_id
- name (t.ex. Simskola)
- picture_id
- date (nullable: en gång)
- weekday (nullable: varje vecka)
- time

activity_child

- activity_id
- user_id (barnet)

activity_items (packlistan)

- activity_id
- picture_id
- label (t.ex. Badbyxor)
- sort_order

activity_item_checks (barnet bockar av dagen innan)

- activity_id
- user_id
- date (tillfället)
- activity_item_id
- checked_at

activity_confirmations (barnet svarar "ja, jag har packat" samma dag)

- activity_id
- user_id
- date
- confirmed_at

## Regler

- Vuxna skapar aktiviteter och packlistor, barn bockar av
- Packlistan återanvänds varje gång aktiviteten sker
- Barnets Idag-vy visar dagens aktiviteter och morgondagens packning
- Vuxna ser om barnet har packat
- Senare: kopplas till kalenderhändelser via namn (t.ex. "Simskola" i Google Kalender)
- Senare: push-påminnelse dagen innan (PWA)

---

# Adminportal

Adminportalen är till för den som driver appen, inte för familjerna.

- Filament 5 på /admin (bygger på Livewire, separat från Inertia-appen)
- Bara användare med users.is_admin = true får åtkomst (canAccessPanel)
- is_admin är en roll för hela appen, inte en familjeroll

Funktioner:

- Bildgalleri: massuppladdning, kategorier, etiketter, sökord, sortering
- Senare: översikt av familjer, moderering av community

Adminportalen visar aldrig familjers innehåll (uppgifter, dagbok, foton). Bara översiktsdata, t.ex. antal familjer.

Bilder som läggs i galleriet delas med alla familjer. Kontrollera att du har rätt att dela dem, även i en framtida betalversion.

---

# Skolmat (Matilda)

Skolmåltider hämtas från Matilda Menu (menu.matildaplatform.com).

## Tabeller

school_menus

- family_id
- name (skolans namn)
- provider (matilda)
- external_id (Matildas distributorId)

family_user

- school_menu_id (nullable): vilken skola familjemedlemmen äter på

Barn på olika skolor får ofta olika lunch, så varje barn kopplas till sin egen skola. Syskon på samma skola delar meny.

school_meals

- school_menu_id
- date
- meal_name (t.ex. "Lunch", "Frukost", "Mellanmål")
- holiday_name
- courses (json: name, optionName, t.ex. "Lunch 1" / "Lunch 2")
- fetched_at

Unik nyckel: school_menu_id + date + meal_name.

Rättnamn trimmas vid import (Matilda skickar ibland avslutande mellanslag).

school_meals nås alltid via familjens school_menus.

## Koppla en skola

Användaren klistrar in skolans Matilda-länk:

https://menu.matildaplatform.com/meals/week/{distributorId}_{namn}

distributorId tolkas ur länken.

## Hämtning

GET https://menu.matildaplatform.com/api/menu?distributorId={id}&startDate=YYYY-MM-DD&endDate=YYYY-MM-DD&lang=sv

Svar: meals[] med date, name (måltid), holidayName och courses[] (name, optionName).

Verifierat mot riktiga skolmenyer 2026-10-11.

Regler:

- Ett schemalagt kommando hämtar denna och nästa vecka varje morgon
- Sidor och hemskärm läser bara från databasen, aldrig direkt från Matilda
- Laravel Http-klient med timeout och retry
- Om hämtningen misslyckas visas senast hämtade data

## Leverantörer

Endpointen ovan är inte officiellt dokumenterad (den används av Matildas publika menysida) och kan ändras.

Officiellt alternativ: OpenMeal 3.0 API (kräver API-nyckel och Distributor ID från Matilda Foodtech).

Hämtningen byggs därför bakom ett interface:

MealProvider

- MatildaMealProvider
- (senare) OpenMealProvider

Tester använder Http::fake(), aldrig riktiga anrop.

---

# Kalender

Familjens kalendrar från Google Kalender, iPhone (iCloud) och Outlook visas i appen och på hemskärmen.

## Steg 1: iCal-länk (alla tre)

Alla tre kan dela en kalender som en hemlig iCal-länk (.ics):

- Google Kalender: "Hemlig adress i iCal-format"
- iPhone/iCloud: dela kalendern som offentlig kalender (webcal:// byts till https:// vid hämtning)
- Outlook: publicera kalendern och kopiera ICS-länken

Kräver ingen inloggning mot Google, Apple eller Microsoft. Bara läsning.

## Steg 2: Tvåvägssynk (senare)

- Google Calendar API (OAuth). Google kräver verifiering av appen innan fler än 100 användare.
- Outlook via Microsoft Graph (OAuth)
- iCloud har inget officiellt API (bara CalDAV med appspecifikt lösenord) och stannar på iCal-länk

## Tabeller

calendars

- family_id
- user_id (nullable: vems kalender)
- name
- color
- provider (ics, senare google, outlook)
- url (krypterad: länken ger läsrätt till hela kalendern)
- last_synced_at

calendar_events

- family_id
- calendar_id
- uid
- starts_at, ends_at (UTC)
- all_day
- title
- location

## Regler

- Bara vuxna kan lägga till kalendrar
- Ett schemalagt kommando synkar var 15:e minut
- sabre/vobject tolkar iCal; återkommande händelser (RRULE) räknas ut för 1 vecka bakåt och 8 veckor framåt
- Sidor och hemskärm läser bara från databasen
- Tider lagras i UTC och visas i Europe/Stockholm
- Om synken misslyckas visas senast hämtade data

---

# PWA och påminnelser

Påminnelser byggs tillsammans med PWA.

- PWA: vite-plugin-pwa (manifest, service worker, installerbar)
- Push: laravel-notification-channels/webpush (VAPID-nycklar)
- iPhone: push fungerar bara när appen lagts till på hemskärmen
- Påminnelser för uppgifter med tid, rutiner, återkommande uppgifter, kalenderhändelser och packning dagen innan en aktivitet
- Barn får påminnelser på sin egen enhet
- Tysta timmar per användare (t.ex. 20–07)
- Påminnelser skickas via Laravel Notifications från ett schemalagt kommando

---

# Struktur Laravel

Models:

app/Models

Controllers:

app/Http/Controllers

Policies:

app/Policies

Requests:

app/Http/Requests

---

# Struktur Vue

Pages:

resources/js/pages

Exempel:

pages/

today/
lists/
activities/
journal/
rewards/
community/
display/

Övriga mappar (från starter kit):

resources/js/components
resources/js/components/ui (shadcn-vue)
resources/js/composables
resources/js/layouts
resources/js/types

---

# Designprinciper

## NPF-vänlig design

NPF-familjen är ett hjälpmedel för barn och familjer med NPF-problematik (t.ex. ADHD och autism).

Systemet ska vara:

- Snabbt
- Enkelt
- Få klick
- Mobilvänligt
- Bildstöd: bild + kort text där det går
- Förutsägbart: samma struktur varje dag, ändringar syns tydligt

Prioriterade vyer:

1 Today (barn: vad ska jag göra idag, med bildstöd. Vuxna: hela familjens dag)
2 Lists
3 Rewards

---

# Kodregler

## Controllers

Controllers ska vara små.

Logik placeras i Services om den växer.

---

## Policies

Alla modeller ska ha policies.

Exempel:

TaskPolicy
ListPolicy
RewardPolicy

---

## Validation

FormRequests används alltid.

---

# Naming

## Tables

snake_case plural

families
tasks
rewards

---

## Models

Singular PascalCase

Family
Task
Reward

---

# Inertia regler

Vue pages skall vara små.

Business logic i Laravel.

---

# Integritet och datakontroll

Familjen äger sin data och har full kontroll över den.

- Export: en vuxen kan exportera all familjens data (JSON + bilder i en ZIP)
- Radering: en vuxen kan radera ett barnkonto eller hela familjen
- Vid radering tas all data och alla filer bort (köjobb), även familjens community-inlägg
- Dagboken krypteras i databasen (Laravel encrypted cast)
- Familjens foton ligger på privat disk
- Hosting inom EU
- Integritetspolicy innan andra familjer bjuds in

---

# Security

Viktiga regler:

- All queries filtreras på family_id (BelongsToFamily)
- Policies används alltid och kontrollerar roll (parent/child)
- Validation används alltid
- Isolationstester: för varje modell testas att familj A inte kan läsa eller ändra familj B:s data
- PIN-inloggning rate-limitas och fungerar bara på betrodda enheter

---

# Framtida arkitektur

När systemet växer:

## Möjliga tillägg

- Redis
- Queue workers (Laravel Horizon vid Redis)
- Websockets (Laravel Reverb + Echo)

## Betalversion (senare)

- Laravel Cashier
- Family är Billable: familjen betalar, inte den enskilda användaren
- Laravel Pennant för att skilja gratis- och betalfunktioner

---

# Copilot riktlinjer

Laravel Boost genererar Laravel-specifika AI-riktlinjer. Reglerna nedan gäller utöver dessa.

All ny kod skall:

- använda policies som kontrollerar roll (parent/child)
- använda FormRequests
- använda BelongsToFamily på alla modeller med family_id
- ha isolationstester mellan familjer

Exempel:

Task::where('family_id', $user->active_family_id)

ALDRIG:

Task::all()
withoutGlobalScopes() i kod som körs för en familj