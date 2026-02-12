<template>
  <div class="space-y-6">
    <div v-if="labResults.length > 0" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
      <div class="p-5 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">Lab Results</h2>
        <p class="text-xs text-gray-500 mt-1">{{ labResults.length }} results, sorted by most recent</p>
      </div>
      <div class="divide-y divide-gray-100">
        <div
          v-for="lab in labResults"
          :key="lab.id"
          class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors"
        >
          <div>
            <p class="font-medium text-gray-800 text-sm">{{ lab.code_display }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(lab.effective_date) }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-medium" :class="interpretationColor(lab.interpretation)">
              {{ lab.value_type === 'quantity' ? `${formatQuantity(lab.value_quantity)} ${lab.value_unit}` : lab.value_string }}
            </p>
            <p v-if="lab.reference_range_text" class="text-[10px] text-gray-400">{{ lab.reference_range_text }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="labResults.length === 0" class="text-center py-12 text-gray-400">
      No lab results available yet.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    observations: { type: Array, default: () => [] },
});

const labResults = computed(() =>
    props.observations
        .filter(o => o.category === 'laboratory')
        .sort((a, b) => new Date(b.effective_date) - new Date(a.effective_date))
);

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatQuantity(val) {
    const num = parseFloat(val);
    if (isNaN(num)) return val;
    return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(2)).toString();
}

function interpretationColor(interp) {
    if (interp === 'H') return 'text-red-600';
    if (interp === 'L') return 'text-blue-600';
    return 'text-gray-800';
}
</script>
