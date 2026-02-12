<template>
  <div class="space-y-6">
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
        <div v-if="deviceHrvAvg" class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-indigo-600">{{ deviceHrvAvg }}</p>
          <p class="text-xs text-gray-500 mt-1">Avg HRV (ms)</p>
        </div>
        <div v-if="deviceSleepAvg" class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-violet-600">{{ deviceSleepAvg }}</p>
          <p class="text-xs text-gray-500 mt-1">Avg Sleep (h)</p>
        </div>
        <div v-if="deviceData?.blood_oxygen" class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-cyan-600">{{ deviceData.blood_oxygen.average_spo2 }}%</p>
          <p class="text-xs text-gray-500 mt-1">Avg SpO2</p>
        </div>
      </div>
    </div>

    <!-- Time Range Filter -->
    <TimeRangeFilter v-model="selectedRange" />

    <!-- BP Trend Chart -->
    <div v-if="bpData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <h2 class="font-semibold text-gray-900 mb-4">Blood Pressure Trend</h2>
      <div class="h-64">
        <Line :data="bpChartData" :options="bpChartOptions" />
      </div>
    </div>

    <!-- HR Trend Chart -->
    <div v-if="hrData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <h2 class="font-semibold text-gray-900 mb-4">Heart Rate Trend</h2>
      <div class="h-64">
        <Line :data="hrChartData" :options="hrChartOptions" />
      </div>
    </div>

    <!-- HRV Chart -->
    <div v-if="hrvData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <h2 class="font-semibold text-gray-900 mb-4">HRV — Heart Rate Variability</h2>
      <div class="h-64">
        <Line :data="hrvChartData" :options="hrvChartOptions" />
      </div>
    </div>

    <!-- Weight Trend Chart (Bar) -->
    <div v-if="weightData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Weight Trend</h2>
        <div class="flex items-center gap-3">
          <span v-if="weightAvg" class="text-sm text-gray-500">Avg: {{ weightAvg }} kg</span>
          <span
            v-if="weightDelta !== null"
            class="text-sm font-medium px-2 py-0.5 rounded-full"
            :class="parseFloat(weightDelta) > 0
              ? 'bg-red-100 text-red-700'
              : 'bg-emerald-100 text-emerald-700'"
          >
            {{ parseFloat(weightDelta) > 0 ? '+' : '' }}{{ weightDelta }} kg
          </span>
        </div>
      </div>
      <div class="h-64">
        <Bar :data="weightChartData" :options="weightChartOptions" />
      </div>
    </div>

    <!-- Sleep Duration Chart -->
    <div v-if="sleepData.length > 0 || sleepDeviceData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
      <h2 class="font-semibold text-gray-900 mb-4">Sleep Duration</h2>
      <div class="h-64">
        <Bar :data="sleepChartData" :options="sleepChartOptions" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!deviceData && bpData.length === 0 && hrData.length === 0 && weightData.length === 0 && hrvData.length === 0 && sleepData.length === 0" class="text-center py-12 text-gray-400">
      No vitals data available yet.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Line, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import TimeRangeFilter from './TimeRangeFilter.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    observations: { type: Array, default: () => [] },
    deviceData: { type: Object, default: null },
});

const selectedRange = ref('30d');

function getCutoffDate(range: string): Date {
    const now = new Date();
    switch (range) {
        case '7d': return new Date(now.getTime() - 7 * 86400000);
        case '30d': return new Date(now.getTime() - 30 * 86400000);
        case '90d': return new Date(now.getTime() - 90 * 86400000);
        case '1y': return new Date(now.getTime() - 365 * 86400000);
        default: return new Date(now.getTime() - 30 * 86400000);
    }
}

const filteredObservations = computed(() => {
    const cutoff = getCutoffDate(selectedRange.value);
    return (props.observations as any[]).filter(o => new Date(o.effective_date) >= cutoff);
});

// --- Data series ---

