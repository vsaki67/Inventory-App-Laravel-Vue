<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  items: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')

watch(search, (value) => {
  router.get(
    '/inventory',
    { search: value },
    { preserveState: true, replace: true }
  )
})
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

    <div class="border rounded p-4 mb-4">
      <h2 class="font-semibold mb-2">Quick Add Stock (temporary)</h2>

      <form
        @submit.prevent="router.post(route('inventory.add'), {
          items: [{ inventory_item_id: items.data[0]?.id, quantity: 1, note: 'quick add' }]
        })"
        class="flex items-center gap-3"
      >
        <button type="submit" class="px-3 py-2 bg-black text-white rounded">
          +1 to first item
        </button>

        <span class="text-sm text-gray-600">
          Adds 1 to the first row item (for testing only)
        </span>
      </form>
    </div>


    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="border p-2 text-left">Name</th>
          <th class="border p-2">Unit</th>
          <th class="border p-2">Quantity</th>
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
          <td colspan="3" class="p-4 text-center text-gray-500">
            No items found
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
