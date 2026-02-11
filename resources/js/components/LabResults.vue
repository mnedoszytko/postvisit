<template>
  <div class="space-y-3">
    <!-- Category filter tabs -->
    <div v-if="categories.length > 1" class="flex gap-2 flex-wrap">
      <button
        v-for="cat in categories"
        :key="cat"
        class="text-xs px-3 py-1 rounded-full transition-colors"
        :class="activeCategory === cat
          ? 'bg-gray-800 text-white'
          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
        @click="activeCategory = cat"
      >
        {{ formatCategory(cat) }}
        <span class="ml-1 opacity-70">({{ countByCategory(cat) }})</span>
      </button>
    </div>

    <!-- Results table -->
    <div class="divide-y divide-gray-100">
      <div
        v-for="obs in filteredObservations"
        :key="obs.id"
        class="py-3 first:pt-0"
      >
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="font-medium text-gray-800 text-sm truncate">{{ obs.code_display }}</span>
              <span
                :class="statusClass(obs)"
                class="text-[10px] px-1.5 py-0.5 rounded-full font-medium shrink-0"
              >
                {{ statusLabel(obs) }}
              </span>
            </div>
            <div class="flex items-center gap-2 mt-0.5">
              <span v-if="obs.value_type === 'quantity'" class="text-sm text-gray-900 font-semibold">
                {{ formatValue(obs.value_quantity) }} {{ obs.value_unit }}
              </span>
              <span v-else class="text-sm text-gray-700">{{ obs.value_string }}</span>
              <span v-if="obs.reference_range_text" class="text-xs text-gray-400">
                ({{ obs.reference_range_text }})
              </span>
            </div>
          </div>
          <span v-if="obs.effective_date" class="text-xs text-gray-400 shrink-0">
            {{ formatDate(obs.effective_date) }}
          </span>
        </div>

        <!-- Range bar for quantity values -->
        <div
          v-if="obs.value_type === 'quantity' && obs.reference_range_low != null && obs.reference_range_high != null"
          class="mt-2"
        >
          <div class="h-1.5 bg-gray-100 rounded-full relative overflow-hidden">
            <div
              class="absolute inset-y-0 rounded-full"
              :class="obs.interpretation === 'N' ? 'bg-emerald-400' : obs.interpretation === 'H' ? 'bg-red-400' : 'bg-blue-400'"
              :style="rangeBarStyle(obs)"
            />
          </div>
        </div>
      </div>
    </div>

    <p v-if="filteredObservations.length === 0" class="text-sm text-gray-400 text-center py-4">
      No results in this category.
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  observations: {
    type: Array,
    required: true,
  },
});

const activeCategory = ref('all');

const categories = computed(() => {
  const cats = [...new Set(props.observations.map(o => o.category))];
  return cats.length > 1 ? ['all', ...cats] : cats;
});

const filteredObservations = computed(() => {
  if (activeCategory.value === 'all') return props.observations;
  return props.observations.filter(o => o.category === activeCategory.value);
});

function countByCategory(cat) {
  if (cat === 'all') return props.observations.length;
  return props.observations.filter(o => o.category === cat).length;
}

function formatCategory(cat) {
  const labels = {
    all: 'All',
    laboratory: 'Lab',
    'vital-signs': 'Vitals',
    exam: 'Exams',
    imaging: 'Imaging',
  };
  return labels[cat] || cat.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function statusClass(obs) {
  if (obs.interpretation === 'N') return 'bg-emerald-100 text-emerald-700';
  if (obs.interpretation === 'H') return 'bg-red-100 text-red-700';
  if (obs.interpretation === 'L') return 'bg-blue-100 text-blue-700';
  return 'bg-gray-100 text-gray-600';
}

function statusLabel(obs) {
  if (obs.interpretation === 'N') return 'Normal';
  if (obs.interpretation === 'H') return 'High';
  if (obs.interpretation === 'L') return 'Low';
  return obs.interpretation || '';
}

function formatValue(val) {
  const num = parseFloat(val);
  return Number.isInteger(num) ? num.toString() : num.toFixed(1);
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function rangeBarStyle(obs) {
  const low = parseFloat(obs.reference_range_low);
  const high = parseFloat(obs.reference_range_high);
  const val = parseFloat(obs.value_quantity);
  const range = high - low;
  if (range <= 0) return { left: '50%', width: '4px' };

  const pct = Math.max(0, Math.min(100, ((val - low) / range) * 100));
  return { left: `${Math.max(0, pct - 2)}%`, width: '4%', minWidth: '4px' };
}
</script>
