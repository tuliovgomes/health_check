<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/60" @click="close"></div>
    <div class="relative w-full max-w-2xl rounded-lg bg-slate-800 p-6 ring-1 ring-white/5">
      <h3 class="text-lg font-semibold text-slate-100">{{ isEditing ? 'Editar Integração' : 'Nova Integração' }}</h3>
      
      <form @submit.prevent="save" class="mt-4 space-y-4">
        <!-- Nome -->
        <div>
          <label class="block text-sm font-medium text-slate-300">Nome</label>
          <input v-model="form.name" type="text" required class="mt-1 w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" placeholder="Ex: Notificações de produção" />
        </div>

        <!-- Tipo (apenas na criação) -->
        <div v-if="!isEditing">
          <label class="block text-sm font-medium text-slate-300">Tipo de Integração</label>
          <div class="mt-2 grid grid-cols-3 gap-3">
            <button type="button" @click="isTypeAllowed('email') && (form.type = 'email')" :disabled="!isTypeAllowed('email')" :class="['flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors', form.type === 'email' ? 'border-indigo-600 bg-indigo-600/10' : 'border-slate-700 bg-slate-900/60 hover:border-slate-600', !isTypeAllowed('email') && 'opacity-40 cursor-not-allowed']">
              <Mail class="h-6 w-6 text-indigo-400" />
              <span class="text-sm font-medium text-slate-200">E-mail</span>
              <span v-if="!isTypeAllowed('email')" class="text-xs text-rose-400">Upgrade</span>
            </button>
            <button type="button" @click="isTypeAllowed('slack') && (form.type = 'slack')" :disabled="!isTypeAllowed('slack')" :class="['flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors', form.type === 'slack' ? 'border-indigo-600 bg-indigo-600/10' : 'border-slate-700 bg-slate-900/60 hover:border-slate-600', !isTypeAllowed('slack') && 'opacity-40 cursor-not-allowed']">
              <MessageSquare class="h-6 w-6 text-indigo-400" />
              <span class="text-sm font-medium text-slate-200">Slack</span>
              <span v-if="!isTypeAllowed('slack')" class="text-xs text-rose-400">Upgrade</span>
            </button>
            <button type="button" @click="isTypeAllowed('discord') && (form.type = 'discord')" :disabled="!isTypeAllowed('discord')" :class="['flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors', form.type === 'discord' ? 'border-indigo-600 bg-indigo-600/10' : 'border-slate-700 bg-slate-900/60 hover:border-slate-600', !isTypeAllowed('discord') && 'opacity-40 cursor-not-allowed']">
              <MessageCircle class="h-6 w-6 text-indigo-400" />
              <span class="text-sm font-medium text-slate-200">Discord</span>
              <span v-if="!isTypeAllowed('discord')" class="text-xs text-rose-400">Upgrade</span>
            </button>
          </div>
        </div>

        <!-- Campos específicos por tipo -->
        <div v-if="form.type === 'email'" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-300">E-mail</label>
            <input v-model="form.email" type="email" required class="mt-1 w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" placeholder="email@example.com" />
          </div>
        </div>

        <div v-else-if="form.type === 'slack'" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-300">Bot Token</label>
            <input v-model="form.token" type="text" :required="!isEditing || !form.has_token" class="mt-1 w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" placeholder="xoxb-your-token" />
            <p v-if="isEditing && form.has_token" class="mt-1 text-xs text-emerald-400">✓ Token configurado. Deixe em branco para manter o atual ou preencha para atualizar.</p>
            <p v-else class="mt-1 text-xs text-slate-500">Token do bot Slack (Ex: xoxb-...)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-300">Channel ID</label>
            <input v-model="form.channel_token" type="text" :required="!isEditing || !form.has_channel_token" class="mt-1 w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" placeholder="C1234567890" />
            <p v-if="isEditing && form.has_channel_token" class="mt-1 text-xs text-emerald-400">✓ Channel ID configurado. Deixe em branco para manter o atual ou preencha para atualizar.</p>
            <p v-else class="mt-1 text-xs text-slate-500">ID do canal do Slack</p>
          </div>
        </div>

        <div v-else-if="form.type === 'discord'" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-slate-300">Webhook URL</label>
            <input v-model="form.token" type="text" :required="!isEditing || !form.has_token" class="mt-1 w-full rounded-md bg-slate-900/60 border border-slate-700 px-3 py-2 text-sm text-slate-100" placeholder="https://discord.com/api/webhooks/..." />
            <p v-if="isEditing && form.has_token" class="mt-1 text-xs text-emerald-400">✓ Webhook configurado. Deixe em branco para manter o atual ou preencha para atualizar.</p>
            <p v-else class="mt-1 text-xs text-slate-500">URL do webhook do Discord</p>
          </div>
        </div>

        <!-- Eventos -->
        <div v-if="form.type">
          <label class="block text-sm font-medium text-slate-300">Eventos para Notificar</label>
          <div class="mt-2 space-y-2">
            <label v-for="event in filteredEvents" :key="event.value" class="flex items-center gap-2">
              <input type="checkbox" :value="event.value" v-model="form.events" class="rounded border-slate-600 bg-slate-900/60 text-indigo-600 focus:ring-indigo-600" />
              <span class="text-sm text-slate-300">{{ event.label }}</span>
            </label>
            <p v-if="filteredEvents.length < availableEvents.length" class="text-xs text-slate-500">
              Plano {{ planLimits.plan_name }}: alguns eventos não disponíveis. Faça upgrade para mais opções.
            </p>
          </div>
        </div>

        <!-- Botões -->
        <div class="flex justify-end gap-2 border-t border-slate-700 pt-4">
          <button type="button" @click="close" class="rounded-md bg-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-600">Cancelar</button>
          <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            {{ isEditing ? 'Atualizar' : 'Criar' }} Integração
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Mail, MessageSquare, MessageCircle } from 'lucide-vue-next'

