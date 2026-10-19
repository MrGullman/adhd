<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Minus, Pencil, Plus, Star, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import PointController from '@/actions/App/Http/Controllers/Rewards/PointController';
import RedemptionController from '@/actions/App/Http/Controllers/Rewards/RedemptionController';
import RewardController from '@/actions/App/Http/Controllers/Rewards/RewardController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import InputError from '@/components/InputError.vue';
import PicturePicker from '@/components/PicturePicker.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDay } from '@/lib/format';
import type { Member, PictureItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Belöningar', href: RewardController.index() }],
    },
});

type Reward = {
    id: number;
    name: string;
    picture: PictureItem | null;
    cost: number;
    active: boolean;
};

const props = defineProps<{
    children: Array<Member & { points: number }>;
    wishes: Array<{
        id: number;
        child: string | null;
        reward: string | null;
        picture: PictureItem | null;
        cost: number;
        enough: boolean;
    }>;
    rewards: Reward[];
    waiting: Array<{
        id: number;
        child: string | null;
        reason: string;
        points: number;
        date: string | null;
        packed: string | null;
    }>;
    history: Array<{
        id: number;
        child: string | null;
        points: number;
        reason: string;
        pending: boolean;
        date: string | null;
    }>;
    settings: {
        enabled: boolean;
        task: number;
        routine: number;
        packing: number;
    };
}>();

// Give or take points by hand.
const pointsForm = useForm({
    child_id: props.children[0]?.id ?? null,
    points: 1,
    reason: '',
});

function givePoints(sign: 1 | -1): void {
    pointsForm
        .transform((data) => ({
            ...data,
            points: sign * Math.abs(data.points),
        }))
        .post(PointController.store.url(), {
            preserveScroll: true,
            onSuccess: () => pointsForm.reset('reason'),
        });
}

// New reward, or the one being changed.
const editing = ref<number | null>(null);
const picture = ref<PictureItem | null>(null);
const rewardForm = useForm({
    name: '',
    cost: 10,
    active: true,
});

function edit(reward: Reward): void {
    editing.value = reward.id;
    rewardForm.name = reward.name;
    rewardForm.cost = reward.cost;
    rewardForm.active = reward.active;
    picture.value = reward.picture;
}

function cancel(): void {
    editing.value = null;
    picture.value = null;
    rewardForm.reset();
}

function saveReward(): void {
    const options = { preserveScroll: true, onSuccess: cancel };
    const form = rewardForm.transform((data) => ({
        ...data,
        picture_id: picture.value?.id ?? null,
    }));

    if (editing.value) {
        form.put(RewardController.update.url(editing.value), options);
    } else {
        form.post(RewardController.store.url(), options);
    }
}

function removeReward(reward: Reward): void {
    if (window.confirm(`Ta bort ${reward.name}?`)) {
        router.delete(RewardController.destroy.url(reward.id), {
            preserveScroll: true,
        });
    }
}

const settingsForm = useForm({ ...props.settings });

