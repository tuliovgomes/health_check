<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/60" @click="close()"></div>
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
          <button type="button" @click="close()" class="rounded-md bg-slate-700 px-3 py-2 text-sm text-slate-200">Cancelar</button>
          <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Criar link</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const emit = defineEmits<{
  close: []
  created: [link: any]
  error: [message: string]
}>()

const form = ref({ url: '', title: '', check_interval: 5 })

function close() {
  form.value = { url: '', title: '', check_interval: 5 }
  emit('close')
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
      emit('error', 'Sessão expirada. Por favor, faça login novamente.')
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
      emit('error', message)
      return
    }

    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      emit('error', json.message || 'Erro')
      return
    }

    const json = await res.json().catch(() => null)
    
    form.value = { url: '', title: '', check_interval: 5 }
    
    if (json && json.data) {
      emit('created', json.data)
    } else {
      // fallback: reload
      location.reload()
    }
  } catch (e) {
    emit('error', 'Erro ao criar link ' + (e instanceof Error ? e.message : ''))
  }
}
</script>

<style scoped>
</style>
