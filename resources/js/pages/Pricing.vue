<template>
  <AppLayout>
    <div class="bg-slate-900 py-20">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-4xl text-center">
          <p class="text-sm font-semibold text-indigo-400">Preços</p>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Preços que crescem com você</h1>
          <p class="mt-4 text-lg text-slate-300">Escolha um plano acessível que oferece os melhores recursos de monitoramento de URL.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:mt-16 md:grid-cols-3">
          <div v-for="plan in plans" :key="plan.key" class="rounded-2xl bg-slate-800/60 ring-1 ring-white/5 p-8 flex flex-col">
            <h3 class="text-base font-semibold text-slate-200">{{ plan.name }}</h3>
            <p class="mt-6 flex items-baseline gap-x-2">
              <span class="text-3xl font-extrabold text-white">{{ formatPrice(plan.price, plan.currency) }}</span>
              <span class="text-sm text-slate-400">/mês</span>
            </p>
            <p class="mt-2 text-sm text-slate-400">{{ plan.annualText ?? '' }}</p>
            <a v-if="plan.key !== currentPlan" href="#" @click.prevent="subscribe(plan.key)" class="mt-6 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Comprar plano</a>
            <button v-else disabled class="mt-6 inline-flex items-center justify-center rounded-md bg-slate-600 px-4 py-2 text-sm font-semibold text-white cursor-not-allowed">Plano atual</button>
            <p class="mt-6 text-sm text-slate-300">{{ plan.description ?? 'Tudo o que você precisa para começar.' }}</p>
            <ul role="list" class="mt-6 space-y-3 text-sm text-slate-300">
              <li v-for="(f, i) in featuresFor(plan)" :key="i" class="flex items-start gap-x-3">
                <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                {{ f }}
              </li>
            </ul>
            <div v-if="plan.notifications" class="mt-6">
              <h4 class="text-sm font-semibold text-slate-200">Notificações</h4>
              <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                  <p class="text-xs text-slate-400">Canais</p>
                  <ul class="mt-2 space-y-2 text-sm text-slate-300">
                    <li v-for="(enabled, key) in plan.notifications.channels" :key="`channel-${key}`" class="flex items-center gap-x-2">
                      <template v-if="enabled">
                        <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                      </template>
                      <template v-else>
                        <svg class="h-5 w-5 text-rose-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-2.47-9.97a.75.75 0 0 1 1.06 0L10 8.99l1.41-1.43a.75.75 0 0 1 1.06 1.06L11.06 10l1.41 1.41a.75.75 0 1 1-1.06 1.06L10 11.06l-1.41 1.41a.75.75 0 1 1-1.06-1.06L8.94 10 7.53 8.59a.75.75 0 0 1 1.06-1.06L10 8.94l1.41-1.41a.75.75 0 0 1 .0 0z" clip-rule="evenodd"/></svg>
                      </template>
                      <span class="capitalize">{{ titleCase(key) }}</span>
                    </li>
                  </ul>
                </div>
                <div>
                  <p class="text-xs text-slate-400">Eventos</p>
                  <ul class="mt-2 space-y-2 text-sm text-slate-300">
                    <li v-for="(enabled, key) in plan.notifications.events" :key="`event-${key}`" class="flex items-center gap-x-2">
                      <template v-if="enabled">
                        <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                      </template>
                      <template v-else>
                        <svg class="h-5 w-5 text-rose-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-2.47-9.97a.75.75 0 0 1 1.06 0L10 8.99l1.41-1.43a.75.75 0 0 1 1.06 1.06L11.06 10l1.41 1.41a.75.75 0 1 1-1.06 1.06L10 11.06l-1.41 1.41a.75.75 0 1 1-1.06-1.06L8.94 10 7.53 8.59a.75.75 0 0 1 1.06-1.06L10 8.94l1.41-1.41a.75.75 0 0 1 .0 0z" clip-rule="evenodd"/></svg>
                      </template>
                      <span class="capitalize">{{ titleCase(key) }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="toast.visible" class="hc-toast">
      <div :class="['rounded-md p-3 shadow-lg', toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white']">
        <p class="text-sm">{{ toast.message }}</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
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

// Determine the current plan key from common backend props and make it reactive
const currentPlan = ref(rawProps.currentPlan ?? rawProps.current_plan ?? (rawProps.auth && rawProps.auth.user ? rawProps.auth.user.plan : null) ?? null)

// Simple in-component toast state
const toast = ref({ visible: false, message: '', type: 'success' })
let toastTimer: number | undefined = undefined

function showToast(message: string, type: 'success' | 'error' = 'success', timeout = 3500) {
  if (toastTimer) window.clearTimeout(toastTimer)
  toast.value = { visible: true, message, type }
  toastTimer = window.setTimeout(() => { toast.value.visible = false }, timeout)
}

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

function titleCase(s: string) {
  if (!s) return ''
  return s.replace(/[_-]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

async function subscribe(planKey: string) {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  function getCookie(name: string) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
    return match ? decodeURIComponent(match[2]) : null
  }
  const xsrf = getCookie('XSRF-TOKEN')
  try {
    const res = await fetch('/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ plan: planKey })
    })

    const data = await res.json().catch(() => null)
    if (res.ok) {
      showToast(data?.message ?? 'Plano atualizado com sucesso', 'success')
      currentPlan.value = planKey
    } else {
      showToast((data && data.message) ? data.message : 'Falha ao atualizar o plano', 'error')
    }
  } catch (e) {
    showToast('Erro de rede ao trocar de plano', 'error')
  }
}
</script>

<style scoped>
.hc-toast {
  position: fixed;
  right: 1rem;
  bottom: 1.25rem;
  z-index: 60;
  min-width: 220px;
}
</style>

