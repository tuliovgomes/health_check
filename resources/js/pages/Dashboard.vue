<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Database, Link, Zap } from 'lucide-vue-next';

type Stats = {
    links: {
        current: number;
        quota: number | null;
        percentage: number;
    };
    integrations: {
        current: number;
        quota: number | null;
        percentage: number;
    };
    logs: {
        count: number;
        retention_days: number;
    };
};

type Plan = {
    name: string;
    key: string;
};

defineProps<{
    stats: Stats;
    plan: Plan;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const getProgressColor = (percentage: number) => {
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Links Card -->
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Health Checks</p>
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-3xl font-bold">{{ stats.links.current }}</h2>
                                <span class="text-sm text-muted-foreground">
                                    / {{ stats.links.quota ?? '∞' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg bg-blue-500/10 p-3">
                            <Link :size="24" class="text-blue-500" />
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Uso do plano</span>
                            <span class="font-medium">{{ stats.links.percentage }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                :class="getProgressColor(stats.links.percentage)"
                                class="h-full transition-all duration-300"
                                :style="{ width: `${stats.links.percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Integrations Card -->
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Integrações</p>
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-3xl font-bold">{{ stats.integrations.current }}</h2>
                                <span class="text-sm text-muted-foreground">
                                    / {{ stats.integrations.quota ?? '∞' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg bg-purple-500/10 p-3">
                            <Zap :size="24" class="text-purple-500" />
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Uso do plano</span>
                            <span class="font-medium">{{ stats.integrations.percentage }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                :class="getProgressColor(stats.integrations.percentage)"
                                class="h-full transition-all duration-300"
                                :style="{ width: `${stats.integrations.percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Logs Card -->
                <div
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Logs Armazenados</p>
                            <div class="flex items-baseline gap-2">
                                <h2 class="text-3xl font-bold">{{ stats.logs.count.toLocaleString('pt-BR') }}</h2>
                            </div>
                        </div>
                        <div class="rounded-lg bg-orange-500/10 p-3">
                            <Database :size="24" class="text-orange-500" />
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Retenção</span>
                            <span class="font-medium">{{ stats.logs.retention_days }} dias</span>
                        </div>
                        <div class="rounded-md bg-muted/30 p-2 text-center">
                            <p class="text-xs text-muted-foreground">
                                Logs mantidos por {{ stats.logs.retention_days }} dias no plano {{ plan.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 bg-card p-6 md:min-h-min dark:border-sidebar-border"
            >
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Visão Geral</h3>
                            <p class="text-sm text-muted-foreground">
                                Seu plano atual: <span class="font-medium">{{ plan.name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-lg border p-4">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-blue-500/10 p-2">
                                    <Link :size="20" class="text-blue-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Total de Checks</p>
                                    <p class="text-2xl font-bold">{{ stats.links.current }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-purple-500/10 p-2">
                                    <Zap :size="20" class="text-purple-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Total de Integrações</p>
                                    <p class="text-2xl font-bold">{{ stats.integrations.current }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border p-4">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-orange-500/10 p-2">
                                    <Database :size="20" class="text-orange-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Logs de Health Check</p>
                                    <p class="text-2xl font-bold">{{ stats.logs.count.toLocaleString('pt-BR') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
