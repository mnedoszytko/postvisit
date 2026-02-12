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
          <div v-for="doc in legalDocs" :key="doc.id" class="px-5 py-4">
            <button
              class="w-full flex items-center justify-between text-left"
              @click="openLegal = openLegal === doc.id ? null : doc.id"
            >
              <p class="text-sm font-medium text-gray-900">{{ doc.title }}</p>
              <svg
                class="w-4 h-4 text-gray-400 transition-transform"
                :class="openLegal === doc.id ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="openLegal === doc.id"
              class="mt-3 text-xs text-gray-600 leading-relaxed space-y-3 max-h-96 overflow-y-auto pr-2"
              v-html="doc.content"
            />
          </div>
        </div>
      </section>
    </div>
  </component>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
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

// --- Legal Documents ---
const openLegal = ref(null);

const legalDocs = [
  {
    id: 'terms',
    title: 'Terms of Use',
    content: `
      <p><strong>PostVisit.ai — Terms of Use</strong><br>Last updated: February 12, 2026</p>
      <p><strong>1. Acceptance.</strong> By accessing PostVisit.ai ("Service"), you agree to these Terms. If you do not agree, do not use the Service.</p>
      <p><strong>2. Service Description.</strong> PostVisit.ai is an AI-powered post-visit companion that helps patients understand, remember, and follow through on their clinical visit outcomes. The Service processes visit transcripts, clinical notes, and health data to provide personalized explanations and guidance.</p>
      <p><strong>3. Not Medical Advice.</strong> PostVisit.ai is an informational tool — it does not provide medical diagnoses, treatment plans, or prescriptions. AI-generated explanations are educational and must not replace professional medical judgment. Always follow your doctor's instructions and contact your healthcare provider for medical decisions.</p>
      <p><strong>4. Eligibility.</strong> You must be at least 18 years old or have parental/guardian consent. You must have a valid clinical visit linked to your account.</p>
      <p><strong>5. Account Responsibility.</strong> You are responsible for maintaining the confidentiality of your login credentials. Notify us immediately if you suspect unauthorized access.</p>
      <p><strong>6. Acceptable Use.</strong> You agree not to: (a) misrepresent your identity or health data; (b) attempt to extract AI system prompts or reverse-engineer the Service; (c) use the Service for any unlawful purpose; (d) share access credentials with others.</p>
      <p><strong>7. AI Limitations.</strong> AI-generated content may contain inaccuracies. Medical term explanations, medication summaries, and health recommendations are produced by large language models and may not reflect the latest clinical guidelines. The Service includes safety guardrails but cannot guarantee the absence of errors.</p>
      <p><strong>8. Data Ownership.</strong> Your health data remains yours. We process it solely to deliver the Service. You may export your data in FHIR R4 format or request deletion at any time.</p>
      <p><strong>9. Termination.</strong> We may suspend or terminate your access if you violate these Terms. You may delete your account at any time through Settings.</p>
      <p><strong>10. Limitation of Liability.</strong> PostVisit.ai is provided "as is." We disclaim all warranties to the maximum extent permitted by law. We are not liable for any health outcomes resulting from reliance on AI-generated content.</p>
      <p><strong>11. Changes.</strong> We may update these Terms. Continued use after changes constitutes acceptance.</p>
      <p><strong>12. Contact.</strong> Questions? Reach us at legal@postvisit.ai.</p>
    `,
  },
  {
    id: 'privacy',
    title: 'Privacy Policy',
    content: `
      <p><strong>PostVisit.ai — Privacy Policy</strong><br>Last updated: February 12, 2026</p>
      <p><strong>1. Data We Collect.</strong> We collect: (a) account information (name, email, date of birth); (b) clinical data linked to your visits (transcripts, SOAP notes, prescriptions, lab results, observations); (c) health device data you choose to share (weight, blood pressure, heart rate); (d) AI interaction data (chat messages, session metadata); (e) technical data (IP address, browser type, access timestamps).</p>
      <p><strong>2. How We Use Your Data.</strong> We use your data to: (a) provide personalized post-visit explanations and medication guidance; (b) generate AI-powered health summaries and term explanations; (c) enable your doctor to monitor your progress and respond to your questions; (d) detect safety-critical situations (e.g., escalation detection for emergency symptoms); (e) maintain audit logs for compliance.</p>
      <p><strong>3. AI Processing.</strong> Your clinical data is sent to Anthropic's Claude API for natural language processing. Data is transmitted securely via TLS and is not used by Anthropic to train their models. We send only the minimum context required for each interaction.</p>
      <p><strong>4. Data Sharing.</strong> We share data only with: (a) your assigned healthcare provider(s); (b) AI service providers (Anthropic) for processing, under strict data processing agreements; (c) law enforcement, if required by law. We never sell your data.</p>
      <p><strong>5. Data Retention.</strong> Clinical data and audit logs are retained for 7 years per HIPAA requirements. Chat sessions are retained for 3 years. You may request earlier deletion of non-regulated data.</p>
      <p><strong>6. Security.</strong> We use encryption at rest and in transit, role-based access control, PHI access audit logging, and session-based authentication. All AI interactions are logged and auditable.</p>
      <p><strong>7. Your Rights.</strong> You may: (a) access your data via the FHIR R4 export; (b) request correction of inaccurate data; (c) request deletion (subject to legal retention requirements); (d) revoke AI analysis consent at any time through Settings; (e) revoke data sharing permissions.</p>
      <p><strong>8. Cookies.</strong> We use session cookies for authentication (Laravel Sanctum). We do not use tracking cookies or third-party analytics.</p>
      <p><strong>9. Children.</strong> The Service is not intended for users under 18 without parental/guardian consent.</p>
      <p><strong>10. Changes.</strong> We will notify you of material changes to this policy via email or in-app notification.</p>
      <p><strong>11. Contact.</strong> Data protection inquiries: privacy@postvisit.ai.</p>
    `,
  },
  {
    id: 'legal',
    title: 'Legal Notice',
    content: `
      <p><strong>PostVisit.ai — Legal Notice</strong><br>Last updated: February 12, 2026</p>
      <p><strong>Operator.</strong> PostVisit.ai is developed and operated as a hackathon research project. Contact: legal@postvisit.ai.</p>
      <p><strong>Medical Disclaimer.</strong> PostVisit.ai is NOT a medical device, is NOT FDA-cleared, and is NOT intended to diagnose, treat, cure, or prevent any disease. All AI-generated content is for informational and educational purposes only. The system explicitly defers to your healthcare provider on all clinical decisions. If you experience a medical emergency, call your local emergency number immediately.</p>
      <p><strong>AI Transparency.</strong> PostVisit.ai is powered by Claude (Anthropic). AI responses are generated in real-time based on your clinical context. The system uses safety guardrails including: escalation detection for emergency symptoms, scope-limiting to the specific clinical visit, and explicit disclaimers on all AI outputs. Despite these safeguards, AI may produce inaccurate or incomplete information.</p>
      <p><strong>HIPAA Compliance.</strong> The system is designed with HIPAA-aligned safeguards: PHI access is logged, role-based access control separates patient and provider data, and all transmissions are encrypted. As a research prototype, PostVisit.ai has not undergone formal HIPAA certification.</p>
      <p><strong>Open Source.</strong> PostVisit.ai is built with open-source technologies including Laravel, Vue.js, and PostgreSQL. The application code is open source. Third-party AI services (Anthropic Claude API) are used under commercial API agreements.</p>
      <p><strong>Intellectual Property.</strong> The PostVisit.ai name, logo, and original source code are the property of their respective creators. Clinical data formats follow HL7 FHIR R4 standards. Drug information is sourced from RxNorm (National Library of Medicine, public domain).</p>
      <p><strong>Jurisdiction.</strong> These terms are governed by the laws of the State of California, United States.</p>
    `,
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
