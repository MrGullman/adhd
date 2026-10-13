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

- MariaDB 12.3 LTS (både lokalt och på servern, ingen SQLite)
- Teckenkodning utf8mb4, sortering utf8mb4_uca1400_swedish_ai_ci (å, ä, ö sorteras efter z)
- Tester körs mot en egen testdatabas i MariaDB

Auth:

- Laravel Fortify (ingår i starter kit; login, registrering, lösenordsåterställning, e-postverifiering, 2FA)

Lokal utvecklingsmiljö:

- Laravel Herd
- MariaDB 12.3 (Homebrew)
- Mailpit (Homebrew): fångar alla mejl lokalt, inkorg på http://localhost:8025

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
- Bildgalleriet, grundlistan med ingredienser och rutinmallarna (från adminportalen) är inte familjedata

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

- parent: vårdnadshavare
- editor: annan vuxen som kan se och ändra (t.ex. mormor eller barnvakt)
- viewer: annan vuxen som bara kan se
- child: barn

| | Vårdnadshavare | Vuxen som kan ändra | Vuxen som kan se | Barn |
| --- | --- | --- | --- | --- |
| Se familjens dag, Imorgon och Veckan | Ja | Ja | Ja | Sin egen |
| Ändra uppgifter, rutiner, aktiviteter och händelser | Ja | Ja | – | – |
| Ändra recept, skafferi och veckomatsedel | Ja | Ja | – | – |
| Lägga till på handlingslistan och bocka av | Ja | Ja | – | Ja |
| Bocka av sina uppgifter, rutiner och packlistor | – | – | – | Ja |
| Dagbok | Ja | – | – | – |
| Ge poäng och godkänna belöningar | Ja | – | – | Önska belöning |
| Medlemmar, barnkonton, koder och enheter | Ja | – | – | – |
| Hemskärmar och kalendrar | Ja | – | – | – |
| Community | Ja | – | – | – |
| Exportera och radera familjens data | Ja | – | – | – |

Rollen kontrolleras i policies.

## Barnens visningsläge (Milestone 20, planerat)

- users.child_view: small (som idag, ungefär 5–10 år) eller large (ungefär 10–14 år). Läget hör till barnet och följer med mellan familjer.
- Bara vårdnadshavare byter läge. Poäng och belöningar av eller på per barn ligger på family_user, eftersom det är familjens beslut.
- Läget påverkar bara barnets egna vyer, aldrig hemskärmen eller vuxnas vyer.

## Vuxenkonton

- Fortify: e-post + lösenord, 2FA
- Öppen registrering skapar en ny familj. Admin öppnar och stänger den i adminportalen (app_settings, App\Support\Registration). Innan knappen använts gäller REGISTRATION_ENABLED i .env. Inbjudna vuxna kan alltid gå med.
- En vårdnadshavare bjuder in andra vuxna via e-post och väljer roll (parent, editor eller viewer)
- Inbjudan är en länk som gäller i 7 dagar
- En vuxen kan tillhöra flera familjer (t.ex. mormor som hjälper två familjer) och byter familj i menyn

family_invitations

- family_id
- email
- role
- token (lagras hashad)
- invited_by
- expires_at
- accepted_at

## Barnkonton

Barn har enkla konton utan e-post och lösenord. De loggar in med en kod på 4–6 siffror.

- En vårdnadshavare skapar barnkontot: namn, avatar och kod
- Koden lagras krypterad (Laravel encrypted cast), inte hashad, så att vårdnadshavare kan visa den för barnet. Vid inloggning jämförs den med hash_equals.
- Vårdnadshavare kan visa, byta och återställa koden. Återställning ger en ny kod som vårdnadshavaren ser och säger till barnet.
- För enkla koder nekas: samma siffra (0000), stigande eller fallande följd (1234, 4321)
- Barnet loggar in på en betrodd enhet: väljer sig själv och skriver koden
- Koden fungerar bara på betrodda enheter
- Fel kod: efter 5 försök i rad väntar man en minut. Efter 10 försök i rad stängs barnets inloggning av, och vårdnadshavarna ser det i dashboarden.
- Barnens inloggning fungerar på både telefon och läsplatta

users (barn)

- email (nullable)
- pin (krypterad)
- login_disabled_at (nullable)

## Stänga av ett barns inloggning

Om en vårdnadshavare misstänker att någon annan har fått tillgång till barnets inloggning:

- Vårdnadshavaren väljer "Stäng av inloggning" på barnet
- Alla barnets sessioner loggas ut direkt (SESSION_DRIVER=database, sessioner tas bort på user_id)
- Barnets egna enheter (devices, type child) kopplas bort
- Familjeenheter finns kvar, men barnet kan inte logga in på dem
- Inloggningen slås på igen med en ny kod

## Inloggningshistorik

Vårdnadshavare ser när och på vilken enhet barnet har loggat in, och misslyckade försök.

login_events

- user_id
- device_id
- succeeded
- created_at

## Flera familjer (förberett för växelvis boende)

I version 1 tillhör ett barn en familj, men datamodellen tillåter fler så att växelvis boende kan läggas till senare.

- family_user är många-till-många. Det finns ingen family_id på users.
- Det som skiljer sig mellan familjer (roll, skola) ligger på family_user, aldrig på users
- Barnets innehåll (uppgifter, rutiner, aktiviteter) tillhör alltid en familj via family_id
- Senare: ett boendeschema som styr vilken familj som är barnets aktiva familj en viss dag

## Enheter

devices

- family_id
- user_id (nullable: barnets egen enhet)
- type (family, child, display)
- name
- token (lagras hashad)
- settings (json, t.ex. hemskärmens täthet)
- last_seen_at

