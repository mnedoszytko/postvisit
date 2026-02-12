<template>
  <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none flex-wrap">
    <button
      v-for="tab in tabs"
      :key="tab.id"
      class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
      :class="modelValue === tab.id
        ? 'bg-emerald-600 text-white shadow-sm'
        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      @click="$emit('update:modelValue', tab.id)"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
      </svg>
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, required: true },
});

defineEmits(['update:modelValue']);

const tabs = [
    {
        id: 'profile',
        label: 'Health Profile',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
    {
        id: 'vitals',
        label: 'Vitals',
        icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    },
    {
        id: 'labs',
        label: 'Lab Results',
        icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5',
    },
    {
        id: 'services',
        label: 'Connected Services',
        icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
    },
    {
        id: 'documents',
        label: 'Documents',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
];
</script>