const bpData = computed(() =>
    filteredObservations.value
        .filter(o => o.code === '85354-9' && o.specialty_data?.systolic)
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const hrData = computed(() =>
    filteredObservations.value
        .filter(o => o.code === '8867-4')
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const weightData = computed(() =>
    filteredObservations.value
        .filter(o => o.code === '29463-7')
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const hrvData = computed(() =>
    filteredObservations.value
        .filter(o => o.code === '80404-7')
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const sleepData = computed(() =>
    filteredObservations.value
        .filter(o => o.code === '93832-4')
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const sleepDeviceData = computed(() => {
    if (!props.deviceData?.sleep?.daily_readings) return [];
    const cutoff = getCutoffDate(selectedRange.value);
    return props.deviceData.sleep.daily_readings
        .filter((r: any) => new Date(r.date) >= cutoff)
        .sort((a: any, b: any) => new Date(a.date).getTime() - new Date(b.date).getTime());
});

// --- Device summary stats ---

const todaySteps = computed(() => {
    if (!props.deviceData?.activity?.daily_steps?.length) return '\u2014';
    return props.deviceData.activity.daily_steps[0].steps.toLocaleString();
});

const deviceHrvAvg = computed(() => {
    if (!props.deviceData?.hrv?.average_sdnn_ms) return null;
    return props.deviceData.hrv.average_sdnn_ms;
});

const deviceSleepAvg = computed(() => {
    if (!props.deviceData?.sleep?.average_hours) return null;
    return props.deviceData.sleep.average_hours;
});

// --- BP Chart ---

const bpChartData = computed(() => ({
    labels: bpData.value.map(o => formatShortDate(o.effective_date)),
    datasets: [
        {
            label: 'Systolic',
            data: bpData.value.map(o => o.specialty_data.systolic.value),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.1)',
            fill: false,
            tension: 0.3,
            pointRadius: 3,
        },
        {
            label: 'Diastolic',
            data: bpData.value.map(o => o.specialty_data.diastolic.value),
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
        legend: { position: 'top' as const },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        y: { min: 60, max: 180, title: { display: true, text: 'mmHg' } },
    },
};

// --- HR Chart ---

const hrChartData = computed(() => ({
    labels: hrData.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'Heart Rate',
        data: hrData.value.map(o => parseFloat(o.value_quantity)),
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

// --- HRV Chart ---

const hrvChartData = computed(() => ({
    labels: hrvData.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'HRV (SDNN)',
        data: hrvData.value.map(o => parseFloat(o.value_quantity)),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
    }],
}));

const hrvChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { min: 15, max: 70, title: { display: true, text: 'ms' } },
    },
};

// --- Weight Chart (Bar) ---

const weightDelta = computed(() => {
    if (weightData.value.length < 2) return null;
    const first = parseFloat(weightData.value[0].value_quantity);
    const last = parseFloat(weightData.value[weightData.value.length - 1].value_quantity);
    return (last - first).toFixed(1);
});

const weightAvg = computed(() => {
    if (weightData.value.length === 0) return null;
    const sum = weightData.value.reduce((acc: number, o: any) => acc + parseFloat(o.value_quantity), 0);
    return (sum / weightData.value.length).toFixed(1);
});

const weightChartData = computed(() => ({
    labels: weightData.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'Weight',
        data: weightData.value.map(o => parseFloat(o.value_quantity)),
        backgroundColor: 'rgba(139,92,246,0.6)',
        borderColor: '#8b5cf6',
        borderWidth: 1,
        borderRadius: 4,
        barPercentage: 0.55,
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

// --- Sleep Chart (Stacked Bar) ---

const sleepChartData = computed(() => {
    if (sleepDeviceData.value.length > 0) {
        return {
            labels: sleepDeviceData.value.map((r: any) => formatShortDate(r.date)),
            datasets: [
                {
                    label: 'Deep',
                    data: sleepDeviceData.value.map((r: any) => r.deep_hours),
                    backgroundColor: '#1e40af',
                },
                {
                    label: 'REM',
                    data: sleepDeviceData.value.map((r: any) => r.rem_hours),
                    backgroundColor: '#7c3aed',
                },
                {
                    label: 'Light',
                    data: sleepDeviceData.value.map((r: any) => r.light_hours),
                    backgroundColor: '#60a5fa',
                },
                {
                    label: 'Awake',
                    data: sleepDeviceData.value.map((r: any) => r.awake_hours),
                    backgroundColor: '#fbbf24',
                },
            ],
        };
    }
    return {
        labels: sleepData.value.map(o => formatShortDate(o.effective_date)),
        datasets: [{
            label: 'Sleep',
            data: sleepData.value.map(o => parseFloat(o.value_quantity)),
            backgroundColor: '#6366f1',
        }],
    };
});

const sleepChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' as const },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        x: { stacked: true },
        y: {
            stacked: true,
            min: 0,
            max: 10,
            title: { display: true, text: 'hours' },
        },
    },
}));

// --- Helpers ---

function formatShortDate(d: string): string {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatDateTime(d: string): string {
    if (!d) return '';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
