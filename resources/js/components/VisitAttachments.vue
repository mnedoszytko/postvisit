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
          class="flex items-center gap-3 rounded-xl border border-gray-100 p-2.5 group hover:bg-gray-50 transition-colors"
        >
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
      </div>

      <!-- Empty state -->
      <p v-else-if="!pendingFiles.length" class="text-sm text-gray-400 text-center py-2">
        No attachments yet. Upload your ECG, imaging, or lab results.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';

const props = defineProps({
    visitId: { type: String, required: true },
});

const api = useApi();
const expanded = ref(true);
const documents = ref([]);
const uploading = ref(false);
const dragOver = ref(false);
const fileInput = ref(null);
const pendingFiles = ref([]);

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
            const { data } = await api.post(`/visits/${props.visitId}/documents`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            documents.value.unshift(data.data);
        } catch {
            // Toast handled by api interceptor
        }
    }

    pendingFiles.value = [];
    uploading.value = false;
}

async function deleteDocument(doc) {
    try {
        await api.delete(`/documents/${doc.id}`);
        documents.value = documents.value.filter(d => d.id !== doc.id);
    } catch {
        // Toast handled by api interceptor
    }
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

async function fetchDocuments() {
    try {
        const { data } = await api.get(`/visits/${props.visitId}/documents`);
        documents.value = data.data || [];
    } catch {
        // Silent — documents will be empty
    }
}

onMounted(fetchDocuments);
</script>
