<template>
  <div class="min-h-screen bg-gradient-to-b from-emerald-50 to-white">
    <div class="max-w-2xl mx-auto px-4 py-12">
      <!-- Header -->
      <div class="mb-8">
        <router-link to="/" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 text-sm font-medium mb-6">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          Back to PostVisit.ai
        </router-link>
        <div class="flex items-center gap-3 mb-2">
          <img src="/images/logo-full.png" alt="PostVisit.ai" class="h-8" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ title }}</h1>
        <p class="text-sm text-gray-400 mt-1">Last updated: February 16, 2026</p>
      </div>

      <!-- Privacy Policy -->
      <div v-if="page === 'privacy'" class="prose prose-gray max-w-none space-y-6">
        <section>
          <h2 class="text-xl font-semibold text-gray-900">1. Overview</h2>
          <p class="text-gray-600 leading-relaxed">PostVisit.ai is an AI-powered post-visit companion designed to help patients understand, remember, and act on their healthcare visits. This Privacy Policy describes how we collect, use, and protect your information.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">2. Information We Collect</h2>
          <ul class="text-gray-600 space-y-2 list-disc pl-5">
            <li><strong>Account Information:</strong> Name and email address used to create your account. Date of birth and demographic data stored in your patient profile.</li>
            <li><strong>Health Data:</strong> Visit transcripts, medical conditions, observations, and prescriptions you or your healthcare provider enter.</li>
            <li><strong>Audio Recordings:</strong> With explicit consent from both parties, audio of medical visits captured via the Companion Scribe feature.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">3. How We Use Your Data</h2>
          <ul class="text-gray-600 space-y-2 list-disc pl-5">
            <li>Generate visit summaries and plain-language explanations using AI.</li>
            <li>Provide personalized health education materials.</li>
            <li>Enable secure communication between patients and healthcare providers.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">4. AI Processing</h2>
          <p class="text-gray-600 leading-relaxed">Your health data is processed by Claude (Anthropic) to generate summaries, answer questions, and create educational content. Per Anthropic's commercial API terms, data sent via the API is not used to train their models. All AI interactions are logged in our audit system for quality assurance.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">5. Data Security</h2>
          <p class="text-gray-600 leading-relaxed">We employ industry-standard security measures including encrypted data transmission (TLS), secure server infrastructure, role-based access controls, and comprehensive audit logging of all data access.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">6. Data Retention</h2>
          <p class="text-gray-600 leading-relaxed">Your health data is retained for as long as your account is active. Audio recordings are processed for transcription and stored on the server. As a prototype, data management features including export and deletion are under development.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">7. Your Rights</h2>
          <p class="text-gray-600 leading-relaxed">As a hackathon prototype, full data rights management (export, correction, deletion) is not yet implemented. You can withdraw consent for audio recording at any time by stopping the Companion Scribe session. For data-related requests, contact us directly.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">8. Contact</h2>
          <p class="text-gray-600 leading-relaxed">For privacy-related inquiries, please contact us at <a href="mailto:privacy@postvisit.ai" class="text-emerald-600 hover:text-emerald-700 underline">privacy@postvisit.ai</a>.</p>
        </section>
      </div>

      <!-- Terms of Use -->
      <div v-else-if="page === 'terms'" class="prose prose-gray max-w-none space-y-6">
        <section>
          <h2 class="text-xl font-semibold text-gray-900">1. Acceptance of Terms</h2>
          <p class="text-gray-600 leading-relaxed">By accessing or using PostVisit.ai, you agree to be bound by these Terms of Use. If you do not agree, please do not use the service.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">2. Service Description</h2>
          <p class="text-gray-600 leading-relaxed">PostVisit.ai is an AI-powered health companion that helps patients understand their medical visits through automated transcription, plain-language summaries, medication information, and interactive Q&A.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">3. Medical Disclaimer</h2>
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800">
            <p class="font-semibold">PostVisit.ai is not a substitute for professional medical advice.</p>
            <p class="mt-2 text-sm">AI-generated summaries and explanations are for informational purposes only. Always consult your healthcare provider for medical decisions. Do not disregard professional medical advice based on information from this service.</p>
          </div>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">4. User Responsibilities</h2>
          <ul class="text-gray-600 space-y-2 list-disc pl-5">
            <li>Provide accurate information about your health and identity.</li>
            <li>Obtain consent from all parties before recording a medical visit.</li>
            <li>Keep your account credentials secure.</li>
            <li>Use the service for personal health management purposes only.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">5. Recording Consent</h2>
          <p class="text-gray-600 leading-relaxed">The Companion Scribe feature records audio of medical visits. You are responsible for obtaining explicit consent from your healthcare provider before initiating any recording. Recording without consent may violate local laws.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">6. AI-Generated Content</h2>
          <p class="text-gray-600 leading-relaxed">Summaries, explanations, and educational materials are generated by AI and may contain errors. AI outputs may include medical references with PubMed IDs that are verified against the PubMed database, but overall accuracy is not guaranteed.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">7. Limitation of Liability</h2>
          <p class="text-gray-600 leading-relaxed">PostVisit.ai is provided "as is" without warranties. We are not liable for any health outcomes resulting from reliance on AI-generated content. This is a prototype developed for the Anthropic Hackathon (February 2026).</p>
        </section>
      </div>

      <!-- Legal Notice -->
      <div v-else-if="page === 'legal'" class="prose prose-gray max-w-none space-y-6">
        <section>
          <h2 class="text-xl font-semibold text-gray-900">Project Information</h2>
          <p class="text-gray-600 leading-relaxed">PostVisit.ai is a prototype application developed for the Anthropic Hackathon (Build with Claude, February 10-16, 2026). It demonstrates the potential of AI-assisted post-visit patient care.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">Technology</h2>
          <ul class="text-gray-600 space-y-2 list-disc pl-5">
            <li><strong>AI Model:</strong> Claude Opus 4.6 by Anthropic — used for transcription analysis, medical summarization, patient education, and interactive Q&A.</li>
            <li><strong>Speech-to-Text:</strong> OpenAI Whisper — for audio transcription of medical visits.</li>
            <li><strong>Backend:</strong> Laravel 12 (PHP 8.4) with PostgreSQL.</li>
            <li><strong>Frontend:</strong> Vue 3 with Tailwind CSS v4.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">Medical Data Sources</h2>
          <ul class="text-gray-600 space-y-2 list-disc pl-5">
            <li><strong>Drug Information:</strong> RxNorm (NLM/NIH) and DailyMed (FDA) — live API integration, public domain data.</li>
            <li><strong>Medical References:</strong> PubMed (NLM/NIH) — PMID verification via E-utilities API.</li>
            <li><strong>Clinical Terminology:</strong> ICD-10-CM and SNOMED CT codes used in condition data structures for standardized coding.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">Open Source Licenses</h2>
          <p class="text-gray-600 leading-relaxed">This application uses open-source software. Key dependencies include Laravel (MIT), Vue.js (MIT), Three.js (MIT), and Tailwind CSS (MIT). A complete license inventory is maintained in the project documentation.</p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900">Contact</h2>
          <p class="text-gray-600 leading-relaxed">For questions about PostVisit.ai, please contact <a href="mailto:hello@postvisit.ai" class="text-emerald-600 hover:text-emerald-700 underline">hello@postvisit.ai</a>.</p>
        </section>
      </div>

      <!-- Footer nav -->
      <div class="mt-12 pt-8 border-t border-gray-200 flex flex-wrap gap-4 text-sm text-gray-400">
        <router-link v-if="page !== 'privacy'" to="/privacy" class="hover:text-emerald-600 transition-colors">Privacy Policy</router-link>
        <router-link v-if="page !== 'terms'" to="/terms" class="hover:text-emerald-600 transition-colors">Terms of Use</router-link>
        <router-link v-if="page !== 'legal'" to="/legal" class="hover:text-emerald-600 transition-colors">Legal Notice</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const page = computed(() => {
  const path = route.path;
  if (path === '/privacy' || path === '/privacy-policy') return 'privacy';
  if (path === '/terms') return 'terms';
  if (path === '/legal') return 'legal';
  return 'privacy';
});

const title = computed(() => {
  switch (page.value) {
    case 'privacy': return 'Privacy Policy';
    case 'terms': return 'Terms of Use';
    case 'legal': return 'Legal Notice';
    default: return 'Privacy Policy';
  }
});
</script>
