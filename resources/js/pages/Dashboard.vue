<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Database, Link, Zap } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';
import LinkChecksChart from '@/components/LinkChecksChart.vue';

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

type UserLink = {
    id: number;
    title: string;
    url: string;
};

type LinkCheck = {
    id: number;
    status: 'healthy' | 'down' | 'unhealth';
    http_status: number | null;
    response_time_ms: number | null;
    error: string | null;
    created_at: string;
};

const props = defineProps<{
    stats: Stats;
    plan: Plan;
    userLinks: UserLink[];
}>();

const selectedLinkId = ref<number | null>(props.userLinks[0]?.id ?? null);
const linkChecks = ref<LinkCheck[]>([]);
const isLoadingChecks = ref(false);

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

const loadLinkChecks = async (linkId: number) => {
    isLoadingChecks.value = true;
    try {
        const response = await axios.get(`/api/links/${linkId}/checks`);
        linkChecks.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar checks:', error);
        linkChecks.value = [];
    } finally {
        isLoadingChecks.value = false;
    }
};

watch(selectedLinkId, (newLinkId) => {
    if (newLinkId) {
        loadLinkChecks(newLinkId);
    }
}, { immediate: true });
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
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Visão Geral</h3>
                            <p class="text-sm text-muted-foreground">
                                Histórico de checks por link
                            </p>
                        </div>
                    </div>

                    <!-- Select de Link -->
                    <div v-if="userLinks.length > 0" class="space-y-2">
                        <label for="link-select" class="text-sm font-medium">
                            Selecione um Link
                        </label>
                        <select
                            id="link-select"
                            v-model="selectedLinkId"
                            class="w-full px-3 py-2 rounded-md border border-input bg-background text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option v-for="link in userLinks" :key="link.id" :value="link.id">
                                {{ link.title }} - {{ link.url }}
                            </option>
                        </select>
                    </div>

                    <div class="rounded-lg border p-6">
                        <h4 class="text-sm font-semibold mb-4">Últimas 20 Requisições</h4>
                        <div v-if="isLoadingChecks" class="flex items-center justify-center py-12">
                            <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                        </div>
                        <LinkChecksChart v-else-if="userLinks.length > 0" :checks="linkChecks" />
                        <div v-else class="flex items-center justify-center py-12 text-muted-foreground">
                            <p>Nenhum link disponível. Crie um link para visualizar os checks.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
