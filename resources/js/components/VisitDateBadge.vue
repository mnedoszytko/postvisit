<template>
  <div
    class="shrink-0 rounded-lg overflow-hidden border border-indigo-200 bg-white"
    :class="sizeClasses.wrapper"
  >
    <div class="bg-indigo-600 text-white text-center font-semibold leading-none" :class="sizeClasses.header">
      {{ monthAbbr }}
    </div>
    <div class="text-center font-bold text-gray-900 leading-none" :class="sizeClasses.day">
      {{ day }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    date: { type: String, default: null },
    size: { type: String, default: 'md', validator: v => ['sm', 'md'].includes(v) },
});

const parsed = computed(() => {
    if (!props.date) return null;
    return new Date(props.date);
});

const day = computed(() => {
    if (!parsed.value) return '—';
    return parsed.value.getDate();
});

const monthAbbr = computed(() => {
    if (!parsed.value) return '···';
    return parsed.value.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
});

const sizeClasses = computed(() => {
    if (props.size === 'sm') {
        return {
            wrapper: 'w-9 h-10',
            header: 'text-[8px] py-0.5 tracking-wider',
            day: 'text-sm pt-0.5',
        };
    }
    return {
        wrapper: 'w-11 h-12',
        header: 'text-[9px] py-1 tracking-wider',
        day: 'text-base pt-0.5',
    };
});
</script>
