<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InvitationAcceptanceController from '@/actions/App/Http/Controllers/Family/InvitationAcceptanceController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';

defineOptions({
    layout: {
        title: 'Inbjudan',
        description: '',
    },
});

defineProps<{
    token: string;
    invitation: {
        familyName: string | null;
        inviterName: string | null;
        roleLabel: string;
        email: string;
    } | null;
    accountExists: boolean;
    signedInAs: string | null;
    emailMatches: boolean;
}>();
</script>

<template>
    <Head title="Inbjudan" />

    <div v-if="!invitation" class="flex flex-col gap-4 text-center">
        <p class="text-title">Inbjudan gäller inte längre.</p>
        <p class="text-ink-muted">
            Den kan ha gått ut eller redan använts. Be den som bjöd in dig att
            skicka en ny.
        </p>
        <Link :href="home()" class="text-primary underline">
            Till startsidan
        </Link>
    </div>

    <div v-else class="flex flex-col gap-6">
        <div class="rounded-lg border border-line bg-surface-raised p-5">
            <p class="text-title">{{ invitation.familyName }}</p>
            <p class="text-ink-muted">
                {{ invitation.inviterName ?? 'Någon' }} har bjudit in dig som
                {{ invitation.roleLabel.toLowerCase() }}.
            </p>
        </div>

        <!-- Inloggad med rätt e-postadress: gå med direkt. -->
        <Form
            v-if="signedInAs && emailMatches"
            v-bind="InvitationAcceptanceController.store.form(token)"
            v-slot="{ processing }"
        >
            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Gå med i familjen
            </Button>
        </Form>

        <!-- Inloggad med en annan e-postadress. -->
        <div v-else-if="signedInAs" class="flex flex-col gap-2 text-center">
            <p>
                Inbjudan gäller {{ invitation.email }}, men du är inloggad som
                {{ signedInAs }}.
            </p>
            <p class="text-ink-muted">Logga ut och öppna länken igen.</p>
        </div>

        <!-- Har redan ett konto: logga in först. -->
        <div v-else-if="accountExists" class="flex flex-col gap-4 text-center">
            <p>
                Det finns redan ett konto för {{ invitation.email }}. Logga in
                för att gå med.
            </p>
            <Form
                v-bind="InvitationAcceptanceController.store.form(token)"
                v-slot="{ processing }"
            >
                <Button type="submit" class="w-full" :disabled="processing">
                    Logga in och gå med
                </Button>
            </Form>
        </div>

        <!-- Nytt konto. -->
        <Form
            v-else
            v-bind="InvitationAcceptanceController.store.form(token)"
            class="flex flex-col gap-5"
            :reset-on-error="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="email">E-postadress</Label>
                <Input id="email" :model-value="invitation.email" disabled />
            </div>
            <div class="grid gap-2">
                <Label for="name">Namn</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="För- och efternamn"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Lösenord</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Bekräfta lösenordet</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>
            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Skapa konto och gå med
            </Button>
        </Form>
    </div>
</template>
