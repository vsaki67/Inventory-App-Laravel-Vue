<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  items: Object,
  filters: Object,
})

/**
 * Search (debounced)
 */
const search = ref(props.filters?.search || '')
let searchTimeout = null

watch(search, (value) => {
  window.clearTimeout(searchTimeout)
  searchTimeout = window.setTimeout(() => {
    router.get(
      '/inventory',
      { search: value },
      { preserveState: true, replace: true, preserveScroll: true }
    )
  }, 300)
})

/**
 * UI message system
 */
const uiMessage = ref(null)
let messageTimeout = null
function showMessage(type, text) {
  uiMessage.value = { type, text }
  window.clearTimeout(messageTimeout)
  messageTimeout = window.setTimeout(() => (uiMessage.value = null), 4000)
}

/**
 * Modal for Create Item
 */
const isCreateModalOpen = ref(false)
function openCreateModal() {
  isCreateModalOpen.value = true
  // small UX: reset message when opening
  uiMessage.value = null
}
function closeCreateModal() {
  isCreateModalOpen.value = false
}

/**
 * Keyboard shortcuts
 * - Ctrl/Cmd + Enter: submit focused form
 * - Escape: clear message / close modal
 */
function handleKeyboardShortcut(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    const focusedElement = document.activeElement
    const form = focusedElement?.closest?.('form')
    if (form) {
      const submitBtn = form.querySelector('button[type="submit"]')
      if (submitBtn && !submitBtn.disabled) submitBtn.click()
    }
  }

  if (e.key === 'Escape') {
    uiMessage.value = null
    if (isCreateModalOpen.value) closeCreateModal()
  }
}

onMounted(() => window.addEventListener('keydown', handleKeyboardShortcut))
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut)
  window.clearTimeout(messageTimeout)
  window.clearTimeout(searchTimeout)
})

/**
 * Row builder (shared by add/deduct)
 */
const createEmptyRow = () => ({
  inventory_item_id: '',
  quantity: '',
  note: '',
  key: Date.now() + Math.random(),
})

/**
 * Shared validation
 */
function validateForm(rows) {
  for (let i = 0; i < rows.length; i++) {
    const row = rows[i]
    if (!row.inventory_item_id) {
      showMessage('error', `Row ${i + 1}: Please select an item`)
      return false
    }
    if (!row.quantity || parseFloat(row.quantity) <= 0) {
      showMessage('error', `Row ${i + 1}: Please enter a valid quantity`)
      return false
    }
  }
  return true
}

/**
 * Create Item form
 */
const createForm = useForm({
  name: '',
  unit: '',
  quantity: '',
})

function submitCreate() {
  uiMessage.value = null
  createForm.post(route('inventory.store'), {
    preserveScroll: true,
    onStart: () => showMessage('info', 'Creating item...'),
    onError: (errors) => showMessage('error', errors?.message ?? 'Please fill all fields correctly.'),
    onSuccess: () => {
      showMessage('success', 'Item created successfully.')
      createForm.reset()
      closeCreateModal()
    },
  })
}

/**
 * Bulk Add form
 */
const addForm = useForm({
  items: [createEmptyRow()],
})

function addRow() {
  addForm.items.push(createEmptyRow())
}
function removeRow(index) {
  if (addForm.items.length > 1) addForm.items.splice(index, 1)
}
function submitAdd() {
  if (!validateForm(addForm.items)) return
  uiMessage.value = null
  addForm.post(route('inventory.add'), {
    preserveScroll: true,
    onStart: () => showMessage('info', 'Adding stock...'),
    onError: (errors) => showMessage('error', errors?.message || 'Could not add stock. Please check inputs.'),
    onSuccess: () => {
      showMessage('success', 'Stock added successfully.')
      addForm.reset()
      addForm.items = [createEmptyRow()]
    },
  })
}

/**
 * Bulk Deduct form
 */
const deductForm = useForm({
  items: [createEmptyRow()],
})

function deductAddRow() {
  deductForm.items.push(createEmptyRow())
}
function deductRemoveRow(index) {
  if (deductForm.items.length > 1) deductForm.items.splice(index, 1)
}
function submitDeduct() {
  if (!validateForm(deductForm.items)) return
  uiMessage.value = null
  deductForm.post(route('inventory.deduct'), {
    preserveScroll: true,
    onStart: () => showMessage('info', 'Deducting stock...'),
    onError: (errors) => showMessage('error', errors?.message || 'Not enough stock to complete this deduction.'),
    onSuccess: () => {
      showMessage('success', 'Stock deducted successfully.')
      deductForm.reset()
      deductForm.items = [createEmptyRow()]
    },
  })
}

