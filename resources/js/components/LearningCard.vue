<script setup lang="ts">
import { OctagonAlert, ShieldCheck, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type {
    LearningCard,
    RiskLevel,
    SignalSource,
    SignalWeight,
} from '@/types/check';

const props = defineProps<{
    card: LearningCard;
}>();

const appearance: Record<
    RiskLevel,
    { label: string; icon: typeof ShieldCheck; classes: string }
> = {
    unauffaellig: {
        label: 'Unauffällig',
        icon: ShieldCheck,
        classes: 'border-green-600/40 bg-green-50 dark:bg-green-950/40',
    },
    verdaechtig: {
        label: 'Verdächtig',
        icon: TriangleAlert,
        classes: 'border-amber-500/50 bg-amber-50 dark:bg-amber-950/40',
    },
    gefaehrlich: {
        label: 'Gefährlich',
        icon: OctagonAlert,
        classes: 'border-red-600/50 bg-red-50 dark:bg-red-950/40',
    },
};

const weightBadges: Record<
    SignalWeight,
    { label: string; variant: 'destructive' | 'secondary' }
> = {
    strong: { label: 'starkes Signal', variant: 'destructive' },
    weak: { label: 'schwaches Signal', variant: 'secondary' },
};

const sourceLabels: Record<SignalSource, string> = {
    rules: 'erkannt durch Regeln',
    ai: 'erkannt durch KI',
};

const riskAppearance = computed(() => appearance[props.card.riskLevel]);
</script>

<template>
    <Card :class="riskAppearance.classes" aria-live="polite">
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-xl">
                <component
                    :is="riskAppearance.icon"
                    class="size-6"
                    aria-hidden="true"
                />
                Einschätzung: {{ riskAppearance.label }}
            </CardTitle>
            <CardDescription class="text-base text-foreground">
                {{ card.summary }}
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
            <section class="space-y-3">
                <h3 class="font-semibold">Gefundene Warnsignale</h3>
                <p
                    v-if="card.signals.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Keine Warnsignale gefunden. Die E-Mail wurde auf alle
                    bekannten Warnsignale geprüft.
                </p>
                <ul v-else class="space-y-3">
                    <li
                        v-for="signal in card.signals"
                        :key="`${signal.source}-${signal.title}`"
                        class="rounded-md border border-border bg-background p-3"
                    >
                        <div
                            class="flex flex-wrap items-center gap-2 font-medium"
                        >
                            {{ signal.title }}
                            <Badge
                                :variant="weightBadges[signal.weight].variant"
                            >
                                {{ weightBadges[signal.weight].label }}
                            </Badge>
                            <Badge variant="outline">
                                {{ sourceLabels[signal.source] }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ signal.explanation }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="space-y-3">
                <h3 class="font-semibold">Was jetzt tun?</h3>
                <ul class="list-disc space-y-1 pl-5">
                    <li v-for="step in card.nextSteps" :key="step">
                        {{ step }}
                    </li>
                </ul>
            </section>

            <p v-if="card.heuristicsOnly" class="text-sm text-muted-foreground">
                KI-Analyse nicht verfügbar – Ergebnis basiert nur auf Regeln
            </p>
        </CardContent>
    </Card>
</template>
