<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-40"
      @click.self="close"
    />
    <div
      v-if="visible"
      class="fixed z-50 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden"
      :style="positionStyle"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-2.5 bg-emerald-50 border-b border-emerald-100">
        <h4 class="font-semibold text-emerald-800 text-sm truncate">{{ term }}</h4>
        <button class="text-gray-400 hover:text-gray-600 text-lg leading-none" @click="close">&times;</button>
      </div>

      <!-- Body -->
      <div class="px-4 py-3 max-h-48 overflow-y-auto">
        <p class="text-gray-700 text-sm leading-relaxed">{{ definition }}</p>
      </div>

      <!-- Footer -->
      <div class="px-4 py-2.5 border-t border-gray-100 flex justify-end">
        <button
          class="inline-flex items-center gap-1.5 text-xs text-emerald-600 hover:text-emerald-700 font-medium transition-colors"
          @click="askMore"
        >
          <img src="/images/logo-icon.png" alt="" class="h-3.5 w-auto" />
          Ask more in chat
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    term: { type: String, default: '' },
    definition: { type: String, default: '' },
    anchorRect: { type: Object, default: null },
});

const emit = defineEmits(['close', 'ask-more']);

const positionStyle = computed(() => {
    if (!props.anchorRect) return {};
    const rect = props.anchorRect;
    const popoverWidth = 320;
    const popoverMaxHeight = 300;

    let left = rect.left + rect.width / 2 - popoverWidth / 2;
    let top = rect.bottom + 8;

    // Keep within viewport
    if (left < 8) left = 8;
    if (left + popoverWidth > window.innerWidth - 8) left = window.innerWidth - popoverWidth - 8;
    if (top + popoverMaxHeight > window.innerHeight - 8) {
        top = rect.top - popoverMaxHeight - 8;
        if (top < 8) top = 8;
    }

    return {
        left: `${left}px`,
        top: `${top}px`,
    };
});

function close() {
    emit('close');
}

function askMore() {
    emit('ask-more', props.term);
    close();
}
</script>
