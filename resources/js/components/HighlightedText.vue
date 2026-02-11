<template>
  <div class="prose prose-sm max-w-none text-gray-700">
    <template v-for="(block, bi) in blocks" :key="bi">
      <ul v-if="block.type === 'list'" class="my-2">
        <li v-for="(item, li) in block.items" :key="li">
          <template v-for="(seg, si) in item" :key="si">
            <span
              v-if="seg.isTerm"
              class="border-b border-dotted border-emerald-400 text-emerald-700 cursor-pointer hover:bg-emerald-50 rounded-sm px-0.5 transition-colors"
              @click="handleClick($event, seg)"
            >{{ seg.text }}</span>
            <template v-else>{{ seg.text }}</template>
          </template>
        </li>
      </ul>
      <p v-else class="my-2 leading-relaxed">
        <template v-for="(seg, si) in block.segments" :key="si">
          <span
            v-if="seg.isTerm"
            class="border-b border-dotted border-emerald-400 text-emerald-700 cursor-pointer hover:bg-emerald-50 rounded-sm px-0.5 transition-colors"
            @click="handleClick($event, seg)"
          >{{ seg.text }}</span>
          <template v-else>{{ seg.text }}</template>
        </template>
      </p>
    </template>
  </div>
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

// Build flat segments with term highlighting (same logic as before)
function buildSegments(text, resolved) {
    const result = [];
    let cursor = 0;

    for (const t of resolved) {
        if (t.start < cursor || t.start > text.length) continue;

        if (t.start > cursor) {
            result.push({ text: text.substring(cursor, t.start), isTerm: false, globalStart: cursor });
        }

        result.push({ text: t.term, isTerm: true, definition: t.definition, globalStart: t.start });
        cursor = t.end;
    }

    if (cursor < text.length) {
        result.push({ text: text.substring(cursor), isTerm: false, globalStart: cursor });
    }

    return result;
}

// Split flat segments into paragraph/list blocks based on double newlines and "- " prefixes
function segmentsToBlocks(flatSegments) {
    // Concatenate all text to find paragraph boundaries
    const fullText = flatSegments.map(s => s.text).join('');

    // Split on double newlines to get paragraph chunks
    const paragraphs = fullText.split(/\n\n+/);

    const blocks = [];
    let charOffset = 0;

    for (const para of paragraphs) {
        if (!para.trim()) {
            charOffset += para.length + 2;
            continue;
        }

        // Check if this paragraph is a list (lines starting with "- " or "* " or "1. ")
        const lines = para.split('\n');
        const isListBlock = lines.every(l => /^\s*[-*]\s|^\s*\d+\.\s/.test(l) || !l.trim());

        if (isListBlock) {
            const items = [];
            for (const line of lines) {
                const cleaned = line.replace(/^\s*[-*]\s+|^\s*\d+\.\s+/, '');
                if (!cleaned.trim()) continue;
                const lineStart = fullText.indexOf(cleaned, charOffset);
                items.push(getSegmentsForRange(flatSegments, lineStart, lineStart + cleaned.length, fullText));
            }
            blocks.push({ type: 'list', items });
        } else {
            const paraStart = fullText.indexOf(para, charOffset);
            blocks.push({
                type: 'paragraph',
                segments: getSegmentsForRange(flatSegments, paraStart, paraStart + para.length, fullText),
            });
        }

        charOffset += para.length + 2; // +2 for the \n\n
    }

    // If no blocks were created (no double newlines), treat as single paragraph
    if (blocks.length === 0 && flatSegments.length > 0) {
        blocks.push({ type: 'paragraph', segments: flatSegments });
    }

    return blocks;
}

// Get highlighted segments for a specific character range of the full text
function getSegmentsForRange(flatSegments, rangeStart, rangeEnd, fullText) {
    const result = [];
    let cursor = 0; // cursor in fullText

    for (const seg of flatSegments) {
        const segStart = cursor;
        const segEnd = cursor + seg.text.length;
        cursor = segEnd;

        // Skip segments entirely outside range
        if (segEnd <= rangeStart || segStart >= rangeEnd) continue;

        // Clip segment to range
        const clipStart = Math.max(segStart, rangeStart);
        const clipEnd = Math.min(segEnd, rangeEnd);
        const offsetInSeg = clipStart - segStart;
        const clippedText = seg.text.substring(offsetInSeg, offsetInSeg + (clipEnd - clipStart));

        if (!clippedText) continue;

        if (seg.isTerm && clippedText === seg.text) {
            // Full term preserved
            result.push({ text: clippedText, isTerm: true, definition: seg.definition });
        } else if (seg.isTerm) {
            // Partial term — don't highlight partial matches
            result.push({ text: clippedText, isTerm: false });
        } else {
            result.push({ text: clippedText, isTerm: false });
        }
    }

    return result;
}

const resolvedTerms = computed(() => {
    if (!props.content || !props.terms?.length) return [];

    const resolved = [];
    for (const t of props.terms) {
        const term = t.term;

        if (
            typeof t.start === 'number' &&
            typeof t.end === 'number' &&
            props.content.substring(t.start, t.end) === term
        ) {
            resolved.push({ start: t.start, end: t.end, term, definition: t.definition || '' });
        } else {
            const idx = props.content.indexOf(term);
            if (idx !== -1) {
                resolved.push({ start: idx, end: idx + term.length, term, definition: t.definition || '' });
            }
        }
    }

    resolved.sort((a, b) => a.start - b.start);

    // Remove overlaps
    const filtered = [];
    let lastEnd = 0;
    for (const t of resolved) {
        if (t.start >= lastEnd) {
            filtered.push(t);
            lastEnd = t.end;
        }
    }

    return filtered;
});

const blocks = computed(() => {
    if (!props.content) return [];
    const flatSegments = buildSegments(props.content, resolvedTerms.value);
    return segmentsToBlocks(flatSegments);
});
</script>
