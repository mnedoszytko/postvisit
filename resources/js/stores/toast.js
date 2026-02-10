import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);
    let nextId = 0;

    function add(message, type = 'error', duration = 5000) {
        const id = ++nextId;
        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => remove(id), duration);
        }
    }

    function remove(id) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    function success(message, duration = 4000) {
        add(message, 'success', duration);
    }

    function error(message, duration = 6000) {
        add(message, 'error', duration);
    }

    function warning(message, duration = 5000) {
        add(message, 'warning', duration);
    }

    function info(message, duration = 4000) {
        add(message, 'info', duration);
    }

    return { toasts, add, remove, success, error, warning, info };
});
