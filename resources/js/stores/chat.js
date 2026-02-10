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
            try {
                const api = useApi();
                const { data } = await api.post(`/visits/${visitId}/chat`, { message });
                const aiMsg = data.data.ai_message;
                this.messages.push({ role: 'assistant', content: aiMsg?.message_text || 'No response' });
                return data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to send message';
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
                this.messages = data.data;
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
