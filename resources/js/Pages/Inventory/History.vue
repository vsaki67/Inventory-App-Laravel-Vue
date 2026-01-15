<script setup>
defineProps({
  item: Object,
  movements: Object,
})
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-2xl font-bold">History</h1>
        <p class="text-gray-600">
          {{ item.name }} ({{ item.unit }}) — current: {{ item.quantity }}
        </p>
      </div>

      <a href="/inventory" class="underline text-sm">← Back to Inventory</a>
    </div>

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100">
          <th class="border p-2 text-left">Type</th>
          <th class="border p-2 text-center">Qty</th>
          <th class="border p-2 text-left">Note</th>
          <th class="border p-2 text-left">Date</th>
          
        </tr>
      </thead>

      <tbody>
        <tr v-for="m in movements.data" :key="m.id">
          <td class="border p-2">{{ m.type }}</td>
          <td class="border p-2 text-center">{{ m.quantity }}</td>
          <td class="border p-2">{{ m.note ?? '-' }}</td>
          <td class="border p-2">{{ m.created_at }}</td>
        </tr>

        <tr v-if="movements.data.length === 0">
          <td colspan="4" class="p-4 text-center text-gray-500">
            No history yet
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
