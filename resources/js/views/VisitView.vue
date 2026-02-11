<template>
  <PatientLayout :wide="true">
    <div class="relative">
      <!-- Mobile tab switcher -->
      <div class="lg:hidden flex bg-white rounded-xl border border-gray-200 p-1 mb-4">
        <button
          :class="[
            'flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors',
            mobileTab === 'visit'
              ? 'bg-emerald-600 text-white'
              : 'text-gray-600 hover:text-gray-800'
          ]"
          @click="mobileTab = 'visit'"
        >
          Visit Summary
        </button>
        <button
          :class="[
            'flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors',
            mobileTab === 'chat'
              ? 'bg-emerald-600 text-white'
              : 'text-gray-600 hover:text-gray-800'
          ]"
          @click="mobileTab = 'chat'"
        >
          <span class="flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            AI Chat
          </span>
        </button>
      </div>

      <!-- Two-column layout -->
      <div class="flex gap-6">
        <!-- LEFT COLUMN: Visit content (scrollable) -->
        <div
          :class="[
            'min-w-0 transition-all duration-300',
            chatVisible ? 'flex-1' : 'w-full',
            mobileTab === 'chat' ? 'hidden lg:block' : ''
          ]"
        >
          <!-- Visit header -->
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Visit Summary</h1>
            <div v-if="visit" class="flex flex-wrap items-center gap-2 mt-2">
              <span class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                {{ formatDate(visit.started_at) }}
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                {{ formatVisitType(visit.visit_type) }}
              </span>
              <span v-if="visit.practitioner" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Dr. {{ visit.practitioner.first_name }} {{ visit.practitioner.last_name }}
                <span v-if="visit.practitioner.primary_specialty" class="text-blue-500">&middot; {{ visit.practitioner.primary_specialty }}</span>
              </span>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="visitStore.loading" class="text-center py-12 text-gray-400">
            Loading visit data...
          </div>

          <!-- Empty visit -->
          <div v-else-if="visit && isEmptyVisit" class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center space-y-4">
              <div class="w-16 h-16 mx-auto bg-emerald-50 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-gray-800">No recording yet</h3>
              <p class="text-gray-500 text-sm max-w-sm mx-auto">
                This visit doesn't have a transcript yet. Start recording to get a complete summary with AI-powered insights.
              </p>
              <router-link
                :to="{ path: '/scribe', query: { visitId: route.params.id } }"
                class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                </svg>
                Start Recording
              </router-link>
            </div>
            <VisitAttachments :visit-id="route.params.id" :terms="allMedicalTerms" @term-click="showTermPopover" />
          </div>

          <!-- Visit sections (has content) -->
          <div v-else-if="visit" class="space-y-4">
            <!-- Quick Summary Card -->
            <div v-if="visitSummary" class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl border border-emerald-200 p-5">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                  <svg class="w-4.5 h-4.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                  </svg>
                </div>
                <div>
                  <h3 class="font-semibold text-emerald-900 text-sm mb-1">Quick Summary</h3>
                  <p class="text-sm text-emerald-800 leading-relaxed">{{ visitSummary }}</p>
                </div>
              </div>
            </div>

            <!-- SOAP Note sections -->
            <VisitSection
              v-for="section in soapSections"
              :key="section.key"
              :title="section.title"
              :content="section.content"
              :terms="section.terms"
              :section-key="section.sectionKey"
              @explain="openChat(section.title)"
              @term-click="showTermPopover"
            />

            <!-- Doctor's Recommendations -->
            <div v-if="recommendations.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 flex items-center justify-center rounded-lg shrink-0 bg-amber-50">
                      <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                      </svg>
                    </span>
                    <h3 class="font-semibold text-gray-800">Doctor's Recommendations</h3>
                  </div>
                  <AskAiButton @click="openChat('Doctor\'s Recommendations')" />
                </div>
                <div class="space-y-2">
                  <div
                    v-for="(rec, idx) in recommendations"
                    :key="idx"
                    class="flex items-start gap-3 rounded-lg bg-amber-50/50 border border-amber-100 px-3 py-2.5"
                  >
                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                      {{ idx + 1 }}
                    </span>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ rec }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Next Actions Checklist -->
            <div v-if="nextActions.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 flex items-center justify-center rounded-lg shrink-0 bg-emerald-50">
                      <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </span>
                    <h3 class="font-semibold text-gray-800">Next Actions</h3>
                  </div>
                  <AskAiButton @click="openChat('Next Actions')" />
                </div>
                <div class="space-y-1.5">
                  <label
                    v-for="(action, idx) in nextActions"
                    :key="idx"
                    class="flex items-start gap-3 rounded-lg px-3 py-2 hover:bg-gray-50 transition-colors cursor-pointer group"
                  >
                    <input
                      type="checkbox"
                      v-model="checkedActions[idx]"
                      class="mt-0.5 w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    <span
                      :class="[
                        'text-sm leading-relaxed transition-colors',
                        checkedActions[idx] ? 'line-through text-gray-400' : 'text-gray-700'
                      ]"
                    >{{ action }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Observations / Test Results -->
            <div v-if="visit.observations?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="obsExpanded = !obsExpanded">
                <div class="flex items-center gap-2">
                  <h3 class="font-semibold text-gray-800">Test Results &amp; Observations</h3>
                  <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                    {{ visit.observations.length }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <AskAiButton @click.stop="openChat('Test Results & Observations')" />
                  <span class="text-gray-400 text-sm">{{ obsExpanded ? 'Collapse' : 'Expand' }}</span>
                </div>
              </button>
              <div v-if="obsExpanded" class="px-4 pb-4">
                <LabResults :observations="visit.observations" />
              </div>
            </div>

            <!-- Conditions / Diagnosis -->
            <div v-if="visit.conditions?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="condExpanded = !condExpanded">
                <h3 class="font-semibold text-gray-800">Diagnosis</h3>
                <div class="flex items-center gap-2">
                  <AskAiButton @click.stop="openChat('Diagnosis')" />
                  <span class="text-gray-400 text-sm">{{ condExpanded ? 'Collapse' : 'Expand' }}</span>
                </div>
              </button>
              <div v-if="condExpanded" class="px-4 pb-4 space-y-2">
                <div v-for="cond in visit.conditions" :key="cond.id" class="flex items-start gap-3">
                  <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full mt-0.5">{{ cond.code }}</span>
                  <div>
                    <p class="font-medium text-gray-800 text-sm">{{ cond.code_display }}</p>
                    <p v-if="cond.clinical_notes" class="text-xs text-gray-500 mt-0.5">{{ cond.clinical_notes }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Prescriptions -->
            <div v-if="visit.prescriptions?.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="rxExpanded = !rxExpanded">
                <h3 class="font-semibold text-gray-800">Medications Prescribed</h3>
                <div class="flex items-center gap-2">
                  <AskAiButton @click.stop="openChat('Medications Prescribed')" />
                  <span class="text-gray-400 text-sm">{{ rxExpanded ? 'Collapse' : 'Expand' }}</span>
                </div>
              </button>
              <div v-if="rxExpanded" class="px-4 pb-4 space-y-3">
                <div v-for="rx in visit.prescriptions" :key="rx.id" class="border-b border-gray-100 pb-2 last:border-0">
                  <p class="font-medium text-gray-800 text-sm">{{ rx.medication?.display_name || rx.medication?.generic_name }}</p>
                  <p class="text-sm text-gray-600">{{ parseFloat(rx.dose_quantity) }} {{ rx.dose_unit }} &middot; {{ rx.frequency_text || rx.frequency }}</p>
                  <p v-if="rx.special_instructions" class="text-xs text-gray-500 mt-1">{{ rx.special_instructions }}</p>
                </div>
              </div>
            </div>

            <!-- Patient Attachments -->
            <VisitAttachments :visit-id="route.params.id" :terms="allMedicalTerms" @term-click="showTermPopover" />

            <!-- AI-Extracted Entities (from transcript analysis) -->
            <div v-if="entities && Object.keys(entities).length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="entitiesExpanded = !entitiesExpanded">
                <div class="flex items-center gap-2">
                  <h3 class="font-semibold text-gray-800">AI-Extracted Clinical Entities</h3>
                  <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">AI</span>
                </div>
                <div class="flex items-center gap-2">
                  <AskAiButton @click.stop="openChat('AI-Extracted Clinical Entities')" />
                  <span class="text-gray-400 text-sm">{{ entitiesExpanded ? 'Collapse' : 'Expand' }}</span>
                </div>
              </button>
              <div v-if="entitiesExpanded" class="px-4 pb-4 space-y-5">
                <div v-for="(items, category) in entities" :key="category">
                  <!-- Medications: structured cards -->
                  <template v-if="category === 'medications' && Array.isArray(items) && items.length > 0">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Medications</h4>
                    <div class="space-y-2">
                      <div
                        v-for="(med, idx) in items"
                        :key="idx"
                        class="flex items-start gap-3 rounded-lg border border-gray-100 p-2.5"
                      >
                        <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-blue-500" />
                        <div class="min-w-0">
                          <p class="font-medium text-gray-800 text-sm">{{ formatMedName(med) }}</p>
                          <p v-if="formatMedDetails(med)" class="text-xs text-gray-500 mt-0.5">{{ formatMedDetails(med) }}</p>
                          <span
                            v-if="getMedStatus(med)"
                            :class="medStatusClass(getMedStatus(med))"
                            class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded-full mt-1"
                          >{{ getMedStatus(med) }}</span>
                        </div>
                      </div>
                    </div>
                  </template>

                  <!-- Test Results: structured rows -->
                  <template v-else-if="category === 'test_results' && Array.isArray(items) && items.length > 0">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Test Results</h4>
                    <div class="space-y-2">
                      <div
                        v-for="(result, idx) in items"
                        :key="idx"
                        class="rounded-lg border border-gray-100 p-2.5"
                      >
                        <template v-if="parseTestResult(result)">
                          <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-800 text-sm">{{ parseTestResult(result).test }}</span>
                            <span v-if="parseTestResult(result).date" class="text-[10px] text-gray-400">{{ parseTestResult(result).date }}</span>
                          </div>
                          <p class="text-sm text-gray-600 mt-0.5">{{ parseTestResult(result).result }}</p>
                        </template>
                        <template v-else>
                          <span class="text-sm text-gray-700">{{ cleanUnclear(formatEntityItem(result)) }}</span>
                        </template>
                      </div>
                    </div>
                  </template>

                  <!-- Vitals: key-value pairs -->
                  <template v-else-if="typeof items === 'object' && !Array.isArray(items) && Object.keys(items).length > 0">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                    <div class="grid grid-cols-2 gap-2">
                      <div v-for="(val, key) in items" :key="key" class="rounded-lg border border-gray-100 p-2">
                        <span class="text-[10px] text-gray-400 uppercase">{{ key }}</span>
                        <p class="text-sm font-medium text-gray-800">{{ cleanUnclear(String(val)) }}</p>
                      </div>
                    </div>
                  </template>

                  <!-- Generic arrays (symptoms, diagnoses, etc.) -->
                  <template v-else-if="Array.isArray(items) && items.length > 0">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ formatEntityCategory(category) }}</h4>
                    <div class="space-y-1.5">
                      <div
                        v-for="(item, idx) in items"
                        :key="idx"
                        class="flex items-start gap-2 text-sm"
                      >
                        <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="entityDotColor(category)" />
                        <span class="text-gray-700">{{ cleanUnclear(formatEntityItem(item)) }}</span>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>

            <!-- Raw Transcript -->
            <div v-if="visit.transcript?.raw_transcript" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <button class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors" @click="transcriptExpanded = !transcriptExpanded">
                <div class="flex items-center gap-2">
                  <h3 class="font-semibold text-gray-800">Visit Transcript</h3>
                  <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                    {{ visit.transcript.processing_status }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <AskAiButton @click.stop="openChat('Visit Transcript')" />
                  <span class="text-gray-400 text-sm">{{ transcriptExpanded ? 'Collapse' : 'Expand' }}</span>
                </div>
              </button>
              <div v-if="transcriptExpanded" class="px-4 pb-4 max-h-96 overflow-y-auto">
                <div v-if="visit.transcript.diarized_transcript?.clean_text" class="text-sm leading-relaxed whitespace-pre-wrap space-y-1">
                  <template v-for="(line, i) in visit.transcript.diarized_transcript.clean_text.split('\n')" :key="i">
                    <p v-if="line.startsWith('Doctor:') || line.startsWith('Dr:')" class="text-gray-700">
                      <span class="font-semibold text-emerald-700">Doctor:</span>{{ line.replace(/^(Doctor|Dr):/, '') }}
                    </p>
                    <p v-else-if="line.startsWith('Patient:')" class="text-gray-700">
                      <span class="font-semibold text-blue-600">Patient:</span>{{ line.replace(/^Patient:/, '') }}
                    </p>
                    <p v-else-if="line.trim()" class="text-gray-600">{{ line }}</p>
                  </template>
                </div>
                <p v-else class="text-gray-600 text-sm leading-relaxed whitespace-pre-wrap">{{ visit.transcript.raw_transcript }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Chat Panel -->
        <div
          v-if="chatVisible || mobileTab === 'chat'"
          :class="[
            'lg:w-[400px] lg:shrink-0 overflow-hidden',
            mobileTab === 'visit' ? 'hidden lg:block' : 'w-full'
          ]"
        >
          <div class="lg:sticky lg:top-20 lg:h-[calc(100vh-6rem)]">
            <ChatPanel
              :visit-id="route.params.id"
              :initial-context="chatContext"
              :highlight="chatHighlight"
              :embedded="true"
              @close="closeChat"
            />
          </div>
        </div>
      </div>

      <!-- Floating chat button (shown when chat is closed on desktop) -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-75"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-75"
      >
        <button
          v-if="!chatVisible"
          class="hidden lg:flex fixed bottom-6 right-6 w-14 h-14 bg-emerald-600 text-white rounded-full shadow-lg items-center justify-center hover:bg-emerald-700 hover:scale-105 transition-all z-40"
          title="Open AI Chat"
          @click="chatVisible = true"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
          </svg>
        </button>
      </Transition>

      <!-- Term Popover -->
      <TermPopover
        :visible="popoverVisible"
        :term="popoverTerm"
        :definition="popoverDefinition"
        :anchor-rect="popoverAnchorRect"
        @close="popoverVisible = false"
        @ask-more="(term) => openChat(term)"
      />
    </div>
  </PatientLayout>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useVisitStore } from '@/stores/visit';
import PatientLayout from '@/layouts/PatientLayout.vue';
import VisitSection from '@/components/VisitSection.vue';
import LabResults from '@/components/LabResults.vue';
import ChatPanel from '@/components/ChatPanel.vue';
import TermPopover from '@/components/TermPopover.vue';
import VisitAttachments from '@/components/VisitAttachments.vue';
import AskAiButton from '@/components/AskAiButton.vue';

const route = useRoute();
const visitStore = useVisitStore();
const mobileTab = ref('visit');
const chatVisible = ref(true);
const chatContext = ref('');
const chatHighlight = ref(false);
const obsExpanded = ref(false);
const condExpanded = ref(false);
const rxExpanded = ref(false);
const entitiesExpanded = ref(false);
const transcriptExpanded = ref(false);
const checkedActions = reactive({});

// Term popover state
const popoverVisible = ref(false);
const popoverTerm = ref('');
const popoverDefinition = ref('');
const popoverAnchorRect = ref(null);

const visit = computed(() => visitStore.currentVisit);

const isEmptyVisit = computed(() => {
    const v = visit.value;
    if (!v) return false;
    return !v.visit_note && !v.transcript?.raw_transcript;
});

const entities = computed(() => visit.value?.transcript?.entities_extracted || null);

// Visit summary: use visit.summary field, or fall back to chief complaint + assessment
const visitSummary = computed(() => {
    const v = visit.value;
    if (!v) return '';
    if (v.summary) return v.summary;
    // Fallback: compose from chief complaint and assessment
    const note = v.visit_note;
    if (!note) return '';
    const parts = [];
    if (note.chief_complaint) parts.push(note.chief_complaint);
    if (note.assessment) {
        // Take first sentence of assessment as summary
        const firstSentence = note.assessment.split(/[.!?\n]/)[0];
        if (firstSentence && firstSentence !== note.chief_complaint) {
            parts.push(firstSentence.trim() + '.');
        }
    }
    return parts.join(' ') || '';
});

// Parse recommendations from plan text
const recommendations = computed(() => {
    const note = visit.value?.visit_note;
    if (!note?.plan) return [];
    return parseActionItems(note.plan);
});

// Parse next actions from plan + follow_up
const nextActions = computed(() => {
    const note = visit.value?.visit_note;
    if (!note) return [];
    const items = [];

    // From follow_up
    if (note.follow_up) {
        const followUpItems = parseActionItems(note.follow_up);
        items.push(...followUpItems);
    }

    // From prescriptions — medication reminders
    const rxs = visit.value?.prescriptions;
    if (rxs?.length) {
        rxs.forEach(rx => {
            const name = rx.medication?.display_name || rx.medication?.generic_name;
            if (name) {
                const freq = rx.frequency_text || rx.frequency || '';
                items.push(`Take ${name} ${parseFloat(rx.dose_quantity)} ${rx.dose_unit} ${freq}`.trim());
            }
        });
    }

    return items;
});

function parseActionItems(text) {
    if (!text) return [];
    // Split by numbered items (1., 2., etc.), bullet points (-, *), or newlines
    const lines = text
        .split(/(?:\r?\n)+/)
        .map(line => line.replace(/^[\s]*[-*\d.)+]+[\s]*/, '').trim())
        .filter(line => line.length > 5);

    // If we got meaningful lines, return them; otherwise return the whole text as one item
    if (lines.length > 1) return lines;
    if (text.trim().length > 5) return [text.trim()];
    return [];
}

