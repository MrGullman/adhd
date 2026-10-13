<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login, privacy } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Skapa ett konto',
        description: 'Fyll i dina uppgifter för att skapa ett konto',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Namn</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="För- och efternamn"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-postadress</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="namn@exempel.se"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Lösenord</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Lösenord"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Bekräfta lösenordet</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Bekräfta lösenordet"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Skapa konto
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Har du redan ett konto?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Logga in</TextLink
            >
        </div>
        <p class="text-center text-sm text-muted-foreground">
            Läs hur vi hanterar familjens data i
            <TextLink :href="privacy()" class="underline underline-offset-4"
                >integritetspolicyn</TextLink
            >.
        </p>
    </Form>
</template>
