<template>
  <DoctorLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
          <p class="text-sm text-gray-500 mt-1">HIPAA-compliant access trail for all patient health data</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            :disabled="!logs.length || exporting"
            @click="exportCsv"
          >
            {{ exporting ? 'Exporting...' : 'Export CSV' }}
          </button>
          <button
            class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
            @click="fetchLogs"
            :disabled="loading"
          >
            Refresh
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-gray-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
          <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
            <select
              v-model="filters.action_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
              @change="fetchLogs"
            >
              <option value="">All actions</option>
              <option value="read">Read</option>
              <option value="create">Create</option>
              <option value="update">Update</option>
              <option value="delete">Delete</option>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
              <option value="download">Download</option>
            </select>
          </div>
          <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Resource</label>
            <select
              v-model="filters.resource_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
              @change="fetchLogs"
            >
              <option value="">All resources</option>
              <option value="visit">Visits</option>
              <option value="patient">Patients</option>
              <option value="document">Documents</option>
              <option value="chat_session">Chat</option>
              <option value="transcript">Transcripts</option>
              <option value="visit_note">SOAP Notes</option>
              <option value="observation">Observations</option>
              <option value="medication">Medications</option>
              <option value="auth">Auth Events</option>
            </select>
          </div>
          <button
            v-if="filters.action_type || filters.resource_type"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
            @click="clearFilters"
          >
            Clear
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading && !logs.length" class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
        Loading audit logs...
      </div>

      <!-- Empty -->
      <div v-else-if="!logs.length" class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
        </svg>
        <p class="text-gray-500 text-sm">No audit entries found</p>
        <p class="text-gray-400 text-xs mt-1">Access logs will appear here as patients and staff interact with the system</p>
      </div>

      <!-- Log table -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">When</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Resource</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">PHI</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="log in logs"
                :key="log.id"
                class="hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ formatTime(log.accessed_at) }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span
                      :class="[
                        'inline-flex items-center justify-center w-5 h-5 rounded-full text-[9px] font-bold',
                        log.user_role === 'doctor' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'
                      ]"
                    >
                      {{ log.user_role === 'doctor' ? 'D' : 'P' }}
                    </span>
                    <span class="text-gray-800 font-medium text-xs">{{ log.user?.name || 'Unknown' }}</span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                      actionStyle(log.action_type)
                    ]"
                  >
                    {{ log.action_type }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-gray-700 text-xs">{{ formatResource(log.resource_type) }}</span>
                  <span class="text-gray-400 text-[10px] ml-1 font-mono">{{ shortId(log.resource_id) }}</span>
                </td>
                <td class="px-4 py-3">
                  <div v-if="log.phi_accessed && log.phi_elements?.length" class="flex flex-wrap gap-1">
                    <span
                      v-for="el in log.phi_elements.slice(0, 2)"
                      :key="el"
                      class="text-[10px] bg-amber-50 text-amber-700 px-1.5 py-0.5 rounded"
                    >
                      {{ el }}
                    </span>
                    <span v-if="log.phi_elements.length > 2" class="text-[10px] text-gray-400">
                      +{{ log.phi_elements.length - 2 }}
                    </span>
                  </div>
                  <span v-else class="text-gray-300 text-xs">&mdash;</span>
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex w-2 h-2 rounded-full',
                      log.success ? 'bg-emerald-400' : 'bg-red-400'
                    ]"
                  />
                </td>
                <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ log.ip_address }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
          <p class="text-xs text-gray-500">
            {{ pagination.total }} entries &middot; Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
          </p>
          <div class="flex gap-2">
            <button
              :disabled="pagination.currentPage <= 1"
              class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              @click="goToPage(pagination.currentPage - 1)"
            >
              Previous
            </button>
            <button
              :disabled="pagination.currentPage >= pagination.lastPage"
              class="px-3 py-1.5 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              @click="goToPage(pagination.currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </DoctorLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import DoctorLayout from '@/layouts/DoctorLayout.vue';

const api = useApi();
const loading = ref(false);
const exporting = ref(false);
const logs = ref([]);
const filters = reactive({ action_type: '', resource_type: '' });
const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0 });

async function fetchLogs(page = 1) {
    loading.value = true;
    try {
        const params = { per_page: 30, page };
        if (filters.action_type) params.action_type = filters.action_type;
        if (filters.resource_type) params.resource_type = filters.resource_type;

        const { data } = await api.get('/audit/logs', { params });
        const paginated = data.data;
        logs.value = paginated.data;
        pagination.currentPage = paginated.current_page;
        pagination.lastPage = paginated.last_page;
        pagination.total = paginated.total;
    } catch {
        logs.value = [];
    } finally {
        loading.value = false;
    }
}

function goToPage(page) {
    fetchLogs(page);
}

async function exportCsv() {
    exporting.value = true;
    try {
        const params = {};
        if (filters.action_type) params.action_type = filters.action_type;
        if (filters.resource_type) params.resource_type = filters.resource_type;

        const response = await api.get('/audit/export', { params, responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit-log-${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    } catch {
        // silently fail
    } finally {
        exporting.value = false;
    }
}

function clearFilters() {
    filters.action_type = '';
    filters.resource_type = '';
    fetchLogs();
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMin = Math.floor(diffMs / 60000);

    if (diffMin < 1) return 'just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffMin < 1440) return `${Math.floor(diffMin / 60)}h ago`;

    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatResource(type) {
    const map = {
        visit: 'Visit',
        patient: 'Patient',
        patient_profile: 'Profile',
        document: 'Document',
        chat_session: 'Chat',
        transcript: 'Transcript',
        visit_note: 'SOAP Note',
        medical_terms: 'Med Terms',
        explanation: 'Explanation',
        observation: 'Observation',
        condition: 'Condition',
        medication: 'Medication',
        health_summary: 'Health Summary',
        auth: 'Auth',
        audit_log: 'Audit Log',
    };
    return map[type] || type;
}

function actionStyle(action) {
    const styles = {
        read: 'bg-blue-50 text-blue-700',
        create: 'bg-emerald-50 text-emerald-700',
        update: 'bg-amber-50 text-amber-700',
        delete: 'bg-red-50 text-red-700',
        login: 'bg-indigo-50 text-indigo-700',
        logout: 'bg-gray-100 text-gray-600',
        download: 'bg-violet-50 text-violet-700',
    };
    return styles[action] || 'bg-gray-100 text-gray-600';
}

function shortId(id) {
    if (!id) return '';
    return id.length > 12 ? id.slice(0, 8) : id;
}

onMounted(() => fetchLogs());
</script>
