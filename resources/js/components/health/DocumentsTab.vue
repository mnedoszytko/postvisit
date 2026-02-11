<template>
  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <h2 class="font-semibold text-gray-900 mb-3">Medical Documents</h2>
    <div class="space-y-2">
      <div
        v-for="doc in documents"
        :key="doc.name"
        class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors"
      >
        <div
          class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
          :class="doc.iconBg"
        >
          <span class="text-sm">{{ doc.icon }}</span>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-800 truncate">{{ doc.name }}</p>
          <p class="text-xs text-gray-400">{{ doc.type }} &middot; {{ doc.date }}</p>
        </div>
        <span
          class="ml-auto text-xs px-2 py-0.5 rounded-full shrink-0"
          :class="doc.statusClass"
        >
          {{ doc.status }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
const documents = [
    { name: 'Discharge Summary — Cardiology Visit', type: 'PDF', date: 'Feb 9, 2026', icon: '\uD83D\uDCC4', iconBg: 'bg-red-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Echocardiogram Report', type: 'PDF', date: 'Feb 9, 2026', icon: '\uD83E\uDEC0', iconBg: 'bg-pink-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: '12-Lead EKG Recording', type: 'DICOM', date: 'Feb 9, 2026', icon: '\uD83D\uDCCA', iconBg: 'bg-blue-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Lab Results — CBC, BMP, Lipids', type: 'HL7 FHIR', date: 'Feb 9, 2026', icon: '\uD83E\uDDEA', iconBg: 'bg-purple-50', status: 'Imported', statusClass: 'bg-green-100 text-green-700' },
    { name: 'Apple Watch Health Export', type: 'JSON', date: 'Feb 10, 2026', icon: '\u231A', iconBg: 'bg-gray-100', status: 'Synced', statusClass: 'bg-blue-100 text-blue-700' },
    { name: 'Prescription — Propranolol 40mg', type: 'e-Rx (NCPDP)', date: 'Feb 9, 2026', icon: '\uD83D\uDC8A', iconBg: 'bg-emerald-50', status: 'Active', statusClass: 'bg-emerald-100 text-emerald-700' },
    { name: 'Insurance Pre-Authorization', type: 'X12 278', date: 'Feb 8, 2026', icon: '\uD83C\uDFE5', iconBg: 'bg-amber-50', status: 'Approved', statusClass: 'bg-amber-100 text-amber-700' },
    { name: 'Holter Monitor Order', type: 'HL7 ORM', date: 'Feb 9, 2026', icon: '\uD83D\uDCCB', iconBg: 'bg-indigo-50', status: 'Pending', statusClass: 'bg-gray-200 text-gray-600' },
];
</script>
