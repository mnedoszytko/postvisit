<template>
  <div class="space-y-6">
    <!-- Upload Zone -->
    <div
      class="bg-white rounded-2xl border-2 border-dashed transition-colors p-6"
      :class="isDragging ? 'border-emerald-400 bg-emerald-50/40' : 'border-gray-200 hover:border-gray-300'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
    >
      <div class="text-center">
        <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
        </svg>
        <p class="text-sm text-gray-600 mb-1">
          Drag files here or
          <button class="text-emerald-600 font-medium hover:text-emerald-700" @click="fileInput?.click()">browse</button>
        </p>
        <p class="text-xs text-gray-400">PDF, JPG, PNG, WebP, HEIC up to 20 MB</p>
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.heic,.heif"
          multiple
          @change="onFileSelect"
        />
      </div>

      <!-- Upload type selector (visible when files pending) -->
      <div v-if="pendingFiles.length > 0" class="mt-4 space-y-3">
        <div v-for="(pf, i) in pendingFiles" :key="i" class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3">
          <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
          </svg>
          <span class="text-sm text-gray-700 truncate flex-1">{{ pf.file.name }}</span>
          <select v-model="pf.type" class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white">
            <option value="lab_result">Lab Result</option>
            <option value="imaging_report">Imaging Report</option>
            <option value="discharge_summary">Discharge Summary</option>
            <option value="prescription">Prescription</option>
            <option value="other">Other</option>
          </select>
          <button class="text-gray-400 hover:text-red-500 transition-colors" @click="pendingFiles.splice(i, 1)">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="flex items-center justify-end gap-2">
          <button
            class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5"
            @click="pendingFiles = []"
          >Cancel</button>
          <button
            class="text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 px-4 py-1.5 rounded-lg transition-colors disabled:opacity-50"
            :disabled="uploading"
            @click="uploadAll"
          >
            {{ uploading ? 'Uploading...' : `Upload ${pendingFiles.length} file${pendingFiles.length > 1 ? 's' : ''}` }}
          </button>
        </div>
      </div>
    </div>

    <!-- Document List -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-900">Medical Documents</h2>
        <span class="text-xs text-gray-400">{{ documents.length }} files</span>
      </div>

      <!-- Empty state -->
      <div v-if="documents.length === 0" class="text-center py-10">
        <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
        <p class="text-sm text-gray-500 mb-1">No documents yet</p>
        <p class="text-xs text-gray-400">Upload your medical records, lab results, or imaging reports</p>
      </div>

      <!-- Document cards -->
      <div v-else class="space-y-2">
        <div
          v-for="doc in documents"
          :key="doc.id"
          class="rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/60 transition-all cursor-pointer group"
          @click="toggleExpand(doc.id)"
        >
          <!-- Card header -->
          <div class="flex items-center gap-3 p-3">
            <!-- Type icon -->
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
              :class="typeIcon(doc.document_type).bg"
            >
              <component :is="typeIcon(doc.document_type).icon" class="w-5 h-5" :class="typeIcon(doc.document_type).color" />
            </div>

            <!-- Info -->
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-800 truncate group-hover:text-gray-900">{{ doc.title }}</p>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-xs text-gray-400">{{ typeLabel(doc.document_type) }}</span>
                <span class="text-gray-300">&middot;</span>
                <span class="text-xs text-gray-400">{{ formatDate(doc.document_date) }}</span>
                <span v-if="doc.file_size" class="text-gray-300">&middot;</span>
                <span v-if="doc.file_size" class="text-xs text-gray-400">{{ formatSize(doc.file_size) }}</span>
              </div>
            </div>

            <!-- Badges & actions -->
            <div class="flex items-center gap-1.5 ml-auto shrink-0">
              <span
                v-if="doc.analysis_status === 'completed'"
                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60"
              >
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
                AI
              </span>
              <span
                v-else-if="doc.analysis_status === 'analyzing'"
                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-200/60 animate-pulse"
              >
                Analyzing...
              </span>
              <AskAiButton @ask="askAboutDocument(doc)" />

              <!-- Expand chevron -->
              <svg
                class="w-4 h-4 text-gray-400 transition-transform"
                :class="expandedId === doc.id ? 'rotate-180' : ''"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Expanded detail -->
          <div v-if="expandedId === doc.id" class="border-t border-gray-100 px-4 py-3 space-y-3">
            <!-- AI Analysis -->
            <div v-if="doc.ai_analysis" class="bg-emerald-50/50 rounded-lg p-3">
              <h4 class="text-xs font-semibold text-emerald-800 uppercase tracking-wide mb-2">AI Analysis</h4>
              <p v-if="doc.ai_analysis.summary" class="text-sm text-gray-700 mb-2">{{ doc.ai_analysis.summary }}</p>
              <div v-if="doc.ai_analysis.findings?.length" class="space-y-1.5">
                <p class="text-xs font-medium text-gray-600">Key Findings:</p>
                <div class="space-y-1.5">
                  <div
                    v-for="(f, i) in doc.ai_analysis.findings"
                    :key="i"
                    class="bg-white rounded-lg border border-emerald-100 px-3 py-2"
                  >
                    <template v-if="typeof f === 'object' && f !== null">
                      <p class="text-xs text-gray-800">{{ f.finding }}</p>
                      <div class="flex items-center gap-2 mt-1">
                        <span v-if="f.location" class="text-[10px] text-gray-400">{{ f.location }}</span>
                        <span
                          v-if="f.significance"
                          class="text-[10px] font-medium px-1.5 py-0.5 rounded-full"
                          :class="{
                            'bg-green-50 text-green-700': f.significance === 'normal',
                            'bg-yellow-50 text-yellow-700': f.significance === 'mild',
                            'bg-orange-50 text-orange-700': f.significance === 'moderate',
                            'bg-red-50 text-red-700': f.significance === 'severe' || f.significance === 'critical',
                          }"
                        >{{ f.significance }}</span>
                      </div>
                    </template>
                    <template v-else>
                      <p class="text-xs text-gray-800">{{ f }}</p>
                    </template>
                  </div>
                </div>
              </div>
              <div v-if="doc.ai_analysis.key_values?.length" class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="(kv, i) in doc.ai_analysis.key_values"
                  :key="i"
                  class="text-xs bg-white border border-emerald-200/60 rounded-lg px-2 py-1 text-gray-700"
                >
                  <span class="font-medium">{{ kv.label }}:</span> {{ kv.value }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <a
                :href="`/api/v1/documents/${doc.id}/download`"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg px-3 py-1.5 transition-colors"
                @click.stop
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download
              </a>
              <button
                class="inline-flex items-center gap-1.5 text-xs font-medium text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition-colors"
                @click.stop="confirmDelete(doc)"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="deleteTarget = null">
        <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4">
          <h3 class="font-semibold text-gray-900 mb-2">Delete Document</h3>
          <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete "{{ deleteTarget.title }}"? This action cannot be undone.</p>
          <div class="flex items-center justify-end gap-2">
            <button class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5" @click="deleteTarget = null">Cancel</button>
            <button
              class="text-sm font-medium text-white bg-red-600 hover:bg-red-700 px-4 py-1.5 rounded-lg transition-colors disabled:opacity-50"
              :disabled="deleting"
              @click="deleteDocument"
            >
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, h, markRaw } from 'vue';
import { useApi } from '@/composables/useApi';
import { useToastStore } from '@/stores/toast';
import AskAiButton from '@/components/AskAiButton.vue';

