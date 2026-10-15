<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    controller: string | null;
    contact: string | null;
    backupDays: number;
}>();
</script>

<!--
    Integritetspolicy. Den beskriver hur appen faktiskt fungerar
    (docs/ARCHITECTURE.md, Integritet och datakontroll). Ändras appen ska
    texten ändras med den.
-->
<template>
    <Head title="Integritetspolicy" />

    <article class="flex flex-col gap-6 text-left">
        <header class="flex flex-col gap-2">
            <h1 class="text-heading font-semibold">Integritetspolicy</h1>
            <p>
                NPF-familjen är ett hjälpmedel för familjer med barn med
                NPF-problematik. Familjen äger sin data. Här står vad som
                sparas, varför och hur ni bestämmer över det.
            </p>
        </header>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Vem ansvarar?</h2>
            <p v-if="controller">
                Personuppgiftsansvarig är {{ controller }}.
                <template v-if="contact"> Kontakt: {{ contact }}. </template>
            </p>
            <p v-else class="text-ink-muted">
                Uppgift om personuppgiftsansvarig saknas ännu.
            </p>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Vad sparas?</h2>
            <ul class="flex list-disc flex-col gap-1 pl-6">
                <li>
                    Vuxna: namn, e-postadress och lösenord (lösenordet bara som
                    en hash som inte går att läsa).
                </li>
                <li>
                    Barn: namn, färg eller bild och en kod för att logga in.
                    Koden sparas krypterad. Barn har ingen e-postadress.
                </li>
                <li>
                    Det familjen själv lägger in: uppgifter, rutiner,
                    aktiviteter, listor, mat, poäng och belöningar, egna bilder
                    och händelser från kalendrar ni kopplar in.
                </li>
                <li>
                    Dagboken. Den sparas krypterad och bara vårdnadshavare kan
                    läsa den.
                </li>
                <li>
                    Kalenderlänkar, krypterade. Appen läser kalendern men kan
                    aldrig ändra i den.
                </li>
                <li>
                    Inloggningar för barn (när och på vilken enhet), så att
                    vårdnadshavare kan se dem.
                </li>
                <li>
                    Tekniskt nödvändigt: inloggningssessioner (med IP-adress och
                    webbläsare) och de enheter där påminnelser är påslagna.
                </li>
                <li>
                    Feedback som skickas med Tyck till, och vilken sida den
                    skrevs på. Den läses av administratören.
                </li>
                <li>
                    I Community: det man skriver, under ett visningsnamn man
                    väljer själv. Familjen och det riktiga namnet visas inte.
                </li>
            </ul>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Varför?</h2>
            <p>
                Uppgifterna används bara för att appen ska fungera för er
                familj. Ingen reklam, ingen spårning och ingen analys. Den enda
                kakan är den som håller er inloggade. Ingenting säljs eller
                lämnas ut.
            </p>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Vad ser administratören?</h2>
            <p>
                Administratören ser kontouppgifter: namn, e-postadress, vilken
                familj kontot hör till, när det skapades och senast loggade in.
                Aldrig familjens innehåll, som dagbok, uppgifter, bilder eller
                kalendrar. Missbrukas ett konto kan administratören stänga av
                det. Ingenting tas bort då.
            </p>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Var finns datan?</h2>
            <p>
                På en server inom EU. Familjens foton ligger på en privat del av
                servern och visas bara för familjen och familjens hemskärmar.
            </p>
            <p>Några tjänster används för att appen ska fungera:</p>
            <ul class="flex list-disc flex-col gap-1 pl-6">
                <li>
                    E-post (inbjudningar och lösenordsåterställning) skickas via
                    Gmail (Google).
                </li>
                <li>
                    Påminnelser skickas via telefonens eller webbläsarens
                    push-tjänst (Apple, Google, Mozilla eller Microsoft).
                    Innehållet är krypterat på vägen.
                </li>
                <li>
                    Skolmaten hämtas från Matilda. Bara skolans id skickas, inga
                    personuppgifter.
                </li>
                <li>
                    Kalendrar läses från Google, Apple eller Microsoft via den
                    länk ni själva lägger in.
                </li>
                <li>
                    Recept från länk: appen hämtar receptsidan ni klistrar in.
                    Inga uppgifter om er skickas med.
                </li>
            </ul>
            <p>Typsnitt och bilder ligger på vår egen server.</p>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Hur länge?</h2>
            <p>
                Så länge familjen använder appen. Raderar ni något försvinner
                det direkt. Säkerhetskopior sparas i {{ backupDays }} dagar och
                försvinner sedan. En export sparas i 7 dagar.
            </p>
        </section>

        <section class="flex flex-col gap-2">
            <h2 class="text-title">Ni bestämmer</h2>
            <ul class="flex list-disc flex-col gap-1 pl-6">
                <li>
                    Vårdnadshavare kan ladda ner all familjens data (Familjen →
                    Familjens data).
                </li>
                <li>
                    Vårdnadshavare kan ta bort ett barnkonto eller hela
                    familjen. Då försvinner all data och alla filer.
                </li>
                <li>
                    Varje vuxen kan ändra eller ta bort sitt eget konto under
                    Inställningar.
                </li>
                <li>
                    Ni har rätt att få veta vad som sparas, få det rättat eller
                    borttaget, och att klaga hos Integritetsskyddsmyndigheten
                    (IMY).
                </li>
            </ul>
        </section>

        <Link :href="home()" class="font-bold underline">Till startsidan</Link>
    </article>
</template>
