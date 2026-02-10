<template>
  <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-3">
    <div class="flex items-start justify-between">
      <div>
        <h3 class="font-semibold text-gray-900">{{ medication.name || medication.generic_name }}</h3>
        <p class="text-sm text-gray-500">
          {{ medication.dose }} {{ medication.frequency }}
        </p>
      </div>
      <span
        v-if="medication.status"
        :class="[
          'text-xs font-medium px-2.5 py-1 rounded-full',
          medication.status === 'new' ? 'bg-emerald-100 text-emerald-700' :
          medication.status === 'changed' ? 'bg-amber-100 text-amber-700' :
          'bg-gray-100 text-gray-600'
        ]"
      >
        {{ medication.status }}
      </span>
    </div>

    <p v-if="medication.reason" class="text-sm text-gray-600">
      <span class="font-medium">Prescribed for:</span> {{ medication.reason }}
    </p>

    <p v-if="medication.side_effects" class="text-sm text-gray-500">
      <span class="font-medium">Watch for:</span> {{ medication.side_effects }}
    </p>

    <ExplainButton @click="$emit('explain')" />
  </div>
</template>

<script setup>
import ExplainButton from '@/components/ExplainButton.vue';

defineProps({
    medication: { type: Object, required: true },
});

defineEmits(['explain']);
</script>