/**
 * Computed stats
 */
const totalItems = computed(() => props.items?.data?.length || 0)

const itemsWithLowStock = computed(() => {
  return (props.items?.data || []).filter(
    (item) => item.quantity < (item.minimum_threshold || 10)
  )
})

const lowStockCount = computed(() => itemsWithLowStock.value.length)

/**
 * Quick action helper
 */
function selectForRestock(item) {
  if (!addForm.items.length) addForm.items = [createEmptyRow()]
  addForm.items[0].inventory_item_id = item.id
  showMessage('info', `${item.name} selected for restock`)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

/**
 * Small UI helpers
 */
function statusBadge(item) {
  const min = item.minimum_threshold || 10
  if (item.quantity === 0) return { text: 'Out', cls: 'bg-rose-100 text-rose-800 border-rose-200' }
  if (item.quantity <= min) return { text: 'Low', cls: 'bg-amber-100 text-amber-800 border-amber-200' }
  return { text: 'OK', cls: 'bg-emerald-100 text-emerald-800 border-emerald-200' }
}

/**
 * Small UI: compact/collapsible forms (reduces “too many words”)
 */
const isAddOpen = ref(true)
const isDeductOpen = ref(true)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50">
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
      <!-- HERO (Dark Blue) -->
<!-- HERO (Dark Blue) -->
<div class="rounded-2xl p-5 md:p-6 shadow-sm border bg-gradient-to-r from-blue-950 via-blue-900 to-indigo-900 text-white">
  <!-- Balanced: equal vertical alignment + consistent widths -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="min-w-0">
      <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Inventory</h1>
      <p class="text-white/80 text-sm mt-1">
        Fast add/deduct • Instant search • Clean history
      </p>
    </div>

    <!-- ✅ FIXED: right side is now 2 rows, but button aligns with the input row -->
    <div class="w-full md:w-auto">
      <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-center">
        <!-- Search -->
        <div class="relative w-full md:w-96">
          <svg
            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-white/70"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>

          <input
            v-model="search"
            type="text"
            placeholder="Search products..."
            class="w-full h-[42px] pl-10 pr-10 rounded-xl bg-white/10 border border-white/15 placeholder:text-white/70 text-white focus:outline-none focus:ring-2 focus:ring-white/25"
          />

          <button
            v-if="search"
            @click="search = ''"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white"
          >
            ✕
          </button>
        </div>

        <!-- Add Product Button -->
        <button
          type="button"
          @click="openCreateModal"
          class="shrink-0 h-[42px] px-4 rounded-xl bg-white text-blue-950 font-extrabold hover:bg-white/90 active:scale-95 transition flex items-center justify-center gap-2"
        >
          <span class="text-lg leading-none">＋</span>
          Add Product
        </button>

        <!-- helper text stays under search only (doesn't affect button alignment) -->
        <p class="text-[11px] text-white/60 sm:col-start-1 -mt-2">
          Auto-search while typing
        </p>
      </div>
    </div>
  </div>

  <!-- Stats row (simplified to reduce text) -->
  <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div class="rounded-xl bg-white/10 border border-white/15 p-4">
      <p class="text-xs text-white/75">Total Items</p>
      <p class="text-2xl font-extrabold">{{ totalItems }}</p>
    </div>
    <div class="rounded-xl bg-white/10 border border-white/15 p-4">
      <p class="text-xs text-white/75">Low Stock</p>
      <p class="text-2xl font-extrabold">{{ lowStockCount }}</p>
    </div>
  </div>
</div>



      <!-- Toast message -->
      <div v-if="uiMessage" class="mt-5 animate-fade-in">
        <div
          class="rounded-xl px-4 py-3 border text-sm flex items-center justify-between shadow-sm"
          :class="{
            'bg-emerald-50 border-emerald-200 text-emerald-800': uiMessage.type === 'success',
            'bg-rose-50 border-rose-200 text-rose-800': uiMessage.type === 'error',
            'bg-sky-50 border-sky-200 text-sky-800': uiMessage.type === 'info'
          }"
        >
          <span class="font-medium">{{ uiMessage.text }}</span>
          <button type="button" @click="uiMessage = null" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
      </div>

      <!-- Forms (collapsible headers) -->
      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bulk Add -->
        <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">
          <button
            type="button"
            class="w-full px-5 py-4 border-b flex items-center justify-between text-left hover:bg-slate-50 transition"
            @click="isAddOpen = !isAddOpen"
          >
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <div>
                <h2 class="font-extrabold text-gray-900">Bulk Add</h2>
                <p class="text-xs text-gray-500">Add stock to many items</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-500">{{ addForm.items.length }} row(s)</span>
              <span class="text-gray-500 text-lg leading-none">{{ isAddOpen ? '▾' : '▸' }}</span>
            </div>
          </button>

          <div v-show="isAddOpen" class="p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="text-xs text-gray-500">Ctrl/Cmd + Enter to submit</div>
              <button
                type="button"
                class="px-3 py-2 rounded-lg bg-blue-50 text-blue-900 border border-blue-100 hover:bg-blue-100 active:scale-95 transition font-semibold"
                @click="addRow"
              >
                + Row
              </button>
            </div>

            <form @submit.prevent="submitAdd" class="space-y-3">
              <div
                v-for="(row, i) in addForm.items"
                :key="row.key || i"
                class="grid grid-cols-12 gap-2 items-end p-3 rounded-xl bg-blue-50/40 border border-blue-100"
              >
                <div class="col-span-12 md:col-span-5">
                  <label class="block text-xs text-gray-600 mb-1">Item</label>
                  <select
                    v-model="row.inventory_item_id"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  >
                    <option value="">Select item...</option>
                    <option v-for="it in items.data" :key="it.id" :value="it.id">
                      {{ it.name }} ({{ it.unit }}) — {{ it.quantity }}
                    </option>
                  </select>
                </div>

                <div class="col-span-6 md:col-span-2">
                  <label class="block text-xs text-gray-600 mb-1">Qty</label>
                  <input
                    v-model="row.quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  />
                </div>

                <div class="col-span-5 md:col-span-4">
                  <label class="block text-xs text-gray-600 mb-1">Note</label>
                  <input
                    v-model="row.note"
                    type="text"
                    placeholder="Optional"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  />
                </div>

                <div class="col-span-1 flex justify-end">
                  <button
                    v-if="addForm.items.length > 1"
                    type="button"
                    class="p-2 text-gray-400 hover:text-rose-600"
                    @click="removeRow(i)"
                    title="Remove row"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-end pt-2">
                <button
                  type="submit"
                  class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 disabled:opacity-60 transition"
                  :disabled="addForm.processing"
                >
                  Add Stock
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Bulk Deduct -->
        <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">
          <button
            type="button"
            class="w-full px-5 py-4 border-b flex items-center justify-between text-left hover:bg-slate-50 transition"
            @click="isDeductOpen = !isDeductOpen"
          >
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
              <div>
                <h2 class="font-extrabold text-gray-900">Bulk Deduct</h2>
                <p class="text-xs text-gray-500">Remove stock from many items</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-500">{{ deductForm.items.length }} row(s)</span>
              <span class="text-gray-500 text-lg leading-none">{{ isDeductOpen ? '▾' : '▸' }}</span>
            </div>
          </button>

          <div v-show="isDeductOpen" class="p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="text-xs text-gray-500">Ctrl/Cmd + Enter to submit</div>
              <button
                type="button"
                class="px-3 py-2 rounded-lg bg-blue-50 text-blue-900 border border-blue-100 hover:bg-blue-100 active:scale-95 transition font-semibold"
                @click="deductAddRow"
              >
                + Row
              </button>
            </div>

            <form @submit.prevent="submitDeduct" class="space-y-3">
              <div
                v-for="(row, i) in deductForm.items"
                :key="row.key || i"
                class="grid grid-cols-12 gap-2 items-end p-3 rounded-xl bg-blue-50/40 border border-blue-100"
              >
                <div class="col-span-12 md:col-span-5">
                  <label class="block text-xs text-gray-600 mb-1">Item</label>
                  <select
                    v-model="row.inventory_item_id"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  >
                    <option value="">Select item...</option>
                    <option v-for="it in items.data" :key="it.id" :value="it.id">
                      {{ it.name }} ({{ it.unit }}) — {{ it.quantity }}
                    </option>
                  </select>
                </div>

                <div class="col-span-6 md:col-span-2">
                  <label class="block text-xs text-gray-600 mb-1">Qty</label>
                  <input
                    v-model="row.quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  />
                </div>

                <div class="col-span-5 md:col-span-4">
                  <label class="block text-xs text-gray-600 mb-1">Note</label>
                  <input
                    v-model="row.note"
                    type="text"
                    placeholder="Optional"
                    class="border rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
                  />
                </div>

                <div class="col-span-1 flex justify-end">
                  <button
                    v-if="deductForm.items.length > 1"
                    type="button"
                    class="p-2 text-gray-400 hover:text-rose-600"
                    @click="deductRemoveRow(i)"
                    title="Remove row"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-end pt-2">
                <button
                  type="submit"
                  class="px-5 py-2.5 rounded-xl bg-rose-600 text-white font-semibold hover:bg-rose-700 disabled:opacity-60 transition"
                  :disabled="deductForm.processing"
                >
                  Deduct Stock
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Inventory table -->
      <div class="mt-7 rounded-2xl border bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-900"></span>
            <h2 class="font-extrabold text-gray-900">Items</h2>
          </div>
          <div class="text-sm text-gray-500">{{ totalItems }} shown</div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[720px]">
            <thead>
              <tr class="bg-slate-50 text-xs font-semibold text-gray-700 uppercase tracking-wider">
                <th class="p-4 text-left">Name</th>
                <th class="p-4 text-left">Unit</th>
                <th class="p-4 text-left">Stock</th>
                <th class="p-4 text-left">Status</th>
                <th class="p-4 text-left">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y">
              <tr
                v-for="item in items.data"
                :key="item.id"
                class="hover:bg-blue-50/40 transition-colors"
              >
                <td class="p-4">
                  <div class="font-semibold text-gray-900">{{ item.name }}</div>
                  <div v-if="item.sku" class="text-xs text-gray-500">{{ item.sku }}</div>
                </td>

                <td class="p-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full bg-blue-50 text-blue-900 border border-blue-100 text-xs font-semibold">
                    {{ item.unit }}
                  </span>
                </td>

                <td class="p-4">
                  <div class="text-lg font-extrabold text-gray-900">{{ item.quantity }}</div>
                  <div v-if="item.minimum_threshold" class="text-xs text-gray-500">
                    Min: {{ item.minimum_threshold }}
                  </div>
                </td>

                <td class="p-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold border" :class="statusBadge(item).cls">
                    {{ statusBadge(item).text }}
                  </span>
                </td>

                <td class="p-4">
                  <div class="flex flex-wrap gap-2">
                    <a
                      :href="route('inventory.history', item.id)"
                      class="px-3 py-1.5 rounded-lg border hover:bg-blue-50 text-sm"
                    >
                      History
                    </a>

                    <button
                      v-if="item.quantity <= (item.minimum_threshold || 10)"
                      type="button"
                      @click="selectForRestock(item)"
                      class="px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-sm"
                    >
                      Restock
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="items.data.length === 0">
                <td colspan="5" class="p-10 text-center text-gray-500">No items found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Item Modal (Dark Blue) -->
    <div
      v-if="isCreateModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      aria-modal="true"
      role="dialog"
    >
      <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="closeCreateModal"></div>

      <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl border overflow-hidden animate-fade-in">
        <div class="px-5 py-4 bg-gradient-to-r from-blue-950 via-blue-900 to-indigo-900 text-white">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-extrabold">Add Product</h3>
              <p class="text-white/80 text-sm">Create a new inventory item</p>
            </div>
            <button
              type="button"
              class="px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20"
              @click="closeCreateModal"
            >
              ✕
            </button>
          </div>
        </div>

        <form @submit.prevent="submitCreate" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-semibold text-slate-800 mb-1">Name</label>
            <input
              v-model="createForm.name"
              type="text"
              placeholder="e.g., Milk"
              class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
            />
          </div>

          <div class="grid grid-cols-12 gap-3">
            <div class="col-span-12 md:col-span-6">
              <label class="block text-sm font-semibold text-slate-800 mb-1">Unit</label>
              <select
                v-model="createForm.unit"
                class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
              >
                <option value="">Select unit</option>
                <option value="Kg">Kg</option>
                <option value="m">m</option>
                <option value="cm">cm</option>
                <option value="ml">ml</option>
                <option value="l">l</option>
                <option value="units">units</option>
              </select>
            </div>

            <div class="col-span-12 md:col-span-6">
              <label class="block text-sm font-semibold text-slate-800 mb-1">Initial Qty</label>
              <input
                v-model="createForm.quantity"
                type="number"
                step="0.01"
                min="0"
                placeholder="0"
                class="border rounded-xl px-3 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-blue-900/15 focus:border-blue-900"
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-2">
            <button type="button" class="px-4 py-2 rounded-xl border hover:bg-slate-50" @click="closeCreateModal">
              Cancel
            </button>

            <button
              type="submit"
              class="px-5 py-2.5 rounded-xl bg-blue-950 text-white font-extrabold hover:bg-blue-900 disabled:opacity-60"
              :disabled="createForm.processing"
            >
              Create
            </button>
          </div>

          <p class="text-xs text-slate-500">
            Tip: Press <span class="font-semibold">Esc</span> to close •
            <span class="font-semibold">Ctrl/Cmd + Enter</span> to submit
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.22s ease-out; }
</style>
