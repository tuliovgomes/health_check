<template>
  <Layout>
    <div class="container">
      <h1 class="mt-6">Meus Links</h1>

      <form @submit.prevent="create" class="mt-4 space-y-2">
        <input v-model="form.url" placeholder="https://example.com" class="input" />
        <input v-model="form.title" placeholder="Título (opcional)" class="input" />

        <div class="flex gap-2 items-center">
          <label class="text-sm">Intervalo de verificação:</label>
          <select v-model.number="form.check_interval" class="input w-48">
            <option :value="1">1 minuto</option>
            <option :value="5">5 minutos</option>
            <option :value="15">15 minutos</option>
            <option :value="30">30 minutos</option>
            <option :value="60">1 hora</option>
          </select>
        </div>

        <button class="btn btn-primary">Criar link</button>
      </form>

      <div class="mt-6">
        <div v-if="links.data.length === 0">Nenhum link criado ainda.</div>
        <ul>
          <li v-for="link in links.data" :key="link.id" class="p-2 border rounded mb-2">
            <div class="flex justify-between items-center">
              <div>
                <div class="font-semibold">{{ link.title || link.code }}</div>
                <div class="text-sm text-muted">{{ link.url }}</div>
                <div class="text-xs text-muted mt-1">Última verificação: {{ link.last_checked_at || '—' }} • Intervalo: {{ link.check_interval }} min</div>
                <div class="text-xs mt-1">Próxima verificação: {{ link.last_checked_at ? new Date(new Date(link.last_checked_at).getTime() + link.check_interval * 60000).toLocaleString() : '—' }}</div>

                <div v-if="link.checks && link.checks.length" class="mt-2 text-xs">
                  <div class="font-medium">Histórico (últimos {{ link.checks.length }}):</div>
                  <ul class="mt-1">
                    <li v-for="c in link.checks" :key="c.id">[{{ c.created_at }}] {{ c.status }} — {{ c.http_status || '—' }} — {{ c.response_time_ms || '—' }}ms</li>
                  </ul>
                </div>
              </div>
              <div>
                <button class="btn btn-danger" @click.prevent="remove(link.id)">Remover</button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = usePage().props.value
const links = ref(props.links ?? { data: [] })

const form = ref({ url: '', title: '' })

async function create() {
  try {
    const res = await fetch('/links', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content },
      body: JSON.stringify(form.value),
    })

    if (!res.ok) {
      const json = await res.json()
      alert(json.message || 'Erro')
      return
    }

    form.value.url = ''
    form.value.title = ''
    location.reload()
  } catch (e) {
    alert('Erro ao criar link')
  }
}

async function remove(id: number) {
  if (!confirm('Confirmar remoção?')) return
  await fetch(`/links/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content } })
  location.reload()
}
</script>
