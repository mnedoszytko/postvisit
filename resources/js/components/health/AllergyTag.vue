<template>
  <span
    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
    :class="severityClasses"
  >
    {{ name }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    severity: { type: String, default: 'mild' },
});

const severityClasses = computed(() => {
    switch (props.severity) {
        case 'severe': return 'bg-red-100 text-red-700';
        case 'moderate': return 'bg-amber-100 text-amber-700';
        default: return 'bg-gray-100 text-gray-600';
    }
});
</script>