const props = defineProps<{
    documents: Array<any>;
    patientId: string | null;
}>();

const emit = defineEmits<{
    (e: 'documents-changed', docs: any[]): void;
}>();

const api = useApi();
const toast = useToastStore();
const openGlobalChat = inject<(topic: string) => void>('openGlobalChat', () => {});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const pendingFiles = ref<{ file: File; type: string }[]>([]);
const uploading = ref(false);
const expandedId = ref<string | null>(null);
const deleteTarget = ref<any>(null);
const deleting = ref(false);

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const files = Array.from(e.dataTransfer?.files || []);
    addFiles(files);
}

function onFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    const files = Array.from(input.files || []);
    addFiles(files);
    input.value = '';
}

function addFiles(files: File[]) {
    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'];
    for (const file of files) {
        if (!allowed.includes(file.type) && !file.name.match(/\.(pdf|jpe?g|png|gif|webp|heic|heif)$/i)) {
            toast.warning(`Skipped "${file.name}" — unsupported file type`);
            continue;
        }
        if (file.size > 20 * 1024 * 1024) {
            toast.warning(`Skipped "${file.name}" — file exceeds 20 MB`);
            continue;
        }
        pendingFiles.value.push({ file, type: guessType(file) });
    }
}

function guessType(file: File): string {
    const name = file.name.toLowerCase();
    if (name.includes('lab') || name.includes('blood') || name.includes('cbc')) return 'lab_result';
    if (name.includes('xray') || name.includes('mri') || name.includes('ct') || name.includes('imaging')) return 'imaging_report';
    if (name.includes('discharge')) return 'discharge_summary';
    if (name.includes('prescription') || name.includes('rx')) return 'prescription';
    return 'other';
}

