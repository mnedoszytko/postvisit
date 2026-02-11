<template>
  <PatientLayout>
    <div class="max-w-lg mx-auto space-y-6">
      <!-- Consent step -->
      <div v-if="step === 'consent'" class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <h1 class="text-2xl font-bold text-gray-900 text-center">Companion Scribe</h1>
        <p class="text-gray-600 text-center">
          Record your doctor's visit to get a complete, understandable summary afterwards.
        </p>

        <!-- Visit info form -->
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Healthcare Provider</label>
            <select
              v-model="selectedPractitionerId"
              class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500"
            >
              <option value="" disabled>Select your doctor...</option>
              <option v-for="p in practitioners" :key="p.id" :value="p.id">
                Dr. {{ p.first_name }} {{ p.last_name }} — {{ p.primary_specialty }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Visit Date</label>
            <input
              v-model="visitDate"
              type="date"
              class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500"
            />
          </div>
          <div v-if="selectedPractitioner" class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 rounded-xl px-3 py-2">
            <span class="font-medium text-gray-700">{{ selectedPractitioner.medical_degree }}</span>
            <span>&middot;</span>
            <span>{{ selectedPractitioner.primary_specialty }}</span>
          </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 text-left space-y-2">
          <p class="font-medium">Both parties must consent</p>
          <p>By pressing the button below, you confirm that both the healthcare provider and the patient have agreed to record this visit.</p>
          <p>
            <a href="/privacy-policy" target="_blank" class="text-amber-700 underline hover:text-amber-900">Privacy Policy</a>
          </p>
        </div>
        <button
          class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
          :disabled="starting || !selectedPractitionerId || !visitDate"
          @click="startRecording"
        >
          {{ starting ? 'Requesting microphone...' : 'Both Parties Consent — Start Recording' }}
        </button>
        <button
          v-if="demoMode"
          class="w-full py-3 border-2 border-emerald-600 text-emerald-700 rounded-xl font-medium hover:bg-emerald-50 transition-colors"
          :disabled="demoLoading"
          @click="useDemoTranscript"
        >
          {{ demoLoading ? 'Creating demo visit...' : 'Use Demo Recording (26 min cardiology visit)' }}
        </button>
        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      </div>

      <!-- Recording + Processing (seamless transition) -->
      <div v-else-if="step === 'recording' || step === 'processing'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-6">
        <h2 class="text-lg font-semibold text-gray-800 transition-all duration-500">
          {{ step === 'recording' ? 'Recording in progress...' : uploadStatusText }}
        </h2>

        <!-- Visualizer during recording -->
        <ThreeVisualizer v-if="step === 'recording'" />

        <!-- Sound wave animation during processing -->
        <div v-else class="flex items-center justify-center gap-[3px] h-[120px]">
          <div
            v-for="i in 24"
            :key="i"
            class="sound-bar w-[4px] rounded-full"
            :class="uploading ? 'bg-emerald-500' : 'bg-gray-300'"
            :style="{
              animationDelay: `${(i * 0.08) - 0.5}s`,
              animationDuration: `${0.8 + Math.sin(i * 0.7) * 0.4}s`,
            }"
          />
        </div>

        <div class="space-y-1">
          <p class="text-2xl font-mono tracking-widest transition-colors duration-500" :class="step === 'recording' ? 'text-gray-700' : 'text-emerald-600'">
            {{ formattedTime }}
          </p>
          <p v-if="step === 'recording'" class="text-xs text-gray-400">
            {{ audioSegments.length > 0 ? `Segment ${audioSegments.length + 1} · ` : '' }}Your conversation is being captured securely
          </p>
          <p v-else-if="uploading" class="text-xs text-gray-400">
            {{ uploadDetailText }}
          </p>
        </div>

        <!-- Progress bar (processing only) -->
        <div v-if="step === 'processing' && uploading" class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
          <div class="bg-emerald-500 h-full rounded-full transition-all duration-700 ease-out" :style="{ width: uploadProgress + '%' }" />
        </div>

        <!-- Stop button (recording) -->
        <button
          v-if="step === 'recording'"
          class="w-full py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
          @click="stopRecording"
        >
          Stop Recording
        </button>

        <!-- Error + retry (processing) -->
        <div v-if="step === 'processing' && error" class="space-y-3">
          <p class="text-red-600 text-sm">{{ error }}</p>
          <button
            class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
            @click="processVisit"
          >
            Retry
          </button>
        </div>
      </div>
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useApi } from '@/composables/useApi';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';
import ThreeVisualizer from '@/components/ThreeVisualizer.vue';

