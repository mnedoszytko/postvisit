<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="p-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-800">Blood Pressure Monitoring</h3>
        <span v-if="highCount > 0" class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
          {{ highCount }}/{{ readings.length }} elevated
        </span>
      </div>
      <span class="text-xs text-gray-400">{{ readings.length }} readings</span>
    </div>

    <div v-if="sortedReadings.length >= 2" class="px-4 pb-4">
      <!-- SVG Chart -->
      <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" class="w-full" preserveAspectRatio="xMidYMid meet">
        <!-- Grid lines -->
        <line
          v-for="(line, i) in gridLines"
          :key="'grid-' + i"
          :x1="padding.left"
          :y1="line.y"
          :x2="svgWidth - padding.right"
          :y2="line.y"
          stroke="#f3f4f6"
          stroke-width="1"
        />

        <!-- Y-axis labels -->
        <text
          v-for="(line, i) in gridLines"
          :key="'label-' + i"
          :x="padding.left - 6"
          :y="line.y + 4"
          text-anchor="end"
          class="text-[10px]"
          fill="#9ca3af"
        >
          {{ line.value }}
        </text>

        <!-- Stage 2 HTN threshold zone (>=140 systolic) -->
        <rect
          :x="padding.left"
          :y="scaleY(180)"
          :width="chartWidth"
          :height="scaleY(140) - scaleY(180)"
          fill="#fef2f2"
          opacity="0.3"
        />
        <line
          :x1="padding.left"
          :y1="scaleY(140)"
          :x2="svgWidth - padding.right"
          :y2="scaleY(140)"
          stroke="#fca5a5"
          stroke-width="1"
          stroke-dasharray="4,3"
        />

        <!-- Stage 1 HTN threshold (130 systolic) -->
        <line
          :x1="padding.left"
          :y1="scaleY(130)"
          :x2="svgWidth - padding.right"
          :y2="scaleY(130)"
          stroke="#fcd34d"
          stroke-width="1"
          stroke-dasharray="4,3"
        />

        <!-- Normal systolic reference (120) -->
        <line
          :x1="padding.left"
          :y1="scaleY(120)"
          :x2="svgWidth - padding.right"
          :y2="scaleY(120)"
          stroke="#86efac"
          stroke-width="1"
          stroke-dasharray="4,3"
        />

        <!-- Systolic line -->
        <polyline
          :points="systolicPath"
          fill="none"
          stroke="#ef4444"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <!-- Diastolic line -->
        <polyline
          :points="diastolicPath"
          fill="none"
          stroke="#3b82f6"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <!-- Systolic data points -->
        <circle
          v-for="(pt, i) in systolicPoints"
          :key="'sys-' + i"
          :cx="pt.x"
          :cy="pt.y"
          r="3.5"
          fill="#ef4444"
          stroke="white"
          stroke-width="1.5"
        />

        <!-- Diastolic data points -->
        <circle
          v-for="(pt, i) in diastolicPoints"
          :key="'dia-' + i"
          :cx="pt.x"
          :cy="pt.y"
          r="3.5"
          fill="#3b82f6"
          stroke="white"
          stroke-width="1.5"
        />

        <!-- X-axis date labels -->
        <text
          v-for="(pt, i) in xLabels"
          :key="'x-' + i"
          :x="pt.x"
          :y="svgHeight - 4"
          text-anchor="middle"
          class="text-[9px]"
          fill="#9ca3af"
        >
          {{ pt.label }}
        </text>
      </svg>

      <!-- Legend and stats -->
      <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
        <span class="flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
          Systolic
        </span>
        <span class="flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
          Diastolic
        </span>
        <span class="ml-auto">
          Avg: {{ avgSystolic }}/{{ avgDiastolic }} mmHg
        </span>
      </div>
    </div>

    <div v-else class="px-4 pb-4 text-center text-gray-400 text-sm">
      Not enough BP data to display chart.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  readings: {
    type: Array,
    required: true,
  },
});

const svgWidth = 400;
const svgHeight = 200;
const padding = { top: 15, right: 15, bottom: 25, left: 40 };
const chartWidth = svgWidth - padding.left - padding.right;
const chartHeight = svgHeight - padding.top - padding.bottom;

const sortedReadings = computed(() => {
  return [...props.readings].sort(
    (a, b) => new Date(a.effective_date) - new Date(b.effective_date)
  );
});

function getSystolic(reading) {
  return reading.specialty_data?.systolic?.value ?? 0;
}

function getDiastolic(reading) {
  return reading.specialty_data?.diastolic?.value ?? 0;
}

const highCount = computed(() => {
  return sortedReadings.value.filter(r => r.interpretation === 'H').length;
});

const avgSystolic = computed(() => {
  const vals = sortedReadings.value.map(getSystolic);
  return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
});

const avgDiastolic = computed(() => {
  const vals = sortedReadings.value.map(getDiastolic);
  return Math.round(vals.reduce((a, b) => a + b, 0) / vals.length);
});

// Fixed Y range for BP: 60-180 mmHg
const yMin = 60;
const yMax = 180;

function scaleY(val) {
  return padding.top + chartHeight - ((val - yMin) / (yMax - yMin)) * chartHeight;
}

const gridLines = computed(() => {
  const lines = [];
  for (let v = 80; v <= 160; v += 20) {
    lines.push({ value: v, y: scaleY(v) });
  }
  return lines;
});

function scaleX(i) {
  const total = sortedReadings.value.length;
  return padding.left + (i / (total - 1)) * chartWidth;
}

const systolicPoints = computed(() => {
  return sortedReadings.value.map((r, i) => ({
    x: scaleX(i),
    y: scaleY(getSystolic(r)),
  }));
});

const diastolicPoints = computed(() => {
  return sortedReadings.value.map((r, i) => ({
    x: scaleX(i),
    y: scaleY(getDiastolic(r)),
  }));
});

const systolicPath = computed(() => {
  return systolicPoints.value.map(p => `${p.x},${p.y}`).join(' ');
});

const diastolicPath = computed(() => {
  return diastolicPoints.value.map(p => `${p.x},${p.y}`).join(' ');
});

const xLabels = computed(() => {
  const sorted = sortedReadings.value;
  if (sorted.length < 2) return [];
  const indices = [0, Math.floor(sorted.length / 2), sorted.length - 1];
  return [...new Set(indices)].map(i => ({
    x: scaleX(i),
    label: formatShortDate(sorted[i].effective_date),
  }));
});

function formatShortDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>
