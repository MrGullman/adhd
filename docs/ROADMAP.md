# NPF-familjen – Roadmap

## Projektmål

NPF-familjen är ett hjälpmedel för barn och familjer med NPF-problematik (t.ex. ADHD och autism) i familjen.

En familjedelad webapp för:

- Att-göra listor
- Matlistor och shoppinglistor
- Dagbok för vårdnadshavare
- Belöningssystem
- Kalender (Google, iPhone, Outlook)
- Påminnelser (PWA)
- Familjedelning med egna enkla konton för barn
- Bildstöd (pictogram och egna foton)
- Aktiviteter med packlistor i bildstöd
- Hemskärm som vårdnadshavarna själva ställer in
- Community för vårdnadshavare

Systemet byggs med:

- Laravel 13
- SQLite
- Laravels Vue starter kit (Fortify-auth)
- Inertia.js 3
- Vue 3 + TypeScript
- Tailwind CSS 4 + shadcn-vue
- Filament 5 (adminportal)

Mobilvänlig design är prioritet.

Familjen äger sin data. Data delas aldrig mellan familjer.

Appen är gratis till en början men kan bli en betaltjänst längre fram.

---

# Milestone 0 – Setup

## Mål
Få en fungerande Laravel-app lokalt.

## Tasks

- [ ] Skapa appen med `laravel new` (Laravel 13, välj Vue starter kit utan Teams, Pest och SQLite)
- [ ] Sätt APP_NAME="NPF-familjen"
- [ ] Servera appen via Laravel Herd
- [ ] Installera Laravel Boost (`composer require laravel/boost --dev` + `php artisan boost:install`, välj Copilot)
- [ ] Kör migrations
- [ ] Verifiera login fungerar

## Definition of Done

- App kör lokalt
- Login fungerar
- Dashboard visas

---

# Milestone 1 – Familjer och konton

## Mål

All privat data ska vara kopplad till en familj. Vuxna och barn har egna konton.

## Tasks

- [ ] Skapa families tabell
- [ ] Skapa family_user pivot tabell med role (parent/child)
- [ ] Lägg till active_family_id i users tabellen
- [ ] Create Family page
- [ ] Middleware för aktuell familj
- [ ] BelongsToFamily trait (global scope + family_id vid create)
- [ ] Barnkonton: vuxen skapar barn (namn, PIN), email nullable
- [ ] devices tabell
- [ ] Familjeenhet: barnet väljer sig själv och skriver PIN
- [ ] Barnets egen enhet kopplas via QR-kod
- [ ] Rate limiting på PIN-inloggning
- [ ] Isolationstester mellan familjer

## Definition of Done

- User kan skapa familj
- Vuxen kan skapa barnkonto
- Barn kan logga in med PIN, men bara på en betrodd enhet
- Aktiv familj används överallt
- Tester visar att familj A aldrig ser familj B:s data

---

# Milestone 2 – Tasks och listor

## Mål

Familjen ska kunna dela uppgifter och listor.

## Tasks

- [ ] Lists tabell
- [ ] Tasks tabell
- [ ] Lists UI
- [ ] Tasks UI
- [ ] Assign user
- [ ] Delete tasks
- [ ] Edit tasks

## Definition of Done

- Familjen kan hantera listor
- Familjen kan hantera tasks

---

# Milestone 3 – Bildstöd

## Mål

Allt viktigt ska gå att förstå utan att läsa: uppgifter, listor, belöningar och familjemedlemmar har bilder.

## Tasks

- [ ] pictures och picture_categories tabeller
- [ ] picture_id på tasks, list_items och users (avatar)
- [ ] is_admin på users
- [ ] Installera Filament 5, adminportal på /admin (bara admin)
- [ ] Admin: massuppladdning till galleriet, kategorier, etiketter, sökord
- [ ] Köjobb: skala ner och konvertera till WebP
- [ ] Bildväljare: galleri och familjens foton
- [ ] Ladda upp eget foto (mobilkamera)
- [ ] Admin: importera Mulberry Symbols med svenska etiketter (valfritt)
- [ ] Sida med bildkällor
- [ ] Visa bild + kort text i listor och tasks

## Definition of Done

- Admin kan ladda upp många bilder på en gång och sortera dem i kategorier
- Familjer kan välja bild från galleriet till en task
- Familjens egna foton är inte publikt åtkomliga

---

# Milestone 4 – Idag vy

## Mål

Visa dagens uppgifter. Barnen ser vad de ska göra idag, vårdnadshavarna ser hela familjens dag.

## Tasks

- [ ] TodayController
- [ ] pages/today/Index.vue
- [ ] Filtrera tasks för idag
- [ ] Barnets vy: dagens uppgifter med bildstöd och stora knappar
- [ ] Vårdnadshavarnas dashboard: hela familjens dag

## Definition of Done

- Idag visar tasks korrekt
- Barnet ser sin egen dag med bildstöd
- Vårdnadshavare ser alla barns dag