const router = useRouter();
const route = useRoute();
const api = useApi();
const auth = useAuthStore();

// Recording state
const step = ref('consent');
const seconds = ref(0);
const starting = ref(false);
const uploading = ref(false);
const error = ref('');
const demoMode = ref(false);
const demoLoading = ref(false);

// Visit info form
const practitioners = ref([]);
const selectedPractitionerId = ref('');
const visitDate = ref(new Date().toISOString().slice(0, 10));

const selectedPractitioner = computed(() =>
    practitioners.value.find(p => p.id === selectedPractitionerId.value) || null
);

// Upload progress
const uploadProgress = ref(0);
const uploadStatusText = ref('Processing audio...');
const uploadDetailText = ref('Transcribing with Whisper AI...');

// Persisted visit ID — allows retry without creating a new visit, or reuse from existing visit
let createdVisitId = route.query.visitId || null;

// Retry wrapper for transient server errors (502, 503, network timeouts)
async function withRetry(fn, { maxRetries = 3, delayMs = 2000, onRetry = null } = {}) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await fn();
        } catch (err) {
            const status = err.response?.status;
            const isRetryable = status === 502 || status === 503 || status === 504 || !err.response;
            if (!isRetryable || attempt === maxRetries) throw err;
            if (onRetry) onRetry(attempt, maxRetries);
            await new Promise(r => setTimeout(r, delayMs * attempt));
        }
    }
}

// Chunking — rotate MediaRecorder every CHUNK_DURATION_SEC to stay under Whisper 25 MB limit
const CHUNK_DURATION_SEC = 10 * 60; // 10 minutes per segment

demoMode.value = route.query.demo === 'true';
let timer = null;
let chunkTimer = null;
let mediaRecorder = null;
let mediaStream = null;

// Completed audio segments (blobs) — one per CHUNK_DURATION_SEC window
const audioSegments = ref([]);
const totalSegments = computed(() => audioSegments.value.length);

const formattedTime = computed(() => {
    const m = Math.floor(seconds.value / 60).toString().padStart(2, '0');
    const s = (seconds.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

function getMimeType() {
    return MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
        ? 'audio/webm;codecs=opus'
        : 'audio/webm';
}

// Promise that resolves when the current recorder's onstop fires and blob is saved
let recorderStopPromise = null;

function createRecorder(stream) {
    const chunkData = []; // each recorder gets its own data array
    const recorder = new MediaRecorder(stream, { mimeType: getMimeType() });

    // Create a promise that resolves when onstop completes
    let resolveStop;
    recorderStopPromise = new Promise(resolve => { resolveStop = resolve; });

    recorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
            chunkData.push(event.data);
        }
    };

    recorder.onstop = () => {
        if (chunkData.length > 0) {
            const blob = new Blob(chunkData, { type: recorder.mimeType });
            audioSegments.value.push(blob);
        }
        resolveStop();
    };

    recorder.start(1000);
    return recorder;
}

function rotateChunk() {
    if (!mediaRecorder || mediaRecorder.state !== 'recording' || !mediaStream) return;

    // Stop current recorder — triggers onstop which saves the blob
    mediaRecorder.stop();

    // Start a new recorder on the same stream
    mediaRecorder = createRecorder(mediaStream);
}

async function startRecording() {
    starting.value = true;
    error.value = '';

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioSegments.value = [];
        mediaRecorder = createRecorder(mediaStream);

        // Schedule chunk rotation every CHUNK_DURATION_SEC
        chunkTimer = setInterval(rotateChunk, CHUNK_DURATION_SEC * 1000);

        step.value = 'recording';
    } catch (err) {
        error.value = err.name === 'NotAllowedError'
            ? 'Microphone access denied. Please allow microphone access and try again.'
            : `Could not start recording: ${err.message}`;
    } finally {
        starting.value = false;
    }
}

