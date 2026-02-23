<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

        <!-- Modal panel -->
        <div class="relative w-full max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
          <!-- Gradient header -->
          <div class="bg-indigo-600 px-6 py-5 text-white">
            <div class="flex items-center justify-between relative">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-lg font-bold">Appointment Invitation</h2>
                  <p class="text-sm text-blue-100">Follow-up visit requested</p>
                </div>
              </div>
              <button
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors"
                @click="close"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </div>

          <!-- Scheduled confirmation -->
          <div v-if="scheduled" class="p-8 text-center">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <p class="text-lg font-bold text-gray-900 mb-1">Appointment Scheduled</p>
            <p class="text-sm text-gray-500 mb-4">
              {{ formatDate(selectedDate) }} at {{ selectedTime }}
            </p>
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-left">
              <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">Confirmation Sent</p>
              <p class="text-sm text-gray-700">
                Your visit context, health record, and appointment details have been prepared for your doctor.
              </p>
            </div>
          </div>

          <!-- Main content -->
          <div v-else class="flex flex-col min-h-0 flex-1 overflow-y-auto">
            <!-- Doctor message -->
            <div class="px-6 pt-5 pb-4">
              <div class="flex items-start gap-3">
                <img
                  v-if="doctorPhoto"
                  :src="doctorPhoto"
                  :alt="doctorName"
                  class="w-10 h-10 rounded-full object-cover shrink-0"
                />
                <div v-else class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-2xl rounded-tl-md px-4 py-3 flex-1">
                  <p class="text-sm font-semibold text-blue-900 mb-0.5">{{ doctorName }}</p>
                  <p class="text-sm text-gray-700 leading-relaxed">{{ invitationMessage }}</p>
                </div>
              </div>
            </div>

            <!-- Divider -->
            <div class="px-6">
              <div class="border-t border-gray-100"></div>
            </div>

            <!-- Date picker section -->
            <div class="px-6 pt-4 pb-3">
              <label class="block text-sm font-semibold text-gray-800 mb-3">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Choose a date
              </label>

              <!-- Mini calendar -->
              <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                <!-- Month nav -->
                <div class="flex items-center justify-between mb-3">
                  <button
                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 transition-colors"
                    @click="prevMonth"
                  >
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                  </button>
                  <span class="text-sm font-semibold text-gray-800">{{ monthLabel }}</span>
                  <button
                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-200 transition-colors"
                    @click="nextMonth"
                  >
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                  </button>
                </div>

                <!-- Day headers -->
                <div class="grid grid-cols-7 gap-1 mb-1">
                  <div
                    v-for="d in weekDays"
                    :key="d"
                    class="text-center text-[10px] font-semibold text-gray-400 uppercase"
                  >{{ d }}</div>
                </div>

                <!-- Day grid -->
                <div class="grid grid-cols-7 gap-1">
                  <div
                    v-for="(day, i) in calendarDays"
                    :key="i"
                    class="aspect-square flex flex-col items-center justify-center text-sm rounded-lg cursor-pointer transition-all relative"
                    :class="dayClasses(day)"
                    @click="day.date && !day.disabled && selectDate(day.date)"
                  >
                    <span v-if="day.date">{{ day.date.getDate() }}</span>
                    <span v-if="day.date && isToday(day.date)" class="absolute bottom-0.5 w-1 h-1 rounded-full bg-indigo-500" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Time slot picker -->
            <div class="px-6 pb-3">
              <label class="block text-sm font-semibold text-gray-800 mb-2">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Preferred time
              </label>
              <div class="grid grid-cols-4 gap-2">
                <button
                  v-for="slot in timeSlots"
                  :key="slot"
                  class="px-3 py-2 text-sm font-medium rounded-lg border transition-all"
                  :class="selectedTime === slot
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-md'
                    : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-300 hover:bg-indigo-50'"
                  @click="selectedTime = slot"
                >{{ slot }}</button>
              </div>
            </div>

            <!-- Share with doctor toggles -->
            <div class="px-6 pb-4">
              <p class="text-sm font-semibold text-gray-800 mb-2.5">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                </svg>
                Share with doctor
              </p>
              <div class="bg-gray-50 rounded-xl border border-gray-200 divide-y divide-gray-100">
                <label
                  v-for="opt in shareOptions"
                  :key="opt.key"
                  class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-100/50 transition-colors"
                >
                  <input
                    v-model="opt.enabled"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ opt.label }}</p>
                    <p class="text-xs text-gray-500">{{ opt.description }}</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- CTA button -->
            <div class="px-6 pb-6 pt-1">
              <button
                :disabled="!selectedDate || !selectedTime"
                class="w-full py-3.5 rounded-xl font-semibold text-white transition-all flex items-center justify-center gap-2"
                :class="selectedDate && selectedTime
                  ? 'bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 hover:shadow-xl'
                  : 'bg-gray-300 cursor-not-allowed'"
                @click="scheduleAppointment"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                Schedule Appointment
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';

