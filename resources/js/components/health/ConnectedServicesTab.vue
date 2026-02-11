<template>
  <div class="space-y-6">
    <div v-for="category in categories" :key="category.name">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ category.name }}</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <ConnectedServiceCard
          v-for="svc in category.services"
          :key="svc.id"
          :service="svc"
          @connect="toggleService"
          @disconnect="toggleService"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ConnectedServiceCard from '@/components/health/ConnectedServiceCard.vue';

const services = ref([
    // Wearables
    { id: 'apple-health', name: 'Apple Health', description: 'Sync heart rate, activity, sleep, and ECG data', dataTypes: ['Heart Rate', 'Steps', 'ECG', 'Sleep'], connected: true, lastSync: '2 hours ago', brandColor: '#FF2D55', icon: '\u2328\uFE0F', category: 'Wearables' },
    { id: 'google-fit', name: 'Google Fit', description: 'Activity tracking and health metrics', dataTypes: ['Steps', 'Heart Rate', 'Weight'], connected: false, lastSync: null, brandColor: '#4285F4', icon: '\uD83C\uDFC3', category: 'Wearables' },
    { id: 'fitbit', name: 'Fitbit', description: 'Comprehensive health and wellness tracking', dataTypes: ['Heart Rate', 'Sleep', 'SpO2', 'Steps'], connected: false, lastSync: null, brandColor: '#00B0B9', icon: '\u231A', category: 'Wearables' },
    { id: 'samsung-health', name: 'Samsung Health', description: 'Galaxy Watch and phone sensor data', dataTypes: ['Heart Rate', 'BP', 'Body Comp'], connected: false, lastSync: null, brandColor: '#1428A0', icon: '\uD83D\uDCF1', category: 'Wearables' },
    { id: 'garmin', name: 'Garmin Connect', description: 'GPS, heart rate, and performance data', dataTypes: ['Heart Rate', 'VO2 Max', 'Steps'], connected: false, lastSync: null, brandColor: '#007CC3', icon: '\uD83E\uDDED', category: 'Wearables' },
    { id: 'withings', name: 'Withings', description: 'Smart scales, BP monitors, sleep trackers', dataTypes: ['Weight', 'BP', 'Sleep', 'ECG'], connected: false, lastSync: null, brandColor: '#00A98F', icon: '\u2696\uFE0F', category: 'Wearables' },
    // EHR Portals
    { id: 'epic-mychart', name: 'Epic MyChart', description: 'Access medical records, lab results, and appointments', dataTypes: ['Records', 'Labs', 'Meds', 'Notes'], connected: true, lastSync: '1 day ago', brandColor: '#862074', icon: '\uD83C\uDFE5', category: 'EHR Portals' },
    { id: 'cerner', name: 'Cerner / Oracle Health', description: 'Hospital records and care summaries', dataTypes: ['Records', 'Labs', 'Discharge'], connected: false, lastSync: null, brandColor: '#E31937', icon: '\uD83D\uDCCB', category: 'EHR Portals' },
    // Pharmacies
    { id: 'cvs', name: 'CVS Pharmacy', description: 'Prescription history and refill tracking', dataTypes: ['Rx History', 'Refills', 'Costs'], connected: true, lastSync: '3 days ago', brandColor: '#CC0000', icon: '\uD83D\uDC8A', category: 'Pharmacies' },
    { id: 'walgreens', name: 'Walgreens', description: 'Medications, immunizations, and health records', dataTypes: ['Rx History', 'Immunizations'], connected: false, lastSync: null, brandColor: '#E31837', icon: '\uD83C\uDFEA', category: 'Pharmacies' },
    // Labs
    { id: 'quest', name: 'Quest Diagnostics', description: 'Lab test results and ordering', dataTypes: ['Lab Results', 'Pathology'], connected: false, lastSync: null, brandColor: '#003B71', icon: '\uD83E\uDDEA', category: 'Labs' },
    { id: 'labcorp', name: 'Labcorp', description: 'Comprehensive lab testing results', dataTypes: ['Lab Results', 'Genetics'], connected: false, lastSync: null, brandColor: '#003865', icon: '\uD83D\uDD2C', category: 'Labs' },
    // Insurance
    { id: 'aetna', name: 'Aetna', description: 'Claims, coverage details, and EOBs', dataTypes: ['Claims', 'Coverage', 'EOBs'], connected: true, lastSync: '1 week ago', brandColor: '#7B2D8E', icon: '\uD83C\uDFE6', category: 'Insurance' },
    { id: 'united', name: 'UnitedHealthcare', description: 'Benefits, claims status, and provider network', dataTypes: ['Claims', 'Benefits', 'Network'], connected: false, lastSync: null, brandColor: '#002677', icon: '\uD83D\uDEE1\uFE0F', category: 'Insurance' },
]);

const categories = [
    { name: 'Wearables', services: services.value.filter(s => s.category === 'Wearables') },
    { name: 'EHR Portals', services: services.value.filter(s => s.category === 'EHR Portals') },
    { name: 'Pharmacies', services: services.value.filter(s => s.category === 'Pharmacies') },
    { name: 'Labs', services: services.value.filter(s => s.category === 'Labs') },
    { name: 'Insurance', services: services.value.filter(s => s.category === 'Insurance') },
];

function toggleService(serviceId) {
    const svc = services.value.find(s => s.id === serviceId);
    if (svc) {
        svc.connected = !svc.connected;
        svc.lastSync = svc.connected ? 'Just now' : null;
    }
}
</script>