async function stopRecording() {
    clearInterval(timer);
    clearInterval(chunkTimer);

    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop(); // triggers onstop → saves final segment

        // Wait for onstop to fire and blob to be saved before proceeding
        if (recorderStopPromise) {
            await recorderStopPromise;
        }
    }

    // Stop all mic tracks
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }

    step.value = 'processing';
    processVisit();
}

async function processVisit() {
    const segments = audioSegments.value;
    if (segments.length === 0) {
        error.value = 'No recording found. Please record again.';
        return;
    }

    uploading.value = true;
    error.value = '';
    uploadProgress.value = 5;

    try {
        // 1. Create visit (reuse if retrying after error)
        let visitId = createdVisitId;

        if (!visitId) {
            const patientId = auth.user?.patient_id || auth.user?.patient?.id;
            if (!patientId) {
                throw new Error('Patient profile not found. Please log in again.');
            }

            const visitRes = await api.post('/visits', {
                patient_id: patientId,
                practitioner_id: selectedPractitionerId.value,
                visit_type: 'office_visit',
                reason_for_visit: 'Companion Scribe recording',
                started_at: new Date(visitDate.value).toISOString(),
            });

            visitId = visitRes.data.data.id;
            createdVisitId = visitId; // persist for retry
        }

        uploadProgress.value = 10;

        const ext = getMimeType().includes('webm') ? 'webm' : 'm4a';

        // Phase 1: Save all audio to server FIRST (safety net for ALL paths)
        for (let i = 0; i < segments.length; i++) {
            uploadStatusText.value = segments.length === 1
                ? 'Saving audio to server...'
                : `Saving segment ${i + 1} of ${segments.length}...`;
            uploadDetailText.value = 'Uploading audio...';

            const saveForm = new FormData();
            saveForm.append('audio', segments[i], segments.length === 1 ? `recording.${ext}` : `chunk-${i}.${ext}`);
            saveForm.append('chunk_index', String(i));

            await withRetry(() => api.post(`/visits/${visitId}/transcript/save-chunk`, saveForm, {
                timeout: 120000, skipErrorToast: true,
            }), { onRetry: (a) => { uploadDetailText.value = `Retry ${a}/3...`; } });

            uploadProgress.value = 10 + Math.round(((i + 1) / segments.length) * 25);
        }

        // Phase 2: Transcribe
        if (segments.length === 1) {
            // Single segment — direct upload-audio (saves + transcribes + creates Transcript + dispatches job)
            uploadStatusText.value = 'Transcribing audio...';
            uploadDetailText.value = 'Sending to Whisper AI...';

            const formData = new FormData();
            formData.append('audio', segments[0], `recording.${ext}`);
            formData.append('source_type', 'ambient_phone');
            formData.append('patient_consent_given', '1');

            await withRetry(() => api.post(`/visits/${visitId}/transcript/upload-audio`, formData, {
                timeout: 300000, skipErrorToast: true,
            }), { onRetry: (a) => { uploadDetailText.value = `Upload failed, retry ${a}/3...`; } });

            uploadProgress.value = 90;
        } else {
            // Multiple segments — transcribe each, then combine
            const transcriptParts = [];

            for (let i = 0; i < segments.length; i++) {
                uploadStatusText.value = `Transcribing segment ${i + 1} of ${segments.length}...`;
                uploadDetailText.value = `Sending segment ${i + 1} to Whisper AI...`;

                const formData = new FormData();
                formData.append('audio', segments[i], `chunk-${i}.${ext}`);
                formData.append('chunk_index', String(i));
                formData.append('total_chunks', String(segments.length));

                const { data } = await withRetry(() => api.post(`/visits/${visitId}/transcript/transcribe-chunk`, formData, {
                    timeout: 300000, skipErrorToast: true,
                }), { onRetry: (a) => { uploadDetailText.value = `Transcription retry ${a}/3...`; } });

                transcriptParts.push(data.data.text);
                uploadProgress.value = 35 + Math.round(((i + 1) / segments.length) * 45);
            }

            // Phase 3: Combine all chunk transcripts and submit as text
            uploadStatusText.value = 'Processing combined transcript...';
            uploadDetailText.value = 'Analyzing with AI...';

            const combinedTranscript = transcriptParts.join('\n\n');

            await withRetry(() => api.post(`/visits/${visitId}/transcript`, {
                raw_transcript: combinedTranscript,
                source_type: 'ambient_phone',
                stt_provider: 'whisper',
                audio_duration_seconds: seconds.value,
                patient_consent_given: true,
                process: true,
            }, { skipErrorToast: true }), { onRetry: (a) => { uploadDetailText.value = `Processing retry ${a}/3...`; } });

            uploadProgress.value = 90;
        }

        uploadProgress.value = 100;
        uploadStatusText.value = 'Done!';

        // Navigate to processing view
        router.push({ path: '/processing', query: { visitId } });
    } catch (err) {
        error.value = err.response?.data?.message || err.message || 'Upload failed. Please try again.';
        uploading.value = false;
    }
}

