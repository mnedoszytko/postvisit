<template>
  <div class="space-y-6">
    <!-- Time Range Filter -->
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Vitals</h2>
      <div class="flex gap-1 bg-gray-100 rounded-lg p-0.5">
        <button
          v-for="range in ranges"
          :key="range.key"
          class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
          :class="selectedRange === range.key
            ? 'bg-white text-emerald-700 shadow-sm'
            : 'text-gray-500 hover:text-gray-700'"
          @click="selectedRange = range.key"
        >
          {{ range.label }}
        </button>
      </div>
    </div>

    <!-- Connected Devices -->
    <div v-if="deviceData" class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h2 class="font-semibold text-gray-900">{{ deviceData.device.type }}</h2>
          <p class="text-xs text-gray-500">Last sync: {{ formatDateTime(deviceData.device.last_sync) }}</p>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-red-600">{{ deviceData.heart_rate.resting_average_bpm }}</p>
          <p class="text-xs text-gray-500 mt-1">Resting HR</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-amber-600">{{ deviceData.irregular_rhythm_events.length }}</p>
          <p class="text-xs text-gray-500 mt-1">PVC Events (7d)</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-emerald-600">{{ todaySteps }}</p>
          <p class="text-xs text-gray-500 mt-1">Steps Today</p>
        </div>
      </div>
    </div>

    <!-- BP Trend Chart -->
    <div v-if="bpFiltered.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Blood Pressure Trend</h2>
        <span class="text-xs text-gray-400">{{ bpFiltered.length }} readings</span>
      </div>
      <div class="h-64">
        <Line :data="bpChartData" :options="bpChartOptions" />
      </div>
    </div>

    <!-- HR Trend Chart -->
    <div v-if="hrFiltered.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Heart Rate Trend</h2>
        <span class="text-xs text-gray-400">{{ hrFiltered.length }} readings</span>
      </div>
      <div class="h-64">
        <Line :data="hrChartData" :options="hrChartOptions" />
      </div>
    </div>

    <!-- Weight Trend Chart -->
    <div v-if="weightFiltered.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Weight Trend</h2>
        <span class="text-xs text-gray-400">{{ weightFiltered.length }} readings</span>
      </div>
      <div class="h-64">
        <Line :data="weightChartData" :options="weightChartOptions" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!deviceData && bpFiltered.length === 0 && hrFiltered.length === 0 && weightFiltered.length === 0" class="text-center py-12 text-gray-400">
      No vitals data available for this period.
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    observations: { type: Array, default: () => [] },
    deviceData: { type: Object, default: null },
});

// --- Time Range Filter ---
const ranges = [
    { key: '7d', label: '7D', days: 7 },
    { key: '30d', label: '30D', days: 30 },
    { key: '90d', label: '90D', days: 90 },
    { key: '1y', label: '1Y', days: 365 },
];

const selectedRange = ref('30d');

const cutoffDate = computed(() => {
    const days = ranges.find(r => r.key === selectedRange.value)?.days ?? 30;
    const d = new Date();
    d.setDate(d.getDate() - days);
    return d;
});

function filterByRange(items) {
    return items.filter(o => new Date(o.effective_date) >= cutoffDate.value);
}

// --- Data filtered by code, then by time range ---
const bpAll = computed(() =>
    props.observations
        .filter(o => o.code === '85354-9' && o.specialty_data?.systolic)
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const hrAll = computed(() =>
    props.observations
        .filter(o => o.code === '8867-4')
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const weightAll = computed(() =>
    props.observations
        .filter(o => o.code === '29463-7')
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const bpFiltered = computed(() => filterByRange(bpAll.value));
const hrFiltered = computed(() => filterByRange(hrAll.value));
const weightFiltered = computed(() => filterByRange(weightAll.value));

const todaySteps = computed(() => {
    if (!props.deviceData?.activity?.daily_steps?.length) return '\u2014';
    return props.deviceData.activity.daily_steps[0].steps.toLocaleString();
});

// --- Chart Data ---
const bpChartData = computed(() => ({
    labels: bpFiltered.value.map(o => formatShortDate(o.effective_date)),
    datasets: [
        {
            label: 'Systolic',
            data: bpFiltered.value.map(o => o.specialty_data.systolic.value),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.1)',
            fill: false,
            tension: 0.3,
            pointRadius: 3,
        },
        {
            label: 'Diastolic',
            data: bpFiltered.value.map(o => o.specialty_data.diastolic.value),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            fill: false,
            tension: 0.3,
            pointRadius: 3,
        },
    ],
}));

const bpChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        tooltip: { mode: 'index', intersect: false },
    },
    scales: {
        y: { min: 60, max: 180, title: { display: true, text: 'mmHg' } },
    },
};

const hrChartData = computed(() => ({
    labels: hrFiltered.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'Heart Rate',
        data: hrFiltered.value.map(o => parseFloat(o.value_quantity)),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239,68,68,0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
    }],
}));

const hrChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { min: 50, max: 110, title: { display: true, text: 'bpm' } },
    },
};

const weightChartData = computed(() => ({
    labels: weightFiltered.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'Weight',
        data: weightFiltered.value.map(o => parseFloat(o.value_quantity)),
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139,92,246,0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
    }],
}));

const weightChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { title: { display: true, text: 'kg' } },
    },
};

function formatShortDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatDateTime(d) {
    if (!d) return '';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
