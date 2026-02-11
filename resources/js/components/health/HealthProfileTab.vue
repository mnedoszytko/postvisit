<template>
  <div class="space-y-4">
    <!-- Edit toggle -->
    <div class="flex justify-end">
      <button
        v-if="!editing"
        class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors"
        @click="startEdit"
      >
        Edit Profile
      </button>
      <div v-else class="flex gap-2">
        <button
          class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
          @click="cancelEdit"
        >
          Cancel
        </button>
        <button
          class="text-sm font-medium bg-emerald-600 text-white px-4 py-1.5 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50"
          :disabled="saving"
          @click="saveProfile"
        >
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <!-- Feedback -->
    <div v-if="feedback" class="text-sm px-4 py-2 rounded-xl" :class="feedbackError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
      {{ feedback }}
    </div>

    <!-- Personal Info -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-3">Personal Information</h3>
      <div v-if="!editing" class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm">
        <div>
          <p class="text-gray-400 text-xs">Full Name</p>
          <p class="text-gray-900 font-medium">{{ patient?.user?.name || patient?.name || '\u2014' }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">Date of Birth</p>
          <p class="text-gray-900 font-medium">{{ formatDate(patient?.date_of_birth) || '\u2014' }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">Gender</p>
          <p class="text-gray-900 font-medium capitalize">{{ patient?.gender || '\u2014' }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">Phone</p>
          <p class="text-gray-900 font-medium">{{ patient?.phone || '\u2014' }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">Email</p>
          <p class="text-gray-900 font-medium">{{ patient?.user?.email || '\u2014' }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">MRN</p>
          <p class="text-gray-900 font-medium font-mono">{{ patient?.mrn || '\u2014' }}</p>
        </div>
      </div>
      <div v-else class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-500">Phone</label>
          <input v-model="form.phone" type="tel" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Gender</label>
          <select v-model="form.gender" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Date of Birth</label>
          <input v-model="form.date_of_birth" type="date" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
      </div>
    </div>

    <!-- Biometrics -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-3">Biometrics</h3>
      <div v-if="!editing" class="grid grid-cols-3 gap-3">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ patient?.height_cm || '\u2014' }}</p>
          <p class="text-xs text-gray-500 mt-1">Height (cm)</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold text-gray-900">{{ patient?.weight_kg || '\u2014' }}</p>
          <p class="text-xs text-gray-500 mt-1">Weight (kg)</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
          <p class="text-2xl font-bold" :class="bmiColor">{{ bmi || '\u2014' }}</p>
          <p class="text-xs text-gray-500 mt-1">BMI</p>
        </div>
      </div>
      <div v-else class="grid grid-cols-3 gap-3">
        <div>
          <label class="text-xs text-gray-500">Height (cm)</label>
          <input v-model.number="form.height_cm" type="number" step="0.1" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Weight (kg)</label>
          <input v-model.number="form.weight_kg" type="number" step="0.1" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Blood Type</label>
          <select v-model="form.blood_type" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            <option value="">Unknown</option>
            <option v-for="bt in bloodTypes" :key="bt" :value="bt">{{ bt }}</option>
          </select>
        </div>
      </div>
      <div v-if="!editing && patient?.blood_type" class="mt-3 text-sm text-gray-600">
        <span class="text-gray-400 text-xs">Blood Type:</span>
        <span class="ml-1 font-medium">{{ patient.blood_type }}</span>
      </div>
    </div>

    <!-- Allergies -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-3">Allergies</h3>
      <div v-if="allergies.length > 0" class="flex flex-wrap gap-2">
        <AllergyTag
          v-for="allergy in allergies"
          :key="allergy.name"
          :name="allergy.name"
          :severity="allergy.severity"
        />
      </div>
      <p v-else class="text-sm text-gray-400">No known allergies</p>
    </div>

    <!-- Emergency Contact -->
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-900 mb-3">Emergency Contact</h3>
      <div v-if="!editing">
        <div v-if="patient?.emergency_contact_name" class="space-y-1 text-sm">
          <p class="text-gray-900 font-medium">{{ patient.emergency_contact_name }}</p>
          <p class="text-gray-500">{{ patient.emergency_contact_relationship || 'Relationship not specified' }}</p>
          <p class="text-gray-500">{{ patient.emergency_contact_phone || 'No phone number' }}</p>
        </div>
        <p v-else class="text-sm text-gray-400">No emergency contact set</p>
      </div>
      <div v-else class="grid grid-cols-1 gap-3">
        <div>
          <label class="text-xs text-gray-500">Name</label>
          <input v-model="form.emergency_contact_name" type="text" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs text-gray-500">Relationship</label>
            <input v-model="form.emergency_contact_relationship" type="text" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
          </div>
          <div>
            <label class="text-xs text-gray-500">Phone</label>
            <input v-model="form.emergency_contact_phone" type="tel" class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { useApi } from '@/composables/useApi';
import AllergyTag from '@/components/health/AllergyTag.vue';

const props = defineProps({
    patient: { type: Object, default: null },
});

const emit = defineEmits(['patient-updated']);

const api = useApi();
const editing = ref(false);
const saving = ref(false);
const feedback = ref('');
const feedbackError = ref(false);

const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const form = reactive({
    phone: '',
    gender: '',
    date_of_birth: '',
    height_cm: null,
    weight_kg: null,
    blood_type: '',
    emergency_contact_name: '',
    emergency_contact_relationship: '',
    emergency_contact_phone: '',
});

const bmi = computed(() => {
    const h = props.patient?.height_cm;
    const w = props.patient?.weight_kg;
    if (!h || !w) return null;
    const meters = h / 100;
    return (w / (meters * meters)).toFixed(1);
});

const bmiColor = computed(() => {
    const val = parseFloat(bmi.value);
    if (isNaN(val)) return 'text-gray-900';
    if (val < 18.5) return 'text-blue-600';
    if (val < 25) return 'text-emerald-600';
    if (val < 30) return 'text-amber-600';
    return 'text-red-600';
});

const allergies = computed(() => {
    return props.patient?.allergies || [];
});

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function startEdit() {
    form.phone = props.patient?.phone || '';
    form.gender = props.patient?.gender || '';
    form.date_of_birth = props.patient?.date_of_birth || '';
    form.height_cm = props.patient?.height_cm || null;
    form.weight_kg = props.patient?.weight_kg || null;
    form.blood_type = props.patient?.blood_type || '';
    form.emergency_contact_name = props.patient?.emergency_contact_name || '';
    form.emergency_contact_relationship = props.patient?.emergency_contact_relationship || '';
    form.emergency_contact_phone = props.patient?.emergency_contact_phone || '';
    editing.value = true;
    feedback.value = '';
}

function cancelEdit() {
    editing.value = false;
    feedback.value = '';
}

async function saveProfile() {
    if (!props.patient?.id) return;
    saving.value = true;
    feedback.value = '';

    try {
        const payload = {};
        for (const [key, val] of Object.entries(form)) {
            if (val !== '' && val !== null) {
                payload[key] = val;
            }
        }

        const { data } = await api.patch(`/patients/${props.patient.id}`, payload);
        emit('patient-updated', data.data);
        editing.value = false;
        feedback.value = 'Profile updated successfully.';
        feedbackError.value = false;
    } catch {
        feedback.value = 'Failed to update profile. Please try again.';
        feedbackError.value = true;
    } finally {
        saving.value = false;
    }
}
</script>