interface PlanLimits {
  plan: string
  plan_name: string
  max_integrations: number | null
  allowed_types: string[]
  allowed_events: string[]
  current_count: number
}

const props = defineProps<{
  integration?: any
  planLimits: PlanLimits
}>()

const emit = defineEmits<{
  close: []
  saved: [integration: any]
}>()

const isEditing = computed(() => !!props.integration)

const availableEvents = [
  { value: 'link_down', label: 'Link Fora do Ar' },
  { value: 'link_up', label: 'Link OK' },
  { value: 'link_slow', label: 'Link Lento' },
  { value: 'link_error', label: 'Erro no Link' },
]

const filteredEvents = computed(() => {
  return availableEvents.filter(event => props.planLimits.allowed_events.includes(event.value))
})

const isTypeAllowed = (type: string) => {
  return props.planLimits.allowed_types.includes(type)
}

const form = ref({
  name: '',
  type: 'email',
  email: '',
  token: '',
  user_token: '',
  channel_token: '',
  events: [] as string[],
  has_token: false,
  has_channel_token: false,
  has_user_token: false,
})

// Preenche o form se estiver editando
watch(() => props.integration, (integration) => {
  if (integration) {
    form.value = {
      name: integration.name || '',
      type: integration.type || 'email',
      email: integration.email || '',
      token: '',
      user_token: '',
      channel_token: '',
      events: integration.events || [],
      has_token: integration.has_token || false,
      has_channel_token: integration.has_channel_token || false,
      has_user_token: integration.has_user_token || false,
    }
  }
}, { immediate: true })

function close() {
  emit('close')
}

function getCookie(name: string) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))
  return match ? decodeURIComponent(match[2]) : null
}

async function save() {
  try {
    const metaEl = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    const csrfToken = metaEl?.getAttribute('content') ?? getCookie('XSRF-TOKEN') ?? ''
    
    const method = isEditing.value ? 'PUT' : 'POST'
    const url = isEditing.value ? `/api/integrations/${props.integration.id}` : '/api/integrations'
    
    // Remove campos vazios baseado no tipo
    const payload: any = {
      name: form.value.name,
      events: form.value.events,
    }

    if (!isEditing.value) {
      payload.type = form.value.type
    }

    if (form.value.type === 'email') {
      payload.email = form.value.email
    } else if (form.value.type === 'slack') {
      if (form.value.token) payload.token = form.value.token
      if (form.value.channel_token) payload.channel_token = form.value.channel_token
    } else if (form.value.type === 'discord') {
      if (form.value.token) payload.token = form.value.token

    }

    const res = await fetch(url, {
      method,
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    if (res.status === 422) {
      const json = await res.json().catch(() => null)
      let message = json?.message ?? 'Validação falhou.'
      if (json?.errors && typeof json.errors === 'object') {
        const msgs: string[] = []
        for (const k of Object.keys(json.errors)) {
          const arr = json.errors[k]
          if (Array.isArray(arr)) msgs.push(...arr)
        }
        if (msgs.length) message = msgs.join(' ')
      }
      try {
        const Swal = await import('sweetalert2').then((m) => m.default)
        await Swal.fire({
          title: 'Erro de Validação',
          text: message,
          icon: 'error',
          confirmButtonText: 'OK'
        })
      } catch (e) {
        alert(message)
      }
      return
    }

    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      const errorMessage = json.message || 'Erro ao salvar integração'
      try {
        const Swal = await import('sweetalert2').then((m) => m.default)
        await Swal.fire({
          title: 'Erro',
          text: errorMessage,
          icon: 'error',
          confirmButtonText: 'OK'
        })
      } catch (e) {
        alert(errorMessage)
      }
      return
    }

    const json = await res.json()
    emit('saved', json.data)
  } catch (e) {
    const errorMessage = 'Erro ao salvar integração: ' + (e instanceof Error ? e.message : '')
    try {
      const Swal = await import('sweetalert2').then((m) => m.default)
      await Swal.fire({
        title: 'Erro',
        text: errorMessage,
        icon: 'error',
        confirmButtonText: 'OK'
      })
    } catch (err) {
      alert(errorMessage)
    }
  }
}
</script>
