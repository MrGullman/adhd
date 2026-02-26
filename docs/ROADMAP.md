# Familjeplanerare – Roadmap

## Projektmål

En familjedelad ADHD-vänlig webapp för:

- Att-göra listor
- Matlistor och shoppinglistor
- Dagbok
- Belöningssystem
- Familjedelning
- Community mellan familjer

Systemet byggs med:

- Laravel 11
- SQLite
- Laravel Breeze
- Inertia.js
- Vue 3

Mobilvänlig design är prioritet.

---

# Milestone 0 – Setup

## Mål
Få en fungerande Laravel-app lokalt.

## Tasks

- [ ] Install Laravel 11
- [ ] Konfigurera SQLite
- [ ] Installera Laravel Breeze (Inertia + Vue)
- [ ] Kör migrations
- [ ] Verifiera login fungerar

## Definition of Done

- App kör lokalt
- Login fungerar
- Dashboard visas

---

# Milestone 1 – Familjer

## Mål

All privat data ska vara kopplad till en familj.

## Tasks

- [ ] Skapa families tabell
- [ ] Skapa family_user pivot tabell
- [ ] Lägg till active_family_id i users tabellen
- [ ] Create Family page
- [ ] Middleware för active family

## Definition of Done

- User kan skapa familj
- User kan tillhöra familj
- Aktiv familj används överallt

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

# Milestone 3 – Idag vy

## Mål

Visa dagens uppgifter.

## Tasks

- [ ] TodayController
- [ ] Today.vue
- [ ] Filtrera tasks för idag

## Definition of Done

- Idag visar tasks korrekt

---

# Milestone 4 – Dagbok

## Mål

Daglig reflektion.

## Tasks

- [ ] journal_entries tabell
- [ ] Journal page
- [ ] Historik

## Definition of Done

- Dagbok fungerar

---

# Milestone 5 – Belöningssystem

## Mål

Motivera användare genom poäng.

## Tasks

- [ ] task_completions tabell
- [ ] daily_goals tabell
- [ ] family_points_ledger tabell
- [ ] rewards tabell
- [ ] reward_redemptions tabell
- [ ] Rewards UI

## Definition of Done

- Tasks ger poäng
- Rewards kan lösas in

---

# Milestone 6 – Återkommande tasks

## Mål

Automatiska tasks.

## Tasks

- [ ] recurring_tasks tabell
- [ ] Scheduler command

## Definition of Done

- Tasks skapas automatiskt

---

# Milestone 7 – Community

## Mål

Familjer kan diskutera problem och tips.

## Tasks

- [ ] groups tabell
- [ ] group_members tabell
- [ ] threads tabell
- [ ] posts tabell
- [ ] Community UI

## Definition of Done

- Diskussion mellan familjer fungerar

---

# Milestone ordning

Rekommenderad byggordning:

1 Setup
2 Families
3 Tasks
4 Today view
5 Journal
6 Rewards
7 Recurring
8 Community

---

# Framtida funktioner

## Möjliga features

- Push-notiser
- PWA
- Realtime chat
- Statistik
- Export
- Dark mode