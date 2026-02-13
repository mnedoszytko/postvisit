<template>
  <div class="space-y-6">
    <!-- Upload drop zone -->
    <div
      class="relative border-2 border-dashed rounded-2xl p-6 text-center transition-colors"
      :class="isDragging ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
    >
      <input
        ref="fileInput"
        type="file"
        accept=".pdf,.jpg,.jpeg,.png,.heic,.heif,.webp"
        multiple
        class="hidden"
        @change="handleFileSelect"
      />
      <div class="flex flex-col items-center gap-2">
        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <p class="text-sm text-gray-500">
          Drag &amp; drop lab results here, or
          <button type="button" class="text-emerald-600 font-medium hover:text-emerald-700" @click="fileInput?.click()">browse files</button>
        </p>
        <p class="text-xs text-gray-400">PDF, JPG, PNG, HEIC — max 10 MB per file</p>
      </div>
      <!-- Upload progress -->
      <div v-if="uploading" class="mt-3">
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
        </div>
        <p class="text-xs text-gray-400 mt-1">Uploading...</p>
      </div>
    </div>

    <!-- Uploaded documents list -->
    <div v-if="labDocuments.length > 0" class="space-y-2">
      <h3 class="text-sm font-semibold text-gray-700">Uploaded Lab Results</h3>
      <div v-for="doc in labDocuments" :key="doc.id" class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 px-4 py-3">
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900 truncate">{{ doc.title }}</p>
          <p class="text-xs text-gray-400">{{ formatDate(doc.document_date || doc.created_at) }}</p>
        </div>
        <span
          v-if="doc.analysis_status"
          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full shrink-0"
          :class="{
            'bg-yellow-100 text-yellow-700': doc.analysis_status === 'pending',
            'bg-emerald-100 text-emerald-700': doc.analysis_status === 'completed',
            'bg-red-100 text-red-700': doc.analysis_status === 'failed',
          }"
        >
          {{ doc.analysis_status === 'pending' ? 'Analyzing...' : doc.analysis_status === 'completed' ? 'Analyzed' : 'Error' }}
        </span>
      </div>
    </div>

    <!-- Time Range Filter -->
    <TimeRangeFilter v-model="selectedRange" />

    <!-- Global Ask button -->
    <div v-if="sortedGroups.length > 0" class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-700">Lab Results</h3>
      <AskAiButton @ask="openGlobalChat('all my lab results')" />
    </div>

    <!-- Marker cards -->
    <div v-for="group in sortedGroups" :key="group.code" class="bg-white rounded-2xl border border-gray-200 overflow-hidden relative group/card">
      <!-- Ask button — top-right corner, subtle, visible on hover -->
      <div class="absolute top-2 right-2 z-10 opacity-50 group-hover/card:opacity-100 transition-all">
        <AskAiButton @ask="openGlobalChat(group.name)" />
      </div>
      <!-- Header: marker name, latest value, badge -->
      <div class="flex items-center justify-between px-5 py-4 pr-14">
        <div>
          <h3 class="font-semibold text-gray-900 text-sm">{{ group.name }}</h3>
          <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(group.latest.effective_date) }}</p>
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
    <div v-if="sortedGroups.length === 0 && labDocuments.length === 0" class="text-center py-12 text-gray-400">
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
import { useApi } from '@/composables/useApi';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';

const openGlobalChat = inject<(topic: string) => void>('openGlobalChat', () => {});
const api = useApi();
const auth = useAuthStore();
const toast = useToastStore();

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler);

const props = defineProps({
    observations: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
});

// Upload state
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadedDocs = ref<any[]>([]);

const labDocuments = computed(() => {
    const fromProps = (props.documents as any[]).filter(d => d.document_type === 'lab_result');
    const combined = [...fromProps, ...uploadedDocs.value];
    // Deduplicate by id
    const seen = new Set<string>();
    return combined.filter(d => {
        if (seen.has(d.id)) return false;
        seen.add(d.id);
        return true;
    });
});

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
const ACCEPTED_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/webp'];

function handleDrop(e: DragEvent): void {
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (files?.length) uploadFiles(Array.from(files));
}

function handleFileSelect(e: Event): void {
    const input = e.target as HTMLInputElement;
    if (input.files?.length) {
        uploadFiles(Array.from(input.files));
        input.value = '';
    }
}

async function uploadFiles(files: File[]): Promise<void> {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (!patientId) {
        toast.error('Patient context not available.');
        return;
    }

    const validFiles = files.filter(f => {
        if (f.size > MAX_FILE_SIZE) {
            toast.error(`${f.name} exceeds 10 MB limit.`);
            return false;
        }
        if (!ACCEPTED_TYPES.includes(f.type) && !f.name.match(/\.(pdf|jpe?g|png|heic|heif|webp)$/i)) {
            toast.error(`${f.name} is not a supported file type.`);
            return false;
        }
        return true;
    });

    if (!validFiles.length) return;

    uploading.value = true;
    uploadProgress.value = 0;

    for (let i = 0; i < validFiles.length; i++) {
        const formData = new FormData();
        formData.append('file', validFiles[i]);
        formData.append('document_type', 'lab_result');
        formData.append('title', validFiles[i].name);

        try {
            const res = await api.post(`/patients/${patientId}/documents`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            uploadedDocs.value.unshift(res.data.data);
            uploadProgress.value = Math.round(((i + 1) / validFiles.length) * 100);
        } catch {
            toast.error(`Failed to upload ${validFiles[i].name}.`);
        }
    }

    uploading.value = false;
    if (uploadedDocs.value.length) {
        toast.success('Lab results uploaded successfully.');
    }
}

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
