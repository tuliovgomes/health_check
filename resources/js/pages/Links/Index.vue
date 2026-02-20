<template>
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-center justify-between">
        <h1 class="mt-6 text-2xl font-semibold text-slate-100">Meus Links</h1>
        <button @click="showModal = true" class="mt-6 inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Adicionar link</button>
      </div>

      <div class="mt-6 overflow-hidden rounded-lg bg-slate-800/60 ring-1 ring-white/5">
        <div class="grid grid-cols-12 gap-4 px-6 py-3 text-xs text-slate-400 border-b border-slate-700">
          <div class="col-span-4">Título / Código</div>
          <div class="col-span-4">URL</div>
          <div class="col-span-2">Intervalo</div>
          <div class="col-span-1">Última</div>
          <div class="col-span-1 text-right">Ações</div>
        </div>
        <div v-if="!links.data || links.data.length === 0" class="px-6 py-8 text-center text-slate-400">Nenhum link criado ainda.</div>
        <div v-else>
          <div v-for="link in links.data" :key="link.id" class="grid grid-cols-12 gap-4 px-6 py-4 hover:bg-slate-700/30">
            <div class="col-span-4">
              <div class="text-sm text-slate-100 font-medium">{{ link.title || link.code }}</div>
              <div class="text-xs text-slate-400">{{ link.code }}</div>
            </div>
            <div class="col-span-4 text-sm text-slate-300 truncate">{{ link.url }}</div>
            <div class="col-span-2 text-sm text-slate-300">{{ link.check_interval }} min</div>
            <div class="col-span-1 text-sm text-slate-300">{{ link.last_checked_at ? new Date(link.last_checked_at).toLocaleString() : '—' }}</div>
            <div class="col-span-1 text-right">
              <button title="Remover link" aria-label="Remover link" class="inline-flex items-center rounded-md bg-rose-600 py-1 px-1 text-sm font-medium text-white hover:bg-rose-500" @click.prevent="remove(link.id)">
                <Trash class=" h-4 w-4" />
              </button>
              <button @click.prevent="openChecks(link.id)" title="Ver atividade" aria-label="Ver atividade" class="inline-flex items-center rounded-md bg-indigo-600 py-1 px-1 text-sm font-medium text-white hover:bg-indigo-500">
                <Activity class=" h-4 w-4" />
              </button>
            </div>
            <div v-if="link.checks && link.checks.length" class="col-span-12 mt-2 px-2 text-xs text-slate-400">
              <div class="font-medium">Histórico (últimos {{ link.checks.length }}):</div>
              <ul class="mt-1 list-disc list-inside">
                <li v-for="c in link.checks" :key="c.id">[{{ c.created_at }}] {{ c.status }} — {{ c.http_status || '—' }} — {{ c.response_time_ms || '—' }}ms</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60" @click="closeModal()"></div>
        <div class="relative w-full max-w-lg rounded-lg bg-slate-800 p-6 ring-1 ring-white/5">
          <h3 class="text-lg font-semibold text-slate-100">Novo link</h3>
          <form @submit.prevent="create" class="mt-4 space-y-3">
            <input v-model="form.url" placeholder="https://example.com" class="w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" />
            <input v-model="form.title" placeholder="Título (opcional)" class="w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" />
            <div class="flex items-center gap-3">
              <label class="text-sm text-slate-300">Intervalo</label>
              <select v-model.number="form.check_interval" class="rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100">
                <option :value="1">1 minuto</option>
                <option :value="5">5 minutos</option>
                <option :value="15">15 minutos</option>
                <option :value="30">30 minutos</option>
                <option :value="60">1 hora</option>
              </select>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" @click="closeModal()" class="rounded-md bg-slate-700 px-3 py-2 text-sm text-slate-200">Cancelar</button>
              <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Criar link</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <LinkChecksModal v-if="showChecksModal" :link-id="selectedLinkId" @close="showChecksModal=false; selectedLinkId=null" />

    <div v-if="toast.visible" class="hc-toast">
      <div :class="['rounded-md p-3 shadow-lg', toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white']" role="alert">
        <p class="text-sm">{{ toast.message }}</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { Trash, Activity } from 'lucide-vue-next';
