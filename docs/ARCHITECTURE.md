# Familjeplanerare – Architecture

## Stack

Backend:

- Laravel 11

Frontend:

- Inertia.js
- Vue 3
- Vite

Database:

- SQLite (development)
- MySQL/Postgres (production)

Auth:

- Laravel Breeze

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

---

# Community Data

Community är separat från familjedata.

Community använder:

groups
threads
posts

Community använder inte family_id.

Behörighet styrs via group_members.

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

resources/js/Pages

Exempel:

Pages/

Today/
Lists/
Journal/
Rewards/
Community/

---

# Designprinciper

## ADHD-vänlig design

Systemet ska vara:

- Snabbt
- Enkelt
- Få klick
- Mobilvänligt

Prioriterade vyer:

1 Today
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

# Security

Viktiga regler:

- All queries filtreras på family_id
- Policies används alltid
- Validation används alltid

---

# Framtida arkitektur

När systemet växer:

## Möjliga tillägg

- Redis
- Queue workers
- Websockets
- Notifications

---

# Copilot riktlinjer

All ny kod skall:

- använda policies
- använda FormRequests
- använda family_id filtering

Exempel:

Task::where('family_id', $user->active_family_id)

ALDRIG:

Task::all()