import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

const MAX_MESSAGES = 100;

export const useChatStore = defineStore('chat', {
    state: () => ({
        messages: [],
        sessions: [],
        loading: false,
        error: null,
    }),

    actions: {
        trimMessages() {
            if (this.messages.length > MAX_MESSAGES) {
                this.messages.splice(0, this.messages.length - MAX_MESSAGES);
            }
        },

        async sendMessage(visitId, message, contextSources = null) {
            this.loading = true;
            this.error = null;
            this.messages.push({ role: 'user', content: message });
            this.trimMessages();

            // Add streaming placeholder for AI response
            const aiIndex = this.messages.length;
            this.messages.push({
                role: 'assistant',
                content: '',
                quickContent: '',
                quickDone: false,
                statusText: '',
                deepReady: false,
                thinking: '',
                thinkingPhase: true,
                streaming: true,
            });

            try {
                const body = { message };
                if (contextSources && contextSources.length > 0) {
                    body.context_sources = contextSources;
                }
                const response = await fetch(`/api/v1/visits/${visitId}/chat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    credentials: 'include',
                    body: JSON.stringify(body),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const payload = line.slice(6).trim();
                            if (payload === '[DONE]') continue;
                            try {
                                const parsed = JSON.parse(payload);
                                if (parsed.quick) {
                                    this.messages[aiIndex].quickContent += parsed.quick;
                                    if (this.messages[aiIndex].thinkingPhase) {
                                        this.messages[aiIndex].thinkingPhase = false;
                                    }
                                } else if (parsed.quick_done) {
                                    this.messages[aiIndex].quickDone = true;
                                    this.messages[aiIndex].thinkingPhase = true;
                                } else if (parsed.status) {
                                    this.messages[aiIndex].statusText = parsed.status;
                                } else if (parsed.phase) {
                                    // Map pipeline phase events to user-facing status
                                    const phaseLabels = {
                                        planning: 'Planning clinical analysis...',
                                        reasoning: 'Deep clinical reasoning...',
                                    };
                                    this.messages[aiIndex].statusText = phaseLabels[parsed.phase] || parsed.phase;
                                } else if (parsed.deep_ready) {
                                    this.messages[aiIndex].deepReady = true;
                                } else if (parsed.thinking) {
                                    this.messages[aiIndex].thinking += parsed.thinking;
                                } else if (parsed.text) {
                                    if (this.messages[aiIndex].thinkingPhase) {
                                        this.messages[aiIndex].thinkingPhase = false;
                                    }
                                    this.messages[aiIndex].content += parsed.text;
                                }
                            } catch (err) {
                                console.error('Parse SSE chunk failed:', err);
                            }
                        }
                    }
                }

                this.messages[aiIndex].streaming = false;
                this.messages[aiIndex].thinkingPhase = false;
            } catch (err) {
                this.messages[aiIndex].content = this.messages[aiIndex].content || 'Failed to get a response. Please try again.';
                this.messages[aiIndex].streaming = false;
                this.messages[aiIndex].thinkingPhase = false;
                this.error = err.message || 'Failed to send message';
            } finally {
                this.loading = false;
            }
        },

        async fetchHistory(visitId) {
            this.loading = true;
            this.error = null;
            try {
                const api = useApi();
                const { data } = await api.get(`/visits/${visitId}/chat/history`);
                this.messages = (data.data.messages || []).map(msg => ({
                    role: msg.role,
                    content: msg.content,
                    thinking: msg.thinking || '',
                }));
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load chat history';
            } finally {
                this.loading = false;
            }
        },

        clearMessages() {
            this.messages = [];
        },
    },
});

function getCsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}
