<template>
  <AppLayout>
    <div class="bg-slate-900 py-20">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
          <p class="text-sm font-semibold text-indigo-400">Pricing</p>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Pricing that grows with you</h1>
          <p class="mt-4 text-lg text-slate-300">Choose an affordable plan that’s packed with the best features for engaging your audience, creating customer loyalty, and driving sales.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:mt-16 md:grid-cols-3">
          <div v-for="plan in plans" :key="plan.key" class="rounded-2xl bg-slate-800/60 ring-1 ring-white/5 p-8 flex flex-col">
            <h3 class="text-base font-semibold text-slate-200">{{ plan.name }}</h3>
            <p class="mt-6 flex items-baseline gap-x-2">
              <span class="text-3xl font-extrabold text-white">{{ formatPrice(plan.price, plan.currency) }}</span>
              <span class="text-sm text-slate-400">/mês</span>
            </p>
            <p class="mt-2 text-sm text-slate-400">{{ plan.annualText ?? '' }}</p>
            <a href="#" @click.prevent="subscribe(plan.key)" class="mt-6 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Buy plan</a>
            <p class="mt-6 text-sm text-slate-300">{{ plan.description ?? 'Everything necessary to get started.' }}</p>
            <ul role="list" class="mt-6 space-y-3 text-sm text-slate-300">
              <li v-for="(f, i) in featuresFor(plan)" :key="i" class="flex items-start gap-x-3">
                <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                {{ f }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { Inertia } from '@inertiajs/inertia'

const page = usePage()
const rawProps = (page.props as any)?.value ?? (page.props as any) ?? {}

// Support plans passed as an object or array from the controller
// Read plans exactly as provided by the backend (no frontend defaults)
const plansRaw = rawProps.plans ?? rawProps.planList ?? []
const plans = Array.isArray(plansRaw)
  ? plansRaw
  : Object.keys(plansRaw).length
  ? Object.keys(plansRaw).map((k) => ({ key: k, ...(plansRaw as any)[k] }))
  : []

function formatPrice(amount: number | null | undefined, currency = 'BRL') {
  if (amount === null || amount === undefined) return ''
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(amount)
}

function featuresFor(plan: any) {
  if (!plan) return []
  if (Array.isArray(plan.features)) return plan.features

  const items: string[] = []
  if (plan.links_quota !== undefined) {
    items.push(plan.links_quota === null ? 'Sem limites de links' : `${plan.links_quota} links`)
  }
  if (plan.logs_quota !== undefined) {
    items.push(`${plan.logs_quota} dias de logs`)
  }

  return items
}

function subscribe(planKey: string) {
  Inertia.post('/subscribe', { plan: planKey })
}
</script>

