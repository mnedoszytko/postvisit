<template>
  <div
    class="rounded-2xl p-4 transition-all"
    :class="service.connected
      ? 'bg-white border-2 border-emerald-500'
      : 'bg-white border-2 border-dashed border-gray-300 opacity-70'"
  >
    <div class="flex items-start gap-3">
      <!-- Brand color accent -->
      <div
        class="w-1 h-12 rounded-full shrink-0"
        :style="{ backgroundColor: service.brandColor }"
      />
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="text-lg">{{ service.icon }}</span>
          <h4 class="font-semibold text-gray-900 text-sm truncate">{{ service.name }}</h4>
          <span
            v-if="service.connected"
            class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded-full shrink-0"
          >
            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full" />
            Connected
          </span>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">{{ service.description }}</p>
        <div v-if="service.dataTypes?.length" class="flex flex-wrap gap-1 mt-2">
          <span
            v-for="dt in service.dataTypes"
            :key="dt"
            class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded"
          >
            {{ dt }}
          </span>
        </div>
        <p v-if="service.connected && service.lastSync" class="text-[10px] text-gray-400 mt-1.5">
          Last sync: {{ service.lastSync }}
        </p>
      </div>
    </div>
    <button
      class="mt-3 w-full text-xs font-medium py-2 rounded-xl transition-colors"
      :class="service.connected
        ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
        : 'bg-emerald-600 text-white hover:bg-emerald-700'"
      @click="$emit(service.connected ? 'disconnect' : 'connect', service.id)"
    >
      {{ service.connected ? 'Disconnect' : 'Connect' }}
    </button>
  </div>
</template>

<script setup>
defineProps({
    service: { type: Object, required: true },
});

defineEmits(['connect', 'disconnect']);
</script>
