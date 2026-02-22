<script setup lang="ts">
import { ref, computed } from 'vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

type LinkCheck = {
    id: number;
    status: 'up' | 'down' | 'unhealth';
    http_status: number | null;
    response_time_ms: number | null;
    error: string | null;
    created_at: string;
};

const props = defineProps<{
    checks: LinkCheck[];
}>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'up':
            return 'bg-green-500';
        case 'down':
            return 'bg-red-500';
        case 'unhealth':
            return 'bg-yellow-500';
        default:
            return 'bg-gray-500';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'up':
            return 'Up';
        case 'down':
            return 'Down';
        case 'unhealth':
            return 'Unhealthy';
        default:
            return 'Unknown';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="w-full">
        <div v-if="checks.length === 0" class="flex items-center justify-center py-12 text-muted-foreground">
            <p>Nenhum check disponível para este link</p>
        </div>
        <div v-else class="flex items-end gap-1 h-32 overflow-x-auto">
            <TooltipProvider v-for="check in checks" :key="check.id" :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="flex flex-col justify-end h-full cursor-pointer group" style="min-width: 24px; max-width: 24px;">
                            <div 
                                :class="[getStatusColor(check.status), 'w-full h-full transition-all duration-200 rounded-t-sm group-hover:opacity-80']"
                            ></div>
                        </div>
                    </TooltipTrigger>
                    <TooltipContent class="p-3 space-y-1.5">
                        <div class="font-semibold text-sm">
                            Status: <span :class="{
                                'text-green-500': check.status === 'up',
                                'text-red-500': check.status === 'down',
                                'text-yellow-500': check.status === 'unhealth'
                            }">{{ getStatusLabel(check.status) }}</span>
                        </div>
                        <div class="text-xs space-y-1">
                            <div v-if="check.http_status">
                                HTTP Status: <span class="font-medium">{{ check.http_status }}</span>
                            </div>
                            <div v-if="check.response_time_ms !== null">
                                Tempo de resposta: <span class="font-medium">{{ check.response_time_ms }}ms</span>
                            </div>
                            <div v-if="check.error" class="text-red-400 max-w-xs break-words">
                                Erro: {{ check.error }}
                            </div>
                            <div class="text-muted-foreground pt-1 border-t">
                                {{ check.created_at }}
                            </div>
                        </div>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>
        <div v-if="checks.length > 0" class="mt-2 text-xs text-muted-foreground text-center">
            Últimas {{ checks.length }} requisições
        </div>
    </div>
</template>