async function uploadAll() {
    if (!props.patientId || pendingFiles.value.length === 0) return;
    uploading.value = true;

    const uploaded: any[] = [];
    for (const pf of pendingFiles.value) {
        try {
            const form = new FormData();
            form.append('file', pf.file);
            form.append('document_type', pf.type);
            const res = await api.post(`/patients/${props.patientId}/documents`, form);
            uploaded.push(res.data.data);
        } catch {
            toast.error(`Failed to upload "${pf.file.name}"`);
        }
    }

    if (uploaded.length > 0) {
        toast.success(`Uploaded ${uploaded.length} document${uploaded.length > 1 ? 's' : ''}`);
        emit('documents-changed', [...uploaded, ...props.documents]);
    }

    pendingFiles.value = [];
    uploading.value = false;
}

function askAboutDocument(doc: any) {
    const parts = [`document: ${doc.title}`];
    if (doc.ai_analysis?.summary) {
        parts.push(`\nAI Analysis: ${doc.ai_analysis.summary}`);
    }
    if (doc.ai_analysis?.findings?.length) {
        const findings = doc.ai_analysis.findings.map((f: any) =>
            typeof f === 'object' ? `${f.finding} (${f.significance || ''})` : f
        ).join('; ');
        parts.push(`\nKey Findings: ${findings}`);
    }
    if (doc.ai_analysis?.key_values?.length) {
        const kvs = doc.ai_analysis.key_values.map((kv: any) => `${kv.label}: ${kv.value}`).join(', ');
        parts.push(`\nKey Values: ${kvs}`);
    }
    openGlobalChat(parts.join(''));
}

function toggleExpand(id: string) {
    expandedId.value = expandedId.value === id ? null : id;
}

function confirmDelete(doc: any) {
    deleteTarget.value = doc;
}

async function deleteDocument() {
    if (!deleteTarget.value) return;
    deleting.value = true;

    try {
        await api.delete(`/documents/${deleteTarget.value.id}`);
        toast.success('Document deleted');
        emit('documents-changed', props.documents.filter(d => d.id !== deleteTarget.value.id));
        deleteTarget.value = null;
    } catch {
        toast.error('Failed to delete document');
    } finally {
        deleting.value = false;
    }
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// --- Type icons ---

const ClipboardDocIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2' }),
            h('rect', { x: '9', y: '3', width: '6', height: '4', rx: '1' }),
            h('path', { d: 'M9 14h6M9 17h4' }),
        ]);
    },
});

const TestTubeIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M9 3v3M15 3v3' }),
            h('path', { d: 'M8 6h8l-1 14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2L8 6z' }),
            h('path', { d: 'M8.5 14h7' }),
        ]);
    },
});

const HeartPulseIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M19.5 12.572l-7.5 7.428-7.5-7.428A5 5 0 0 1 12 6.006a5 5 0 0 1 7.5 6.566z' }),
            h('path', { d: 'M7 12h2l1.5-3 2 6L14 12h3' }),
        ]);
    },
});

const PrescriptionIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('rect', { x: '6', y: '7', width: '12', height: '14', rx: '2' }),
            h('path', { d: 'M8 7V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2' }),
            h('path', { d: 'M10 12h4M12 10v4' }),
        ]);
    },
});

const DocumentIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z' }),
        ]);
    },
});

const TYPE_MAP: Record<string, { icon: any; bg: string; color: string; label: string }> = {
    lab_result:         { icon: TestTubeIcon,     bg: 'bg-purple-50',  color: 'text-purple-600',  label: 'Lab Result' },
    imaging_report:     { icon: HeartPulseIcon,   bg: 'bg-pink-50',    color: 'text-pink-600',    label: 'Imaging Report' },
    discharge_summary:  { icon: ClipboardDocIcon, bg: 'bg-rose-50',    color: 'text-rose-600',    label: 'Discharge Summary' },
    prescription:       { icon: PrescriptionIcon, bg: 'bg-emerald-50', color: 'text-emerald-600', label: 'Prescription' },
    other:              { icon: DocumentIcon,      bg: 'bg-gray-100',   color: 'text-gray-600',    label: 'Document' },
};

function typeIcon(type: string) {
    return TYPE_MAP[type] || TYPE_MAP.other;
}

function typeLabel(type: string): string {
    return (TYPE_MAP[type] || TYPE_MAP.other).label;
}
</script>
