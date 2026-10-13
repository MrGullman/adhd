# NPF-familjen – Stilguide

Identiteten är lekfull som logotypen, men gränssnittet är lugnt: logotypens färger används som accenter och för att visa tid på dagen, medan ytorna är stilla och ljusa. Allt ska gå att förstå med bild och ett par ord.

- Alla färger, typsnitt, avstånd och storlekar: [design/tokens.json](design/tokens.json). Det är källan. Skriv aldrig in färgvärden direkt i koden.
- Visuell stilguide med komponenter och sidutkast: https://claude.ai/artifact/2Y3bTXfJXmZSYqLFmFpk18 (privat länk)
- Utkast av hemskärmen i full storlek: [mockups/](mockups/)

---

## Ton och text

- Skriv till en person med "du". Korta meningar, en sak i taget.
- Barnens texter börjar med verbet och har högst tre ord: "Borsta tänderna", "Packa väskan", "Klar". I läget Ungdom börjar de med verbet men får vara längre: "Packa gympapåsen till fredag".
- Vuxnas texter är raka och vänliga: "Simskola imorgon. Inte packat än."
- Skriv aldrig skuld eller stress. Inte "Du är sen!", utan "10 minuter kvar". Inte "Misslyckades", utan "Vi hoppar över det idag".
- Utropstecken bara i firandet när något är klart: "Bra jobbat!"
- Versal bara först i meningen och i namn: "Lägg till vara".
- Klockslag med 24 timmar och kolon: 07:45. Datum med veckodag: tisdag 13 oktober.
- Inga emoji i gränssnittet. Bildstöd är bilder från galleriet. (Utkasten använder emoji som platshållare.)

## Färg

| Token | Används till |
| --- | --- |
| `surface` | Sidans bakgrund |
| `surface-raised` | Kort, listrader, widgets och fält, med `line`-kant |
| `surface-sunken` | Bildrutor, spår och knappsatser |
| `ink` / `ink-muted` | Text / tider, metadata och "vem som lade till" |
| `primary` / `on-primary` | Det man trycker på för att gå vidare. Högst en per vy. |
| `primary-soft` | Det som pågår just nu (med `primary`-kant) |
| `brand-teal`, `brand-orange`, `brand-purple`, `brand-yellow`, `brand-green`, `brand-blue` | Logotypens färger: avatarer, dagsdelar, firande, Klar-knappen. Text på dem: `on-brand` (lila: `on-brand-purple`). Aldrig som text på ljus yta. |
| `orange-soft` / `orange-strong` | Det som behöver göras (inte packat). En påminnelse, aldrig ett larm. |
| `success` / `success-soft` / `on-success` | Klart, alltid med bock och ordet Klar |
| `danger` | Bara Ta bort i vuxnas vyer. Aldrig för barn, tider eller påminnelser. |
| `focus-ring` | Tangentbordsfokus: 3px solid ring med 2px avstånd |

- Alla textpar har minst 4,5:1 kontrast i både ljust och mörkt tema.
- Färg bär aldrig betydelse ensam: alltid också ett ord eller en ikon.
- Klar text stryks aldrig över. Den tonas ner till `ink-muted`.

## Dagsdelar

| Dagsdel | Förval | Symbol (Lucide) | Fyllnad | Etikett | Text |
| --- | --- | --- | --- | --- | --- |
| Morgon | 06:00 | Sunrise | `morning` | `morning-soft` | `morning-strong` |
| Förmiddag | 09:00 | Sun | `forenoon` | `forenoon-soft` | `forenoon-strong` |
| Eftermiddag | 12:00 | CloudSun | `afternoon` | `afternoon-soft` | `afternoon-strong` |
| Kväll | 17:00 | Moon | `evening` | `evening-soft` | `evening-strong` |

## Typografi

- `display` (Fredoka): rubriker, barnens texter, bildetiketter och stora siffror
- `body` (Atkinson Hyperlegible Next): all löptext, formulär och vuxnas listor
- Stilar: `display-xl` klockan, `display-l` steget i fokusläget, `heading` sidrubriker, `child-label` barnens uppgifter, `title` rubriker i vuxnas kort, `body` text, `caption` tider och metadata, `overline` gruppetiketter
- Barnens text är aldrig mindre än 22px (`child-label`). I läget Ungdom aldrig mindre än 18px (`youth-label`), tider och metadata 14px (`caption`). Vuxnas text aldrig mindre än 14px (`caption`).
- Siffror i rad har `tabular-nums`.

## Storlek, avstånd och former

- 4px-rutnät: `space-1` till `space-12`. Kort har `space-4` inre marginal, sektioner `space-8` emellan.
- Tryckytor: minst `tap-adult` (48px) för vuxna och läget Ungdom, och `tap-child` (64px) för barn i läget Barn.
- Former: knappar, etiketter och dagsdelar `radius-pill`. Kort `radius-lg`, bildstöd och fält `radius-md`, widgets `radius-xl`.
- Skugga (`shadow-raised`) bara för det som ligger ovanpå.

## Telefon och läsplatta

- Barnens vyer byggs för både telefon och läsplatta, stående och liggande.
- Tailwinds brytpunkter: telefon under `md` (768px), läsplatta från `md`, dator från `lg` (1024px).
- På läsplatta i liggande läge visar barnets Idag "Nu" och dagens lista bredvid varandra.

