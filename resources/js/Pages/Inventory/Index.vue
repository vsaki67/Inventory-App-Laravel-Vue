<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  items: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')

watch(search, (value) => {
  router.get('/inventory', { search: value }, { preserveState: true, replace: true })
})

// Bulk Add form
const addForm = useForm({
  items: [{ inventory_item_id: '', quantity: '', note: '' }],
})

function addRow() {
  addForm.items.push({ inventory_item_id: '', quantity: '', note: '' })
}

function removeRow(i) {
  addForm.items.splice(i, 1)
}

function submitAdd() {
  addForm.post(route('inventory.add'), {
    preserveScroll: true,
    onSuccess: () => {
      addForm.reset()
      addForm.items = [{ inventory_item_id: '', quantity: '', note: '' }]
    },
  })
}

// Bulk Deduct form
const deductForm = useForm({
  items: [{ inventory_item_id: '', quantity: '', note: '' }],
})

function deductAddRow() {
  deductForm.items.push({ inventory_item_id: '', quantity: '', note: '' })
}

function deductRemoveRow(i) {
  deductForm.items.splice(i, 1)
}

function submitDeduct() {
  deductForm.post(route('inventory.deduct'), {
    preserveScroll: true,
    onError: (errors) => {
      alert(errors?.message ?? 'Not enough stock to complete this deduction.')
    },
    onSuccess: () => {
      deductForm.reset()
      deductForm.items = [{ inventory_item_id: '', quantity: '', note: '' }]
    },
  })
}
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Inventory</h1>

    <input
      v-model="search"
      type="text"
      placeholder="Search item..."
      class="border rounded px-3 py-2 mb-4 w-full"
    />

    <!-- Bulk Add Stock -->
    <div class="border rounded p-4 mb-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Bulk Add Stock</h2>

        <button type="button" class="px-3 py-2 border rounded" @click="addRow">
          + Add Row
        </button>
      </div>

      <form @submit.prevent="submitAdd" class="space-y-3">
        <div
          v-for="(row, i) in addForm.items"
          :key="`add-${i}`"
          class="grid grid-cols-12 gap-2 items-center"
        >
          <select v-model="row.inventory_item_id" class="border rounded px-2 py-2 col-span-4">
            <option value="">Select item</option>
            <option v-for="it in items.data" :key="it.id" :value="it.id">
              {{ it.name }} ({{ it.unit }})
            </option>
          </select>

          <input
            v-model="row.quantity"
            type="number"
            step="0.01"
            min="0"
            placeholder="Qty"
            class="border rounded px-2 py-2 col-span-2"
          />

          <input
            v-model="row.note"
            type="text"
            placeholder="Note (optional)"
            class="border rounded px-2 py-2 col-span-5"
          />

          <button
            v-if="addForm.items.length > 1"
            type="button"
            class="col-span-1 text-sm underline"
            @click="removeRow(i)"
          >
            Remove
          </button>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            class="px-4 py-2 bg-black text-white rounded"
            :disabled="addForm.processing"
          >
            Add Stock
          </button>

          <span v-if="addForm.recentlySuccessful" class="text-sm text-green-600">
            Added!
          </span>
        </div>
      </form>
    </div>

    <!-- Bulk Deduct Stock -->
    <div class="border rounded p-4 mb-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Bulk Deduct Stock</h2>

        <button type="button" class="px-3 py-2 border rounded" @click="deductAddRow">
          + Add Row
        </button>
      </div>

      <form @submit.prevent="submitDeduct" class="space-y-3">
        <div
          v-for="(row, i) in deductForm.items"
          :key="`deduct-${i}`"
          class="grid grid-cols-12 gap-2 items-center"
        >
          <select v-model="row.inventory_item_id" class="border rounded px-2 py-2 col-span-4">
            <option value="">Select item</option>
            <option v-for="it in items.data" :key="it.id" :value="it.id">
              {{ it.name }} ({{ it.unit }})
            </option>
          </select>

          <input
            v-model="row.quantity"
            type="number"
            step="0.01"
            min="0"
            placeholder="Qty"
            class="border rounded px-2 py-2 col-span-2"
          />

          <input
            v-model="row.note"
            type="text"
            placeholder="Note (optional)"
            class="border rounded px-2 py-2 col-span-5"
          />

          <button
            v-if="deductForm.items.length > 1"
            type="button"
            class="col-span-1 text-sm underline"
            @click="deductRemoveRow(i)"
          >
            Remove
          </button>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            class="px-4 py-2 bg-black text-white rounded"
            :disabled="deductForm.processing"
          >
            Deduct Stock
          </button>

          <span v-if="deductForm.recentlySuccessful" class="text-sm text-green-600">
            Deducted!
          </span>
        </div>
      </form>
    </div>

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="border p-2 text-left">Name</th>
          <th class="border p-2 text-center">Unit</th>
          <th class="border p-2 text-center">Quantity</th>
          <th class="border p-2 text-center">History</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="item in items.data" :key="item.id">
          <td class="border p-2">{{ item.name }}</td>
          <td class="border p-2 text-center">{{ item.unit }}</td>
          <td class="border p-2 text-center">{{ item.quantity }}</td>
          <td class="border p-2 text-center">
            <a :href="route('inventory.history', item.id)" class="underline text-sm">
              View
            </a>
          </td>
        </tr>

        <tr v-if="items.data.length === 0">
          <td colspan="4" class="p-4 text-center text-gray-500">
            No items found
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
