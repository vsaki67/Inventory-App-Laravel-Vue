<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  item: Object,
  movements: Object,
})

/**
 * Local UI helpers (no backend changes needed)
 */
const typeFilter = ref('all')
const noteSearch = ref('')
const sortOrder = ref('desc') // desc = newest first

const filteredMovements = computed(() => {
  let rows = props.movements?.data ? [...props.movements.data] : []

  if (typeFilter.value !== 'all') {
    rows = rows.filter((m) => (m.type || '').toLowerCase() === typeFilter.value)
  }

  const q = noteSearch.value.trim().toLowerCase()
  if (q) {
    rows = rows.filter((m) => {
      const note = (m.note || '').toLowerCase()
      const type = (m.type || '').toLowerCase()
      return note.includes(q) || type.includes(q)
    })
  }

  rows.sort((a, b) => {
    const da = new Date(a.created_at).getTime()
    const db = new Date(b.created_at).getTime()
    return sortOrder.value === 'desc' ? db - da : da - db
  })

  return rows
})

const totalAdded = computed(() => {
  return filteredMovements.value
    .filter((m) => (m.type || '').toLowerCase() === 'add')
    .reduce((sum, m) => sum + Number(m.quantity || 0), 0)
})

const totalDeducted = computed(() => {
  return filteredMovements.value
    .filter((m) => (m.type || '').toLowerCase() === 'deduct')
    .reduce((sum, m) => sum + Number(m.quantity || 0), 0)
})

const netChange = computed(() => totalAdded.value - totalDeducted.value)

const formatDateTime = (value) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString()
}

/**
 * Dark-blue palette styling helpers
 */
const typeBadgeClass = (type) => {
  const t = (type || '').toLowerCase()
  if (t === 'add') return 'bg-emerald-100 text-emerald-800 border-emerald-200'
  if (t === 'deduct') return 'bg-rose-100 text-rose-800 border-rose-200'
  return 'bg-slate-100 text-slate-700 border-slate-200'
}

const qtyClass = (type) => {
  const t = (type || '').toLowerCase()
  if (t === 'add') return 'text-emerald-700'
  if (t === 'deduct') return 'text-rose-700'
  return 'text-slate-800'
}

