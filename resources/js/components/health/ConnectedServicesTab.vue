<template>
  <div class="space-y-6">
    <!-- Stats header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">
          <span class="font-semibold text-emerald-600">{{ connectedCount }}</span>
          of {{ services.length }} services connected
        </p>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-gray-400">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        HIPAA compliant
      </div>
    </div>

    <!-- Categories -->
    <div v-for="category in categories" :key="category.name" class="space-y-3">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryIcons[category.name]" />
        </svg>
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ category.name }}</h3>
        <span class="text-[10px] text-gray-400 font-medium">
          {{ category.services.filter(s => s.connected).length }}/{{ category.services.length }}
        </span>
      </div>
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

<script setup lang="ts">
import { ref, computed } from 'vue';
import ConnectedServiceCard from '@/components/health/ConnectedServiceCard.vue';

const services = ref([
    // Wearables
    { id: 'apple-health', name: 'Apple Health', description: 'Sync heart rate, activity, sleep, and ECG data', dataTypes: ['Heart Rate', 'Steps', 'ECG', 'Sleep'], connected: true, lastSync: '2 hours ago', brandColor: '#FF2D55', category: 'Wearables' },
    { id: 'google-fit', name: 'Google Fit', description: 'Activity tracking and health metrics', dataTypes: ['Steps', 'Heart Rate', 'Weight'], connected: false, lastSync: null, brandColor: '#4285F4', category: 'Wearables' },
    { id: 'fitbit', name: 'Fitbit', description: 'Comprehensive health and wellness tracking', dataTypes: ['Heart Rate', 'Sleep', 'SpO2', 'Steps'], connected: false, lastSync: null, brandColor: '#00B0B9', category: 'Wearables' },
    { id: 'samsung-health', name: 'Samsung Health', description: 'Galaxy Watch and phone sensor data', dataTypes: ['Heart Rate', 'BP', 'Body Comp'], connected: false, lastSync: null, brandColor: '#1428A0', category: 'Wearables' },
    { id: 'garmin', name: 'Garmin Connect', description: 'GPS, heart rate, and performance data', dataTypes: ['Heart Rate', 'VO2 Max', 'Steps'], connected: false, lastSync: null, brandColor: '#007CC3', category: 'Wearables' },
    { id: 'withings', name: 'Withings', description: 'Smart scales, BP monitors, sleep trackers', dataTypes: ['Weight', 'BP', 'Sleep', 'ECG'], connected: false, lastSync: null, brandColor: '#00A98F', category: 'Wearables' },
    // EHR Portals
    { id: 'epic-mychart', name: 'Epic MyChart', description: 'Access medical records, lab results, and appointments', dataTypes: ['Records', 'Labs', 'Meds', 'Notes'], connected: true, lastSync: '1 day ago', brandColor: '#862074', category: 'EHR Portals' },
    { id: 'cerner', name: 'Cerner / Oracle Health', description: 'Hospital records and care summaries', dataTypes: ['Records', 'Labs', 'Discharge'], connected: false, lastSync: null, brandColor: '#E31937', category: 'EHR Portals' },
    // Pharmacies
    { id: 'cvs', name: 'CVS Pharmacy', description: 'Prescription history and refill tracking', dataTypes: ['Rx History', 'Refills', 'Costs'], connected: true, lastSync: '3 days ago', brandColor: '#CC0000', category: 'Pharmacies' },
    { id: 'walgreens', name: 'Walgreens', description: 'Medications, immunizations, and health records', dataTypes: ['Rx History', 'Immunizations'], connected: false, lastSync: null, brandColor: '#E31837', category: 'Pharmacies' },
    // Labs
    { id: 'quest', name: 'Quest Diagnostics', description: 'Lab test results and ordering', dataTypes: ['Lab Results', 'Pathology'], connected: false, lastSync: null, brandColor: '#003B71', category: 'Labs' },
    { id: 'labcorp', name: 'Labcorp', description: 'Comprehensive lab testing results', dataTypes: ['Lab Results', 'Genetics'], connected: false, lastSync: null, brandColor: '#003865', category: 'Labs' },
    // Insurance
    { id: 'aetna', name: 'Aetna', description: 'Claims, coverage details, and EOBs', dataTypes: ['Claims', 'Coverage', 'EOBs'], connected: true, lastSync: '1 week ago', brandColor: '#7B2D8E', category: 'Insurance' },
    { id: 'united', name: 'UnitedHealthcare', description: 'Benefits, claims status, and provider network', dataTypes: ['Claims', 'Benefits', 'Network'], connected: false, lastSync: null, brandColor: '#002677', category: 'Insurance' },
]);

const connectedCount = computed(() => services.value.filter(s => s.connected).length);

const categoryIcons: Record<string, string> = {
    'Wearables': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'EHR Portals': 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'Pharmacies': 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m0 0l.94-2.06',
    'Labs': 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5',
    'Insurance': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
};

const categories = computed(() => [
    { name: 'Wearables', services: services.value.filter(s => s.category === 'Wearables') },
    { name: 'EHR Portals', services: services.value.filter(s => s.category === 'EHR Portals') },
    { name: 'Pharmacies', services: services.value.filter(s => s.category === 'Pharmacies') },
    { name: 'Labs', services: services.value.filter(s => s.category === 'Labs') },
    { name: 'Insurance', services: services.value.filter(s => s.category === 'Insurance') },
]);

function toggleService(serviceId: string) {
    const svc = services.value.find(s => s.id === serviceId);
    if (svc) {
        svc.connected = !svc.connected;
        svc.lastSync = svc.connected ? 'Just now' : null;
    }
}
</script>
