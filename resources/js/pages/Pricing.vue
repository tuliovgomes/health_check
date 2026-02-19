<template>
  <Layout>
    <div class="container">
      <h1 class="mt-6">Planos e Cobrança</h1>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div v-for="(plan, key) in plans" :key="key" class="card p-4">
          <h3 class="font-bold">{{ plan.name }}</h3>
          <p class="text-sm mt-2">{{ plan.links_quota === null ? 'Sem limites' : plan.links_quota + ' links' }}</p>
          <p class="mt-4 text-2xl">{{ plan.price === 0 ? 'Gratuito' : (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: plan.currency ?? 'BRL' }).format(plan.price) + '/mês') }}</p>

          <div class="mt-4">
            <button
              class="btn btn-primary"
              :disabled="currentPlan === key"
              @click="subscribe(key)"
            >
              {{ currentPlan === key ? 'Plano Atual' : (plan.price === 0 ? 'Escolher (Grátis)' : 'Assinar') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'
const props = usePage().props.value
const plans = props.plans
const currentPlan = props.currentPlan

function subscribe(plan: string) {
  Inertia.post('/subscribe', { plan })
}
</script>
