<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      variant === 'danger' ? 'bg-red-100 text-red-700' :
      variant === 'warning' ? 'bg-amber-100 text-amber-700' :
      'bg-emerald-100 text-emerald-700'
    ]"
  >
    {{ text }}
  </span>
</template>

<script setup>
defineProps({
    text: { type: String, required: true },
    variant: { type: String, default: 'default' },
});
</script>
