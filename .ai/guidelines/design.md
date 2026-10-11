# NPF-familjen – design

Följ `docs/STYLEGUIDE.md`. Alla färger, typsnitt, avstånd och storlekar kommer från `docs/design/tokens.json`.

- Använd tokens via CSS-variabler och Tailwind-klasser. Skriv aldrig in färgvärden eller pixelmått direkt.
- Typsnitt: `display` (Fredoka) för rubriker och barnens texter, `body` (Atkinson Hyperlegible Next) för löptext.
- Tryckytor minst 48px för vuxna och 64px för barn. Barnens text minst 22px.
- Barnens vyer byggs för både telefon och läsplatta (stående och liggande). Vuxnas vyer byggs för telefon först.
- Bild + kort text där det går. Barn ser aldrig en ikon utan ord.
- Högst en primary-knapp per vy.
- Inga röda larm eller stressande nedräkningar. `danger` bara för Ta bort i vuxnas vyer.
- Klart visas med bock, ordet Klar och nedtonad text. Stryk aldrig över.
- Färg bär aldrig betydelse ensam.
- Texter på svenska: barnens börjar med verbet och har högst tre ord. Klockslag 07:45.
- Ikoner från Lucide. Inga emoji i gränssnittet.
- Övergångar högst 200 ms och respektera `prefers-reduced-motion`.
