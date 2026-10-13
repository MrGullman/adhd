<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import CommunityController from '@/actions/App/Http/Controllers/Community/CommunityController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ current: string | null }>();

const form = useForm({ community_name: props.current ?? '' });
</script>

<!-- Namnet som syns i Community i stället för kontots namn. -->
<template>
    <form
        class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-4"
        @submit.prevent="
            form.put(CommunityController.name.url(), { preserveScroll: true })
        "
    >
        <div class="grid gap-2">
            <Label for="community-name">Ditt namn i Community</Label>
            <Input
                id="community-name"
                v-model="form.community_name"
                required
                minlength="2"
                maxlength="40"
                autocomplete="off"
                placeholder="T.ex. Mamma till tre"
            />
            <InputError :message="form.errors.community_name" />
            <p class="text-caption text-ink-muted">
                Andra familjer ser bara det här namnet, aldrig ditt riktiga namn
                eller din familj. Skriv inte barnens namn eller annat som kan
                peka ut dem.
            </p>
        </div>
        <div>
            <Button
                type="submit"
                :variant="current ? 'outline' : 'default'"
                :disabled="form.processing"
            >
                Spara namnet
            </Button>
        </div>
    </form>
</template>
