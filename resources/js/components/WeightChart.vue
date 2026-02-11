<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <div class="p-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-800">Weight Monitoring</h3>
        <span v-if="alert" class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
          Alert: +{{ weightGain3d.toFixed(1) }}kg in 3 days
        </span>
      </div>
      <span class="text-xs text-gray-400">{{ weights.length }} readings</span>
    </div>

    <div v-if="weights.length >= 2" class="px-4 pb-4">
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

        <!-- Alert threshold zone -->
        <rect
          v-if="alertThresholdY !== null"
          :x="padding.left"
          :y="alertThresholdY"
          :width="chartWidth"
          :height="Math.max(0, chartHeight + padding.top - alertThresholdY)"
          fill="#fef2f2"
          opacity="0.5"
        />
        <line
          v-if="alertThresholdY !== null"
          :x1="padding.left"
          :y1="alertThresholdY"
          :x2="svgWidth - padding.right"
          :y2="alertThresholdY"
          stroke="#fca5a5"
          stroke-width="1"
          stroke-dasharray="4,3"
        />

        <!-- Dry weight line -->
        <line
          v-if="dryWeightY !== null"
          :x1="padding.left"
          :y1="dryWeightY"
          :x2="svgWidth - padding.right"
          :y2="dryWeightY"
          stroke="#86efac"
          stroke-width="1"
          stroke-dasharray="4,3"
        />

        <!-- Line path -->
        <polyline
          :points="pathPoints"
          fill="none"
          :stroke="alert ? '#ef4444' : '#10b981'"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <!-- Data points -->
        <circle
          v-for="(pt, i) in dataPoints"
          :key="'dot-' + i"
          :cx="pt.x"
          :cy="pt.y"
          r="3.5"
          :fill="pt.interpretation === 'H' ? '#ef4444' : '#10b981'"
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

      <!-- Legend -->
      <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
        <span class="flex items-center gap-1">
          <span class="w-3 h-0.5 bg-green-300 inline-block" style="border-top: 1px dashed #86efac;"></span>
          Dry weight ({{ dryWeight }}kg)
        </span>
        <span class="flex items-center gap-1">
          <span class="w-3 h-0.5 bg-red-300 inline-block" style="border-top: 1px dashed #fca5a5;"></span>
          Alert threshold (+{{ alertThresholdKg }}kg)
        </span>
        <span class="ml-auto">Latest: {{ latestWeight }}kg</span>
      </div>
    </div>

    <div v-else class="px-4 pb-4 text-center text-gray-400 text-sm">
      Not enough weight data to display chart.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  weights: {
    type: Array,
    required: true,
  },
});

const svgWidth = 400;
const svgHeight = 200;
const padding = { top: 15, right: 15, bottom: 25, left: 40 };
const chartWidth = svgWidth - padding.left - padding.right;
const chartHeight = svgHeight - padding.top - padding.bottom;

const dryWeight = computed(() => {
  const first = props.weights[0];
  return first?.specialty_data?.dry_weight ?? props.weights[0]?.value_quantity ?? 0;
});

const alertThresholdKg = computed(() => {
  const first = props.weights[0];
  return first?.specialty_data?.alert_threshold_kg ?? 2.0;
});

const latestWeight = computed(() => {
  if (!props.weights.length) return 0;
  return parseFloat(props.weights[props.weights.length - 1].value_quantity);
});

const weightGain3d = computed(() => {
  const sorted = sortedWeights.value;
  if (sorted.length < 2) return 0;
  const latest = parseFloat(sorted[sorted.length - 1].value_quantity);
  // Find entry ~3 days ago
  const threeAgo = sorted.length >= 4 ? sorted[sorted.length - 4] : sorted[0];
  return latest - parseFloat(threeAgo.value_quantity);
});

const alert = computed(() => weightGain3d.value >= alertThresholdKg.value);

const sortedWeights = computed(() => {
  return [...props.weights].sort(
    (a, b) => new Date(a.effective_date) - new Date(b.effective_date)
  );
});

const yRange = computed(() => {
  const values = sortedWeights.value.map(w => parseFloat(w.value_quantity));
  const dw = dryWeight.value;
  const threshold = dw + alertThresholdKg.value;
  const all = [...values, dw, threshold];
  const min = Math.floor(Math.min(...all) - 1);
  const max = Math.ceil(Math.max(...all) + 1);
  return { min, max };
});

const gridLines = computed(() => {
  const { min, max } = yRange.value;
  const step = Math.ceil((max - min) / 4);
  const lines = [];
  for (let v = min; v <= max; v += step) {
    lines.push({
      value: v,
      y: padding.top + chartHeight - ((v - min) / (max - min)) * chartHeight,
    });
  }
  return lines;
});

function scaleY(val) {
  const { min, max } = yRange.value;
  return padding.top + chartHeight - ((val - min) / (max - min)) * chartHeight;
}

const dryWeightY = computed(() => scaleY(dryWeight.value));

const alertThresholdY = computed(() => {
  return scaleY(dryWeight.value + alertThresholdKg.value);
});

const dataPoints = computed(() => {
  const sorted = sortedWeights.value;
  if (sorted.length < 2) return [];
  return sorted.map((w, i) => ({
    x: padding.left + (i / (sorted.length - 1)) * chartWidth,
    y: scaleY(parseFloat(w.value_quantity)),
    interpretation: w.interpretation,
  }));
});

const pathPoints = computed(() => {
  return dataPoints.value.map(p => `${p.x},${p.y}`).join(' ');
});

const xLabels = computed(() => {
  const sorted = sortedWeights.value;
  if (sorted.length < 2) return [];
  // Show labels for first, middle, and last
  const indices = [0, Math.floor(sorted.length / 2), sorted.length - 1];
  return [...new Set(indices)].map(i => ({
    x: padding.left + (i / (sorted.length - 1)) * chartWidth,
    label: formatShortDate(sorted[i].effective_date),
  }));
});

function formatShortDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>
