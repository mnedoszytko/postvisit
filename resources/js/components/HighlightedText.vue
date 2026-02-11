<template>
  <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">
    <template v-for="(segment, i) in segments" :key="i">
      <span
        v-if="segment.isTerm"
        class="border-b border-dotted border-emerald-400 text-emerald-700 cursor-pointer hover:bg-emerald-50 rounded-sm px-0.5 transition-colors"
        @click="handleClick($event, segment)"
      >{{ segment.text }}</span>
      <template v-else>{{ segment.text }}</template>
    </template>
  </p>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    content: { type: String, required: true },
    terms: { type: Array, default: () => [] },
    sectionKey: { type: String, default: '' },
});

const emit = defineEmits(['term-click']);

function handleClick(event, segment) {
    const rect = event.target.getBoundingClientRect();
    emit('term-click', {
        term: segment.text,
        definition: segment.definition || '',
        sectionKey: props.sectionKey,
        anchorRect: { top: rect.top, bottom: rect.bottom, left: rect.left, width: rect.width },
    });
}

const segments = computed(() => {
    if (!props.content || !props.terms?.length) {
        return [{ text: props.content, isTerm: false }];
    }

    // Build a lookup map for definitions
    const defMap = {};
    for (const t of props.terms) {
        defMap[t.term] = t.definition || '';
    }

    // Resolve each term to a valid { start, end, term, definition } with validation
    const resolved = [];
    for (const t of props.terms) {
        let start = t.start;
        let end = t.end;
        const term = t.term;

        // Validate offset-based position
        if (
            typeof start === 'number' &&
            typeof end === 'number' &&
            props.content.substring(start, end) === term
        ) {
            resolved.push({ start, end, term, definition: t.definition || '' });
        } else {
            // Fallback: search for the term in content
            const idx = props.content.indexOf(term);
            if (idx !== -1) {
                resolved.push({ start: idx, end: idx + term.length, term, definition: t.definition || '' });
            }
        }
    }

    // Sort by start position
    resolved.sort((a, b) => a.start - b.start);

    // Build segments, skipping overlapping terms
    const result = [];
    let cursor = 0;

    for (const t of resolved) {
        if (t.start < cursor) {
            continue;
        }

        if (t.start > cursor) {
            result.push({ text: props.content.substring(cursor, t.start), isTerm: false });
        }

        result.push({ text: t.term, isTerm: true, definition: t.definition });
        cursor = t.end;
    }

    if (cursor < props.content.length) {
        result.push({ text: props.content.substring(cursor), isTerm: false });
    }

    return result;
});
</script>
