<template>
  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="mt-6 text-2xl font-semibold text-slate-100">Integrações</h1>
          <p class="mt-1 text-sm text-slate-400">Configure notificações para Email, Slack e GitHub</p>
        </div>
        <button @click="createIntegration" class="mt-6 inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
          <Plus class="h-4 w-4" />
          Nova Integração
        </button>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div v-if="!integrations.length" class="col-span-full rounded-lg bg-slate-800/60 px-6 py-8 text-center text-slate-400 ring-1 ring-white/5">
          Nenhuma integração criada ainda.
        </div>

        <div v-for="integration in integrations" :key="integration.id" class="overflow-hidden rounded-lg bg-slate-800/60 ring-1 ring-white/5">
          <div class="px-6 py-4">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600/20">
                  <Mail v-if="integration.type === 'email'" class="h-5 w-5 text-indigo-400" />
                  <MessageSquare v-else-if="integration.type === 'slack'" class="h-5 w-5 text-indigo-400" />
                  <MessageCircle v-else class="h-5 w-5 text-indigo-400" />
                </div>
                <div>
                  <h3 class="text-base font-medium text-slate-100">{{ integration.name }}</h3>
                  <p class="text-xs text-slate-400">{{ integration.type_label }}</p>
                </div>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <div v-if="integration.email" class="text-sm text-slate-300">
                <span class="text-slate-500">Email:</span> {{ integration.email }}
              </div>
              <div class="flex flex-wrap gap-1">
                <span v-for="eventLabel in integration.event_labels" :key="eventLabel" class="rounded-md bg-indigo-600/20 px-2 py-1 text-xs text-indigo-300">
                  {{ eventLabel }}
                </span>
              </div>
              <div v-if="integration.last_notification_at" class="text-xs text-slate-500">
                Última notificação: {{ new Date(integration.last_notification_at).toLocaleString() }}
              </div>
            </div>

            <div class="mt-4 flex items-center gap-2 border-t border-slate-700 pt-4">
              <button @click="testIntegration(integration)" class="inline-flex items-center gap-1 rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-600">
                <Zap class="h-3 w-3" />
                Testar
              </button>
              <button @click="editIntegration(integration)" class="inline-flex items-center gap-1 rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-600">
                <Settings class="h-3 w-3" />
                Editar
              </button>
              <button @click="deleteIntegration(integration)" class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-500">
                <Trash class="h-3 w-3" />
                Remover
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <IntegrationModal 
      v-if="showCreateModal || showEditModal" 
      :integration="editingIntegration" 
      :planLimits="planLimits"
      @close="closeModals" 
      @saved="handleSaved" 
    />

    <div v-if="toast.visible" class="hc-toast">
      <div :class="['rounded-md p-3 shadow-lg', toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white']" role="alert">
        <p class="text-sm">{{ toast.message }}</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import IntegrationModal from './IntegrationModal.vue'
import { Plus, Mail, MessageSquare, MessageCircle, Zap, Settings, Trash } from 'lucide-vue-next'

interface PlanLimits {
  plan: string
  plan_name: string
  max_integrations: number | null
  allowed_types: string[]
  allowed_events: string[]
  current_count: number
}

const props = defineProps<{
  planLimits: PlanLimits
}>()

const integrations = ref<any[]>([])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingIntegration = ref<any>(null)

const toast = ref({ visible: false, message: '', type: 'success' })
let toastTimer: number | undefined = undefined

function showToast(message: string, type: 'success' | 'error' = 'success', timeout = 4000) {
  if (toastTimer) window.clearTimeout(toastTimer)
  toast.value = { visible: true, message, type }
  toastTimer = window.setTimeout(() => { toast.value.visible = false }, timeout)
}

function getCookie(name: string) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
  return match ? decodeURIComponent(match[2]) : null
}

function createIntegration() {
  // Verifica limite do plano
  if (props.planLimits.max_integrations !== null && integrations.value.length >= props.planLimits.max_integrations) {
    showToast(`Plano ${props.planLimits.plan_name} permite até ${props.planLimits.max_integrations} integração(s). Faça upgrade para adicionar mais.`, 'error', 6000)
    return
  }
  showCreateModal.value = true
}

async function loadIntegrations() {
  try {
    const res = await fetch('/api/integrations', {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    
    if (res.ok) {
      const json = await res.json()
      integrations.value = json.data || []
    }
  } catch (e) {
    console.error('Erro ao carregar integrações:', e)
  }
}

async function testIntegration(integration: any) {
  try {
    const metaEl = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    const csrfToken = metaEl?.getAttribute('content') ?? getCookie('XSRF-TOKEN') ?? ''
    
    const res = await fetch(`/api/integrations/${integration.id}/test`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
        'Accept': 'application/json',
      },
    })
    
    if (res.ok) {
      const json = await res.json()
      showToast(json.message, 'success')
    } else {
      const json = await res.json().catch(() => ({}))
      showToast(json.message || 'Erro ao testar integração', 'error')
    }
  } catch (e) {
    showToast('Erro ao testar integração', 'error')
  }
}

function editIntegration(integration: any) {
  editingIntegration.value = integration
  showEditModal.value = true
}

async function deleteIntegration(integration: any) {
  let confirmed = false
  try {
    const Swal = await import('sweetalert2').then((m) => m.default)
    const result = await Swal.fire({
      title: 'Tem certeza?',
      text: 'Deseja remover esta integração? Esta ação não pode ser desfeita.',
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

  try {
    const metaEl = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    const csrfToken = metaEl?.getAttribute('content') ?? getCookie('XSRF-TOKEN') ?? ''
    
    const res = await fetch(`/api/integrations/${integration.id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
        'Accept': 'application/json',
      },
    })
    
    if (res.ok) {
      integrations.value = integrations.value.filter(i => i.id !== integration.id)
      showToast('Integração removida com sucesso', 'success')
    } else {
      const json = await res.json().catch(() => ({}))
      showToast(json.message || 'Erro ao remover integração', 'error')
    }
  } catch (e) {
    showToast('Erro ao remover integração', 'error')
  }
}

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  editingIntegration.value = null
}

function handleSaved(integration: any) {
  const index = integrations.value.findIndex(i => i.id === integration.id)
  if (index >= 0) {
    integrations.value[index] = integration
  } else {
    integrations.value.unshift(integration)
  }
  closeModals()
  showToast('Integração salva com sucesso', 'success')
}

onMounted(() => {
  loadIntegrations()
})
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