const props = defineProps<{
  modelValue: boolean;
  doctorName?: string;
  doctorPhoto?: string;
  doctorSpecialty?: string;
  visitType?: string;
  invitationMessage?: string;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void;
  (e: 'scheduled', payload: { date: string; time: string }): void;
}>();

const weekDays = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
const timeSlots = ['9:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

function nextAvailableWeekday(daysFromNow: number): Date {
  const d = new Date();
  d.setHours(0, 0, 0, 0);
  d.setDate(d.getDate() + daysFromNow);
  // Skip weekends
  while (d.getDay() === 0 || d.getDay() === 6) {
    d.setDate(d.getDate() + 1);
  }
  return d;
}

const selectedDate = ref<Date | null>(nextAvailableWeekday(2));
const selectedTime = ref<string>('');
const scheduled = ref(false);

const shareOptions = reactive([
  { key: 'visit_history', label: 'Visit History', description: 'Previous visits and SOAP notes', enabled: true },
  { key: 'conditions', label: 'Conditions & Diagnoses', description: 'Active conditions and past diagnoses', enabled: true },
  { key: 'medications', label: 'Medications', description: 'Current prescriptions and dosages', enabled: true },
  { key: 'lab_results', label: 'Lab Results', description: 'Recent lab work and trends', enabled: true },
  { key: 'vitals', label: 'Vitals', description: 'Weight, sleep data, blood pressure, heart rate', enabled: false },
  { key: 'connected', label: 'Connected Records', description: 'Apple Health, EHR integrations', enabled: false },
]);

const today = new Date();
today.setHours(0, 0, 0, 0);
const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());

const monthLabel = computed(() => {
  const d = new Date(currentYear.value, currentMonth.value, 1);
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
  const first = new Date(currentYear.value, currentMonth.value, 1);
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
  let startDay = first.getDay() - 1;
  if (startDay < 0) startDay = 6;

  const days: { date: Date | null; disabled: boolean }[] = [];
  for (let i = 0; i < startDay; i++) {
    days.push({ date: null, disabled: true });
  }
  for (let d = 1; d <= lastDay; d++) {
    const date = new Date(currentYear.value, currentMonth.value, d);
    const isWeekend = date.getDay() === 0 || date.getDay() === 6;
    days.push({ date, disabled: date < today || isWeekend });
  }
  return days;
});

function isToday(date: Date): boolean {
  return date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear();
}

function dayClasses(day: { date: Date | null; disabled: boolean }): string {
  if (!day.date) return '';
  if (day.disabled) return 'text-gray-300 cursor-not-allowed';

  const isSelected = selectedDate.value &&
    day.date.getDate() === selectedDate.value.getDate() &&
    day.date.getMonth() === selectedDate.value.getMonth() &&
    day.date.getFullYear() === selectedDate.value.getFullYear();

  if (isSelected) return 'bg-indigo-600 text-white font-bold shadow-md';
  if (isToday(day.date)) return 'bg-indigo-50 text-indigo-700 font-semibold hover:bg-indigo-100';
  return 'text-gray-700 hover:bg-indigo-50';
}

function selectDate(date: Date): void {
  selectedDate.value = date;
}

function prevMonth(): void {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
}

function nextMonth(): void {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
}

function formatDate(date: Date | null): string {
  if (!date) return '';
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
}

function scheduleAppointment(): void {
  if (!selectedDate.value || !selectedTime.value) return;
  scheduled.value = true;
  emit('scheduled', {
    date: selectedDate.value.toISOString().split('T')[0],
    time: selectedTime.value,
  });
}

function close(): void {
  emit('update:modelValue', false);
  setTimeout(() => {
    scheduled.value = false;
    selectedDate.value = null;
    selectedTime.value = '';
  }, 300);
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .relative {
  transform: translateY(1rem);
}
</style>
