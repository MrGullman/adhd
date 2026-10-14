<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import ProfilePictureController from '@/actions/App/Http/Controllers/Settings/ProfilePictureController';
import DeleteUser from '@/components/DeleteUser.vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import Heading from '@/components/Heading.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { PictureItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profil',
                href: edit(),
            },
        ],
    },
});

const props = defineProps<{
    picture: PictureItem | null;
    avatarColor: string;
    mustVerifyEmail: boolean;
    status?: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// Your own picture: saved as soon as it is chosen or removed.
const picture = ref<PictureItem | null>(props.picture);

watch(picture, (chosen) => {
    router.put(
        ProfilePictureController.update.url(),
        { picture_id: chosen?.id ?? null },
        { preserveScroll: true },
    );
});
</script>

<template>
    <Head title="Profil" />

    <h1 class="sr-only">Profil</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Din bild"
            description="Visas för familjen i stället för din bokstav"
        />
        <div class="flex flex-wrap items-center gap-4">
            <FamilyAvatar
                v-if="!picture"
                :name="user.name"
                :color="avatarColor"
                size="lg"
            />
            <PicturePicker
                v-model="picture"
                purpose="avatar-adult"
                button-label="Välj bild"
            />
        </div>

        <Heading
            variant="small"
            title="Profil"
            description="Ändra ditt namn och din e-postadress"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Namn</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="För- och efternamn"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-postadress</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email ?? ''"
                    required
                    autocomplete="username"
                    placeholder="E-postadress"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Din e-postadress är inte bekräftad.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Skicka bekräftelsemejlet igen.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    En ny bekräftelselänk har skickats till din e-postadress.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Spara</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
