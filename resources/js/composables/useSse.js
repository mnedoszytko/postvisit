import { ref, onScopeDispose } from 'vue';

export function useSse() {
    const data = ref('');
    const isStreaming = ref(false);
    const error = ref(null);
    let eventSource = null;

    function connect(url) {
        close();
        data.value = '';
        isStreaming.value = true;
        error.value = null;

        eventSource = new EventSource(url, { withCredentials: true });

        eventSource.onmessage = (event) => {
            if (event.data === '[DONE]') {
                close();
                return;
            }
            try {
                const parsed = JSON.parse(event.data);
                data.value += parsed.content || '';
            } catch {
                data.value += event.data;
            }
        };

        eventSource.onerror = () => {
            error.value = 'Connection lost. Please try again.';
            close();
        };
    }

    function close() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        isStreaming.value = false;
    }

    onScopeDispose(() => close());

    return { data, isStreaming, error, connect, close };
}