## Bildstöd

- Varje uppgift, steg, vara och familjemedlem kan ha en bild. Till vänster i listor, ovanför etiketten i rutnät.
- Storlekar: `picture-sm` i listor, `picture-md` i barnens kort och bildväljaren, `picture-lg` i fokusläget och på hemskärmen. I läget Ungdom `picture-xs` i listor.
- Kvadratiska bilder med rundade hörn. Samma sak har alltid samma bild.

## Tid som syns

- Tid som är kvar visas med kvartsprickar: en prick är 15 minuter, och prickarna släcks när tiden går. Alltid också i text: "30 min kvar · klar 07:45".
- Bortrest förälder visas som nätter: "2 sömnar kvar tills mamma kommer hem".
- Inga nedräkningar i sekunder, inget som blinkar, inga röda tider.

## Tillstånd

| Tillstånd | Utseende |
| --- | --- |
| Pågår nu | `primary-soft` bakgrund, `primary`-kant, etiketten "Nu" |
| Kvar att göra | Vanligt kort, tom bockruta |
| Behöver göras snart | Etiketten "Inte packat" i `orange-soft` och `orange-strong` |
| Klar | Fylld bockruta i `success`, texten i `ink-muted`, etiketten "Klar" |
| Valfri | Etiketten "Valfri" med streckad kant |
| Hoppas över | Etiketten "Hoppar över idag" i `surface-sunken` |
| Annorlunda dag | `orange-soft` bakgrund och etiketten "Annorlunda" |

## Barnens visningslägen

Vårdnadshavare väljer läge per barn. Mognad och behov följer inte alltid åldern. Båda lägena har samma struktur, samma färger och samma lugn. Läget Ungdom byter bara skala och ton, så att ett äldre barn känner igen appen men inte känner sig litet.

| | Barn (ungefär 5–10 år) | Ungdom (ungefär 10–14 år) |
| --- | --- | --- |
| Typsnitt | `display` (Fredoka) för allt barnet läser | `display` bara i rubriker. Listor och text i `body`, som vuxnas vyer. |
| Text | `child-label` 22px, högst tre ord | `youth-label` 18px, kort men utan ordgräns. Tider `caption`. |
| Tryckytor | `tap-child` 64px | `tap-adult` 48px |
| Bildstöd | `picture-sm` i listor, `picture-md`–`picture-lg` för Nu | `picture-xs` i listor, 44px för Nu. På från början, vårdnadshavare kan stänga av dem. |
| Startsidan | "Hej Alva!" och ett stort Nu-kort | "Idag" med datum, Nu som en rad överst, dagens lista med tider och dagsdelar som små etiketter |
| Rutiner | Fokusläge: ett steg i taget med kvartsprickar | Checklista med alla steg och en tunn förloppslinje. Fokusläget kan väljas. |
| Klar | Bock, ordet Klar, nedtonad text | Samma, i mindre storlek |
| Poäng | Stjärna med poäng i Min dag | En länk längst ner. Dold om belöningar är avstängda för barnet. |

- Högst en primary-knapp per vy i båda lägena.
- Hemskärmen ser likadan ut för alla barn, oavsett läge.
- Visuellt utkast: https://claude.ai/artifact/6yMR9uauSVohCTEsD6bF5F (privat länk)

## Hemskärmen

- Ingen meny och inga knappar. Hemskärmen är bara en visning.
- Täthet per skärm efter avstånd: Stor (3–4 m), Mellan (2–3 m), Kompakt (1–2 m). Samma tokens, bara storleksstegen ändras.
- Mörkt tema kvällstid.
- Widgets per barn delas upp med barnets avatar och namn.

## Rörelse

- Övergångar högst 200 ms, ease-out. Firandet när en rutin är klar: lugnt, högst en sekund, en gång.
- `prefers-reduced-motion` respekteras. Ingenting blinkar eller studsar.

## Ikoner och logotyp

- Gränssnittsikoner från Lucide (ingår i shadcn-vue), 2px linje, 24px för vuxna och 32px för barn.
- Barn ser aldrig en ikon utan ord bredvid.
- Logotypen (`resources/images/logo.png`) på inloggning och startsida, minst 160px bred. I sidhuvudet skrivs namnet i `display`: "NPF" i `primary` och "familjen" i `orange-strong`.
- En förenklad logotyp utan text behövs för favicon och appikon. Den finns inte än.

## Komponenter

Button, Field, DayPart, QuarterDots, PictureTile, TaskCard, ChecklistItem, StatusChip, Avatar och Widget. Utkast och riktlinjer för varje komponent finns i den visuella stilguiden.

## I koden

Tokens blir CSS-variabler i `resources/css/app.css` (Tailwind 4 `@theme`) och kopplas till shadcn-vues variabler:

| shadcn-vue | Token |
| --- | --- |
| `--background` | `surface` |
| `--foreground` | `ink` |
| `--card` | `surface-raised` |
| `--muted` | `surface-sunken` |
| `--muted-foreground` | `ink-muted` |
| `--primary` | `primary` |
| `--primary-foreground` | `on-primary` |
| `--accent` | `primary-soft` |
| `--border` | `line` |
| `--input` | `line-strong` |
| `--ring` | `focus-ring` |
| `--destructive` | `danger` |
| `--radius` | `radius-md` |
