<template>
  <div class="flex flex-wrap gap-1.5 mr-8">
    <span
      v-for="source in sources"
      :key="source.key"
      :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium', colorClass(source.key)]"
    >
      <component :is="iconFor(source.key)" class="w-2.5 h-2.5" />
      {{ source.label }}
    </span>
  </div>
</template>

<script setup>
import { h } from 'vue';

defineProps({
    sources: { type: Array, required: true },
});

const icons = {
    visit_notes: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z' }),
    ]),
    practitioner: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0' }),
    ]),
    openfda: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z' }),
    ]),
    guidelines: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25' }),
    ]),
    patient_record: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm-3.375 6.166a4.5 4.5 0 017.5 0' }),
    ]),
};

const colors = {
    visit_notes: 'bg-blue-50 text-blue-700',
    practitioner: 'bg-violet-50 text-violet-700',
    openfda: 'bg-amber-50 text-amber-700',
    guidelines: 'bg-emerald-50 text-emerald-700',
    patient_record: 'bg-rose-50 text-rose-700',
};

function iconFor(key) {
    return icons[key] || icons.visit_notes;
}

function colorClass(key) {
    return colors[key] || 'bg-gray-50 text-gray-600';
}
</script>
