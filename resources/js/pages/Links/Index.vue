<template>
  <Layout>
    <div class="container">
      <h1 class="mt-6">Meus Links</h1>

      <form @submit.prevent="create" class="mt-4 space-y-2">
        <input v-model="form.url" placeholder="https://example.com" class="input" />
        <input v-model="form.title" placeholder="Título (opcional)" class="input" />
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
import { Inertia } from '@inertiajs/inertia'
import { usePage } from '@inertiajs/inertia-vue3'

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