function resetFilters() {
  typeFilter.value = 'all'
  noteSearch.value = ''
  sortOrder.value = 'desc'
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50">
    <div class="p-4 md:p-6 max-w-6xl mx-auto">
      <!-- Hero Header (Dark Blue) -->
      <div
        class="rounded-2xl p-5 md:p-6 shadow-sm border bg-gradient-to-r from-blue-950 via-blue-900 to-indigo-900 text-white"
      >
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Inventory History</h1>
            <p class="text-white/80 mt-1 text-sm">
              <span class="font-semibold text-white">{{ item.name }}</span>
              <span class="text-white/70">({{ item.unit }})</span>
              <span class="text-white/40">—</span>
              current stock:
              <span class="font-extrabold text-white">{{ item.quantity }}</span>
            </p>
          </div>

          <a
            href="/inventory"
            class="inline-flex items-center gap-2 text-sm px-4 py-2.5 rounded-xl bg-white/10 border border-white/15 hover:bg-white/15 active:scale-95 transition"
          >
            <span>←</span>
            Back to Inventory
          </a>
        </div>

        <!-- Stats -->
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="rounded-xl bg-white/10 border border-white/15 p-4">
            <p class="text-xs text-white/75">Total Added (filtered)</p>
            <p class="text-2xl font-extrabold">{{ totalAdded.toFixed(2) }}</p>
          </div>
          <div class="rounded-xl bg-white/10 border border-white/15 p-4">
            <p class="text-xs text-white/75">Total Deducted (filtered)</p>
            <p class="text-2xl font-extrabold">{{ totalDeducted.toFixed(2) }}</p>
          </div>
          <div class="rounded-xl bg-white/10 border border-white/15 p-4">
            <p class="text-xs text-white/75">Net Change (filtered)</p>
            <p
              class="text-2xl font-extrabold"
              :class="netChange >= 0 ? 'text-emerald-200' : 'text-rose-200'"
            >
              {{ netChange >= 0 ? '+' : '' }}{{ netChange.toFixed(2) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Controls -->
      <div class="mt-6 rounded-2xl border bg-white shadow-sm p-4">
        <div class="grid grid-cols-12 gap-3 items-end">
          <div class="col-span-12 md:col-span-3">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Type</label>
            <select
              v-model="typeFilter"
              class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"
            >
              <option value="all">All</option>
              <option value="add">Add</option>
              <option value="deduct">Deduct</option>
            </select>
          </div>

          <div class="col-span-12 md:col-span-6">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Search note / type</label>
            <input
              v-model="noteSearch"
              type="text"
              placeholder="e.g., damaged, supplier, add..."
              class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"
            />
          </div>

          <div class="col-span-12 md:col-span-3">
            <label class="block text-sm font-semibold text-slate-800 mb-1">Sort</label>
            <div class="flex gap-2">
              <select
                v-model="sortOrder"
                class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900"
              >
                <option value="desc">Newest first</option>
                <option value="asc">Oldest first</option>
              </select>
              <button
                type="button"
                class="px-3 py-2.5 rounded-xl border bg-slate-50 hover:bg-slate-100 transition"
                @click="resetFilters"
                title="Reset filters"
              >
                Reset
              </button>
            </div>
          </div>
        </div>

        <div class="mt-3 text-xs text-slate-500 flex flex-wrap gap-x-4 gap-y-1">
          <span>
            Showing:
            <span class="font-semibold text-slate-700">{{ filteredMovements.length }}</span>
            row(s)
          </span>
          <span v-if="movements?.total">
            Total in system:
            <span class="font-semibold text-slate-700">{{ movements.total }}</span>
          </span>
        </div>
      </div>

      <!-- Table -->
      <div class="mt-5 border rounded-2xl bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between bg-gradient-to-r from-blue-950 to-blue-900 text-white">
          <div>
            <h2 class="font-extrabold text-lg">Movement History</h2>
            <p class="text-sm text-white/75">Adds and deductions recorded for this item</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[720px]">
            <thead>
              <tr class="bg-slate-50 text-xs font-semibold text-slate-700 uppercase tracking-wider">
                <th class="p-4 text-left">Type</th>
                <th class="p-4 text-center">Qty</th>
                <th class="p-4 text-left">Note</th>
                <th class="p-4 text-left">Date</th>
              </tr>
            </thead>

            <tbody class="divide-y">
              <tr
                v-for="m in filteredMovements"
                :key="m.id"
                class="hover:bg-blue-50/40 transition-colors"
              >
                <td class="p-4">
                  <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border"
                    :class="typeBadgeClass(m.type)"
                  >
                    {{ (m.type || 'unknown').toUpperCase() }}
                  </span>
                </td>

                <td class="p-4 text-center">
                  <span class="font-extrabold" :class="qtyClass(m.type)">
                    {{ (m.type || '').toLowerCase() === 'deduct' ? '-' : '+' }}
                    {{ Number(m.quantity || 0).toFixed(2) }}
                  </span>
                </td>

                <td class="p-4">
                  <div class="text-sm text-slate-900">
                    {{ m.note ?? '-' }}
                  </div>
                </td>

                <td class="p-4">
                  <div class="text-sm text-slate-700">{{ formatDateTime(m.created_at) }}</div>
                </td>
              </tr>

              <tr v-if="filteredMovements.length === 0">
                <td colspan="4" class="p-12 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                    <p class="text-lg font-semibold text-slate-600">No history found</p>
                    <p class="text-sm mt-1">Try changing filters/search, or add/deduct stock to create movements.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="movements?.links?.length" class="px-5 py-4 border-t flex flex-wrap gap-2 bg-slate-50">
          <a
            v-for="(link, idx) in movements.links"
            :key="idx"
            :href="link.url || '#'"
            class="px-3 py-1.5 rounded-xl border text-sm font-semibold"
            :class="[
              link.active ? 'bg-blue-950 text-white border-blue-950' : 'hover:bg-white',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>

      <div class="mt-6 text-xs text-slate-500">
        Tip: Use filters to isolate <span class="font-semibold text-slate-700">adds</span> /
        <span class="font-semibold text-slate-700">deductions</span> and search notes quickly.
      </div>
    </div>
  </div>
</template>