Typer:

- family: delad enhet (t.ex. surfplatta i köket) där barnen loggar in med PIN
- child: barnets egen telefon/platta, kopplas genom att en vårdnadshavare visar en QR-kod
- display: hemskärmen

En vårdnadshavare aktiverar och återkallar enheter. Token sparas i en långlivad cookie (hemskärmen: i länken).

---

# Uppgifter och listor

## Tabeller

lists (modellen heter FamilyList, eftersom List är ett reserverat ord i PHP)

- family_id
- type (shopping, general)
- name
- created_by

list_items

- family_id
- list_id
- name
- added_by
- completed_at, completed_by

tasks

- family_id
- title
- assigned_to (nullable: null = hela familjen)
- date (dagen i Sverige)
- day_part (nullable)
- time (nullable, klockslaget i Sverige)
- completed_at, completed_by
- created_by

## Regler

- Varje familj har exakt en handlingslista (type shopping). Den skapas med familjen och kan döpas om och tömmas, men inte tas bort.
- Barn ser och ändrar bara handlingslistan: lägga till och bocka av. Ta bort och döpa om gör vuxna som kan ändra.
- tasks.date och tasks.time är kalenderdag och klockslag i Sverige, inte tidpunkter. Simskolan 17:15 är 17:15 både på sommar- och vintertid. Tidsstämplar (completed_at m.fl.) lagras i UTC.
- En uppgift med klockslag får alltid sin del av dagen från klockslaget. Tider före morgonen räknas som morgon.
- När familjen ändrar dagsdelarna flyttas kommande uppgifter med klockslag (från idag och framåt). Passerade dagar lämnas orörda.
- Uppgifter utan del av dagen och klockslag visas under "Under dagen".

## Återkommande uppgifter

recurring_tasks

- family_id
- title, assigned_to, day_part, time (som tasks)
- frequency (daily, weekly)
- weekdays (ISO-veckodagar, 1 = måndag, för weekly)
- week_interval (1 = varje vecka, 2 = varannan vecka, räknat från veckan med starts_on)
- starts_on, ends_on (nullable)
- created_by

tasks får:

- recurring_task_id (nullable, unikt tillsammans med date, så att samma dag aldrig skapas två gånger)
- customized (dagen är ändrad för hand)
- deleted_at (en överhoppad dag ligger kvar som borttagen och skapas inte igen)

Regler:

- Kommandot tasks:generate-recurring körs 00:05 svensk tid och skapar uppgifterna en vecka framåt (idag + 6 dagar), så att Imorgon och Veckan kan planeras. Det körs också direkt när en återkommande uppgift sparas.
- Kommandot går igenom familjerna en i taget som aktuell familj, så familjefiltreringen gäller som på en sida.
- Att ta bort en enskild dag betyder "hoppa över den här dagen".
- En ändring av en återkommande uppgift gäller från imorgon. Idag, avbockade dagar, dagar som ändrats för hand och överhoppade dagar lämnas orörda.
- Att ta bort en återkommande uppgift tar bort de kommande dagarna som ingen har rört. Idag och det som varit finns kvar.

---

# Dagbok

Dagboken är för vårdnadshavare: anteckningar om ett eller flera av familjens barn.

- Bara vårdnadshavare (parent) kan läsa och skriva. Andra vuxna (editor, viewer) ser den inte.
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

I version 2 (Milestone 13):

- /dagbok är skyddad både av rollen (role:parent) och av JournalEntryPolicy. Andra vuxna får 403, barn skickas till sin egen sida.
- Sökningen sker efter att anteckningarna dekrypterats, eftersom krypterad text inte går att söka i databasen.
- Båda vårdnadshavarna kan ändra och ta bort anteckningar. Den som skrev visas.
- Krypteringen använder APP_KEY. Nyckeln måste sparas säkert utanför servern (DRIFT.md).

---

# Belöningar

Barn får poäng för det de gör och kan önska sig något i verkliga livet, aldrig skärmtid i ett spel.

point_transactions (poängboken)

- family_id
- user_id (barnet)
- points (positivt när poäng ges, negativt när de används)
- reason
- source_type, source_id (task, routine, packing eller redemption)
- created_by (vårdnadshavaren, null när poängen kom av sig själv)

Ett barns poäng är summan. Raderna ändras aldrig i efterhand, utom automatiska poäng som tas bort när barnet bockar ur igen.

rewards

- family_id
- name
- picture_id
- cost
- active

reward_redemptions

- family_id
- reward_id
- user_id
- cost
- status (requested, approved, declined)
- decided_at, decided_by

I version 2 (Milestone 14):

- Bara vårdnadshavare (parent) ger och drar poäng, skapar belöningar och svarar på önskningar. /beloningar skyddas av role:parent och policies.
- Poäng av sig själv (App\Points\Points): avklarad uppgift (barnet som uppgiften är till, eller barnet som bockade av), avklarad rutin och "Ja, jag har packat". Familjen väljer antal eller stänger av det (families.settings.points, standard 1/3/1).
- Samma sak ger aldrig poäng två gånger: unikt index på (source_type, source_id, user_id). Bockas den ur tas poängen bort.
- Vuxna får aldrig poäng automatiskt.
- Ett barn kan önska när poängen räcker. Önskningar som väntar räknas bort från det barnet kan önska med. Godkänns önskningen skrivs en negativ rad (source redemption). "Inte nu" kostar inget.
- Widgeten points visar varje barns poäng och hur långt det är kvar till nästa belöning.

---

# Community Data

Community är separat från familjedata.

Community använder (prefix community_, eftersom groups är ett reserverat ord):

community_groups
community_group_members
community_threads
community_posts
community_reports

Community använder inte family_id.

