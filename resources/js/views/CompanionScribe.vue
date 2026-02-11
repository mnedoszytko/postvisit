<template>
  <PatientLayout>
    <div class="max-w-lg mx-auto space-y-6">
      <!-- Consent step -->
      <div v-if="step === 'consent'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">Companion Scribe</h1>
        <p class="text-gray-600">
          Record your doctor's visit to get a complete, understandable summary afterwards.
        </p>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
          Both the provider and the patient consent to this recording.
        </div>
        <button
          class="w-full py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50"
          :disabled="starting"
          @click="startRecording"
        >
          {{ starting ? 'Requesting microphone...' : 'I Consent — Start Recording' }}
        </button>
        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      </div>

      <!-- Recording step -->
      <div v-else-if="step === 'recording'" class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-6">
        <h2 class="text-lg font-semibold text-gray-800">Recording in progress...</h2>

        <ThreeVisualizer />

        <p class="text-2xl font-mono text-gray-700 tracking-widest">{{ formattedTime }}</p>
        <p class="text-xs text-gray-400">Your conversation is being captured securely</p>
        <button
          class="w-full py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
          @click="stopRecording"
        >
          Stop Recording
        </button>
      </div>

      <!-- Post-recording / uploading step -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 p-6 text-center space-y-4">
        <div v-if="!uploading" class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-emerald-600">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-800">
          {{ uploading ? 'Processing audio...' : 'Recording Complete' }}
        </h2>
        <p class="text-gray-500">{{ formattedTime }} recorded</p>

        <div v-if="uploading" class="space-y-3">
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-emerald-600 h-2 rounded-full animate-pulse" style="width: 60%" />
          </div>
          <p class="text-sm text-gray-400">Transcribing with Whisper AI...</p>
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
  </PatientLayout>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useApi } from '@/composables/useApi';
import { useAuthStore } from '@/stores/auth';
import PatientLayout from '@/layouts/PatientLayout.vue';
import ThreeVisualizer from '@/components/ThreeVisualizer.vue';

const router = useRouter();
const api = useApi();
const auth = useAuthStore();

const step = ref('consent');
const seconds = ref(0);
const starting = ref(false);
const uploading = ref(false);
const error = ref('');
let timer = null;
let mediaRecorder = null;
let audioChunks = [];
let audioBlob = null;

const formattedTime = computed(() => {
    const m = Math.floor(seconds.value / 60).toString().padStart(2, '0');
    const s = (seconds.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

async function startRecording() {
    starting.value = true;
    error.value = '';

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream, {
            mimeType: MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                ? 'audio/webm;codecs=opus'
                : 'audio/webm',
        });

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType });
            // Stop all mic tracks
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start(1000); // collect data every second
        step.value = 'recording';
    } catch (err) {
        error.value = err.name === 'NotAllowedError'
            ? 'Microphone access denied. Please allow microphone access and try again.'
            : `Could not start recording: ${err.message}`;
    } finally {
        starting.value = false;
    }
}

function stopRecording() {
    clearInterval(timer);
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
    step.value = 'done';
}

async function processVisit() {
    if (!audioBlob) {
        error.value = 'No recording found. Please record again.';
        return;
    }

    uploading.value = true;
    error.value = '';

    try {
        // 1. Create a new visit
        const patientId = auth.user?.patient_id || auth.user?.patient?.id;
        if (!patientId) {
            throw new Error('Patient profile not found. Please log in again.');
        }

        // Get the first available practitioner (for demo)
        const practitionerId = auth.user?.patient?.visits?.[0]?.practitioner_id
            || await getFirstPractitionerId(patientId);

        const visitRes = await api.post('/visits', {
            patient_id: patientId,
            practitioner_id: practitionerId,
            visit_type: 'consultation',
            reason_for_visit: 'Companion Scribe recording',
            started_at: new Date().toISOString(),
        });

        const visitId = visitRes.data.data.id;

        // 2. Upload audio to the visit
        const formData = new FormData();
        const ext = mediaRecorder.mimeType.includes('webm') ? 'webm' : 'm4a';
        formData.append('audio', audioBlob, `recording.${ext}`);
        formData.append('source_type', 'ambient_phone');
        formData.append('patient_consent_given', '1');

        await api.post(`/visits/${visitId}/transcript/upload-audio`, formData, {
            timeout: 120000, // Whisper can take up to 2 minutes
        });

        // 3. Navigate to processing view
        router.push({ path: '/processing', query: { visitId } });
    } catch (err) {
        error.value = err.response?.data?.message || err.message || 'Upload failed. Please try again.';
        uploading.value = false;
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

watch(step, (val) => {
    if (val === 'recording') {
        seconds.value = 0;
        timer = setInterval(() => seconds.value++, 1000);
    }
});

onUnmounted(() => {
    clearInterval(timer);
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
});
</script>
