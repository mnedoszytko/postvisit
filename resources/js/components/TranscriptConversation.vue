<template>
  <div class="space-y-3">
    <div
      v-for="(segment, idx) in segments"
      :key="idx"
      class="flex gap-3"
      :class="segment.speaker === 'doctor' ? '' : 'flex-row-reverse'"
    >
      <!-- Avatar -->
      <div
        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
        :class="segment.speaker === 'doctor'
          ? 'bg-blue-100 text-blue-700'
          : 'bg-emerald-100 text-emerald-700'"
      >
        {{ segment.speaker === 'doctor' ? 'Dr' : 'Pt' }}
      </div>

      <!-- Bubble -->
      <div
        class="max-w-[80%] rounded-2xl px-4 py-2.5"
        :class="segment.speaker === 'doctor'
          ? 'bg-blue-50 text-gray-800 rounded-tl-sm'
          : 'bg-emerald-50 text-gray-800 rounded-tr-sm'"
      >
        <p class="text-sm leading-relaxed">{{ segment.text }}</p>
        <span
          v-if="segment.timestamp"
          class="text-[10px] text-gray-400 mt-1 block"
        >
          {{ segment.timestamp }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  segments: {
    type: Array,
    required: true,
    validator: (val) => val.every(s => s.speaker && s.text),
  },
});
</script>