import LinkChecksModal from '@/components/LinkChecksModal.vue'

const page = usePage()
const rawProps = (page.props as any)?.value ?? (page.props as any) ?? {}
const links = ref(rawProps.links ?? { data: [] })

const form = ref({ url: '', title: '', check_interval: 5 })
const showModal = ref(false)
const showChecksModal = ref(false)
const selectedLinkId = ref<number | null>(null)
// toast state
const toast = ref({ visible: false, message: '', type: 'success' })
let toastTimer: number | undefined = undefined

function showToast(message: string, type: 'success' | 'error' = 'success', timeout = 4000) {
  if (toastTimer) window.clearTimeout(toastTimer)
  toast.value = { visible: true, message, type }
  toastTimer = window.setTimeout(() => { toast.value.visible = false }, timeout)
}

function closeModal() {
  showModal.value = false
  form.value.url = ''
  form.value.title = ''
  form.value.check_interval = 5
}

function openChecks(id: number) {
  selectedLinkId.value = id
  showChecksModal.value = true
}

function getCookie(name: string) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
  return match ? decodeURIComponent(match[2]) : null
}

async function create() {
  try {
    // resolve CSRF token safely from meta tag or XSRF cookie
    const metaEl = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    const csrfToken = metaEl ? metaEl.getAttribute('content') : (getCookie('XSRF-TOKEN') ?? '')
    const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken ?? '', 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '' }

    const res = await fetch('/links', {
      method: 'POST',
      headers,
      credentials: 'include',
      body: JSON.stringify(form.value),
    })

    if (res.status === 302 || res.status === 419) {
      showToast('Sessão expirada. Por favor, faça login novamente.', 'error')
      window.location.href = '/login'
      return
    }

    if (res.status === 422) {
      const json = await res.json().catch(() => null)
      // build a friendly message from validation errors
      let message = json?.message ?? 'Validação falhou.'
      if (json?.errors && typeof json.errors === 'object') {
        const msgs: string[] = []
        for (const k of Object.keys(json.errors)) {
          const arr = json.errors[k]
          if (Array.isArray(arr)) msgs.push(...arr)
        }
        if (msgs.length) message = msgs.join(' ')
      }
      showToast(message, 'error')
      return
    }

    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      showToast(json.message || 'Erro', 'error')
      return
    }

    const json = await res.json().catch(() => null)
    if (json && json.data) {
      // push new link into local list
      links.value.data = links.value.data || []
      links.value.data.unshift(json.data)
    } else {
      // fallback: reload
      location.reload()
    }

    form.value.url = ''
    form.value.title = ''
    form.value.check_interval = 5
    showModal.value = false
    showToast('Link criado com sucesso', 'success')
  } catch (e) {
    alert('Erro ao criar link ' + (e instanceof Error ? e.message : ''))
  }
}

async function remove(id: number) {
  // Try to use SweetAlert2 for nicer confirmation, fallback to native confirm
  let confirmed = false
  try {
    const Swal = await import('sweetalert2').then((m) => m.default)
    const result = await Swal.fire({
      title: 'Tem certeza?',
      text: 'Deseja remover este link? Esta ação não pode ser desfeita.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, remover',
      cancelButtonText: 'Cancelar',
    })
    confirmed = !!result.isConfirmed
  } catch (e) {
    confirmed = confirm('Confirmar remoção?')
  }

  if (!confirmed) return

  const metaEl = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
  const csrfToken = metaEl ? metaEl.getAttribute('content') : (getCookie('XSRF-TOKEN') ?? '')
  const res = await fetch(`/links/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken ?? '', 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '' }, credentials: 'include' })
  if (res.status === 302 || res.status === 419) {
    showToast('Sessão expirada. Por favor, faça login novamente.', 'error')
    window.location.href = '/login'
    return
  }

  if (res.ok) {
    links.value.data = (links.value.data || []).filter((l: any) => l.id !== id)
    showToast('Link removido', 'success')
  } else {
    const json = await res.json().catch(() => ({}))
    showToast(json.message || 'Falha ao remover', 'error')
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
