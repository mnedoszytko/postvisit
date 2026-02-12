<template>
  <div class="space-y-6">
    <!-- Time Range Filter -->
    <TimeRangeFilter v-model="selectedRange" />

    <!-- Marker cards -->
    <div v-for="group in sortedGroups" :key="group.code" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
      <!-- Header: marker name, latest value, badge -->
      <div class="flex items-center justify-between px-5 py-4">
        <div class="flex items-center gap-2">
          <div>
            <h3 class="font-semibold text-gray-900 text-sm">{{ group.name }}</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(group.latest.effective_date) }}</p>
          </div>
          <AskAiButton @ask="openGlobalChat(group.name)" />
        </div>
        <div class="text-right">
          <p class="text-lg font-bold" :class="interpColor(group.latest.interpretation)">
            {{ formatQuantity(group.latest.value_quantity) }}
            <span class="text-xs font-normal text-gray-500">{{ group.latest.value_unit }}</span>
          </p>
          <span
            class="inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded-full mt-0.5"
            :class="interpBadge(group.latest.interpretation)"
          >
            {{ interpLabel(group.latest.interpretation) }}
          </span>
          <p v-if="group.latest.reference_range_text" class="text-[10px] text-gray-400 mt-0.5">
            {{ group.latest.reference_range_text }}
          </p>
        </div>
      </div>

      <!-- Trend chart (only if 2+ readings) -->
      <div v-if="group.readings.length >= 2" class="px-5 pb-4">
        <div class="h-36">
          <Line :data="chartData(group)" :options="chartOptions(group)" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="sortedGroups.length === 0" class="text-center py-12 text-gray-400">
      No lab results available yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, inject } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Filler,
} from 'chart.js';
import TimeRangeFilter from './TimeRangeFilter.vue';
import AskAiButton from '@/components/AskAiButton.vue';

const openGlobalChat = inject<(topic: string) => void>('openGlobalChat', () => {});

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler);

const props = defineProps({
    observations: { type: Array, default: () => [] },
});

const selectedRange = ref('1y');

function getCutoffDate(range: string): Date {
    const now = new Date();
    switch (range) {
        case '7d': return new Date(now.getTime() - 7 * 86400000);
        case '30d': return new Date(now.getTime() - 30 * 86400000);
        case '90d': return new Date(now.getTime() - 90 * 86400000);
        case '1y': return new Date(now.getTime() - 365 * 86400000);
        default: return new Date(now.getTime() - 365 * 86400000);
    }
}

interface LabGroup {
    code: string;
    name: string;
    latest: any;
    readings: any[];
    isAbnormal: boolean;
}

const labGroups = computed((): LabGroup[] => {
    const cutoff = getCutoffDate(selectedRange.value);
    const labs = (props.observations as any[]).filter(
        o => o.category === 'laboratory' && o.value_type === 'quantity' && new Date(o.effective_date) >= cutoff
    );

    const grouped = new Map<string, any[]>();
    for (const lab of labs) {
        const arr = grouped.get(lab.code) || [];
        arr.push(lab);
        grouped.set(lab.code, arr);
    }

    const groups: LabGroup[] = [];
    for (const [code, readings] of grouped) {
        readings.sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime());
        const latest = readings[readings.length - 1];
        groups.push({
            code,
            name: simplifyName(latest.code_display),
            latest,
            readings,
            isAbnormal: latest.interpretation === 'H' || latest.interpretation === 'L',
        });
    }

    return groups;
});

const sortedGroups = computed(() =>
    [...labGroups.value].sort((a, b) => {
        if (a.isAbnormal !== b.isAbnormal) return a.isAbnormal ? -1 : 1;
        return a.name.localeCompare(b.name);
    })
);

function simplifyName(display: string): string {
    return display
        .replace(/\s*\[.*?\]\s*/g, '')
        .replace(/\s*in Serum or Plasma\s*/g, '')
        .replace(/\s*in Blood\s*/g, '')
        .trim();
}

function chartData(group: LabGroup) {
    const refHigh = parseFloat(group.latest.reference_range_high);
    const refLow = parseFloat(group.latest.reference_range_low);
    const hasRefHigh = !isNaN(refHigh) && refHigh > 0;
    const hasRefLow = !isNaN(refLow) && refLow > 0;

    const datasets: any[] = [
        {
            label: group.name,
            data: group.readings.map(r => parseFloat(r.value_quantity)),
            borderColor: '#111827',
            backgroundColor: 'rgba(17,24,39,0.05)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: group.readings.map(r => pointColor(r.interpretation)),
            pointBorderColor: group.readings.map(r => pointColor(r.interpretation)),
            borderWidth: 2,
        },
    ];

    if (hasRefHigh) {
        datasets.push({
            label: 'Upper limit',
            data: group.readings.map(() => refHigh),
            borderColor: 'rgba(239,68,68,0.35)',
            borderDash: [6, 4],
            borderWidth: 1,
            pointRadius: 0,
            fill: false,
        });
    }

    if (hasRefLow) {
        datasets.push({
            label: 'Lower limit',
            data: group.readings.map(() => refLow),
            borderColor: 'rgba(59,130,246,0.35)',
            borderDash: [6, 4],
            borderWidth: 1,
            pointRadius: 0,
            fill: false,
        });
    }

    return {
        labels: group.readings.map(r => formatShortDate(r.effective_date)),
        datasets,
    };
}

function chartOptions(group: LabGroup) {
    const values = group.readings.map(r => parseFloat(r.value_quantity));
    const refHigh = parseFloat(group.latest.reference_range_high);
    const refLow = parseFloat(group.latest.reference_range_low);
    const allValues = [...values];
    if (!isNaN(refHigh)) allValues.push(refHigh);
    if (!isNaN(refLow) && refLow > 0) allValues.push(refLow);
    const min = Math.min(...allValues);
    const max = Math.max(...allValues);
    const padding = (max - min) * 0.2 || 1;

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx: any) => {
                        if (ctx.datasetIndex > 0) return ctx.dataset.label;
                        return `${ctx.parsed.y} ${group.latest.value_unit}`;
                    },
                },
            },
        },
        scales: {
            y: {
                min: Math.floor(min - padding),
                max: Math.ceil(max + padding),
                title: { display: true, text: group.latest.value_unit, font: { size: 10 } },
                ticks: { font: { size: 10 } },
            },
            x: {
                ticks: { font: { size: 10 } },
            },
        },
    };
}

function pointColor(interp: string): string {
    if (interp === 'H') return '#dc2626';
    if (interp === 'L') return '#2563eb';
    return '#16a34a';
}

function interpColor(interp: string): string {
    if (interp === 'H') return 'text-red-600';
    if (interp === 'L') return 'text-blue-600';
    return 'text-gray-900';
}

function interpBadge(interp: string): string {
    if (interp === 'H') return 'bg-red-100 text-red-700';
    if (interp === 'L') return 'bg-blue-100 text-blue-700';
    return 'bg-emerald-100 text-emerald-700';
}

function interpLabel(interp: string): string {
    if (interp === 'H') return 'HIGH';
    if (interp === 'L') return 'LOW';
    return 'NORMAL';
}

function formatQuantity(val: any): string {
    const num = parseFloat(val);
    if (isNaN(num)) return val;
    return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(2)).toString();
}

function formatDate(d: string): string {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatShortDate(d: string): string {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>
