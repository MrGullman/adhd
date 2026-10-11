# NPF-familjen

NPF-familjen är ett hjälpmedel för barn och familjer med NPF-problematik (t.ex. ADHD och autism).

Stack: Laravel 13, Inertia 3, Vue 3 (TypeScript), Tailwind CSS 4, shadcn-vue, Filament 5 (adminportal), MariaDB 12.3.

## Läs först

- `docs/ARCHITECTURE.md`: datamodell, regler och beslut. Följ den.
- `docs/ROADMAP.md`: milestones i byggordning. Bygg det som hör till aktuell milestone och bocka av tasks när de är klara.
- Om koden eller en uppgift strider mot dokumenten: fråga innan du ändrar dokumenten.

## Gäller alltid

- Data delas aldrig mellan familjer. Modeller med `family_id` använder `BelongsToFamily`. Aldrig `Model::all()` eller `withoutGlobalScopes()` i kod som körs för en familj.
- Alla modeller har policies, och de kontrollerar roll: `parent` (vårdnadshavare) eller `child` (barn).
- All validering sker i FormRequests.
- Varje ny familjemodell får ett isolationstest: familj A kan aldrig läsa eller ändra familj B:s data.
- Barn har aldrig åtkomst till dagbok eller community.
- Hemskärmen är skrivskyddad och får bara data för de widgets som är valda.

## Språk, tid och databas

- Allt användaren ser är på svenska. Kod, tabeller och variabler skrivs på engelska.
- Tider lagras i UTC. "Idag", schemalagda jobb och visning använder `Europe/Stockholm`.
- Databasen är MariaDB, även i tester. Aldrig SQLite.

## Design (NPF-vänligt)

- Bildstöd: bild + kort text där det går.
- Få klick, stora knappar för barn, mobil först.
- Förutsägbart och lugnt: inga röda larm eller stressande nedräkningar.

## Integritet

- Inga riktiga personuppgifter (namn, skolor, adresser) i kod, tester, fixtures eller seeders. Använd påhittade.
- Hemligheter (`.env`, applösenord, tokens) committas aldrig.

## Externa källor

- Matilda (skolmat) och iCal-kalendrar anropas bara från schemalagda jobb, aldrig när en sida visas.
- Tester gör aldrig riktiga anrop: använd `Http::fake()`.
