<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/60" @click="close"></div>
    <div class="relative w-full max-w-2xl rounded-lg bg-slate-800 p-6 ring-1 ring-white/5">
      <div class="flex items-start justify-between">
        <h3 class="text-lg font-semibold text-slate-100">Atividade do link</h3>
        <button @click="close" class="text-slate-400 hover:text-slate-200">Fechar</button>
      </div>

      <div class="mt-4">
            <div v-if="loading" class="text-sm text-slate-400">Carregando...</div>
            <div v-else>
              <div v-if="!checks.length" class="text-sm text-slate-400">Nenhuma verificação encontrada.</div>
              <ul v-else class="space-y-2 text-sm text-slate-300">
                <li v-for="c in checks" :key="c.id" class="rounded-md bg-slate-900/60 p-3">
                  <div class="text-xs text-slate-400">{{ formatDate(c.created_at) }}</div>
                  <div class="mt-1">Status: <span class="font-medium">{{ c.status }}</span> — HTTP: {{ c.http_status || '—' }} — {{ c.response_time_ms || '—' }}ms</div>
                </li>
              </ul>

              <div v-if="pagination" class="mt-4 flex items-center justify-between text-sm text-slate-400">
                <div>Mostrando página {{ pagination.current_page }} de {{ pagination.last_page }} — {{ pagination.total }} registros</div>
                <div class="flex items-center gap-2">
                  <button :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)" class="rounded-md bg-slate-700 px-2 py-1 text-slate-200 disabled:opacity-50">Anterior</button>
                  <button :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)" class="rounded-md bg-slate-700 px-2 py-1 text-slate-200 disabled:opacity-50">Próxima</button>
                </div>
              </div>
            </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

const props = defineProps<{ linkId: number }>()
const emit = defineEmits(['close'])

const checks = ref<any[]>([])
const pagination = ref<any | null>(null)
const loading = ref(false)
const currentPage = ref(1)

function close() {
  emit('close')
}

function formatDate(v: string | null) {
  return v ? new Date(v).toLocaleString() : '—'
}

async function load(page = 1) {
  if (!props.linkId) return
  loading.value = true
  try {
    const res = await fetch(`/links/${props.linkId}?page=${page}`, { 
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    if (!res.ok) {
      checks.value = []
      pagination.value = null
      loading.value = false
      return
    }
    const json = await res.json().catch(() => null)
    const checksPage = json?.data?.checks ?? json?.checks ?? null
    if (checksPage && Array.isArray(checksPage.data)) {
      checks.value = checksPage.data
      pagination.value = {
        current_page: checksPage.current_page,
        last_page: checksPage.last_page,
        per_page: checksPage.per_page,
        total: checksPage.total,
      }
      currentPage.value = pagination.value.current_page
    } else if (Array.isArray(checksPage)) {
      checks.value = checksPage
      pagination.value = null
    } else {
      checks.value = []
      pagination.value = null
    }
  } catch (e) {
    checks.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

function changePage(page: number) {
  if (!pagination.value) return
  const p = Math.max(1, Math.min(page, pagination.value.last_page))
  load(p)
}

onMounted(() => load(1))
watch(() => props.linkId, (n, o) => {
  if (n) load(1)
})
</script>

<style scoped>
</style>