Behörighet styrs via group_members: man läser och skriver bara i grupper man gått med i.

Bara vårdnadshavare (parent) har åtkomst. Barnkonton kommer aldrig åt Community.

I version 2 (Milestone 18):

- Admin skapar grupperna i adminportalen. Migrationen lägger in några att börja med.
- Varje vårdnadshavare väljer ett visningsnamn (users.community_name, unikt). Det riktiga namnet och familjen visas aldrig för andra familjer.
- Man ändrar och tar bort sina egna inlägg. Tar man bort trådens första inlägg försvinner tråden. Andras inlägg kan anmälas; admin tar bort dem eller låter dem vara kvar.
- Tas en vuxen bort försvinner hens trådar och inlägg (cascade), även svar andra skrivit i hens trådar.

---

# Hemskärm (Display)

En separat helskärmsvy för en skärm hemma (likt DAKboard). Hemskärmen är bara en visning, inte en dashboard: ingen meny, inga knappar.

## Widgets

Vårdnadshavarna väljer själva vad som visas. Familjer prioriterar olika: vissa vill ha kalender, andra skolmat eller vad som ska göras.

Tillgängliga widgets:

- Klocka och datum
- Kalender (dagens händelser)
- Skollunch per barn
- Middag ikväll / veckans mat (familjens veckomatsedel, inte skolmaten)
- Dagens uppgifter per familjemedlem (med bildstöd och avatar)
- Rutiner (varje barns pågående steg)
- Aktiviteter och packning (t.ex. "Imorgon: simskola")
- Imorgon (det man ska förbereda sig på)
- Veckan (bara det stora och det som avviker, se Aktiviteter och händelser)
- Veckoschema som whiteboarden: en rad per vald familjemedlem och en kolumn per dag (måndag–söndag, från söndag kväll nästa vecka), med aktiviteter och händelser. Det som inte gäller någon särskild hamnar på raden Alla. Raderna Middag och Skolmat kan väljas till. Vuxnas händelser syns bara med "Visa för barnen".
- Handlingslista
- Poäng och belöningar

Dagbok kan aldrig visas på hemskärmen.

## Täthet

Varje skärm har en täthet efter hur långt bort man brukar stå. Upplösningen spelar ingen roll: sidan skalar om sig, så 1080p och 4K ger samma storlek på samma skärm.

| Täthet | Passar när man står | Ungefär antal widgets |
| --- | --- | --- |
| Stor | 3–4 m bort | 4–5 |
| Mellan | 2–3 m bort | 6–7 |
| Kompakt | 1–2 m bort | 8–10 |

- Sparas i devices.settings.density (large, medium, compact). Förval: medium.
- Samma tokens i alla tätheter. Bara storleksstegen ändras, och rutnätet får fler rader när det blir tätare.
- Redigeraren förhandsvisar med vald täthet