async function useDemoTranscript() {
    demoLoading.value = true;
    error.value = '';

    try {
        const patientId = auth.user?.patient_id || auth.user?.patient?.id;
        if (!patientId) {
            throw new Error('Patient profile not found. Please log in again.');
        }

        const practitionerId = selectedPractitionerId.value
            || auth.user?.patient?.visits?.[0]?.practitioner_id
            || await getFirstPractitionerId(patientId);

        const visitRes = await api.post('/visits', {
            patient_id: patientId,
            practitioner_id: practitionerId,
            visit_type: 'office_visit',
            reason_for_visit: 'Demo — Companion Scribe recording',
            started_at: new Date(visitDate.value).toISOString(),
        });

        const visitId = visitRes.data.data.id;

        await withRetry(() => api.post(`/visits/${visitId}/transcript`, {
            use_demo_transcript: true,
            raw_transcript: 'demo',
            source_type: 'ambient_device',
            patient_consent_given: true,
            process: true,
        }, { skipErrorToast: true }));

        router.push({ path: '/processing', query: { visitId } });
    } catch (err) {
        error.value = err.response?.data?.message || err.message || 'Demo failed. Please try again.';
    } finally {
        demoLoading.value = false;
    }
}

async function getFirstPractitionerId(patientId) {
    try {
        const { data } = await api.get(`/patients/${patientId}/visits`);
        const visits = data.data || [];
        if (visits.length > 0 && visits[0].practitioner_id) {
            return visits[0].practitioner_id;
        }
    } catch {
        // ignore
    }
    throw new Error('No practitioner found. Please contact support.');
}

// Warn user before closing/navigating away during recording or upload
function onBeforeUnload(e) {
    if (step.value === 'recording' || step.value === 'processing' || uploading.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}

onMounted(async () => {
    window.addEventListener('beforeunload', onBeforeUnload);

    try {
        const { data } = await api.get('/practitioners');
        practitioners.value = data.data || [];
        if (practitioners.value.length === 1) {
            selectedPractitionerId.value = practitioners.value[0].id;
        }
    } catch {
        // Non-blocking — user can still type manually
    }

    // Pre-fill from existing visit when navigating from Visit Summary
    if (route.query.visitId) {
        try {
            const { data } = await api.get(`/visits/${route.query.visitId}`);
            const visit = data.data;
            if (visit?.practitioner_id) {
                selectedPractitionerId.value = visit.practitioner_id;
            }
            if (visit?.started_at) {
                visitDate.value = visit.started_at.slice(0, 10);
            }
        } catch {
            // Non-blocking — user can still select manually
        }
    }
});

watch(step, (val) => {
    if (val === 'recording') {
        seconds.value = 0;
        timer = setInterval(() => seconds.value++, 1000);
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    clearInterval(timer);
    clearInterval(chunkTimer);
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
    }
});
</script>

<style scoped>
.sound-bar {
    height: 12px;
    animation: sound-pulse 1s ease-in-out infinite alternate;
}

@keyframes sound-pulse {
    0% {
        height: 8px;
        opacity: 0.4;
    }
    50% {
        opacity: 0.7;
    }
    100% {
        height: 64px;
        opacity: 1;
    }
}
</style>
