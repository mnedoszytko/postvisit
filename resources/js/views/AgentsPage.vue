<template>
  <PatientLayout>
  <div class="space-y-6">
      <router-link
        to="/settings"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-emerald-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back to Settings
      </router-link>

      <div>
        <h1 class="text-2xl font-bold text-gray-900">AI Agents & API</h1>
        <p class="text-sm text-gray-500 mt-1">Connect external AI agents to your health data via the PostVisit API</p>
      </div>

      <!-- API Endpoint Info -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">PostVisit API</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-900">Base URL</p>
              <div class="mt-1 flex items-center gap-2">
                <code class="text-sm bg-gray-100 rounded-lg px-3 py-1.5 text-emerald-700 font-mono">https://api.postvisit.ai/v1/</code>
                <button
                  class="text-gray-400 hover:text-emerald-600 transition-colors"
                  title="Copy URL"
                  @click="copyText('https://api.postvisit.ai/v1/')"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div>
            <p class="text-sm font-medium text-gray-900 mb-1">Authentication</p>
            <p class="text-sm text-gray-600">Bearer token via <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5 font-mono">Authorization</code> header</p>
          </div>

          <div>
            <p class="text-sm font-medium text-gray-900 mb-2">Available Endpoints</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div v-for="ep in endpoints" :key="ep.path" class="flex items-start gap-2 bg-gray-50 rounded-lg px-3 py-2">
                <span class="shrink-0 mt-0.5 text-xs font-mono font-semibold rounded px-1.5 py-0.5" :class="ep.method === 'GET' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">{{ ep.method }}</span>
                <div class="min-w-0">
                  <code class="text-xs font-mono text-gray-800">{{ ep.path }}</code>
                  <p class="text-xs text-gray-500 mt-0.5">{{ ep.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Token Management -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">API Tokens</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">
          <!-- Generate token form -->
          <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
            <div class="flex-1 w-full">
              <label for="token-name" class="block text-sm font-medium text-gray-700 mb-1">Token name</label>
              <input
                id="token-name"
                v-model="newTokenName"
                type="text"
                placeholder="e.g. Claude Code Agent"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              />
            </div>
            <div>
              <label for="token-expiry" class="block text-sm font-medium text-gray-700 mb-1">Expires in</label>
              <select
                id="token-expiry"
                v-model="tokenExpiry"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="30">30 days</option>
                <option value="90">90 days</option>
                <option value="365">1 year</option>
              </select>
            </div>
            <button
              class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors shrink-0"
              @click="generateToken"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Generate Token
            </button>
          </div>

          <!-- Newly generated token alert -->
          <div v-if="justCreatedToken" class="rounded-lg bg-emerald-50 border border-emerald-200 p-4">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-emerald-800">Token created! Copy it now — it won't be shown again.</p>
                <div class="mt-2 flex items-center gap-2">
                  <code class="text-xs bg-white rounded-lg px-3 py-1.5 text-gray-800 font-mono border border-emerald-200 break-all">{{ justCreatedToken }}</code>
                  <button
                    class="text-emerald-600 hover:text-emerald-800 transition-colors shrink-0"
                    title="Copy token"
                    @click="copyText(justCreatedToken)"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Active tokens list -->
          <div v-if="tokens.length > 0">
            <p class="text-sm font-medium text-gray-700 mb-2">Active Tokens</p>
            <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
              <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between px-4 py-3 bg-white">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ token.name }}</p>
                  <p class="text-xs text-gray-500 mt-0.5">
                    <span class="font-mono">{{ token.prefix }}...{{ token.suffix }}</span>
                    <span class="mx-1.5 text-gray-300">|</span>
                    Created {{ token.created }}
                    <span class="mx-1.5 text-gray-300">|</span>
                    Expires {{ token.expires }}
                  </p>
                </div>
                <button
                  class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors px-2 py-1 rounded hover:bg-red-50"
                  @click="revokeToken(token.id)"
                >
                  Revoke
                </button>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 italic">No active tokens. Generate one above to get started.</p>
        </div>
      </section>

      <!-- Agent Instructions -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Connect an AI Agent</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-5">
          <p class="text-sm text-gray-600">
            Use the PostVisit API to let AI agents (Claude Code, custom scripts, or any MCP-compatible client) securely query your health data, add observations, and interact with your visit history.
          </p>

          <!-- Step 1: cURL example -->
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">1</span>
              <p class="text-sm font-medium text-gray-900">Test with cURL</p>
            </div>
            <div class="relative group">
              <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-x-auto leading-relaxed"><code>curl -s https://api.postvisit.ai/v1/health/summary \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool</code></pre>
              <button
                class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-700 hover:bg-gray-600 text-gray-300 rounded px-2 py-1 text-xs"
                @click="copyText(curlExample)"
              >
                Copy
              </button>
            </div>
          </div>

          <!-- Step 2: MCP Server config -->
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">2</span>
              <p class="text-sm font-medium text-gray-900">Add as MCP Server (Claude Code / Claude Desktop)</p>
            </div>
            <p class="text-sm text-gray-500 mb-2">Add this to your <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5 font-mono">claude_desktop_config.json</code> or <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5 font-mono">.mcp.json</code>:</p>
            <div class="relative group">
              <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-x-auto leading-relaxed"><code>{{ mcpConfigJson }}</code></pre>
              <button
                class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-700 hover:bg-gray-600 text-gray-300 rounded px-2 py-1 text-xs"
                @click="copyText(mcpConfigJson)"
              >
                Copy
              </button>
            </div>
          </div>

          <!-- Step 3: Start chatting -->
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">3</span>
              <p class="text-sm font-medium text-gray-900">Start using your agent</p>
            </div>
            <p class="text-sm text-gray-600">
              Once connected, your AI agent can query your health data, ask about medications, review visit summaries, and add observations — all with your explicit permission per request.
            </p>
          </div>
        </div>
      </section>

      <!-- Skill Description -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-3">MCP Skill Definition</h2>
        <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">
          <p class="text-sm text-gray-600">
            PostVisit exposes itself as an MCP tool with the following skill definition. AI agents use this to understand what capabilities are available.
          </p>
          <div class="relative group">
            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-x-auto leading-relaxed"><code>{{ skillDefinitionJson }}</code></pre>
            <button
              class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-700 hover:bg-gray-600 text-gray-300 rounded px-2 py-1 text-xs"
              @click="copyText(skillDefinitionJson)"
            >
              Copy
            </button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <p class="text-sm font-semibold text-gray-900">Query</p>
              <p class="text-xs text-gray-500 mt-1">Health data, visits, labs, medications</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <p class="text-sm font-semibold text-gray-900">Observe</p>
              <p class="text-xs text-gray-500 mt-1">Add symptoms, notes, measurements</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3 text-center">
              <p class="text-sm font-semibold text-gray-900">Chat</p>
              <p class="text-xs text-gray-500 mt-1">Ask questions about visit context</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Copy toast -->
      <Transition name="fade">
        <div v-if="copyToast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-lg">
          Copied to clipboard
        </div>
      </Transition>
  </div>
  </PatientLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import PatientLayout from '@/layouts/PatientLayout.vue';

// --- API Endpoints ---
const endpoints = [
  { method: 'GET', path: '/health/summary', description: 'Patient health overview' },
  { method: 'GET', path: '/health/observations', description: 'Vital signs and measurements' },
  { method: 'POST', path: '/health/observations', description: 'Add new observation' },
  { method: 'GET', path: '/visits', description: 'List all visits' },
  { method: 'GET', path: '/visits/:id/notes', description: 'Visit SOAP notes and terms' },
  { method: 'GET', path: '/documents', description: 'Uploaded medical documents' },
  { method: 'POST', path: '/documents', description: 'Upload a document' },
  { method: 'POST', path: '/chat', description: 'AI-powered health chat (SSE)' },
];

// --- Token Management ---
interface Token {
  id: string;
  name: string;
  prefix: string;
  suffix: string;
  created: string;
  expires: string;
}

const newTokenName = ref('');
const tokenExpiry = ref('90');
const justCreatedToken = ref('');
const tokens = reactive<Token[]>([]);

function generateUUID(): string {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

function generateToken(): void {
  const name = newTokenName.value.trim() || 'Unnamed Token';
  const fullToken = `pv_${generateUUID().replace(/-/g, '')}`;

  const now = new Date();
  const expiresDate = new Date(now);
  expiresDate.setDate(expiresDate.getDate() + parseInt(tokenExpiry.value));

  const token: Token = {
    id: generateUUID(),
    name,
    prefix: fullToken.slice(0, 7),
    suffix: fullToken.slice(-4),
    created: now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
    expires: expiresDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
  };

  tokens.unshift(token);
  justCreatedToken.value = fullToken;
  newTokenName.value = '';
}

function revokeToken(id: string): void {
  const idx = tokens.findIndex((t) => t.id === id);
  if (idx !== -1) {
    tokens.splice(idx, 1);
  }
}

// --- Copy to clipboard ---
const copyToast = ref(false);

async function copyText(text: string): Promise<void> {
  try {
    await navigator.clipboard.writeText(text);
    copyToast.value = true;
    setTimeout(() => {
      copyToast.value = false;
    }, 2000);
  } catch (err) {
    console.error('Copy to clipboard failed:', err);
  }
}

// --- Code snippets ---
const curlExample = `curl -s https://api.postvisit.ai/v1/health/summary \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -H "Accept: application/json" | python3 -m json.tool`;

const mcpConfigJson = JSON.stringify(
  {
    mcpServers: {
      postvisit: {
        url: 'https://api.postvisit.ai/v1/mcp',
        headers: {
          Authorization: 'Bearer YOUR_TOKEN',
        },
      },
    },
  },
  null,
  2,
);

const skillDefinitionJson = JSON.stringify(
  {
    name: 'postvisit',
    description: 'Query and interact with patient health data from PostVisit.ai — visit summaries, medications, lab results, observations, and AI-powered clinical chat.',
    capabilities: [
      {
        name: 'query_health_data',
        description: 'Retrieve patient health summary, observations, lab results, and medication list',
      },
      {
        name: 'query_visits',
        description: 'List visits, read SOAP notes, view medical terms and explanations',
      },
      {
        name: 'add_observation',
        description: 'Record a new symptom, measurement, or patient-reported observation',
      },
      {
        name: 'manage_documents',
        description: 'Upload or retrieve medical documents (lab reports, imaging, referrals)',
      },
      {
        name: 'clinical_chat',
        description: 'Ask questions about a specific visit with AI-powered context-aware responses',
      },
    ],
  },
  null,
  2,
);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
