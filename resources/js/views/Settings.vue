<template>
  <component :is="layout">
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>

      <!-- AI Settings -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">AI Settings</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
          <AiTierSelector />
        </div>
      </section>

      <!-- Agents & API -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Agents & API</h2>
        <router-link
          to="/agents"
          class="block bg-white rounded-2xl border border-gray-200 hover:border-emerald-300 hover:shadow-md transition-all group"
        >
          <div class="p-5">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold text-gray-900">Connect AI Agents to Your Health Data</p>
                  <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                  </svg>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                  Generate API tokens, configure MCP servers, and let Claude Code or other AI agents securely access your visits, medications, and lab results.
                </p>
                <div class="flex items-center gap-3 mt-3">
                  <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-full px-2.5 py-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    API Ready
                  </span>
                  <span class="text-xs text-gray-400">8 endpoints available</span>
                </div>
              </div>
            </div>
          </div>
        </router-link>
      </section>

      <!-- Data Governance -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Data Governance</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          <div
            v-for="perm in permissions"
            :key="perm.key"
            class="flex items-center justify-between px-5 py-4"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ perm.label }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ perm.description }}</p>
            </div>
            <button
              type="button"
              role="switch"
              :aria-checked="perm.enabled"
              class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
              :class="perm.enabled ? 'bg-emerald-500' : 'bg-gray-200'"
              @click="perm.enabled = !perm.enabled"
            >
              <span
                class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="perm.enabled ? 'translate-x-5' : 'translate-x-0'"
              />
            </button>
          </div>

          <!-- Export button row -->
          <div class="flex items-center justify-between px-5 py-4">
            <div>
              <p class="text-sm font-medium text-gray-900">Export my data (FHIR R4)</p>
              <p class="text-xs text-gray-500 mt-0.5">Download a standards-compliant copy of your health records</p>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
              @click="exportData"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export
            </button>
          </div>
        </div>
      </section>

      <!-- Audit Logs -->
      <section>
        <div class="flex items-center gap-3 mb-3">
          <h2 class="text-lg font-semibold text-gray-800">Audit Logs</h2>
          <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200/80 ring-inset">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            PHI Access Tracking
          </span>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3">Timestamp</th>
                <th class="px-5 py-3">Action</th>
                <th class="px-5 py-3">Actor</th>
                <th class="px-5 py-3 hidden sm:table-cell">Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(log, idx) in auditLogs"
                :key="idx"
                :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'"
              >
                <td class="px-5 py-3 whitespace-nowrap text-gray-500 font-mono text-xs">{{ log.timestamp }}</td>
                <td class="px-5 py-3">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="actionBadgeClass(log.actionType)"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-5 py-3 text-gray-900 font-medium">{{ log.actor }}</td>
                <td class="px-5 py-3 text-gray-500 hidden sm:table-cell">{{ log.details }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="mt-2 text-xs text-gray-400">
          Showing most recent entries. Full audit trail is retained for 7 years per HIPAA requirements.
        </p>
      </section>

      <!-- Legal -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Legal</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100">
          <router-link
            v-for="link in legalLinks"
            :key="link.to"
            :to="link.to"
            class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors"
          >
            <span class="text-sm font-medium text-gray-900">{{ link.label }}</span>
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </router-link>
        </div>
      </section>
    </div>
  </component>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';
import DoctorLayout from '@/layouts/DoctorLayout.vue';
import AiTierSelector from '@/components/AiTierSelector.vue';

const auth = useAuthStore();
const layout = computed(() => auth.isDoctor ? DoctorLayout : PatientLayout);

// --- Document Permissions (mock state) ---
const permissions = reactive([
  {
    key: 'share_doctor',
    label: 'Share documents with my doctor',
    description: 'Allow your care provider to view uploaded documents and visit summaries',
    enabled: true,
  },
  {
    key: 'ai_analysis',
    label: 'Allow AI analysis of my documents',
    description: 'Enable AI-powered insights, term explanations, and health recommendations',
    enabled: true,
  },
  {
    key: 'share_care_team',
    label: 'Share health data with care team',
    description: 'Let nurses, specialists, and other authorized staff access your records',
    enabled: true,
  },
]);

function exportData() {
  alert('FHIR R4 export will be available in a future release.');
}

const legalLinks = [
  { label: 'Terms of Use', to: '/terms' },
  { label: 'Privacy Policy', to: '/privacy' },
  { label: 'Legal Notice', to: '/legal' },
];

// --- Audit Logs (mock data) ---
const auditLogs = [
  {
    timestamp: '2026-02-11 14:32',
    action: 'Viewed',
    actionType: 'view',
    actor: 'Dr. Moreau',
    details: 'Accessed SOAP note for cardiology follow-up visit',
  },
  {
    timestamp: '2026-02-11 14:30',
    action: 'AI Analysis',
    actionType: 'ai',
    actor: 'PostVisit AI',
    details: 'Completed analysis of uploaded ECG document',
  },
  {
    timestamp: '2026-02-11 09:15',
    action: 'Accessed',
    actionType: 'view',
    actor: 'Anna Kowalska',
    details: 'Opened chat session for visit #1042',
  },
  {
    timestamp: '2026-02-10 16:45',
    action: 'Exported',
    actionType: 'export',
    actor: 'Anna Kowalska',
    details: 'Downloaded visit summary as PDF',
  },
  {
    timestamp: '2026-02-10 11:20',
    action: 'Modified',
    actionType: 'modify',
    actor: 'Dr. Moreau',
    details: 'Updated medication dosage in prescriptions',
  },
  {
    timestamp: '2026-02-09 08:30',
    action: 'AI Analysis',
    actionType: 'ai',
    actor: 'PostVisit AI',
    details: 'Generated term explanations for lab results panel',
  },
  {
    timestamp: '2026-02-08 14:10',
    action: 'Shared',
    actionType: 'share',
    actor: 'Dr. Moreau',
    details: 'Shared visit notes with referring cardiologist',
  },
];

function actionBadgeClass(type) {
  const classes = {
    view: 'bg-blue-50 text-blue-700',
    ai: 'bg-purple-50 text-purple-700',
    export: 'bg-amber-50 text-amber-700',
    modify: 'bg-emerald-50 text-emerald-700',
    share: 'bg-indigo-50 text-indigo-700',
  };
  return classes[type] || 'bg-gray-50 text-gray-700';
}
</script>
