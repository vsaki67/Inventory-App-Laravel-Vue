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

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="border p-2 text-left">Name</th>
          <th class="border p-2">Unit</th>
          <th class="border p-2">Quantity</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items.data" :key="item.id">
          <td class="border p-2">{{ item.name }}</td>
          <td class="border p-2 text-center">{{ item.unit }}</td>
          <td class="border p-2 text-center">{{ item.quantity }}</td>
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
