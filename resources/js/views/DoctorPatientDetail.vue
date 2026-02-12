<template>
  <DoctorLayout>
    <div class="space-y-6">
      <router-link to="/doctor/patients" class="text-sm text-emerald-600 hover:text-emerald-700">
        &larr; Back to Patients
      </router-link>

      <!-- Patient profile -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex items-center gap-6">
        <img
          v-if="patient?.photo_url"
          :src="patient.photo_url"
          :alt="`${patient.first_name} ${patient.last_name}`"
          class="w-16 h-16 rounded-full object-cover shrink-0"
        />
        <div v-else class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-xl font-bold text-emerald-700 shrink-0">
          {{ patient?.first_name?.[0] || '?' }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ patient ? `${patient.first_name} ${patient.last_name}` : 'Patient' }}</h1>
          <p class="text-gray-500">
            <template v-if="patientAge">Age {{ patientAge }}</template>
            <template v-if="patientAge && activeConditions.length"> &middot; </template>
            {{ activeConditions.join(', ') }}
          </p>
        </div>
        <div v-if="engagement" class="ml-auto text-right">
          <p class="text-sm text-gray-500">AI Sessions</p>
          <p class="text-2xl font-bold text-emerald-600">{{ engagement.total_sessions }}</p>
          <p class="text-xs text-gray-400">{{ engagement.total_messages }} messages</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-6">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="[
              'pb-3 text-sm font-medium border-b-2 transition-colors',
              activeTab === tab.id
                ? 'border-emerald-600 text-emerald-700'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Overview Tab -->
      <template v-if="activeTab === 'overview'">
        <!-- Active Alerts -->
        <section v-if="patientAlerts.length > 0">
          <h2 class="text-lg font-semibold text-red-700 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Active Alerts
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="(alert, idx) in patientAlerts"
              :key="idx"
              :class="[
                'rounded-2xl border p-5',
                alert.severity === 'high'
                  ? 'bg-red-50 border-red-300'
                  : 'bg-amber-50 border-amber-300'
              ]"
            >
              <div class="flex items-start gap-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                        alert.severity === 'high'
                          ? 'bg-red-100 text-red-700'
                          : 'bg-amber-100 text-amber-700'
                      ]"
                    >
                      {{ alert.type === 'weight_gain' ? 'Weight Alert' : 'BP Trend' }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ alert.message }}</p>

                  <div v-if="alert.type === 'weight_gain'" class="mt-2 text-xs text-gray-500">
                    {{ alert.data.from }} kg &rarr; {{ alert.data.to }} kg
                    <span class="text-red-600 font-medium">(+{{ alert.data.delta_kg }} kg)</span>
                  </div>

                  <div v-if="alert.type === 'elevated_bp' && alert.data.readings" class="mt-2 flex gap-2 flex-wrap">
                    <span
                      v-for="(r, i) in alert.data.readings"
                      :key="i"
                      class="text-xs bg-white/70 border border-amber-200 rounded px-2 py-0.5"
                    >
                      {{ r.systolic }}/{{ r.diastolic }} <span class="text-gray-400">{{ r.date }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Weight Chart -->
        <div v-if="weightData.length >= 2" class="bg-white rounded-2xl border border-gray-200 p-5">
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

        <!-- Blood Pressure Chart -->
        <div v-if="bpData.length >= 2" class="bg-white rounded-2xl border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Blood Pressure Trend</h2>
          </div>
          <div class="h-64">
            <Line :data="bpChartData" :options="bpChartOptions" />
          </div>
        </div>

        <!-- Visit history -->
        <section>
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Visit History</h2>
          <div class="bg-white rounded-2xl border border-indigo-100 divide-y divide-indigo-50">
            <div v-if="visits.length === 0" class="p-6 text-center text-gray-400">
              No visits recorded.
            </div>
            <router-link
              v-for="visit in visits"
              :key="visit.id"
              :to="`/doctor/patients/${route.params.id}/visits/${visit.id}`"
              class="p-4 flex items-start gap-3 hover:bg-gray-50/50 transition-colors"
            >
              <VisitDateBadge :date="visit.started_at" size="sm" />
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="font-medium text-gray-900">{{ formatVisitType(visit.visit_type) }}</p>
                </div>
                <p v-if="visit.practitioner" class="text-sm text-gray-500">
                  Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                </p>
                <p v-if="visit.reason_for_visit" class="text-sm text-gray-400 mt-1">{{ visit.reason_for_visit }}</p>
              </div>
              <svg class="w-5 h-5 text-gray-300 shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </router-link>
          </div>
        </section>

        <!-- AI Audit Trail -->
        <section>
          <h2 class="text-lg font-semibold text-gray-800 mb-3">AI Audit Trail</h2>
          <div class="bg-white rounded-2xl border border-gray-200">
            <div v-if="chatSessions.length === 0" class="p-6 text-center text-gray-400">
              No AI interactions recorded.
            </div>
            <div v-for="session in chatSessions" :key="session.id" class="border-b border-gray-100 last:border-b-0">
              <button
                class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors text-left"
                @click="toggleSession(session.id)"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ session.topic || 'Chat Session' }}</p>
                    <p class="text-xs text-gray-400">{{ formatDateTime(session.initiated_at) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs px-2 py-1 rounded-full" :class="sessionStatusClass(session.status)">
                    {{ session.status || 'active' }}
                  </span>
                  <span class="text-xs text-gray-400">{{ session.messages?.length || 0 }} msgs</span>
                  <svg
                    class="w-4 h-4 text-gray-400 transition-transform"
                    :class="{ 'rotate-180': expandedSessions.has(session.id) }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </button>

              <div v-if="expandedSessions.has(session.id) && session.messages?.length" class="px-4 pb-4">
                <div class="space-y-2 max-h-96 overflow-y-auto">
                  <div
                    v-for="msg in session.messages"
                    :key="msg.id"
                    class="flex gap-3"
                    :class="msg.sender_type === 'patient' ? '' : 'flex-row-reverse'"
                  >
                    <div
                      class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                      :class="senderAvatarClass(msg.sender_type)"
                    >
                      {{ senderInitial(msg.sender_type) }}
                    </div>
                    <div
                      class="max-w-[75%] rounded-xl px-3 py-2 text-sm"
                      :class="senderBubbleClass(msg.sender_type)"
                    >
                      <p class="whitespace-pre-wrap">{{ msg.message_text }}</p>
                      <p class="text-[10px] mt-1 opacity-60">{{ formatTime(msg.created_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Messages -->
        <section>
          <h2 class="text-lg font-semibold text-gray-800 mb-3">Messages</h2>
          <div class="bg-white rounded-2xl border border-gray-200">
            <div v-if="notifications.length === 0 && !loadingNotifications" class="p-6 text-center text-gray-400">
              No messages from this patient.
            </div>
            <div v-if="loadingNotifications" class="p-6 text-center text-gray-400">
              Loading messages...
            </div>

            <div v-for="notif in notifications" :key="notif.id" class="border-b border-gray-100 last:border-b-0 p-4">
              <div class="flex items-start gap-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                  :class="notif.type === 'doctor_reply' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                >
                  {{ notif.type === 'doctor_reply' ? 'Dr' : 'Pt' }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <p class="font-medium text-sm text-gray-900">{{ notif.title }}</p>
                    <span v-if="!notif.read_at && notif.type !== 'doctor_reply'" class="w-2 h-2 bg-emerald-500 rounded-full shrink-0"></span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ notif.body }}</p>
                  <p class="text-xs text-gray-400 mt-1">{{ formatDateTime(notif.created_at) }}</p>

                  <button
                    v-if="notif.type !== 'doctor_reply'"
                    class="mt-2 text-xs text-emerald-600 hover:text-emerald-700 font-medium"
                    @click="startReply(notif)"
                  >
                    Reply
                  </button>
                </div>
              </div>

              <div v-if="replyingTo === notif.id" class="mt-3 ml-11">
                <textarea
                  v-model="replyText"
                  rows="3"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"
                  placeholder="Type your reply..."
                ></textarea>
                <div class="flex gap-2 mt-2">
                  <button
                    class="px-4 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50"
                    :disabled="!replyText.trim() || sendingReply"
                    @click="sendReply(notif)"
                  >
                    {{ sendingReply ? 'Sending...' : 'Send Reply' }}
                  </button>
                  <button
                    class="px-4 py-1.5 text-gray-600 text-sm rounded-lg hover:bg-gray-100 transition-colors"
                    @click="cancelReply"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </template>

      <!-- Vitals Tab -->
      <VitalsTab v-if="activeTab === 'vitals'" :observations="allObservations" :device-data="null" />

      <!-- Labs Tab -->
      <LabResultsTab v-if="activeTab === 'labs'" :observations="allObservations" />
    </div>
  </DoctorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
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
import DoctorLayout from '@/layouts/DoctorLayout.vue';
import VitalsTab from '@/components/health/VitalsTab.vue';
import LabResultsTab from '@/components/health/LabResultsTab.vue';
import { useDoctorStore } from '@/stores/doctor';
import VisitDateBadge from '@/components/VisitDateBadge.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const route = useRoute();
const api = useApi();
const doctorStore = useDoctorStore();

const activeTab = ref('overview');
const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'vitals', label: 'Vitals' },
    { id: 'labs', label: 'Labs' },
];

const patient = ref(null);
const visits = ref([]);
const allObservations = ref([]);

// Chart data derived from allObservations (matching VitalsTab style)
const weightData = computed(() =>
    allObservations.value
        .filter(o => o.code === '29463-7')
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const bpData = computed(() =>
    allObservations.value
        .filter(o => o.code === '85354-9' && o.specialty_data?.systolic)
        .sort((a, b) => new Date(a.effective_date).getTime() - new Date(b.effective_date).getTime())
);

const weightDelta = computed(() => {
    if (weightData.value.length < 2) return null;
    const first = parseFloat(weightData.value[0].value_quantity);
    const last = parseFloat(weightData.value[weightData.value.length - 1].value_quantity);
    return (last - first).toFixed(1);
});

const weightAvg = computed(() => {
    if (weightData.value.length === 0) return null;
    const sum = weightData.value.reduce((acc, o) => acc + parseFloat(o.value_quantity), 0);
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

function formatShortDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const chatSessions = ref([]);
const engagement = ref(null);
const notifications = ref([]);
const loadingNotifications = ref(false);
const replyText = ref('');
const replyingTo = ref(null);
const sendingReply = ref(false);
const expandedSessions = ref(new Set());

const patientAge = computed(() => {
    if (!patient.value?.dob) return null;
    const dob = new Date(patient.value.dob);
    const now = new Date();
    let age = now.getFullYear() - dob.getFullYear();
    const monthDiff = now.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dob.getDate())) age--;
    return age;
});

const activeConditions = computed(() => {
    if (!patient.value?.conditions?.length) return [];
    return patient.value.conditions.map(c => c.code_display);
});

const patientAlerts = computed(() => {
    return doctorStore.alerts.filter(a => a.patient_id === route.params.id);
});

onMounted(async () => {
    try {
        const [patientRes, visitsRes, engagementRes, chatAuditRes, allObsRes] = await Promise.all([
            api.get(`/doctor/patients/${route.params.id}`),
            api.get(`/doctor/patients/${route.params.id}/visits`),
            api.get(`/doctor/patients/${route.params.id}/engagement`),
            api.get(`/doctor/patients/${route.params.id}/chat-audit`),
            api.get(`/doctor/patients/${route.params.id}/observations`),
        ]);
        patient.value = patientRes.data.data;
        visits.value = visitsRes.data.data;
        engagement.value = engagementRes.data.data;
        chatSessions.value = chatAuditRes.data.data;
        allObservations.value = allObsRes.data.data;
    } catch {
        // Handled by API interceptor
    }

    await fetchNotifications();

    doctorStore.fetchAlerts();
});

async function fetchNotifications() {
    loadingNotifications.value = true;
    try {
        const res = await api.get('/doctor/notifications');
        const patientVisitIds = visits.value.map(v => v.id);
        notifications.value = res.data.data.filter(
            n => patientVisitIds.includes(n.visit_id),
        );
    } catch {
        // Handled by API interceptor
    } finally {
        loadingNotifications.value = false;
    }
}

function toggleSession(sessionId) {
    const next = new Set(expandedSessions.value);
    if (next.has(sessionId)) {
        next.delete(sessionId);
    } else {
        next.add(sessionId);
    }
    expandedSessions.value = next;
}

function startReply(notif) {
    replyingTo.value = notif.id;
    replyText.value = '';
}

function cancelReply() {
    replyingTo.value = null;
    replyText.value = '';
}

async function sendReply(notif) {
    if (!replyText.value.trim()) return;
    sendingReply.value = true;
    try {
        await api.post(`/doctor/messages/${notif.id}/reply`, {
            body: replyText.value.trim(),
        });
        cancelReply();
        await fetchNotifications();
    } catch {
        // Handled by API interceptor
    } finally {
        sendingReply.value = false;
    }
}

function formatVisitType(type) {
    if (!type) return 'Visit';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function sessionStatusClass(status) {
    if (status === 'completed') return 'bg-green-100 text-green-700';
    if (status === 'escalated') return 'bg-red-100 text-red-700';
    return 'bg-blue-100 text-blue-700';
}

function senderInitial(senderType) {
    if (senderType === 'patient') return 'P';
    if (senderType === 'assistant') return 'AI';
    return '?';
}

function senderAvatarClass(senderType) {
    if (senderType === 'patient') return 'bg-emerald-100 text-emerald-700';
    if (senderType === 'assistant') return 'bg-violet-100 text-violet-700';
    return 'bg-gray-100 text-gray-700';
}

function senderBubbleClass(senderType) {
    if (senderType === 'patient') return 'bg-gray-100 text-gray-800';
    if (senderType === 'assistant') return 'bg-violet-50 text-violet-900';
    return 'bg-gray-50 text-gray-800';
}
</script>
