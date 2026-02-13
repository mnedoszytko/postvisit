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
            <div class="flex items-center gap-2">
              <select
                v-model="selectedPractitionerId"
                class="flex-1 rounded-xl border border-gray-300 px-3 py-2.5 text-gray-900 focus:border-emerald-500 focus:ring-emerald-500"
              >
                <option value="" disabled>Select your doctor...</option>
                <option v-for="p in practitioners" :key="p.id" :value="p.id">
                  Dr. {{ p.first_name }} {{ p.last_name }} — {{ p.primary_specialty }}
                </option>
              </select>
              <button
                type="button"
                class="w-10 h-10 rounded-xl border border-gray-300 flex items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-emerald-600 transition-colors shrink-0"
                title="Add new doctor"
                @click="showAddDoctor = true"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </button>
            </div>
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

      <!-- Recording step -->
      <div v-else-if="step === 'recording'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-6">
        <h2 class="text-lg font-semibold text-gray-800">Recording in progress...</h2>

        <ThreeVisualizer />

        <p class="text-2xl font-mono text-gray-700 tracking-widest">{{ formattedTime }}</p>
        <p class="text-xs text-gray-400">
          {{ audioSegments.length > 0 ? `Segment ${audioSegments.length + 1} · ` : '' }}Your conversation is being captured securely
        </p>
        <button
          class="w-full py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
          @click="stopRecording"
        >
          Stop Recording
        </button>
        <button
          v-if="demoMode && showDemoRecordingBtn"
          class="w-full py-2.5 border border-emerald-300 text-emerald-700 rounded-xl text-sm font-medium hover:bg-emerald-50 transition-all duration-500"
          :class="demoRecordingBtnVisible ? 'opacity-100' : 'opacity-0'"
          :disabled="demoLoading"
          @click="useDemoRecordingDuringCapture"
        >
          {{ demoLoading ? 'Loading demo...' : 'Use Demo Recording Instead' }}
        </button>
      </div>

      <!-- Post-recording / uploading step -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <div v-if="!uploading" class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-emerald-600">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
          </svg>
        </div>
        <!-- Pulsating processing indicator -->
        <div v-if="uploading" class="relative w-16 h-16 mx-auto">
          <div class="absolute inset-0 rounded-full bg-emerald-400/30 animate-ping" style="animation-duration: 1.5s;" />
          <div class="absolute inset-2 rounded-full bg-emerald-400/20 animate-ping" style="animation-duration: 2s; animation-delay: 0.3s;" />
          <div class="relative w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-emerald-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
            </svg>
          </div>
        </div>
        <h2 class="text-lg font-semibold text-gray-800">
          {{ uploading ? uploadStatusText : 'Recording Complete' }}
        </h2>
        <p class="text-gray-500">{{ formattedTime }} recorded{{ totalSegments > 1 ? ` (${totalSegments} segments)` : '' }}</p>

        <div v-if="uploading" class="space-y-3">
          <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
            <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500 relative" :style="{ width: uploadProgress + '%' }">
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-[shimmer_1.5s_ease-in-out_infinite]" />
            </div>
          </div>
          <div class="flex items-center justify-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
            <p class="text-sm text-gray-500">{{ uploadDetailText }}</p>
          </div>
        </div>

        <button
          v-else
          class="block w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
          :disabled="uploading"
          @click="processVisit"
        >
          Process Visit
        </button>

        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      </div>
    </div>

    <!-- Add Doctor Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showAddDoctor" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showAddDoctor = false" />
          <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-bold text-gray-900">Add Healthcare Provider</h2>
              <button class="text-gray-400 hover:text-gray-600 text-xl" @click="showAddDoctor = false">&times;</button>
            </div>

            <div class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                  <input v-model="newDoctor.first_name" type="text" placeholder="Lisa" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                  <input v-model="newDoctor.last_name" type="text" placeholder="Chen" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Specialty</label>
                <input v-model="newDoctor.primary_specialty" type="text" placeholder="e.g. Cardiology, Family Medicine" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Degree <span class="text-gray-400 font-normal">(optional)</span></label>
                <input v-model="newDoctor.medical_degree" type="text" placeholder="e.g. MD, DO" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
              </div>
            </div>

            <p v-if="addDoctorError" class="text-red-600 text-sm">{{ addDoctorError }}</p>

            <div class="flex gap-3 pt-1">
              <button
                class="flex-1 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                @click="showAddDoctor = false"
              >Cancel</button>
              <button
                class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
                :disabled="addingDoctor || !newDoctor.first_name || !newDoctor.last_name || !newDoctor.primary_specialty"
                @click="addDoctor"
              >{{ addingDoctor ? 'Adding...' : 'Add Doctor' }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
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
const showDemoRecordingBtn = ref(false);
const demoRecordingBtnVisible = ref(false);
let demoRecordingTimer = null;

// Visit info form
const practitioners = ref([]);
const selectedPractitionerId = ref('');
const visitDate = ref(new Date().toISOString().slice(0, 10));

// Add doctor modal
const showAddDoctor = ref(false);
const addingDoctor = ref(false);
const addDoctorError = ref('');
const newDoctor = ref({ first_name: '', last_name: '', primary_specialty: '', medical_degree: '' });

async function addDoctor() {
    addingDoctor.value = true;
    addDoctorError.value = '';
    try {
        const { data } = await api.post('/practitioners', newDoctor.value);
        const created = data.data;
        practitioners.value.push(created);
        selectedPractitionerId.value = created.id;
        showAddDoctor.value = false;
        newDoctor.value = { first_name: '', last_name: '', primary_specialty: '', medical_degree: '' };
    } catch (err) {
        addDoctorError.value = err.response?.data?.message || 'Failed to add doctor. Please try again.';
    } finally {
        addingDoctor.value = false;
    }
}

const selectedPractitioner = computed(() =>
    practitioners.value.find(p => p.id === selectedPractitionerId.value) || null
);

// Upload progress
const uploadProgress = ref(0);
const uploadStatusText = ref('Processing audio...');
const uploadDetailText = ref('Preparing transcription...');

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
let wakeLock = null;

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

        // Prevent screen from locking during recording (iOS Safari 16.4+)
        await acquireWakeLock();

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

    releaseWakeLock();
    step.value = 'done';
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
            uploadDetailText.value = 'Sending audio for transcription...';

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
                uploadDetailText.value = `Transcribing segment ${i + 1}...`;

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

async function useDemoRecordingDuringCapture() {
    // Stop the active recording, discard it, and use demo transcript instead
    clearInterval(timer);
    clearInterval(chunkTimer);
    clearTimeout(demoRecordingTimer);
    releaseWakeLock();

    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        if (recorderStopPromise) await recorderStopPromise;
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }

    // Clear captured segments — we are using demo data
    audioSegments.value = [];
    await useDemoTranscript();
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

// Screen Wake Lock — prevents iPhone screen from locking during recording
async function acquireWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try {
        wakeLock = await navigator.wakeLock.request('screen');
        wakeLock.addEventListener('release', () => { wakeLock = null; });
    } catch {
        // Wake Lock not available or denied — recording still works, just screen may lock
    }
}

