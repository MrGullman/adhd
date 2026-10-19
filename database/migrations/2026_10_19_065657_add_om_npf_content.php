<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The first content of Om NPF. Added once, when the library is empty; after
 * that it is written and changed by admins in the admin portal.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('articles')->exists()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ([...$this->intro(), ...$this->topics(), ...$this->situations()] as $index => $article) {
            $rows[] = [
                'kind' => $article['kind'],
                'slug' => $article['slug'],
                'title' => $article['title'],
                'summary' => $article['summary'] ?? null,
                'icon' => $article['icon'] ?? null,
                'body' => $article['body'] ?? null,
                'why' => $article['why'] ?? null,
                'helps' => $article['helps'] ?? null,
                'in_app' => $article['in_app'] ?? null,
                'sort_order' => $index,
                'published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('articles')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The content is the admins' from now on; it is not removed.
    }

    /**
     * @return list<array<string, string>>
     */
    private function intro(): array
    {
        return [[
            'kind' => 'intro',
            'slug' => 'valkommen',
            'title' => 'Välkommen till NPF Familjen',
            'summary' => 'För en enklare vardag, tillsammans.',
            'body' => <<<'MD'
Alla familjer är olika. Vissa dagar flyter allt på, medan andra dagar kan kännas som en enda lång kamp med tider, rutiner, känslor och förväntningar.

För familjer där någon har ADHD, autism eller andra neuropsykiatriska funktionsnedsättningar kan vardagen innebära särskilda utmaningar.

Att komma iväg till skolan, komma ihåg gympapåsen, avsluta en aktivitet eller bara sätta sig vid middagsbordet kan ibland kräva mycket mer energi än omgivningen förstår.

**NPF Familjen är skapad för att hjälpa familjer att få mer struktur, tydlighet och trygghet i vardagen.**

Med hjälp av planering, bildstöd, rutiner och positiv uppmuntran vill vi göra det lättare för både barn och vuxna att förstå vad som ska hända, vad som förväntas och vad som kommer sedan.

Målet är inte att alla ska fungera likadant. Målet är att hitta det som fungerar för just er familj.
MD,
        ]];
    }

    /**
     * @return list<array<string, string>>
     */
    private function topics(): array
    {
        return [
            [
                'kind' => 'topic',
                'slug' => 'vad-ar-npf',
                'title' => 'Vad är NPF?',
                'summary' => 'En grundläggande förklaring till neuropsykiatriska funktionsnedsättningar.',
                'icon' => 'brain',
                'body' => <<<'MD'
NPF står för neuropsykiatriska funktionsnedsättningar. Det är ett samlingsnamn för tillstånd som påverkar hur hjärnan bland annat hanterar information, uppmärksamhet, impulser och socialt samspel.

ADHD och autism är två exempel på NPF. En person kan också ha både ADHD och autism.

Att ha en NPF-diagnos betyder inte att man är mindre intelligent, mindre kapabel eller mindre värd än andra. Det innebär att vissa saker kan fungera annorlunda och att man ibland behöver andra förutsättningar för att må bra och klara vardagen.

**Två personer med samma diagnos kan ha helt olika behov.**

Det som fungerar för ett barn behöver inte fungera för ett annat. Därför är förståelse, individuella anpassningar och delaktighet så viktiga.

## Du behöver inte ha en diagnos för att ha nytta av struktur

Bildstöd, tydliga scheman och rutiner kan vara värdefulla även för familjer där ingen har en fastställd NPF-diagnos.

Vissa barn behöver helt enkelt mer förutsägbarhet, andra behöver hjälp att komma igång och några mår bäst av att veta exakt vad som ska hända härnäst.

Det viktigaste är inte vilken diagnos någon har, utan vilka behov som finns.

## Läs mer

- [1177: Neuropsykiatriska funktionsnedsättningar](https://www.1177.se/sjukdomar--besvar/hjarna-och-nerver/neuropsykiatriska-funktionsnedsattningar/)
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'adhd',
                'title' => 'Att förstå ADHD',
                'summary' => 'Hur ADHD kan påverka koncentration, känslor, beteenden och vardagen.',
                'icon' => 'zap',
                'body' => <<<'MD'
ADHD kan påverka förmågan att styra uppmärksamheten, aktivitetsnivån och impulserna.

För ett barn kan det exempelvis vara svårt att komma igång med läxor, sitta still under lektioner, komma ihåg instruktioner eller avsluta något som känns väldigt roligt.

För en vuxen kan ADHD göra det svårare att planera, passa tider, prioritera uppgifter och hantera många krav samtidigt.

Det handlar inte nödvändigtvis om bristande vilja. Man kan mycket väl veta vad som behöver göras men ändå ha svårt att komma igång eller genomföra uppgiften.

ADHD behöver inte heller innebära att man är överaktiv. Hos vissa märks framför allt svårigheter med uppmärksamhet och organisation.

Samtidigt kan personer med ADHD ha värdefulla styrkor, exempelvis kreativitet, engagemang och förmågan att fördjupa sig i sådant som väcker intresse. Det är individuellt och gäller inte alla.

## Vad kan hjälpa?

Tydliga instruktioner, kortare uppgifter, visuella påminnelser, pauser och uppmuntran kan göra stor skillnad.

I stället för att säga ”Städa ditt rum” kan det vara lättare att visa tre mindre uppgifter: lägg kläderna i tvätten, plocka upp leksakerna och bädda sängen.

## Läs mer

- [1177: ADHD](https://www.1177.se/sjukdomar--besvar/hjarna-och-nerver/neuropsykiatriska-funktionsnedsattningar/adhd/)
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'autism',
                'title' => 'Att förstå autism',
                'summary' => 'Behovet av tydlighet, förutsägbarhet, kommunikation och återhämtning.',
                'icon' => 'puzzle',
                'body' => <<<'MD'
Autism påverkar bland annat hur en person uppfattar, bearbetar och tolkar information och sociala situationer.

En del personer med autism har ett stort behov av förutsägbarhet och tydliga rutiner. Oväntade förändringar kan skapa oro eller stress.

Andra upplever att ljud, ljus, lukter, beröring eller många människor samtidigt tar mycket energi.

Sociala situationer kan också vara krävande, särskilt när andra förväntar sig att man ska förstå outtalade regler.

Autism är ett spektrum. Det betyder att personer med autism kan fungera väldigt olika och behöva olika mycket stöd.

Många har också styrkor inom särskilda intresseområden, detaljuppmärksamhet eller problemlösning.

## Vad kan hjälpa?

Förberedelser, tydliga scheman, möjlighet till återhämtning och information i god tid kan minska stressen.

Att i förväg kunna se att ”först ska vi handla, sedan åker vi hem” kan skapa en helt annan trygghet än att bara få höra att familjen ska iväg.

## Läs mer

- [1177: Autism](https://www.1177.se/sjukdomar--besvar/hjarna-och-nerver/neuropsykiatriska-funktionsnedsattningar/autism/)
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'vardagen',
                'title' => 'Familjelivet och vardagen',
                'summary' => 'Morgonrutiner, läggdags, konflikter, syskon och familjens gemensamma behov.',
                'icon' => 'house',
                'body' => <<<'MD'
För många familjer är det inte de stora händelserna som tar mest energi. Det är alla små saker som behöver fungera varje dag.

Att vakna, klä på sig, borsta tänderna, packa skolväskan och komma hemifrån i tid kan bli en lång kedja av påminnelser och konflikter.

Efter skolan kan ett barn vara helt slut. Barnet kanske har ansträngt sig hela dagen för att koncentrera sig, hantera ljud, följa regler och fungera socialt.

När barnet kommer hem och känner sig tryggt kan trötthet och överbelastning ibland visa sig genom starka känslor, irritation, gråt eller behov av att dra sig undan.

Det kan vara svårt för omgivningen att förstå varför barnet klarar vissa saker i skolan men inte hemma.

I sådana situationer behöver man försöka förstå vad som ligger bakom svårigheterna, inte bara reagera på beteendet.

**Struktur handlar inte om att kontrollera barnet. Det handlar om att skapa trygghet och förutsägbarhet.**

Ett tydligt dagsschema, rimliga förväntningar och möjlighet till återhämtning kan göra vardagen mer hanterbar.

## Syskon och relationer

När ett barn behöver mycket stöd kan andra familjemedlemmars behov ibland hamna i skymundan.

Syskon kan uppleva att reglerna är olika, att ett barn får mer uppmärksamhet eller att familjens aktiviteter ofta måste anpassas.

Det är viktigt att också syskon får känna sig sedda, lyssnade på och delaktiga.

En NPF-diagnos kan hjälpa till att förklara varför någon behöver särskilda anpassningar, men varje familjemedlems känslor och behov är viktiga.

Målet är inte alltid att alla ska behandlas exakt likadant, utan att alla ska få det stöd och den omtanke de behöver.
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'skolan',
                'title' => 'NPF i skolan',
                'summary' => 'Utmaningar i skolan, möjliga anpassningar och samarbetet mellan hem och skola.',
                'icon' => 'school',
                'body' => <<<'MD'
Skolmiljön ställer många krav på barn. De ska koncentrera sig, komma ihåg instruktioner, samarbeta, byta aktiviteter och hantera sociala situationer.

För barn med ADHD eller autism kan vissa av dessa krav vara extra ansträngande.

En elev kan exempelvis behöva mer hjälp att komma igång med arbetet, förstå instruktioner, hantera förändringar i schemat eller hitta lugn i en stökig miljö.

Raster, matsal, omklädningsrum och grupparbeten kan ibland vara minst lika krävande som själva undervisningen.

Det är viktigt att skolan ser elevens individuella behov och skapar förutsättningar för lärande.

**Ett barn behöver inte ha en NPF-diagnos för att få stöd i skolan.** Skolan ska uppmärksamma stödbehov och ge de insatser som eleven har rätt till utifrån sina förutsättningar.

Tydliga instruktioner, visuella scheman, förberedelser inför förändringar och möjlighet till återhämtning är exempel på stöd som kan underlätta.

Ett gott samarbete mellan hemmet och skolan kan också bidra till att barnet får en mer förutsägbar vardag.

## Läs mer

- [Skolverket: Extra anpassningar, särskilt stöd och åtgärdsprogram](https://www.skolverket.se/regler-och-ansvar/ansvar-i-skolfragor/extra-anpassningar-sarskilt-stod-och-atgardsprogram)
- [Specialpedagogiska skolmyndigheten (SPSM)](https://www.spsm.se/)
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'vuxna',
                'title' => 'NPF hos vuxna',
                'summary' => 'Föräldraskap, relationer, ansvar, vardagsplanering och återhämtning.',
                'icon' => 'user',
                'body' => <<<'MD'
NPF är inte något som bara berör barn.

En förälder eller annan vuxen med ADHD eller autism kan också behöva stöd för att få vardagen att fungera.

Att hålla reda på tider, planera måltider, hantera arbete, sköta hemmet och samtidigt möta barnens behov kan innebära en stor belastning.

Det kan bli särskilt utmanande om både barn och vuxna i familjen har olika behov av struktur, aktivitet och återhämtning.

I en familj kanske en person behöver fasta rutiner medan en annan har svårt att följa samma rutiner.

Då handlar det om att hitta lösningar som tar hänsyn till allas förutsättningar.

**Även vuxna behöver påminnelser, tydlighet, förståelse och möjlighet till återhämtning.**

NPF Familjen ska därför inte enbart vara ett hjälpmedel för barn, utan ett stöd för hela familjen.
MD,
            ],
            [
                'kind' => 'topic',
                'slug' => 'sa-hjalper-npf-familjen',
                'title' => 'Så hjälper NPF Familjen',
                'summary' => 'Praktiska exempel på hur bildstöd, scheman, rutiner och uppmuntran kan användas.',
                'icon' => 'heart-handshake',
                'body' => <<<'MD'
NPF Familjen bygger på en enkel idé:

**När vardagen blir tydligare kan det bli lättare att förstå, planera och samarbeta.**

Appens verktyg är tänkta att hjälpa familjer att:

- **Skapa tydliga dags- och veckoscheman.** Se vad som ska hända idag, imorgon och under veckan.
- **Använda bildstöd.** Göra aktiviteter och instruktioner lättare att förstå med hjälp av bilder och symboler.
- **Bygga fungerande rutiner.** Dela upp morgonbestyr, läggdags och andra återkommande aktiviteter i mindre steg.
- **Uppmuntra och motivera.** Synliggöra framsteg och använda positiva belöningar utifrån familjens egna förutsättningar.
- **Minska behovet av upprepade påminnelser.** Göra det lättare för varje familjemedlem att själv se nästa steg.
- **Skapa delaktighet.** Låta barn och vuxna vara med och forma sina rutiner och mål.

En plan behöver inte vara perfekt. Den behöver fungera tillräckligt bra för att vara till hjälp.

Och vissa dagar behöver planen ändras. Det är också okej.

## Små förändringar kan göra stor skillnad

Ibland kan en enkel bild på vad som ska göras vara mer hjälpsam än tio muntliga påminnelser.

Ibland kan fem minuters förberedelse minska oron inför en aktivitet.

Och ibland är den viktigaste förändringen att sänka kraven, ta en paus eller inse att något behöver göras på ett annat sätt.

Det finns ingen enda metod som passar alla familjer.

Därför vill vi att NPF Familjen ska vara flexibelt och kunna anpassas efter barnets, den vuxnes och familjens behov.

## Vår tanke med NPF Familjen

Vi tror inte att en app kan lösa alla utmaningar som en familj möter.

Men vi tror att rätt hjälpmedel, förståelse och små anpassningar kan underlätta mycket.

Vi vill bidra till mindre stress, färre missförstånd, mer självständighet och fler positiva stunder tillsammans.

**NPF Familjen – för en vardag där alla får möjlighet att fungera på sitt sätt.**
MD,
            ],
        ];
    }

    /**
     * "Känner du igen dig?": everyday situations, never blaming the child or the adult.
     *
     * @return list<array<string, string>>
     */
    private function situations(): array
    {
        return [
            [
                'kind' => 'situation',
                'slug' => 'spelet-som-inte-gar-att-stanga-av',
                'title' => 'Spelet går inte att stänga av',
                'summary' => 'Det blir bråk varje gång skärmtiden är slut.',
                'icon' => 'clock',
                'why' => <<<'MD'
Att gå från något roligt till något mindre roligt är en övergång, och övergångar kan vara svåra. Ett spel ger mycket belöning hela tiden, och det kan vara svårt att själv bryta när hjärnan är helt inne i det.

Ett plötsligt ”nu stänger vi av” kan också kännas som att tappa kontrollen, särskilt om man inte hann förbereda sig.
MD,
                'helps' => <<<'MD'
- Bestäm innan spelet börjar hur länge det ska hålla på, och vad som kommer sedan.
- Gör tiden synlig, så att den inte bara finns i den vuxnes huvud.
- Ge en förvarning en stund innan, och låt barnet få avsluta en omgång eller spara.
- Ha något tydligt att gå vidare till, gärna något barnet tycker om.
MD,
                'in_app' => <<<'MD'
Lägg in uppgiften, t.ex. ”Spela”, med en **timer** på den tid ni kommit överens om. Barnet trycker Starta och ser skivan krympa, och timern säger Klart med ett mjukt pling. Lägg det som kommer sedan direkt efter, så att barnet ser **Nästa** i Min dag.
MD,
            ],
            [
                'kind' => 'situation',
                'slug' => 'brak-pa-morgonen',
                'title' => 'Det blir bråk när ni ska iväg på morgonen',
                'summary' => 'Varje morgon blir en lång kedja av påminnelser och stress.',
                'icon' => 'sun',
                'why' => <<<'MD'
En morgon består av många små steg som ska göras i rätt ordning och på kort tid. För den som har svårt att komma igång, hålla flera saker i huvudet eller hantera tidspress kan det bli för mycket på en gång.

Ju mer stress det blir, desto svårare brukar det bli att komma igång, både för barnet och för den vuxne.
MD,
                'helps' => <<<'MD'
- Gör samma saker i samma ordning varje morgon.
- Dela upp morgonen i små steg, ett i taget.
- Förbered det som går kvällen innan, som kläder och väska.
- Låt tiden synas, så att den vuxne inte behöver vara klockan.
MD,
                'in_app' => <<<'MD'
Gör en **morgonrutin** med bildstöd för varje steg. Barnet ser ett steg i taget och trycker Klar, och kvartsprickarna visar hur mycket tid som är kvar. Med en **påminnelse** när rutinen börjar och **packning kvällen innan** blir morgonen mer förutsägbar. På **hemskärmen** syns vilket steg barnet är på och hur mycket tid som är kvar.
MD,
            ],
            [
                'kind' => 'situation',
                'slug' => 'utbrott-efter-skolan',
                'title' => 'Utbrott efter skolan',
                'summary' => 'Barnet har klarat hela skoldagen, men hemma blir det för mycket.',
                'icon' => 'heart-handshake',
                'why' => <<<'MD'
Många barn lägger ner mycket energi på att hålla ihop under skoldagen: koncentrera sig, hantera ljud och följa regler. Hemma, där det är tryggt, kan tröttheten komma ut som starka känslor.

Det betyder inte att barnet har det dåligt hemma. Ofta är det tvärtom ett tecken på att hemmet är den trygga platsen.
MD,
                'helps' => <<<'MD'
- Planera in återhämtning direkt efter skolan, innan läxor och andra krav.
- Ställ få frågor i början, och ge tid att landa.
- Ha samma lugna start på eftermiddagen varje dag.
- Spara samtal om dagen till senare, när barnet har fått vila.
MD,
                'in_app' => <<<'MD'
Lägg in **vila** som en egen uppgift direkt efter skolan, så att den är en del av planen och inte något man måste be om. I Min dag syns det som **Nästa**, och med en **timer** blir det tydligt hur länge vilan varar.
MD,
            ],
            [
                'kind' => 'situation',
                'slug' => 'laxorna-blir-inte-av',
                'title' => 'Läxorna blir inte av',
                'summary' => 'Glosorna skulle vara klara till fredag, och det upptäcks på torsdag kväll.',
                'icon' => 'book-open',
                'why' => <<<'MD'
En läxa som ska vara klar om flera dagar är svår att få grepp om. Den syns inte, den känns inte bråttom förrän det är för sent, och det kan vara svårt att veta var man ska börja.

Det handlar sällan om att inte vilja. Ofta är det svårt att komma igång utan någon hjälp.
MD,
                'helps' => <<<'MD'
- Gör läxan synlig varje dag, inte bara den dag den ska vara klar.
- Dela upp den i små delar, t.ex. fem glosor om dagen.
- Gör läxor på samma tid och plats, efter återhämtningen.
- Bestäm en kort tid i taget, med paus emellan.
MD,
                'in_app' => <<<'MD'
Lägg in en uppgift som **Läxa** med dagen den ska vara klar. Den syns då varje dag fram till dess, märkt ”Läxa · till fredag”, både för barnet och i vuxnas **Min dag**. Med en **timer** på 15 minuter blir det lättare att komma igång.
MD,
            ],
            [
                'kind' => 'situation',
                'slug' => 'nar-planerna-andras',
                'title' => 'När planerna ändras',
                'summary' => 'Simskolan är inställd, och plötsligt blir hela eftermiddagen fel.',
                'icon' => 'puzzle',
                'why' => <<<'MD'
För den som behöver förutsägbarhet kan en ändrad plan kännas som att marken försvinner. Det som var bestämt gäller inte längre, och det nya är okänt.

Ju senare ändringen kommer och ju mindre man vet om det nya, desto svårare brukar det vara.
MD,
                'helps' => <<<'MD'
- Berätta om ändringen så tidigt som möjligt.
- Säg vad som gäller i stället, inte bara vad som inte blir av.
- Visa det nya, gärna med bild, och vad som är som vanligt.
- Ge tid att ställa om, och räkna med att det tar energi.
MD,
                'in_app' => <<<'MD'
Markera dagen som **Annorlunda** när den inte blir som vanligt, så att den syns tydligt för alla. Lägg in det som gäller i stället med bildstöd, så att barnet kan se den nya planen i Min dag och på **hemskärmen**.
MD,
            ],
            [
                'kind' => 'situation',
                'slug' => 'helt-slut-som-foralder',
                'title' => 'Du är helt slut efter alla påminnelser',
                'summary' => 'Hela dagen har gått åt till att påminna, säga till och hålla ihop allt.',
                'icon' => 'users',
                'why' => <<<'MD'
När en vuxen bär hela familjens planering i huvudet blir det en tung och osynlig uppgift. Varje påminnelse tar lite energi, och det blir många under en dag.

Har den vuxne själv svårt med planering eller återhämtning blir belastningen ännu större.
MD,
                'helps' => <<<'MD'
- Flytta planeringen ut ur huvudet, så att den syns för alla.
- Låt barnen se själva vad som är nästa, i stället för att höra det.
- Dela på ansvaret med andra vuxna där det går.
- Planera in återhämtning för dig själv också.
MD,
                'in_app' => <<<'MD'
Låt appen göra en del av påminnandet: **rutiner**, **uppgifter med klockslag** och **påminnelser** på barnens egna enheter. I vuxnas **Min dag** ser du snabbt hur barnens dag går, utan att behöva fråga. Andra vuxna i familjen kan också se och hjälpa till.
MD,
            ],
        ];
    }
};