const sectionFieldMap = {
    cc: 'chief_complaint',
    hpi: 'history_of_present_illness',
    ros: 'review_of_systems',
    pe: 'physical_exam',
    assessment: 'assessment',
    plan: 'plan',
    followup: 'follow_up',
};

const soapSections = computed(() => {
    const note = visit.value?.visit_note;
    if (!note) return [];
    return [
        { key: 'cc', title: 'Chief Complaint' },
        { key: 'hpi', title: 'History of Present Illness' },
        { key: 'ros', title: 'Reported Symptoms' },
        { key: 'pe', title: 'Physical Examination' },
        { key: 'assessment', title: 'Assessment' },
        { key: 'plan', title: 'Plan' },
        { key: 'followup', title: 'Follow-up' },
    ].map(s => ({
        ...s,
        sectionKey: sectionFieldMap[s.key],
        content: note[sectionFieldMap[s.key]],
        terms: note.medical_terms?.[sectionFieldMap[s.key]] || [],
    })).filter(s => s.content);
});

// Flattened medical terms from all SOAP sections — used for attachment highlighting
const allMedicalTerms = computed(() => {
    const note = visit.value?.visit_note;
    if (!note?.medical_terms) return [];

    const seen = new Set();
    const terms = [];

    for (const sectionTerms of Object.values(note.medical_terms)) {
        if (!Array.isArray(sectionTerms)) continue;
        for (const t of sectionTerms) {
            const key = t.term?.toLowerCase();
            if (key && !seen.has(key)) {
                seen.add(key);
                terms.push(t);
            }
        }
    }

    return terms;
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatVisitType(type) {
    if (!type) return 'Visit';
    return type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

const entityCategoryLabels = {
    symptoms: 'Symptoms',
    diagnoses: 'Diagnoses',
    medications: 'Medications',
    tests_ordered: 'Tests Ordered',
    test_results: 'Test Results',
    vitals: 'Vitals',
    allergies: 'Allergies',
    procedures: 'Procedures',
};

function formatEntityCategory(key) {
    return entityCategoryLabels[key] || key.replace(/_/g, ' ');
}

function formatEntityItem(item) {
    if (typeof item === 'string') return item;
    if (typeof item === 'object' && item !== null) {
        if (item.name) {
            const parts = [item.name];
            if (item.dose) parts.push(item.dose);
            if (item.frequency) parts.push(item.frequency);
            if (item.route) parts.push(`(${item.route})`);
            if (item.status) parts.push(`[${item.status}]`);
            return parts.join(' — ');
        }
        if (item.description) return item.description;
        return JSON.stringify(item);
    }
    return String(item);
}

function entityDotColor(category) {
    const colors = {
        symptoms: 'bg-red-400',
        diagnoses: 'bg-amber-500',
        medications: 'bg-blue-500',
        tests_ordered: 'bg-purple-500',
        test_results: 'bg-purple-400',
        vitals: 'bg-emerald-500',
        allergies: 'bg-orange-500',
        procedures: 'bg-indigo-500',
    };
    return colors[category] || 'bg-gray-400';
}

function cleanUnclear(text) {
    if (!text) return '';
    return text.replace(/\[UNCLEAR\]/gi, '').trim() || 'Not specified';
}

function formatMedName(med) {
    if (typeof med === 'string') return cleanUnclear(med);
    return cleanUnclear(med?.name || '');
}

function formatMedDetails(med) {
    if (typeof med !== 'object' || !med) return '';
    const parts = [];
    if (med.dose && !/^\[UNCLEAR\]$/i.test(med.dose)) parts.push(med.dose);
    if (med.frequency && !/^\[UNCLEAR\]$/i.test(med.frequency)) parts.push(med.frequency);
    if (med.route && !/^\[UNCLEAR\]$/i.test(med.route)) parts.push(med.route);
    return parts.join(' · ');
}

function getMedStatus(med) {
    if (typeof med !== 'object' || !med) return '';
    return med.status || '';
}

function medStatusClass(status) {
    const classes = {
        new: 'bg-green-100 text-green-700',
        continued: 'bg-blue-100 text-blue-700',
        changed: 'bg-amber-100 text-amber-700',
        discontinued: 'bg-gray-200 text-gray-500',
    };
    return classes[status] || 'bg-gray-100 text-gray-600';
}

function parseTestResult(result) {
    if (typeof result === 'string') {
        return { test: result, result: '', date: '' };
    }
    if (typeof result === 'object' && result !== null) {
        return {
            test: result.test || result.name || result.code || '',
            result: result.result || result.value || '',
            date: result.date || '',
        };
    }
    return null;
}

function showTermPopover(payload) {
    popoverTerm.value = payload.term;
    popoverDefinition.value = payload.definition;
    popoverAnchorRect.value = payload.anchorRect;
    popoverVisible.value = true;
}

function closeChat() {
    if (window.innerWidth < 1024) {
        mobileTab.value = 'visit';
    } else {
        chatVisible.value = false;
    }
}

function openChat(context = '') {
    popoverVisible.value = false;
    chatVisible.value = true;
    chatHighlight.value = true;
    setTimeout(() => { chatHighlight.value = false; }, 600);
    chatContext.value = context;
    // On mobile, switch to chat tab
    if (window.innerWidth < 1024) {
        mobileTab.value = 'chat';
    }
}

onMounted(() => {
    visitStore.fetchVisit(route.params.id);
});
</script>
