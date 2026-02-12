<template>
  <div class="flex gap-2">
    <button
      v-for="option in options"
      :key="option.value"
      class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors"
      :class="modelValue === option.value
        ? 'bg-emerald-600 text-white shadow-sm'
        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      @click="$emit('update:modelValue', option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: string;
}>();

defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const options = [
    { label: '7d', value: '7d' },
    { label: '30d', value: '30d' },
    { label: '90d', value: '90d' },
    { label: '1y', value: '1y' },
];
</script>
