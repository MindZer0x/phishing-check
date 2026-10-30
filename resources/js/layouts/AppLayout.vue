<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b border-border">
            <div
                class="mx-auto flex h-16 w-full max-w-3xl items-center gap-2 px-4"
            >
                <ShieldCheck class="size-6" aria-hidden="true" />
                <span class="text-lg font-semibold">{{ page.props.name }}</span>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-10">
            <slot />
        </main>
    </div>
</template>