function releaseWakeLock() {
    if (wakeLock) {
        wakeLock.release();
        wakeLock = null;
    }
}

// Re-acquire wake lock when returning from background (iOS releases it on tab switch)
function onVisibilityChange() {
    if (document.visibilityState === 'visible' && step.value === 'recording') {
        acquireWakeLock();
    }
}

// Warn user before closing/navigating away during recording or upload
function onBeforeUnload(e) {
    if (step.value === 'recording' || uploading.value) {
        e.preventDefault();
        e.returnValue = '';
    }
}

onMounted(async () => {
    window.addEventListener('beforeunload', onBeforeUnload);
    document.addEventListener('visibilitychange', onVisibilityChange);

    try {
        const { data } = await api.get('/practitioners');
        practitioners.value = data.data || [];
        if (practitioners.value.length > 0) {
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
        // Show "Use Demo Recording Instead" button after 2s delay
        if (demoMode.value) {
            demoRecordingTimer = setTimeout(() => {
                showDemoRecordingBtn.value = true;
                requestAnimationFrame(() => { demoRecordingBtnVisible.value = true; });
            }, 2000);
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    clearInterval(timer);
    clearInterval(chunkTimer);
    clearTimeout(demoRecordingTimer);
    releaseWakeLock();
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
    }
});
</script>

<style scoped>
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}
</style>