function saveSettings(): void {
    settingsForm.put(PointController.settings.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Belöningar" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Belöningar</h1>
            <p class="text-ink-muted">
                Barnen får poäng för det de gör och kan önska sig något när de
                har tillräckligt. Bara vårdnadshavare ser den här sidan.
            </p>
        </header>

        <!-- Barnens poäng och ge poäng -->
        <section class="flex flex-col gap-4" aria-labelledby="poang">
            <h2 id="poang" class="text-title">Poäng</h2>
            <p v-if="!children.length" class="text-ink-muted">
                Lägg till barn under Familjen först.
            </p>
            <ul class="grid gap-3 sm:grid-cols-2">
                <li
                    v-for="child in children"
                    :key="child.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                    />
                    <span class="min-w-0 flex-1 font-bold break-words">
                        {{ child.name }}
                    </span>
                    <span
                        class="flex shrink-0 items-center gap-1 rounded-full bg-orange-soft px-3 py-1 font-bold"
                    >
                        <Star
                            class="size-4 text-orange-strong"
                            aria-hidden="true"
                        />
                        {{ child.points }} poäng
                    </span>
                </li>
            </ul>

            <form
                v-if="children.length"
                class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-3"
                @submit.prevent="givePoints(1)"
            >
                <fieldset class="flex flex-wrap gap-2">
                    <legend class="mb-2 font-bold">Till vem?</legend>
                    <label
                        v-for="child in children"
                        :key="child.id"
                        class="flex min-h-tap-adult cursor-pointer items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 font-bold has-checked:border-primary has-checked:bg-primary-soft has-focus-visible:ring-2 has-focus-visible:ring-focus-ring"
                    >
                        <input
                            v-model="pointsForm.child_id"
                            type="radio"
                            name="child_id"
                            :value="child.id"
                            class="sr-only"
                        />
                        <Check
                            v-if="pointsForm.child_id === child.id"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ child.name }}
                    </label>
                </fieldset>
                <InputError :message="pointsForm.errors.child_id" />

                <div class="flex flex-wrap items-end gap-3">
                    <div class="grid w-24 gap-2">
                        <Label for="points-amount">Poäng</Label>
                        <Input
                            id="points-amount"
                            v-model.number="pointsForm.points"
                            type="number"
                            min="1"
                            max="100"
                            required
                        />
                    </div>
                    <div class="grid min-w-52 flex-1 gap-2">
                        <Label for="points-reason">Varför?</Label>
                        <Input
                            id="points-reason"
                            v-model="pointsForm.reason"
                            required
                            maxlength="100"
                            placeholder="T.ex. hjälpte till med disken"
                        />
                    </div>
                </div>
                <InputError :message="pointsForm.errors.points" />
                <InputError :message="pointsForm.errors.reason" />

                <div class="flex flex-wrap gap-2">
                    <Button type="submit" :disabled="pointsForm.processing">
                        <Plus aria-hidden="true" />
                        Ge poäng
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="pointsForm.processing"
                        @click="givePoints(-1)"
                    >
                        <Minus aria-hidden="true" />
                        Dra av poäng
                    </Button>
                </div>
            </form>
        </section>

        <!-- Poäng som väntar på Godkänn, t.ex. för packning -->
        <section
            v-if="waiting.length"
            class="flex flex-col gap-3"
            aria-labelledby="godkanna"
        >
            <h2 id="godkanna" class="text-title">Att godkänna</h2>
            <p class="text-ink-muted">
                Kolla att det är gjort innan poängen räknas.
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="row in waiting"
                    :key="row.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">
                            {{ row.child }} · {{ row.reason }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            {{ row.points }} poäng
                            <template v-if="row.packed">
                                · {{ row.packed }}
                            </template>
                            <template v-if="row.date">
                                · {{ formatDay(row.date) }}
                            </template>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            @click="
                                router.post(
                                    PointController.approve.url(row.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            "
                        >
                            <Check aria-hidden="true" />
                            Godkänn
                        </Button>
                        <Button
                            variant="ghost"
                            @click="
                                router.post(
                                    PointController.decline.url(row.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            "
                        >
                            Inte nu
                        </Button>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Önskningar som väntar på svar -->
        <section
            v-if="wishes.length"
            class="flex flex-col gap-3"
            aria-labelledby="onskningar"
        >
            <h2 id="onskningar" class="text-title">Önskningar</h2>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="wish in wishes"
                    :key="wish.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <PictureThumb v-if="wish.picture" :picture="wish.picture" />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">
                            {{ wish.child }} önskar {{ wish.reward }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            {{ wish.cost }} poäng
                            <template v-if="!wish.enough">
                                · har inte tillräckligt just nu
                            </template>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            :disabled="!wish.enough"
                            @click="
                                router.post(
                                    RedemptionController.approve.url(wish.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            "
                        >
                            <Check aria-hidden="true" />
                            Ja
                        </Button>
                        <Button
                            variant="ghost"
                            @click="
                                router.post(
                                    RedemptionController.decline.url(wish.id),
                                    {},
                                    { preserveScroll: true },
                                )
                            "
                        >
                            Inte nu
                        </Button>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Belöningar barnen kan önska sig -->
        <section class="flex flex-col gap-4" aria-labelledby="beloningar">
            <h2 id="beloningar" class="text-title">Att önska sig</h2>
            <p class="text-ink-muted">
                Saker i verkligheten, t.ex. välja film, baka tillsammans eller
                en utflykt.
            </p>

            <form
                class="flex flex-col gap-3 rounded-lg bg-surface-sunken p-3"
                @submit.prevent="saveReward"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="grid min-w-52 flex-1 gap-2">
                        <Label for="reward-name">Belöning</Label>
                        <Input
                            id="reward-name"
                            v-model="rewardForm.name"
                            required
                            maxlength="100"
                            placeholder="T.ex. välja film"
                        />
                    </div>
                    <div class="grid w-28 gap-2">
                        <Label for="reward-cost">Kostar poäng</Label>
                        <Input
                            id="reward-cost"
                            v-model.number="rewardForm.cost"
                            type="number"
                            min="1"
                            max="1000"
                            required
                        />
                    </div>
                </div>
                <InputError :message="rewardForm.errors.name" />
                <InputError :message="rewardForm.errors.cost" />
                <PicturePicker
                    v-model="picture"
                    button-label="Välj bild (valfritt)"
                    @select="
                        (selected) => {
                            if (rewardForm.name.trim() === '') {
                                rewardForm.name = selected.label;
                            }
                        }
                    "
                />
                <label
                    v-if="editing"
                    class="flex min-h-tap-adult items-center gap-3"
                >
                    <input
                        v-model="rewardForm.active"
                        type="checkbox"
                        class="size-5"
                    />
                    Barnen kan önska sig den
                </label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="rewardForm.processing"
                    >
                        {{ editing ? 'Spara' : 'Lägg till' }}
                    </Button>
                    <Button
                        v-if="editing"
                        type="button"
                        variant="ghost"
                        @click="cancel"
                    >
                        Avbryt
                    </Button>
                </div>
            </form>

            <p v-if="!rewards.length" class="text-ink-muted">
                Inga belöningar än.
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="reward in rewards"
                    :key="reward.id"
                    class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised p-2 pl-3"
                    :class="!reward.active && 'opacity-60'"
                >
                    <PictureThumb
                        v-if="reward.picture"
                        :picture="reward.picture"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="font-bold break-words">{{ reward.name }}</p>
                        <p class="text-caption text-ink-muted">
                            {{ reward.cost }} poäng
                            <template v-if="!reward.active">
                                · dold för barnen
                            </template>
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="`Ändra ${reward.name}`"
                        @click="edit(reward)"
                    >
                        <Pencil aria-hidden="true" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-danger"
                        :aria-label="`Ta bort ${reward.name}`"
                        @click="removeReward(reward)"
                    >
                        <Trash2 aria-hidden="true" />
                    </Button>
                </li>
            </ul>
        </section>

        <!-- Vad som ger poäng av sig självt -->
        <section class="flex flex-col gap-3" aria-labelledby="automatiskt">
            <h2 id="automatiskt" class="text-title">Poäng av sig själv</h2>
            <form
                class="flex flex-col gap-3 rounded-lg border border-line p-3"
                @submit.prevent="saveSettings"
            >
                <label class="flex min-h-tap-adult items-center gap-3">
                    <input
                        v-model="settingsForm.enabled"
                        type="checkbox"
                        class="size-5"
                    />
                    Barnen får poäng när de gör saker
                </label>
                <div
                    v-if="settingsForm.enabled"
                    class="grid gap-3 sm:grid-cols-3"
                >
                    <div class="grid gap-2">
                        <Label for="points-task">En uppgift klar</Label>
                        <Input
                            id="points-task"
                            v-model.number="settingsForm.task"
                            type="number"
                            min="0"
                            max="20"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="points-routine">En rutin klar</Label>
                        <Input
                            id="points-routine"
                            v-model.number="settingsForm.routine"
                            type="number"
                            min="0"
                            max="20"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="points-packing">Har packat</Label>
                        <Input
                            id="points-packing"
                            v-model.number="settingsForm.packing"
                            type="number"
                            min="0"
                            max="20"
                        />
                    </div>
                </div>
                <InputError
                    :message="
                        settingsForm.errors.task ??
                        settingsForm.errors.routine ??
                        settingsForm.errors.packing
                    "
                />
                <p class="text-caption text-ink-muted">
                    0 betyder inga poäng för just det. Bockar barnet ur igen
                    försvinner poängen. Poängen för packning räknas först när du
                    har godkänt dem under Att godkänna.
                </p>
                <div>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="settingsForm.processing"
                    >
                        Spara
                    </Button>
                </div>
            </form>
        </section>

        <!-- Senaste poängen -->
        <section
            v-if="history.length"
            class="flex flex-col gap-3"
            aria-labelledby="senaste"
        >
            <h2 id="senaste" class="text-title">Senaste</h2>
            <ul class="flex flex-col divide-y divide-line">
                <li
                    v-for="row in history"
                    :key="row.id"
                    class="flex items-center gap-3 py-2"
                >
                    <span
                        class="w-14 shrink-0 text-right font-bold tabular-nums"
                    >
                        {{ row.points > 0 ? `+${row.points}` : row.points }}
                    </span>
                    <span class="min-w-0 flex-1 break-words">
                        {{ row.child }} · {{ row.reason }}
                        <span v-if="row.pending" class="text-ink-muted">
                            · väntar på godkänn
                        </span>
                    </span>
                    <span
                        v-if="row.date"
                        class="shrink-0 text-caption text-ink-muted first-letter:uppercase"
                    >
                        {{ formatDay(row.date) }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
