<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CheckController from '@/actions/App/Http/Controllers/CheckController';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LearningCard from '@/components/LearningCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { LearningCard as LearningCardData } from '@/types/check';

defineProps<{
    learningCard?: LearningCardData;
}>();

const form = useForm({
    sender: '',
    subject: '',
    body: '',
});

const tooManyChecks = ref(false);

function check() {
    tooManyChecks.value = false;

    form.submit(CheckController(), {
        preserveState: true,
        preserveScroll: true,
        preserveUrl: true,
        onHttpException: (response) => {
            if (response.status === 429) {
                tooManyChecks.value = true;

                return false;
            }
        },
    });
}
</script>

<template>
    <Heading
        title="E-Mail prüfen"
        description="Prüfen Sie, ob eine erhaltene E-Mail ein Phishing-Versuch ist, und lernen Sie, woran Sie Warnsignale erkennen."
    />

    <form class="space-y-6" @submit.prevent="check">
        <div class="grid gap-2">
            <Label for="sender">Absender</Label>
            <Input
                id="sender"
                v-model="form.sender"
                placeholder="Name <name@example.com>"
                autocomplete="off"
                :aria-invalid="!!form.errors.sender"
            />
            <InputError :message="form.errors.sender" />
        </div>

        <div class="grid gap-2">
            <Label for="subject">Betreff</Label>
            <Input
                id="subject"
                v-model="form.subject"
                autocomplete="off"
                :aria-invalid="!!form.errors.subject"
            />
            <InputError :message="form.errors.subject" />
        </div>

        <div class="grid gap-2">
            <Label for="body">Text der E-Mail</Label>
            <textarea
                id="body"
                v-model="form.body"
                rows="10"
                maxlength="20000"
                :aria-invalid="!!form.errors.body"
                class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
            />
            <InputError :message="form.errors.body" />
        </div>

        <AlertError
            v-if="tooManyChecks"
            title="Zu viele Prüfungen"
            :errors="[
                'Sie haben in kurzer Zeit sehr viele E-Mails geprüft. Bitte warten Sie eine Minute und versuchen Sie es dann erneut.',
            ]"
        />

        <Button type="submit" :disabled="form.processing">
            <Spinner v-if="form.processing" />
            E-Mail prüfen
        </Button>
    </form>

    <LearningCard v-if="learningCard" :card="learningCard" class="mt-10" />
</template>