Utkast: docs/mockups/hemskarm.html (#dag, #kvall, #kompakt) och bilderna i docs/mockups/.

## Byte till nästa dag

Widgets som visar en dag byter till nästa dag vid tider som familjen ställer in (families.settings):

- Skollunch: "idag" blir "imorgon" efter skoldagen. Förval 15:00.
- Middag: "ikväll" blir "imorgon" efter middagen. Förval 19:00.

## Redigera hemskärmen

Vårdnadshavarnas dashboard har en redigerare för hemskärmen:

- Dra och släpp widgets i ett rutnät och ändra storlek (gridstack)
- Inställningar per widget (t.ex. vilka barn, vilka kalendrar, vilken lista)
- Förhandsvisning som ser ut som skärmen
- Varje skärm har sin egen layout (t.ex. kök och hall)

display_widgets

- family_id
- device_id (skärmen)
- type (clock, calendar, school_meals, meal_plan, tasks, routines, activities, tomorrow, week, week_grid, shopping_list, points, calendar)
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

- En vårdnadshavare skapar en skärm och får länken /display/{token}
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

## I version 1 (Milestone 11)

- Klocka, datum, vecka och del av dagen står alltid överst. De är ingen widget.
- Widgets: child (Barnets dag: rutinens steg med kvartsprickar, annars nästa uppgift, och barnets aktiviteter), tasks, activities, tomorrow, week, shopping_list. school_meals och meal_plan kommer med Milestone 8 och 10.
- Rutnätet är alltid 12 × 6. Tätheten ändrar storleken på allt (Stor 1,25, Mellan 1, Kompakt 0,82), inte rutnätet.
- Skärmen ritas för 1920 px bredd och zoomas (CSS zoom) till skärmens storlek, så 1080p och 4K ser likadana ut. Förhandsvisningen i redigeraren använder samma komponent.
- Mörkt tema från kvällens start till morgonens start (familjens dagsdelar).
- Länken är /display/{token}. Bara enheter av typen display öppnar skärmen. Skärmen sparar sin token som enhetscookie, så att bilderna (/bilder/{id}) kan visas.
- Skärmen visar aldrig de vuxnas egna händelser: bara det som är markerat "Visa för barnen" eller gäller ett barn.
- Ett barn ser inte syskons aktiviteter. Det gäller både Min dag och barnkortet på skärmen.
- Nya widgets hamnar på första lediga plats i rutnätet.

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
- routines och routine_steps
- recipes
- ingredients (så att barn kan lägga till på handlingslistan genom att välja bild)
- users (avatar)

## Regler

- Galleriet (family_id = null) kan läsas av alla familjer men bara ändras av admin
- Familjens bilder hämtas med: where family_id = aktiv familj OR family_id IS NULL
- Familjens foton sparas på privat disk och visas via en route som kontrollerar behörighet (PicturePolicy, hemskärmen via enhetens token)
- Uppladdade bilder skalas ner och konverteras till WebP i ett köjobb
- Bildväljaren söker i galleriet och familjens foton
- Uppladdade bilder vänds rätt, skalas ner till högst 600 px och sparas som WebP utan kameradata (t.ex. var fotot togs). Originalet tas bort.
- Bilder visas via /bilder/{id}, för inloggade i familjen och betrodda enheter. Andra familjers foton ger 404.
- Picture använder inte BelongsToFamily: SharedOrFamilyScope visar galleriet och den aktuella familjens foton. Utan aktuell familj (t.ex. i adminportalen) bara galleriet.
- Adminportalen (/admin) använder appens vanliga inloggning. Adminkonton skapas bara på servern: php artisan admin:create (eget konto utan familj, hamnar direkt i adminportalen) eller users:admin <e-post> för ett befintligt konto.
- Vuxna som kan ändra laddar upp familjens foton. Barn väljer bilder men laddar inte upp.

---

# Aktiviteter, händelser och packlistor

Barnen ska se vad som händer och få hjälp att förbereda sig. Exempel: simskola.

- Dagen innan: "Packa till simskolan imorgon" med bildstöd för det som ska packas
- Samma dag: "Har du packat till simskolan?" med samma bilder

Samma tabell används för händelser som rör vuxna, t.ex. när en förälder reser bort eller någon annan hämtar. Barn med NPF oroar sig ofta för "Var är mamma?" och "Vem hämtar mig?".

## Typer

- activity: aktivitet med eventuell packlista (simskola, fotboll)
- away: någon är bortrest (Mamma i Stockholm tisdag–onsdag)
- pickup: vem som hämtar eller lämnar (Pappa hämtar 16:00, Mormor hämtar)
- day_off: annorlunda dag (studiedag, lov)

## Tabeller

activities

- family_id
- type (activity, away, pickup, day_off)
- name (t.ex. Simskola)
- picture_id
- date (nullable: en gång)
- end_date (nullable: flera dagar, t.ex. en resa)
- weekday (nullable: varje vecka)
- time
- visible_to_children (Visa för barnen)
- unusual (markeras som Annorlunda)

activity_participants

- activity_id
- user_id (barn eller vuxen)

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

- Vuxna skapar aktiviteter, händelser och packlistor, barn bockar av
- Packlistor finns bara för type activity och återanvänds varje gång aktiviteten sker
- Barnets Idag-vy visar dagens aktiviteter och morgondagens packning
- Vuxna ser om barnet har packat
- En vuxens händelser syns för barnen bara när "Visa för barnen" är valt. Barnen ser det som rör dem (vem som hämtar, vem som är borta), aldrig de vuxnas möten eller arbete.
- Bortrest: barnen ser en nedräkning i nätter, t.ex. "2 sömnar kvar tills mamma kommer hem"
- Det som avviker från en vanlig dag (studiedag, någon annan hämtar) markeras som Annorlunda
- Senare: importerade kalendrar (version 2) blir händelser som är dolda för barnen från början
- Senare: kopplas till kalenderhändelser via namn (t.ex. "Simskola" i Google Kalender)
- Senare: push-påminnelse dagen innan (PWA)

I koden:

- En gång: date (till end_date för flera dagar). Varje vecka: weekday, mellan date och end_date om de är satta. Dagarna är dagar i Sverige.
- Packning och "Ja, jag har packat" sparas per tillfälle (aktivitetens dag), så listan börjar om nästa gång.
- Barnet packar bara för idag eller imorgon, och bara till det barnet själv är med i.
- Bortrest: aktivitetens deltagare är den som är borta, end_date är hemkomstdagen. Sömnar kvar = dagar till end_date.
- Barnets vecka visar högst tre saker per dag, resten som "Och 2 till".

## Idag, Imorgon och Veckan

| Vy | Visar |
| --- | --- |
| Idag | Allt: rutiner, uppgifter, packning, mat |
| Imorgon | Det man ska förbereda sig på: aktiviteter, packning, vem som hämtar, det som är annorlunda |
| Veckan | Bara aktiviteter, händelser och det som är annorlunda. Inga rutiner eller vardagsuppgifter. |

- Veckan visar högst två–tre saker per dag och barn, med bild
- Idag är markerad och dagar som har varit tonas ner
- Barn ser sin egen vecka, vuxna ser allas
- Imorgon och Veckan finns i appen och som widgets på hemskärmen

Idag i version 1 (Milestone 5):

- Vuxna (/dashboard) ser hela familjens dag, grupperad efter del av dagen, och kan visa en person i taget. Varje barn visas med "3 av 5 klara".
- Barnet (/min-dag) ser bara sina egna uppgifter. Uppgifter för hela familjen visas för vuxna.
- "Nu" är den första uppgiften som inte är klar i den aktuella delen av dagen eller senare. Missade uppgifter från tidigare delar av dagen trycks inte fram igen, de syns lugnt i listan.
- Aktuell del av dagen räknas ut från klockan i Sverige och familjens dagsdelar.
- På läsplatta i liggande läge (md och landscape) visas "Nu" och listan bredvid varandra.

---

# Rutiner

En rutin är en kedja av steg med bildstöd, t.ex. morgonrutin, efter skolan eller läggdags.

## Inspiration från liknande appar

- Brili: rutinen har en sluttid, tiden per steg anpassas efter hur det går, rätt rutin startar vid rätt tid
- Tiimo: fokus på nu och nästa, visuell timer
- Goally: steg för steg med bild och timer, vuxna följer läget från sin telefon
- Choiceworks: "att göra" och "klart", först–sen
- MEMOplanner (Abilia): kvartsprincipen, 1 prick = 15 minuter, prickarna släcks när tiden går
- Joon: barnet bockar av, vuxen godkänner, poäng

## Så gör vi

- Fokusläge: ett steg i taget med stor bild och stor Klar-knapp. Nästa steg syns litet (först–sen).
- Synlig tid: kvartsprickar fram till "klar senast". Lugnt, inga röda larm.
- Om tiden inte räcker föreslår appen att hoppa över steg som vuxna markerat som valfria
- Kopplat till dagen: steget "Kolla packningen" visar dagens packlistor (t.ex. simskola)
- Steg kan gälla bara vissa dagar (t.ex. "packa gympapåsen" på måndagar)
- Uppläsning av steget med webbläsarens talsyntes (svenska), för barn som inte läser
- Rätt rutin visas överst i barnets Idag-vy när det är dags
- Syns på hemskärmen: varje barns pågående steg
- Vuxna ser läget live i sin dashboard
- Belöning i verkliga livet: avklarad rutin kan ge poäng i belöningssystemet, aldrig skärmtid i ett spel
- Färdiga mallar (morgon, efter skolan, kväll, läggdags) från adminportalen. Familjen kopierar och anpassar.
- Lugnt firande när rutinen är klar

## Tabeller

routines

- family_id (nullable: null = mall från adminportalen)
- name
- picture_id
- part_of_day
- start_time (nullable)
- done_by (nullable: klar senast)
- weekdays
- ordered (fast eller fri ordning)

routine_child

- routine_id
- user_id

routine_steps

- routine_id
- picture_id
- label
- minutes (uppskattad tid)
- type (step, packlist)
- weekdays (nullable: bara vissa dagar)
- optional (kan hoppas över om tiden inte räcker)
- sort_order

routine_runs (en körning per barn och dag)

- routine_id
- user_id
- date
- started_at
- completed_at

routine_step_checks

- routine_run_id
- routine_step_id
- checked_at
- skipped

Mallar kopieras till familjen. Familjens rutiner delas aldrig tillbaka.

Regler i koden:

- Routine filtreras på aktuell familj (FamilyScope) men får aldrig family_id automatiskt, så att en mall förblir utan familj även när en admin som också är förälder skapar den. Mallar läses bara via Routine::templates().
- Mallarna (morgon, efter skolan, kväll, läggdags) läggs in av migreringen och ändras i adminportalen under Rutiner.
- En körning (routine_runs) skapas när barnet bockar av första steget och blir klar när alla dagens steg är gjorda eller överhoppade.
- Steg behåller sitt id när rutinen ändras, så att det barnet redan gjort idag finns kvar.
- "Dags" betyder: rutinens del av dagen är nu, eller den har startat och "klar senast" har inte passerat med mer än en timme.
- Om de återstående stegen tar längre tid än det som är kvar till "klar senast" föreslås de valfria stegen. Bara valfria steg kan hoppas över.
- Vuxnas Idag läses om var 15:e sekund (Inertia usePoll), så att läget syns nästan live.

---

# Adminportal

Adminportalen är till för den som driver appen, inte för familjerna.

- Filament 5 på /admin (bygger på Livewire, separat från Inertia-appen)
- Bara användare med users.is_admin = true får åtkomst (canAccessPanel)
- is_admin är en roll för hela appen, inte en familjeroll

Funktioner:

- Bildgalleri: massuppladdning, kategorier, etiketter, sökord, sortering
- Grundlista med ingredienser och butiksavdelningar
- Rutinmallar
- Senare: översikt av familjer, moderering av community

Adminportalen visar aldrig familjers innehåll (uppgifter, dagbok, foton). Bara översiktsdata, t.ex. antal familjer.

Bilder som läggs i galleriet delas med alla familjer. Kontrollera att du har rätt att dela dem, även i en framtida betalversion.

---

# Mat: receptbank, skafferi och veckomatsedel

Familjens egen mat. Hålls helt skild från Skolmat (Matilda): egna tabeller, egna sidor och egna widgets.

## Flöde

1. Familjen bygger en receptbank
2. Familjen skriver in vad som finns hemma (skafferi)
3. Appen föreslår recept utifrån vad som finns hemma
4. Recept läggs in i veckomatsedeln
5. Appen skapar en handlingslista med det som behöver kompletteringshandlas
6. Det som bockas av på handlingslistan hamnar i skafferiet

## Tabeller

ingredients

- family_id (nullable: null = gemensam grundlista från adminportalen)
- name
- picture_id (nullable)
- category (butiksavdelning, t.ex. Frukt & grönt, Mejeri, Skafferi, Frys)
- default_unit

recipes

- family_id
- name
- picture_id
- portions
- instructions
- time_minutes

recipe_ingredients

- recipe_id
- ingredient_id
- quantity (nullable)
- unit
- note (t.ex. "hackad")

pantry_items (det som finns hemma)

- family_id
- ingredient_id
- quantity (nullable: tom = "finns")
- unit
- always_home (basvara som alltid finns, t.ex. salt och olja)

meal_plan_entries (veckomatsedel)

- family_id
- date
- meal (t.ex. middag)
- recipe_id (nullable)
- title (fritext utan recept, t.ex. "Pizza ute")
- portions

list_items (handlingslistan) får också:

- ingredient_id (nullable)
- quantity, unit
- meal_plan_entry_id (nullable: vilken måltid varan behövs till)
- added_by (vem som lade till varan)

## Regler

- Ingredienser väljs med autocomplete, så samma vara alltid heter samma sak. Det krävs för att kunna jämföra recept och skafferi.
- Grundlistan med vanliga ingredienser underhålls i adminportalen. Familjen kan lägga till egna.
- Matförslag: recept rangordnas efter hur stor del av ingredienserna som finns hemma. Visas som "Kan lagas nu" och "Saknar 1–2 saker". Basvaror räknas som att de finns.
- Handlingslista från veckomatsedeln: veckans ingredienser (skalade efter portioner) minus det som finns hemma
- Finns en vara hemma utan angiven mängd räknas den som tillräcklig
- Samma vara slås ihop. Mängder räknas om inom vikt (g, kg) och volym (krm, tsk, msk, ml, dl, l). Olika enhetsgrupper (t.ex. st och g) listas var för sig.
- Handlingslistan sorteras efter butiksavdelning
- Avbockad vara läggs i skafferiet
- Om veckomatsedeln ändras: "Uppdatera handlingslistan" lägger till det som tillkommit och tar bort varor från måltider som strukits, om de inte är avbockade. Varor som familjen lagt till själv rörs aldrig.
- Barn ser veckomatsedeln med bild ("Middag ikväll" i Idag-vyn)
- Barn kan lägga till på handlingslistan, genom att skriva eller välja en bild. Vuxna ser vem som lagt till.

I version 1 (Milestone 9):

- Grundlistan (ingredients med family_id null, drygt 100 varor med butiksavdelning och vanlig enhet) läggs in av migreringen och underhålls i adminportalen under Mat → Grundlista.
- Ingredient använder SharedOrFamilyScope, samma som Picture: grundlistan och familjens egna.
- Ett namn som inte finns blir familjens egen vara. Namn jämförs utan hänsyn till versaler, så "mjölk" blir grundlistans Mjölk.
- Ett skafferi har en rad per vara. Att lägga till samma vara igen uppdaterar raden.
- "Smör el margarin (till stekning)": "el", "eller" och "/" skiljer alternativ som alla duger, och det som står inom parentes blir anteckningen, aldrig en del av varans namn. Första varan sparas i recipe_ingredients.ingredient_id och resten i recipe_ingredient_alternatives. Raden räknas som hemma när någon av varorna finns.
- Matförslag i Milestone 9: en vara som finns hemma räknas som tillräcklig oavsett mängd. Mängder och enheter jämförs när handlingslistan räknas ut (Milestone 10).
- Butiksavdelningar i gångordning: Frukt & grönt, Bröd, Mejeri, Kött & fisk, Skafferi, Frys, Dryck, Hushåll, Övrigt.

I version 1 (Milestone 10):

- Veckomatsedeln gäller middag (meal_plan_entries.meal = dinner). Fritext utan recept handlas inte till.
- "Uppdatera handlingslistan" gäller veckan som visas, från idag och framåt (måltider som varit handlas inte till). Varorna som räknades ut förra gången för samma vecka och inte är avbockade byts ut. Det familjen lagt till själv (list_items.meal_plan_week null) och det som är avbockat rörs aldrig.
- Mängder skalas med veckomatsedelns portioner delat med receptets. Samma vara slås ihop per enhetsgrupp: vikt (g, kg), volym (krm, tsk, msk, ml, dl, l) eller samma enhet. Styck, påse, förpackning m.fl. avrundas uppåt till hela.
- Avdrag mot skafferiet: basvaror och varor utan mängd räcker alltid. En mängd dras av inom samma enhetsgrupp. Olika grupper (t.ex. st och g) kan inte jämföras, då handlas hela behovet.
- "smör eller margarin": inget att handla när någon av varorna finns hemma.
- En vara som läggs på handlingslistan med ett namn som finns bland varorna får varans butiksavdelning. Avbockat på handlingslistan läggs i skafferiet (utan mängd).
- "Middag ikväll" visas i Idag, Min dag och på hemskärmen (widget Middag). Efter families.settings.dinner_switch (förval 19:00) visas morgondagens middag.

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

I version 1 (Milestone 8):

- En skola läggs till med Matilda-länken. Namnet tas ur länken (…/meals/week/{distributorId}_{namn}) och kan ändras. Ingen kontakt med Matilda medan sidan visas: menyn hämtas i ett köjobb och sedan av school-meals:fetch kl. 05:30 varje morgon.
- Hämtningen gäller måndag denna vecka till söndag nästa vecka (14 dagar).
- Skollunchen visas i vuxnas Idag, barnets Min dag, barnkortet på hemskärmen och widgeten Skollunch.
- Efter families.settings.school_lunch_switch (förval 15:00) visas nästa skoldags lunch: "Skollunch imorgon", eller till exempel "Skollunch måndag" på fredag eftermiddag.
- Skolnamn och distributorId för familjens riktiga skolor finns bara i databasen, aldrig i kod, tester eller dokument.

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
- provider (ics, senare google, outlook)
- url (krypterad: länken ger läsrätt till hela kalendern)
- visible_to_children
- last_synced_at, last_failed_at

calendar_events

- family_id
- calendar_id
- uid
- starts_at, ends_at (UTC)
- all_day
- title
- location

## Regler

- Bara vårdnadshavare kan lägga till kalendrar
- Ett schemalagt kommando synkar var 15:e minut
- sabre/vobject tolkar iCal; återkommande händelser (RRULE) räknas ut för 1 vecka bakåt och 8 veckor framåt
- Sidor och hemskärm läser bara från databasen
- Tider lagras i UTC och visas i Europe/Stockholm
- Om synken misslyckas visas senast hämtade data

I version 2 (Milestone 15):

- Vuxna ser alla kalendrar. Ett barn ser sin egen kalender och de som är valda med "Visa för barnen" (inte ett syskons). Hemskärmen visar det som något barn får se.
- Heldagshändelser lagras som datum (starts_at 00:00, ends_at dagen efter). Händelser med tid lagras i UTC.
- En händelse som heter som en aktivitet (t.ex. "Simskola") får aktivitetens bild, och visas inte en gång till de dagar aktiviteten redan syns.
- Länken måste vara https (webcal:// byts) till en publik adress. Servern hämtar aldrig adresser i sitt eget nät, inte heller efter en omdirigering.
- Kalendern läses i bakgrunden när den läggs till och med knappen Läs in nu. Sidor läser aldrig länken.

---

# PWA och påminnelser

Påminnelser byggs tillsammans med PWA.

- PWA: eget manifest (public/manifest.webmanifest) och en liten egen service worker (public/sw.js) som bara visar notiser. Inget cachas, så appen visar alltid aktuell data. (Beslut i Milestone 16 i stället för vite-plugin-pwa: inga nya byggsteg eller paket.)
- Push: laravel-notification-channels/webpush (VAPID-nycklar)
- iPhone: push fungerar bara när appen lagts till på hemskärmen
- Påminnelser för uppgifter med tid, rutiner, återkommande uppgifter, kalenderhändelser och packning dagen innan en aktivitet
- Barn får påminnelser på sin egen enhet
- Tysta timmar per användare (t.ex. 20–07)
- Påminnelser skickas via Laravel Notifications från ett schemalagt kommando

I version 2 (Milestone 16):

- reminders:send körs varje minut. En påminnelse skickas vid sin tid, eller upp till 10 minuter senare om körningen var sen, och aldrig två gånger (sent_reminders med unik nyckel).
- Uppgift med klockslag: till den uppgiften är till. Rutin: när den startar, till varje barn som inte är klart. Kalenderhändelse: 15 minuter innan, till den vars kalender det är. Packning: 18:00 dagen innan, till barn som inte packat allt.
- Tysta timmar och på/av ligger på users (gäller personen i alla familjer). Förval 20:00–07:00. Vårdnadshavare ställer in barnens.
- Push skickas bara till publika https-adresser, aldrig till serverns eget nät.

---

# Språk och tid

- Appen är på svenska (APP_LOCALE=sv, APP_FAKER_LOCALE=sv_SE)
- Laravels egna texter (validering, lösenord m.m.) översätts med laravel-lang/lang
- Startkitets Vue-texter skrivs om till svenska direkt i komponenterna. Inget i18n-bibliotek i version 1.
- Databasen lagrar tider i UTC
- "Idag", schemalagda jobb och visning använder Europe/Stockholm

## Del av dagen och klockslag

Uppgifter, återkommande uppgifter, rutiner och aktiviteter kan ha:

- del av dagen (morgon, förmiddag, eftermiddag, kväll)
- klockslag (t.ex. simskolan 17:15)
- eller båda

Om klockslag anges räknas delen av dagen ut automatiskt.

Familjen ställer in när dagsdelarna börjar (families.day_parts). Standard: morgon 06, förmiddag 09, eftermiddag 12, kväll 17.

Idag-vyn grupperar efter del av dagen och sorterar på klockslag.

---

# Drift

Version 1 körs på en egen server hos en leverantör, med root-åtkomst. Ingen Forge: deploy sker från GitHub. Steg för steg: [DRIFT.md](DRIFT.md).

## Server

- Debian 12 (Bookworm). Den har LTS-stöd till juni 2028, så servern bör flyttas till en nyare Debian innan dess.
- PHP 8.4 (samma version som lokalt) från Ondřej Surýs paketförråd (packages.sury.org), eftersom Debian 12 bara har PHP 8.2. Nginx.
- MariaDB 12.3 LTS (från MariaDB:s eget paketförråd)
- Servern delas med andra projekt. NPF-familjen har egen användare (`npf-familjen`), egen PHP-FPM-pool, egen Nginx-sajt och egen databas med egen databasanvändare. Installationen ändrar aldrig något som delas (systemuppgraderingar, brandvägg, MariaDB-inställningar, andra sajter).
- Användaren npf-familjen sköter deployer, inte root. Inloggning bara med SSH-nyckel.
- Brandvägg, fail2ban och SSH-inställningar gäller hela servern och sköts utanför NPF-familjen (rekommendationer i DRIFT.md)
- Köworker som systemd-tjänst
- Scheduler: cron kör php artisan schedule:run varje minut
- .env och storage (bilder) ligger i en delad mapp som överlever deployer

## Deploy från GitHub

GitHub Actions:

1. Vid varje push: Pest (mot MariaDB 12.3 i en tjänstecontainer), Pint, Larastan och frontend-build
2. Vid push till main, om testerna gick igenom: deploy med Deployer 8 (deployphp/action)

Deployer:

- Zero-downtime: varje version i en egen mapp, current pekar på den aktiva
- Frontend byggs i GitHub Actions och laddas upp. Servern behöver inte Node.
- Kör composer install --no-dev, migrate --force, optimize och queue:restart
- dep rollback går tillbaka till förra versionen
- SSH-nyckel och serverns host key ligger som GitHub Secrets

## Domän och HTTPS

- Till en början DuckDNS-adressen npf-familjen.duckdns.org. Servern har fast IP hos leverantören, så adressen behöver inte uppdateras och NAT loopback är inget problem.
- HTTPS med Let's Encrypt: Certbot med HTTP-01 via Nginx-pluginet, förnyas automatiskt
- Vid byte till egen domän: ändra APP_URL, skaffa nytt certifikat och uppdatera hemskärmslänken på Raspberry Pi:n

## E-post

Version 1: Gmail via SMTP.

- smtp.gmail.com, port 587, med ett applösenord (kräver tvåstegsverifiering på Google-kontot)
- Använd ett separat Gmail-konto för appen. Ett applösenord ger åtkomst till hela brevlådan och ska inte ligga på servern för ett personligt konto.
- Max 500 mottagare per dag
- Applösenordet finns bara i serverns .env

Senare, med egen domän: e-posttjänst inom EU, t.ex. Brevo (gratis 300 mejl/dag via SMTP) eller Mailgun med EU-region (inbyggd drivrutin i Laravel).

## Backup

Version 1: backup på servern.

- spatie/laravel-backup: databas och bilder varje natt kl. 02:30 (rensning 02:00)
- Sparas i /var/backups/npf-familjen (BACKUP_PATH), behålls i 14 dagar. Mejl bara när något går fel.
- Återställning testas
- Skyddar mot misstag och trasiga uppdateringar, men inte mot att servern eller disken går sönder
- Senare: en andra kopia utanför servern (spatie/laravel-backup klarar flera mål)

Hemskärmen (Raspberry Pi) och telefonerna ansluter mot servern.

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
recipes/
pantry/
meal-plan/
activities/
routines/
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
- Lugnt: inga röda larm eller stressande nedräkningar, tid visas visuellt

Färger, typsnitt, storlekar och komponenter finns i stilguiden: docs/STYLEGUIDE.md och docs/design/tokens.json.

## Telefon och läsplatta

Barnen använder både telefoner och läsplattor, så barnens vyer byggs för båda, stående och liggande.

- Tailwinds brytpunkter: telefon under md (768px), läsplatta från md, dator från lg (1024px)
- På läsplatta i liggande läge visar barnets Idag "Nu" och dagens lista bredvid varandra
- Vuxnas vyer byggs för telefon först och får sidomeny från lg

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

- Export: en vårdnadshavare kan exportera all familjens data (JSON + bilder i en ZIP)
- Radering: en vårdnadshavare kan radera ett barnkonto eller hela familjen
- Vårdnadshavare ser barnens inloggningshistorik och kan stänga av ett barns inloggning
- Vid radering tas all data och alla filer bort (köjobb), även familjens community-inlägg
- Dagboken krypteras i databasen (Laravel encrypted cast)
- Familjens foton ligger på privat disk
- Hosting inom EU
- Integritetspolicy innan andra familjer bjuds in

I version 2 (Milestone 17):

- Export (Familjen → Familjens data): en JSON-fil per del och familjens egna bilder i en ZIP. Dagboken exporteras läsbar. Lösenord, barnens koder, tvåstegsnycklar, länktokens och kalenderlänkar är aldrig med. Filen sparas 7 dagar (exports:prune).
- Ta bort barn: barnets konto och allt om barnet. Ett barn som också finns i en annan familj behåller kontot där; bara den här familjens data om barnet tas bort.
- Ta bort familjen: vårdnadshavare bekräftar med lösenord och familjens namn. Allt med family_id försvinner (cascade), liksom bilder och exporter på disken, barnens konton och vuxna som inte är med i någon annan familj (med deras community-inlägg). Adminkonton tas aldrig bort.
- Integritetspolicyn (/integritet) hämtar personuppgiftsansvarig från PRIVACY_CONTROLLER och PRIVACY_CONTACT i .env.

## Konton och missbruk (Milestone 19)

- Admin ser familjer och vuxenkonton i adminportalen (Konton → Familjer, Vuxna, Logg), aldrig familjens innehåll.
- users.suspended_at och families.suspended_at stänger av ett konto eller en familj. Anledningen syns bara för admin. Ingenting tas bort.
- EnsureAccountActive körs vid varje anrop: avstängda loggas ut och skickas till /avstangt. En vuxen som också finns i en annan familj fortsätter där. Familjens enheter och hemskärmar stoppas. Fortify säger direkt vid inloggning att kontot är avstängt.
- Avstängda får inga påminnelser, och deras inlägg i Community döljs.
- Adminkonton kan aldrig stängas av. Varje avstängning och upphävning loggas i admin_actions.
- Abonnemang (senare, Laravel Cashier): familjen är kunden. Ett abonnemang som gått ut kan använda samma spärr som en avstängd familj, med eget meddelande.

---

# Security

Viktiga regler:

- All queries filtreras på family_id (BelongsToFamily)
- Policies används alltid och kontrollerar roll (parent, editor, viewer, child)
- Validation används alltid
- Isolationstester: för varje modell testas att familj A inte kan läsa eller ändra familj B:s data
- Barnens kod fungerar bara på betrodda enheter, lagras krypterad och jämförs med hash_equals
- Efter 5 fel i rad: vänta en minut. Efter 10 fel i rad: barnets inloggning stängs av tills en vårdnadshavare slår på den med ny kod.
- Att stänga av ett barns inloggning loggar ut alla barnets sessioner och kopplar bort barnets egna enheter

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

# AI-riktlinjer

Projektets regler för AI-verktyg ligger i .ai/guidelines/ (npf-familjen.md och design.md). Laravel Boost tar med dem i CLAUDE.md (Claude Code) och AGENTS.md (Copilot) vid boost:install och boost:update. Vilka agenter som används står i boost.json. Ändra reglerna där, inte i de genererade filerna.

Regler som bara gäller vissa sökvägar sparas i .ai/rules via Boosts record-rule.

Laravel Boost genererar även Laravel-specifika AI-riktlinjer. Reglerna nedan gäller utöver dessa.

All ny kod skall:

- använda policies som kontrollerar roll (parent, editor, viewer, child)
- använda FormRequests
- använda BelongsToFamily på alla modeller med family_id
- ha isolationstester mellan familjer

Exempel:

Task::where('family_id', $user->active_family_id)

ALDRIG:

Task::all()
withoutGlobalScopes() i kod som körs för en familj