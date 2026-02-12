<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <button
      class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
      @click="expanded = !expanded"
    >
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-800">Attachments</h3>
        <span v-if="documents.length" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
          {{ documents.length }}
        </span>
      </div>
      <span class="text-gray-400 text-sm">{{ expanded ? 'Collapse' : 'Expand' }}</span>
    </button>

    <div v-if="expanded" class="px-4 pb-4 space-y-3">
      <!-- Upload area -->
      <div
        class="border-2 border-dashed rounded-xl p-4 text-center transition-colors cursor-pointer"
        :class="dragOver ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:border-emerald-300'"
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="handleDrop"
        @click="triggerFileInput"
      >
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept="image/*,.pdf"
          multiple
          @change="handleFileSelect"
        />
        <div v-if="uploading" class="flex items-center justify-center gap-2">
          <svg class="w-5 h-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-emerald-600">Uploading...</span>
        </div>
        <div v-else>
          <svg class="w-8 h-8 text-gray-400 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 3.75 3.75 0 013.572 5.346A4.5 4.5 0 0118 19.5H6.75z" />
          </svg>
          <p class="text-sm text-gray-500">
            <span class="text-emerald-600 font-medium">Upload files</span> or drag and drop
          </p>
          <p class="text-xs text-gray-400 mt-0.5">Images, PDF &middot; up to 20 MB</p>
        </div>
      </div>

      <!-- Document type selector (shown after file selection) -->
      <div v-if="pendingFiles.length" class="space-y-2">
        <div v-for="(pf, i) in pendingFiles" :key="i" class="flex items-center gap-2 text-sm">
          <span class="truncate flex-1 text-gray-700">{{ pf.file.name }}</span>
          <select
            v-model="pf.type"
            class="text-xs border border-gray-200 rounded-lg px-2 py-1 text-gray-600"
          >
            <option value="ecg">ECG</option>
            <option value="imaging">Imaging</option>
            <option value="lab_result">Lab Result</option>
            <option value="photo">Photo</option>
            <option value="other">Other</option>
          </select>
          <button class="text-red-400 hover:text-red-600 text-xs" @click="pendingFiles.splice(i, 1)">&times;</button>
        </div>
        <button
          class="w-full px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
          :disabled="uploading"
          @click="uploadAll"
        >
          Upload {{ pendingFiles.length }} file{{ pendingFiles.length > 1 ? 's' : '' }}
        </button>
      </div>

      <!-- Existing documents -->
      <div v-if="documents.length" class="space-y-2">
        <div
          v-for="doc in documents"
          :key="doc.id"
          class="rounded-xl border border-gray-100 overflow-hidden group hover:bg-gray-50/50 transition-colors"
        >
          <!-- Document card header -->
          <div class="flex items-center gap-3 p-2.5">
            <!-- Thumbnail / icon -->
            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
              <img
                v-if="doc.content_type === 'image'"
                :src="`/api/v1/documents/${doc.id}/thumbnail`"
                class="w-full h-full object-cover rounded-lg"
                alt=""
              />
              <svg v-else class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">{{ doc.title }}</p>
              <p class="text-xs text-gray-400">
                {{ formatType(doc.document_type) }}
                &middot; {{ formatSize(doc.file_size) }}
                &middot; {{ formatDate(doc.document_date) }}
              </p>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="`/api/v1/documents/${doc.id}/download`"
                class="p-1.5 text-gray-400 hover:text-emerald-600 transition-colors"
                title="Download"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
              </a>
              <button
                class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                title="Delete"
                @click="deleteDocument(doc)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
              </button>
            </div>
          </div>

          <!-- AI Analysis section -->
          <div v-if="doc._analysis_status" class="border-t border-gray-100">
            <!-- Processing state -->
            <div
              v-if="doc._analysis_status === 'pending' || doc._analysis_status === 'processing'"
              class="px-3 py-3 bg-emerald-50/60 border-l-4 border-emerald-400 animate-[analysing-pulse_2s_ease-in-out_infinite]"
            >
              <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                  <svg class="w-6 h-6 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-400 rounded-full animate-ping"></span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-emerald-800">AI is analyzing this document...</p>
                  <p class="text-xs text-emerald-600 mt-0.5">Extracting findings and key values</p>
                </div>
              </div>
              <!-- Progress bar animation -->
              <div class="mt-2.5 w-full h-1.5 bg-emerald-200/50 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full animate-[analysing-bar_2.5s_ease-in-out_infinite]"></div>
              </div>
            </div>

            <!-- Failed state -->
            <div v-else-if="doc._analysis_status === 'failed'" class="px-3 py-2 flex items-center gap-2">
              <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
              </svg>
              <span class="text-xs text-red-500">Analysis failed</span>
              <button class="text-xs text-emerald-600 hover:underline ml-auto" @click="pollAnalysis(doc)">Retry</button>
            </div>

            <!-- Completed state — collapsible -->
            <div v-else-if="doc._analysis_status === 'completed' && doc._analysis">
              <button
                class="w-full px-3 py-2 flex items-center gap-2 text-left hover:bg-gray-50 transition-colors"
                @click="doc._analysisExpanded = !doc._analysisExpanded"
              >
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
                <span class="text-xs font-medium text-emerald-700">AI Analysis</span>
                <span class="text-xs text-gray-400 ml-auto">{{ doc._analysisExpanded ? 'Hide' : 'Show' }}</span>
              </button>

              <div v-if="doc._analysisExpanded" class="px-3 pb-3 space-y-2.5">
                <!-- Summary (with term highlighting) -->
                <HighlightedText
                  v-if="doc._analysis.summary && matchTermsInText(doc._analysis.summary).length"
                  :content="doc._analysis.summary"
                  :terms="matchTermsInText(doc._analysis.summary)"
                  section-key="attachment_analysis"
                  class="text-sm"
                  @term-click="(payload) => emit('term-click', payload)"
                />
                <p v-else-if="doc._analysis.summary" class="text-sm text-gray-700">{{ doc._analysis.summary }}</p>

                <!-- Findings (with term highlighting on finding text) -->
                <div v-if="doc._analysis.findings?.length" class="space-y-1">
                  <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Findings</p>
                  <div v-for="(f, fi) in doc._analysis.findings" :key="fi" class="flex items-start gap-2">
                    <span
                      class="mt-0.5 inline-block w-2 h-2 rounded-full shrink-0"
                      :class="significanceDot(f.significance)"
                    ></span>
                    <div class="min-w-0">
                      <HighlightedText
                        v-if="matchTermsInText(f.finding).length"
                        :content="f.finding"
                        :terms="matchTermsInText(f.finding)"
                        section-key="attachment_finding"
                        class="inline text-sm"
                        @term-click="(payload) => emit('term-click', payload)"
                      />
                      <span v-else class="text-sm text-gray-700">{{ f.finding }}</span>
                      <span v-if="f.location" class="text-xs text-gray-400 ml-1">({{ f.location }})</span>
                    </div>
                  </div>
                </div>

                <!-- Key values -->
                <div v-if="doc._analysis.key_values?.length">
                  <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Key Values</p>
                  <div class="grid grid-cols-2 gap-1.5">
                    <div
                      v-for="(kv, ki) in doc._analysis.key_values"
                      :key="ki"
                      class="flex items-center justify-between bg-gray-50 rounded-lg px-2.5 py-1.5"
                    >
                      <HighlightedText
                        v-if="matchTermsInText(kv.label).length"
                        :content="kv.label"
                        :terms="matchTermsInText(kv.label)"
                        section-key="attachment_kv"
                        class="text-xs text-gray-500"
                        @term-click="(payload) => emit('term-click', payload)"
                      />
                      <span v-else class="text-xs text-gray-500">{{ kv.label }}</span>
                      <div class="flex items-center gap-1">
                        <span class="text-sm font-medium" :class="valueStatusColor(kv.status)">
                          {{ kv.value }}<span v-if="kv.unit" class="text-xs font-normal text-gray-400 ml-0.5">{{ kv.unit }}</span>
                        </span>
                        <span v-if="kv.status && kv.status !== 'normal'" class="text-[10px] font-medium px-1 py-0.5 rounded" :class="valueStatusBadge(kv.status)">
                          {{ kv.status.toUpperCase() }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Safety note -->
                <p v-if="doc._analysis.safety_note" class="text-[11px] text-gray-400 italic border-t border-gray-100 pt-2">
                  {{ doc._analysis.safety_note }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload from phone button -->
      <button
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-dashed border-gray-300 rounded-xl text-sm text-gray-600 hover:border-emerald-400 hover:text-emerald-600 transition-colors"
        @click="startQrUpload"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
        </svg>
        Upload from phone
      </button>

      <!-- Empty state -->
      <p v-if="!pendingFiles.length && !documents.length" class="text-sm text-gray-400 text-center py-2">
        No attachments yet. Upload your ECG, imaging, or lab results.
      </p>
    </div>

    <!-- QR Code Modal -->
    <Teleport to="body">
      <div v-if="qrModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeQrModal">
        <div class="fixed inset-0 bg-black/40" @click="closeQrModal"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-4 z-10">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Scan with your phone</h3>
            <button class="text-gray-400 hover:text-gray-600 transition-colors" @click="closeQrModal">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-if="qrModal.loading" class="flex items-center justify-center py-12">
            <svg class="w-8 h-8 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </div>

          <div v-else-if="qrModal.url" class="space-y-4">
            <div class="flex justify-center">
              <QrcodeVue :value="qrModal.url" :size="220" level="M" />
            </div>
            <p class="text-sm text-gray-500 text-center">
              Point your phone camera at the QR code to open the upload page.
            </p>
            <div v-if="qrModal.status === 'completed'" class="flex items-center justify-center gap-2 text-emerald-600">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm font-medium">Photo received!</span>
            </div>
            <div v-else class="flex items-center justify-center gap-2 text-gray-400">
              <div class="w-2 h-2 bg-gray-300 rounded-full animate-pulse"></div>
              <span class="text-xs">Waiting for photo...</span>
            </div>
          </div>

          <div v-if="qrModal.error" class="text-sm text-red-500 text-center">
            {{ qrModal.error }}
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useApi } from '@/composables/useApi';
import QrcodeVue from 'qrcode.vue';
import HighlightedText from '@/components/HighlightedText.vue';

const props = defineProps({
    visitId: { type: String, required: true },
    terms: { type: Array, default: () => [] },
});

const emit = defineEmits(['term-click']);

/**
 * Match visit-level medical terms against a given text string.
 * Returns term objects with start/end positions for HighlightedText.
 */
function matchTermsInText(text) {
    if (!text || !props.terms?.length) return [];

    const matched = [];
    const lowerText = text.toLowerCase();

    for (const t of props.terms) {
        const termStr = t.term;
        if (!termStr) continue;

        const lowerTerm = termStr.toLowerCase();
        let searchFrom = 0;

        // Find all occurrences of this term in the text
        while (searchFrom < lowerText.length) {
            const idx = lowerText.indexOf(lowerTerm, searchFrom);
            if (idx === -1) break;

            matched.push({
                term: text.substring(idx, idx + termStr.length),
                definition: t.definition || '',
                start: idx,
                end: idx + termStr.length,
            });
            searchFrom = idx + termStr.length;
        }
    }

    // Sort by position and remove overlaps
    matched.sort((a, b) => a.start - b.start);
    const filtered = [];
    let lastEnd = 0;
    for (const m of matched) {
        if (m.start >= lastEnd) {
            filtered.push(m);
            lastEnd = m.end;
        }
    }

    return filtered;
}

const api = useApi();
const expanded = ref(false);
const documents = ref([]);
const uploading = ref(false);
const dragOver = ref(false);
const fileInput = ref(null);
const pendingFiles = ref([]);
const pollTimers = ref({});

const qrModal = reactive({
    show: false,
    loading: false,
    url: null,
    token: null,
    status: 'pending',
    error: null,
    pollTimer: null,
    expiryTimer: null,
});

function triggerFileInput() {
    if (!uploading.value) {
        fileInput.value?.click();
    }
}

function handleFileSelect(event) {
    addFiles(event.target.files);
    event.target.value = '';
}

function handleDrop(event) {
    dragOver.value = false;
    addFiles(event.dataTransfer.files);
}

function addFiles(fileList) {
    for (const file of fileList) {
        pendingFiles.value.push({
            file,
            type: guessType(file.name),
        });
    }
}

function guessType(filename) {
    const lower = filename.toLowerCase();
    if (lower.includes('ecg') || lower.includes('ekg')) return 'ecg';
    if (lower.includes('echo') || lower.includes('mri') || lower.includes('ct') || lower.includes('xray')) return 'imaging';
    if (lower.includes('lab') || lower.includes('blood') || lower.includes('result')) return 'lab_result';
    return 'photo';
}

async function uploadAll() {
    uploading.value = true;

    for (const pf of pendingFiles.value) {
        const formData = new FormData();
        formData.append('file', pf.file);
        formData.append('document_type', pf.type);

        try {
            const { data } = await api.post(`/visits/${props.visitId}/documents`, formData);
            const doc = data.data;
            doc._analysis_status = doc.analysis_status || 'pending';
            doc._analysis = doc.ai_analysis || null;
            doc._analysisExpanded = false;
            documents.value.unshift(doc);

            // Start polling using the reactive proxy (documents.value[0]) not the raw object
            const reactiveDoc = documents.value[0];
            if (['pending', 'processing'].includes(reactiveDoc._analysis_status) && ['image', 'pdf'].includes(reactiveDoc.content_type)) {
                startPolling(reactiveDoc);
            }
        } catch {
            // Toast handled by api interceptor
        }
    }

    pendingFiles.value = [];
    uploading.value = false;
}

function startPolling(doc) {
    if (pollTimers.value[doc.id]) return;

    pollTimers.value[doc.id] = setInterval(() => pollAnalysis(doc), 3000);
}

function stopPolling(docId) {
    if (pollTimers.value[docId]) {
        clearInterval(pollTimers.value[docId]);
        delete pollTimers.value[docId];
    }
}

async function pollAnalysis(doc) {
    try {
        const { data } = await api.get(`/documents/${doc.id}/analysis`);
        const result = data.data;

        doc._analysis_status = result.analysis_status;
        doc._analysis = result.ai_analysis;

        if (['completed', 'failed', 'skipped'].includes(result.analysis_status)) {
            stopPolling(doc.id);
            if (result.analysis_status === 'completed') {
                doc._analysisExpanded = true;
            }
        }
    } catch {
        stopPolling(doc.id);
    }
}

async function deleteDocument(doc) {
    try {
        stopPolling(doc.id);
        await api.delete(`/documents/${doc.id}`);
        documents.value = documents.value.filter(d => d.id !== doc.id);
    } catch {
        // Toast handled by api interceptor
    }
}

function significanceDot(significance) {
    const map = {
        normal: 'bg-green-400',
        mild: 'bg-emerald-400',
        moderate: 'bg-yellow-400',
        significant: 'bg-orange-400',
        critical: 'bg-red-500',
    };
    return map[significance] || 'bg-gray-300';
}

function valueStatusColor(status) {
    const map = {
        normal: 'text-gray-800',
        low: 'text-blue-600',
        high: 'text-red-600',
        abnormal: 'text-orange-600',
    };
    return map[status] || 'text-gray-800';
}

function valueStatusBadge(status) {
    const map = {
        low: 'bg-blue-50 text-blue-600',
        high: 'bg-red-50 text-red-600',
        abnormal: 'bg-orange-50 text-orange-600',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

function formatType(type) {
    const labels = {
        ecg: 'ECG',
        imaging: 'Imaging',
        lab_result: 'Lab Result',
        photo: 'Photo',
        other: 'Document',
        lab_report: 'Lab Report',
        imaging_report: 'Imaging Report',
        discharge_summary: 'Discharge Summary',
        progress_note: 'Progress Note',
    };
    return labels[type] || type;
}

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

async function startQrUpload() {
    qrModal.show = true;
    qrModal.loading = true;
    qrModal.url = null;
    qrModal.token = null;
    qrModal.status = 'pending';
    qrModal.error = null;

    try {
        const { data } = await api.post(`/visits/${props.visitId}/upload-tokens`);
        qrModal.url = data.data.url;
        qrModal.token = data.data.token;
        qrModal.loading = false;

        // Start polling for upload completion
        qrModal.pollTimer = setInterval(() => pollQrStatus(), 3000);

        // Auto-close after 15 minutes (token expiry)
        qrModal.expiryTimer = setTimeout(() => closeQrModal(), 15 * 60 * 1000);
    } catch {
        qrModal.loading = false;
        qrModal.error = 'Failed to create upload link. Please try again.';
    }
}

async function pollQrStatus() {
    if (!qrModal.token) return;

    try {
        const { data } = await api.get(`/upload-tokens/${qrModal.token}/status`);
        const result = data.data;

        if (result.status === 'completed') {
            qrModal.status = 'completed';
            clearInterval(qrModal.pollTimer);
            qrModal.pollTimer = null;

            // Refresh documents list
            await fetchDocuments();

            // Auto-close after showing success briefly
            setTimeout(() => closeQrModal(), 2000);
        } else if (result.status === 'expired') {
            qrModal.error = 'Upload link expired. Please try again.';
            clearInterval(qrModal.pollTimer);
            qrModal.pollTimer = null;
        }
    } catch {
        // Silent — will retry on next poll
    }
}

function closeQrModal() {
    qrModal.show = false;
    if (qrModal.pollTimer) {
        clearInterval(qrModal.pollTimer);
        qrModal.pollTimer = null;
    }
    if (qrModal.expiryTimer) {
        clearTimeout(qrModal.expiryTimer);
        qrModal.expiryTimer = null;
    }
}

async function fetchDocuments() {
    try {
        const { data } = await api.get(`/visits/${props.visitId}/documents`);
        const docs = data.data || [];

        docs.forEach(doc => {
            doc._analysis_status = doc.analysis_status || null;
            doc._analysis = doc.ai_analysis || null;
            doc._analysisExpanded = doc.analysis_status === 'completed';
        });

        // Assign first so Vue wraps items in reactive proxies
        documents.value = docs;

        // Auto-expand section if any documents have completed analysis
        if (docs.some(d => d.analysis_status === 'completed')) {
            expanded.value = true;
        }

        // Resume polling using reactive proxy objects, not raw ones
        documents.value.forEach(doc => {
            if (['pending', 'processing'].includes(doc._analysis_status) && ['image', 'pdf'].includes(doc.content_type)) {
                startPolling(doc);
            }
        });
    } catch {
        // Silent — documents will be empty
    }
}

onMounted(fetchDocuments);

onUnmounted(() => {
    Object.keys(pollTimers.value).forEach(stopPolling);
    closeQrModal();
});
</script>
