<template>
  <div
    class="bg-white rounded-xl border transition-all group"
    :class="service.connected
      ? 'border-emerald-200 shadow-sm hover:shadow-md'
      : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'"
  >
    <div class="p-4">
      <div class="flex items-start gap-3.5">
        <ServiceLogo
          :service-id="service.id"
          :color="service.brandColor"
          :size="44"
        />
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h4 class="font-semibold text-gray-900 text-sm truncate">{{ service.name }}</h4>
            <span
              v-if="service.connected"
              class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-full shrink-0"
            >
              <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse" />
              Connected
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ service.description }}</p>
        </div>
      </div>

      <div v-if="service.dataTypes?.length" class="flex flex-wrap gap-1 mt-3 ml-[57px]">
        <span
          v-for="dt in service.dataTypes"
          :key="dt"
          class="text-[10px] font-medium px-2 py-0.5 rounded-md"
          :class="service.connected
            ? 'bg-emerald-50 text-emerald-700'
            : 'bg-gray-100 text-gray-500'"
        >
          {{ dt }}
        </span>
      </div>
    </div>

    <div
      class="flex items-center justify-between px-4 py-2.5 border-t"
      :class="service.connected ? 'border-emerald-100 bg-emerald-50/30' : 'border-gray-100 bg-gray-50/50'"
    >
      <p v-if="service.connected && service.lastSync" class="text-[11px] text-gray-400">
        Synced {{ service.lastSync }}
      </p>
      <p v-else class="text-[11px] text-gray-400">
        Not connected
      </p>
      <button
        class="text-xs font-medium px-3.5 py-1.5 rounded-lg transition-colors"
        :class="service.connected
          ? 'text-gray-500 hover:text-red-600 hover:bg-red-50'
          : 'text-white hover:opacity-90'"
        :style="!service.connected ? { backgroundColor: service.brandColor } : {}"
        @click="$emit(service.connected ? 'disconnect' : 'connect', service.id)"
      >
        {{ service.connected ? 'Disconnect' : 'Connect' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import ServiceLogo from '@/components/health/ServiceLogo.vue';

defineProps<{
  service: {
    id: string;
    name: string;
    description: string;
    dataTypes: string[];
    connected: boolean;
    lastSync: string | null;
    brandColor: string;
    category: string;
  };
}>();

defineEmits<{
  connect: [id: string];
  disconnect: [id: string];
}>();
</script>
