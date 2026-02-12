<template>
  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-gray-900">Medical Documents</h2>
      <span class="text-xs text-gray-400">{{ documents.length }} files</span>
    </div>
    <div class="space-y-2">
      <div
        v-for="doc in documents"
        :key="doc.name"
        class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/60 transition-all cursor-pointer group"
      >
        <!-- Icon with type-specific SVG -->
        <div
          class="relative w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
          :class="doc.iconBg"
        >
          <component :is="doc.iconComponent" class="w-5 h-5" :class="doc.iconColor" />
          <!-- New indicator dot -->
          <span
            v-if="doc.isNew"
            class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-blue-500 rounded-full ring-2 ring-white"
          />
        </div>

        <!-- Document info -->
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-800 truncate group-hover:text-gray-900">{{ doc.name }}</p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="text-xs text-gray-400">{{ doc.type }}</span>
            <span class="text-gray-300">&middot;</span>
            <span class="text-xs text-gray-400">{{ doc.date }}</span>
          </div>
        </div>

        <!-- Badges -->
        <div class="flex items-center gap-1.5 ml-auto shrink-0">
          <span
            v-if="doc.aiAnalyzed"
            class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60"
          >
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
            AI
          </span>
          <span
            class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
            :class="doc.statusClass"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="doc.statusDot" />
            {{ doc.status }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { h, markRaw } from 'vue';

/**
 * Inline SVG icon components — Heroicons-style (24x24, stroke-based).
 * Each renders a distinctive clinical icon for its document type.
 */

/** Visit Summary / Discharge — clipboard with document lines */
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

/** Heart / Echocardiogram — anatomical heart with pulse line */
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

/** EKG / Heart rhythm — ECG waveform */
const EkgWaveIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M3 12h4l2-6 3 12 2-6h2l1.5-3 1.5 3H22' }),
            h('rect', { x: '2', y: '4', width: '20', height: '16', rx: '2', 'stroke-opacity': '0.3' }),
        ]);
    },
});

/** Lab Results — test tube with liquid level */
const TestTubeIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M9 3v3M15 3v3' }),
            h('path', { d: 'M8 6h8l-1 14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2L8 6z' }),
            h('path', { d: 'M8.5 14h7' }),
            h('circle', { cx: '11', cy: '17', r: '0.5', fill: 'currentColor', stroke: 'none' }),
            h('circle', { cx: '13', cy: '18', r: '0.5', fill: 'currentColor', stroke: 'none' }),
        ]);
    },
});

/** Wearable / Watch — smartwatch with heart rate */
const SmartWatchIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('rect', { x: '6', y: '6', width: '12', height: '12', rx: '3' }),
            h('path', { d: 'M9 6V3h6v3M9 18v3h6v-3' }),
            h('path', { d: 'M9.5 12h1.5l1-2 1.5 4 1-2H16' }),
        ]);
    },
});

/** Prescription — Rx pill bottle */
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

/** Insurance / Authorization — shield with check */
const ShieldCheckIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M12 2l7 4v5c0 5.25-3.5 8.75-7 10-3.5-1.25-7-4.75-7-10V6l7-4z' }),
            h('path', { d: 'M9 12l2 2 4-4' }),
        ]);
    },
});

/** Medical Order — clipboard with list */
const OrderFormIcon = markRaw({
    render() {
        return h('svg', {
            viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
            'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        }, [
            h('path', { d: 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2' }),
            h('rect', { x: '9', y: '3', width: '6', height: '4', rx: '1' }),
            h('circle', { cx: '9', cy: '12', r: '0.75', fill: 'currentColor', stroke: 'none' }),
            h('path', { d: 'M12 12h4' }),
            h('circle', { cx: '9', cy: '15.5', r: '0.75', fill: 'currentColor', stroke: 'none' }),
            h('path', { d: 'M12 15.5h4' }),
        ]);
    },
});

const documents = [
    {
        name: 'Discharge Summary — Cardiology Visit',
        type: 'PDF',
        date: 'Feb 9, 2026',
        iconComponent: ClipboardDocIcon,
        iconBg: 'bg-rose-50',
        iconColor: 'text-rose-600',
        status: 'Imported',
        statusClass: 'bg-green-50 text-green-700 border border-green-200/60',
        statusDot: 'bg-green-500',
        aiAnalyzed: true,
        isNew: false,
    },
    {
        name: 'Echocardiogram Report',
        type: 'PDF',
        date: 'Feb 9, 2026',
        iconComponent: HeartPulseIcon,
        iconBg: 'bg-pink-50',
        iconColor: 'text-pink-600',
        status: 'Imported',
        statusClass: 'bg-green-50 text-green-700 border border-green-200/60',
        statusDot: 'bg-green-500',
        aiAnalyzed: true,
        isNew: false,
    },
    {
        name: '12-Lead EKG Recording',
        type: 'DICOM',
        date: 'Feb 9, 2026',
        iconComponent: EkgWaveIcon,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
        status: 'Imported',
        statusClass: 'bg-green-50 text-green-700 border border-green-200/60',
        statusDot: 'bg-green-500',
        aiAnalyzed: true,
        isNew: false,
    },
    {
        name: 'Lab Results — CBC, BMP, Lipids',
        type: 'HL7 FHIR',
        date: 'Feb 9, 2026',
        iconComponent: TestTubeIcon,
        iconBg: 'bg-purple-50',
        iconColor: 'text-purple-600',
        status: 'Imported',
        statusClass: 'bg-green-50 text-green-700 border border-green-200/60',
        statusDot: 'bg-green-500',
        aiAnalyzed: true,
        isNew: false,
    },
    {
        name: 'Apple Watch Health Export',
        type: 'JSON',
        date: 'Feb 10, 2026',
        iconComponent: SmartWatchIcon,
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        status: 'Synced',
        statusClass: 'bg-blue-50 text-blue-700 border border-blue-200/60',
        statusDot: 'bg-blue-500',
        aiAnalyzed: false,
        isNew: true,
    },
    {
        name: 'Prescription — Propranolol 40mg',
        type: 'e-Rx (NCPDP)',
        date: 'Feb 9, 2026',
        iconComponent: PrescriptionIcon,
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
        status: 'Active',
        statusClass: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',
        statusDot: 'bg-emerald-500',
        aiAnalyzed: false,
        isNew: false,
    },
    {
        name: 'Insurance Pre-Authorization',
        type: 'X12 278',
        date: 'Feb 8, 2026',
        iconComponent: ShieldCheckIcon,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
        status: 'Approved',
        statusClass: 'bg-amber-50 text-amber-700 border border-amber-200/60',
        statusDot: 'bg-amber-500',
        aiAnalyzed: false,
        isNew: false,
    },
    {
        name: 'Holter Monitor Order',
        type: 'HL7 ORM',
        date: 'Feb 9, 2026',
        iconComponent: OrderFormIcon,
        iconBg: 'bg-indigo-50',
        iconColor: 'text-indigo-600',
        status: 'Pending',
        statusClass: 'bg-gray-100 text-gray-600 border border-gray-200/60',
        statusDot: 'bg-gray-400',
        aiAnalyzed: false,
        isNew: true,
    },
];
</script>