---

# Milestone 5 – Aktiviteter och packlistor

## Mål

Barnen ser dagens aktiviteter (t.ex. simskola) och får hjälp att packa med bildstöd.

## Tasks

- [ ] activities, activity_child och activity_items tabeller
- [ ] activity_item_checks och activity_confirmations tabeller
- [ ] Vuxen skapar aktivitet (en gång eller varje vecka) och packlista med bilder
- [ ] Dagen innan: "Packa till simskolan imorgon" i barnets Idag-vy, bocka av varje sak
- [ ] Samma dag: "Har du packat till simskolan?" med samma bilder
- [ ] Vuxna ser om barnet har packat

## Definition of Done

- Barnet får packlistan med bilder dagen innan
- Barnet får frågan om packningen samma dag
- Packlistan återanvänds varje gång aktiviteten sker

---

# Milestone 6 – Dagbok

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

# Milestone 7 – Belöningssystem

## Mål

Motivera användare genom poäng.

## Tasks

- [ ] task_completions tabell
- [ ] daily_goals tabell
- [ ] family_points_ledger tabell
- [ ] rewards tabell
- [ ] reward_redemptions tabell
- [ ] Rewards UI
- [ ] Bara vuxna kan ge och dra poäng
- [ ] Barn kan önska belöning, vuxen godkänner

## Definition of Done

- Tasks ger poäng
- Rewards kan lösas in
- Barn kan inte ge sig själva poäng

---

# Milestone 8 – Återkommande tasks

## Mål

Automatiska tasks.

## Tasks

- [ ] recurring_tasks tabell
- [ ] Scheduler command (schemaläggs i routes/console.php)

## Definition of Done

- Tasks skapas automatiskt

---

# Milestone 9 – Skolmat

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

# Milestone 10 – Kalender

## Mål

Familjens kalendrar från Google, iPhone (iCloud) och Outlook visas i appen och på hemskärmen.

## Tasks

- [ ] calendars tabell (iCal-länk lagras krypterad)
- [ ] calendar_events tabell
- [ ] Lägg till kalender via iCal-länk, med guide för Google, iPhone och Outlook
- [ ] Synk med sabre/vobject, inklusive återkommande händelser
- [ ] Schemalagt kommando som synkar var 15:e minut
- [ ] Visa dagens händelser i Idag-vyn
- [ ] Koppla aktiviteter till kalenderhändelser via namn (t.ex. "Simskola")
- [ ] Tester med Http::fake() och exempel-filer från alla tre

## Definition of Done

- Kalendrar från Google, iPhone och Outlook visas
- Återkommande händelser visas rätt
- Tider visas i svensk tid, även över sommartid/vintertid

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
- [ ] DisplayLayout utan navigation, pages/display/Index.vue
- [ ] Widgets: klocka/datum, kalender, skollunch, dagens uppgifter, aktiviteter och packning, shoppinglistor, poäng
- [ ] Automatisk uppdatering med usePoll

## Definition of Done

- Vårdnadshavare kan välja och placera widgets med dra och släpp
- Varje skärm har sin egen layout
- Skärmen visar bara de valda widgetarna, utan inloggning
- Ändringar syns på skärmen inom en minut
- Skärmlänken kan återkallas

---

# Milestone 12 – Datakontroll

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

# Milestone 13 – PWA och påminnelser

## Mål

Appen kan installeras på telefonen och skickar påminnelser.

## Tasks

- [ ] PWA med vite-plugin-pwa (manifest, service worker, ikon)
- [ ] Web Push med laravel-notification-channels/webpush (VAPID-nycklar)
- [ ] Guide: lägg till på hemskärmen (krävs för push på iPhone)
- [ ] Påminnelser för uppgifter med tid, rutiner och kalenderhändelser
- [ ] Påminnelse om packning dagen innan en aktivitet
- [ ] Barn får påminnelser på sin egen enhet
- [ ] Tysta timmar per användare

## Definition of Done

- Appen går att installera på iPhone och Android
- Påminnelser kommer i tid som push-notis
- Inga påminnelser under tysta timmar

---

# Milestone 14 – Community

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

# Milestone ordning

Rekommenderad byggordning:

1 Setup
2 Families
3 Tasks
4 Bildstöd
5 Today view
6 Aktiviteter och packlistor
7 Journal
8 Rewards
9 Recurring
10 Skolmat
11 Kalender
12 Hemskärm
13 Datakontroll
14 PWA och påminnelser
15 Community

---

# Framtida funktioner

## Möjliga features

- Realtime chat (Laravel Reverb + Echo)
- Statistik
- Betalversion (Laravel Cashier, familjen betalar)
- Kalender: tvåvägssynk med Google och Outlook
- Hemskärm: väder, foton
- Hemskärm: bocka av tasks via pekskärm

Dark mode ingår redan i starter kit (inställningar → Appearance).