import { ref, onScopeDispose, type Ref } from 'vue';

interface SseReturn {
    data: Ref<string>;
    isStreaming: Ref<boolean>;
    error: Ref<string | null>;
    connect: (url: string) => void;
    close: () => void;
}

export function useSse(): SseReturn {
    const data = ref('');
    const isStreaming = ref(false);
    const error = ref<string | null>(null);
    let eventSource: EventSource | null = null;

    function connect(url: string): void {
        close();
        data.value = '';
        isStreaming.value = true;
        error.value = null;

        eventSource = new EventSource(url, { withCredentials: true });

        eventSource.onmessage = (event: MessageEvent) => {
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

    function close(): void {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        isStreaming.value = false;
    }

    onScopeDispose(() => close());

    return { data, isStreaming, error, connect, close };
}
