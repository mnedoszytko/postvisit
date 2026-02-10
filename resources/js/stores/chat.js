import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useChatStore = defineStore('chat', {
    state: () => ({
        messages: [],
        sessions: [],
        loading: false,
        error: null,
    }),

    actions: {
        async sendMessage(visitId, message) {
            this.loading = true;
            this.error = null;
            this.messages.push({ role: 'user', content: message });

            // Add streaming placeholder for AI response
            const aiIndex = this.messages.length;
            this.messages.push({ role: 'assistant', content: '', streaming: true });

            try {
                const api = useApi();
                const response = await fetch(`/api/v1/visits/${visitId}/chat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    credentials: 'include',
                    body: JSON.stringify({ message }),
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
                                if (parsed.text) {
                                    this.messages[aiIndex].content += parsed.text;
                                }
                            } catch {
                                // skip malformed chunks
                            }
                        }
                    }
                }

                this.messages[aiIndex].streaming = false;
            } catch (err) {
                this.messages[aiIndex].content = this.messages[aiIndex].content || 'Failed to get a response. Please try again.';
                this.messages[aiIndex].streaming = false;
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
