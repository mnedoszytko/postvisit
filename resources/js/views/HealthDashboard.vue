<template>
  <PatientLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">My Health</h1>
        <router-link to="/profile" class="text-sm text-emerald-600 hover:text-emerald-700">&larr; Back to Profile</router-link>
      </div>

      <div v-if="loading" class="text-center py-12 text-gray-400">Loading health data...</div>

      <template v-else>
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

        <!-- Weight Trend Chart -->
        <div v-if="weightData.length > 0" class="bg-white rounded-2xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-900 mb-4">Weight Trend</h2>
          <div class="h-64">
            <Line :data="weightChartData" :options="weightChartOptions" />
          </div>
        </div>

        <!-- Recent Lab Results -->
        <div v-if="labResults.length > 0" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="labsExpanded = !labsExpanded">
            <h2 class="font-semibold text-gray-900">Recent Lab Results</h2>
            <span class="text-gray-400 text-sm">{{ labsExpanded ? 'Collapse' : 'Expand' }}</span>
          </button>
          <div v-if="labsExpanded" class="px-4 pb-4 space-y-2">
            <div v-for="lab in labResults" :key="lab.id" class="flex items-center justify-between border-b border-gray-100 pb-2 last:border-0">
              <div>
                <p class="font-medium text-gray-800 text-sm">{{ lab.code_display }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(lab.effective_date) }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium" :class="interpretationColor(lab.interpretation)">
                  {{ lab.value_type === 'quantity' ? `${formatQuantity(lab.value_quantity)} ${lab.value_unit}` : lab.value_string }}
                </p>
                <p v-if="lab.reference_range_text" class="text-[10px] text-gray-400">{{ lab.reference_range_text }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Medical Documents -->
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-900 mb-3">Medical Documents</h2>
          <div class="space-y-2">
            <div v-for="doc in mockDocuments" :key="doc.name" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors">
              <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="doc.iconBg">
                <span class="text-sm">{{ doc.icon }}</span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ doc.name }}</p>
                <p class="text-xs text-gray-400">{{ doc.type }} &middot; {{ doc.date }}</p>
              </div>
              <span class="ml-auto text-xs px-2 py-0.5 rounded-full" :class="doc.statusClass">{{ doc.status }}</span>
            </div>
          </div>
        </div>
      </template>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
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
import { useAuthStore } from '@/stores/auth';
import { useApi } from '@/composables/useApi';
import PatientLayout from '@/layouts/PatientLayout.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const auth = useAuthStore();
const api = useApi();

const loading = ref(true);
const observations = ref([]);
const deviceData = ref(null);
const labsExpanded = ref(false);

const bpData = computed(() =>
    observations.value
        .filter(o => o.code === '85354-9' && o.specialty_data?.systolic)
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const hrData = computed(() =>
    observations.value
        .filter(o => o.code === '8867-4')
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const weightData = computed(() =>
    observations.value
        .filter(o => o.code === '29463-7')
        .sort((a, b) => new Date(a.effective_date) - new Date(b.effective_date))
);

const labResults = computed(() =>
    observations.value
        .filter(o => o.category === 'laboratory')
        .sort((a, b) => new Date(b.effective_date) - new Date(a.effective_date))
);

const todaySteps = computed(() => {
    if (!deviceData.value?.activity?.daily_steps?.length) return '—';
    return deviceData.value.activity.daily_steps[0].steps.toLocaleString();
});

// Chart data
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
        legend: { position: 'top' },
        tooltip: { mode: 'index', intersect: false },
    },
    scales: {
        y: { min: 60, max: 180, title: { display: true, text: 'mmHg' } },
    },
};

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

const weightChartData = computed(() => ({
    labels: weightData.value.map(o => formatShortDate(o.effective_date)),
    datasets: [{
        label: 'Weight',
        data: weightData.value.map(o => parseFloat(o.value_quantity)),
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

const mockDocuments = [
    { name: 'Discharge Summary — Cardiology Visit', type: 'PDF', date: 'Feb 9, 2026', icon: '📄', iconBg: 'bg-red-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Echocardiogram Report', type: 'PDF', date: 'Feb 9, 2026', icon: '🫀', iconBg: 'bg-pink-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: '12-Lead EKG Recording', type: 'DICOM', date: 'Feb 9, 2026', icon: '📊', iconBg: 'bg-blue-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Lab Results — CBC, BMP, Lipids', type: 'HL7 FHIR', date: 'Feb 9, 2026', icon: '🧪', iconBg: 'bg-purple-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Apple Watch Health Export', type: 'JSON', date: 'Feb 10, 2026', icon: '⌚', iconBg: 'bg-gray-100', status: 'Synced', statusClass: 'bg-blue-100 text-blue-700' },
    { name: 'Prescription — Propranolol 40mg', type: 'e-Rx (NCPDP)', date: 'Feb 9, 2026', icon: '💊', iconBg: 'bg-emerald-50', status: 'Active', statusClass: 'bg-emerald-100 text-emerald-700' },
    { name: 'Insurance Pre-Authorization', type: 'X12 278', date: 'Feb 8, 2026', icon: '🏥', iconBg: 'bg-amber-50', status: 'Approved', statusClass: 'bg-amber-100 text-amber-700' },
    { name: 'Holter Monitor Order', type: 'HL7 ORM', date: 'Feb 9, 2026', icon: '📋', iconBg: 'bg-indigo-50', status: 'Pending', statusClass: 'bg-gray-200 text-gray-600' },
];

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatShortDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatDateTime(d) {
    if (!d) return '';
    return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatQuantity(val) {
    const num = parseFloat(val);
    if (isNaN(num)) return val;
    return Number.isInteger(num) ? num.toString() : parseFloat(num.toFixed(2)).toString();
}

function interpretationColor(interp) {
    if (interp === 'H') return 'text-red-600';
    if (interp === 'L') return 'text-blue-600';
    return 'text-gray-800';
}

onMounted(async () => {
    const patientId = auth.user?.patient_id || auth.user?.patient?.id;
    if (!patientId) {
        loading.value = false;
        return;
    }

    try {
        const [obsRes, deviceRes] = await Promise.allSettled([
            api.get(`/patients/${patientId}/observations`),
            fetch('/demo/apple-watch-alex.json').then(r => r.ok ? r.json() : null),
        ]);

        if (obsRes.status === 'fulfilled') {
            observations.value = obsRes.value.data.data || [];
        }
        if (deviceRes.status === 'fulfilled' && deviceRes.value) {
            deviceData.value = deviceRes.value;
        }
    } finally {
        loading.value = false;
    }
});
</script>
