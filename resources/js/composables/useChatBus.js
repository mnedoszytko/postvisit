import { ref } from 'vue';

// Global reactive bus for chat context — solves provide/inject limitation
// where views wrap PatientLayout (child), so inject can't reach the provider.
const chatContextRequest = ref({ context: '', timestamp: 0 });

export function useChatBus() {
    function openGlobalChat(context = '') {
        chatContextRequest.value = { context, timestamp: Date.now() };
    }

    return {
        chatContextRequest,
        openGlobalChat,
    };
}
