import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios';
import { useToastStore } from '@/stores/toast';
import { useAuthStore } from '@/stores/auth';
import router from '@/router';

interface CustomAxiosConfig extends InternalAxiosRequestConfig {
    skipErrorToast?: boolean;
    skipAuthRedirect?: boolean;
}

const api: AxiosInstance = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

let csrfFetched = false;
let lastAuthRedirectAt = 0;

api.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method ?? '') && !csrfFetched) {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
        csrfFetched = true;
    }
    return config;
});

const ERROR_MESSAGES: Record<number, string | null> = {
    401: 'Your session has expired. Please log in again.',
    403: 'You don\'t have permission to perform this action.',
    404: 'The requested resource was not found.',
    419: 'Session expired. Please refresh the page.',
    422: null,
    429: 'Too many requests. Please wait a moment and try again.',
    500: 'Something went wrong on our end. Please try again.',
    503: 'Service temporarily unavailable. Please try again shortly.',
};

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status: number | undefined = error.response?.status;
        const config = error.config as CustomAxiosConfig | undefined;
        const toast = useToastStore();

        if (config?.skipErrorToast) {
            return Promise.reject(error);
        }

        if (status === 419) {
            csrfFetched = false;
        }

        if (status === 401) {
            const auth = useAuthStore();
            auth.user = null;
            const now = Date.now();
            if (!config?.skipAuthRedirect && now - lastAuthRedirectAt > 5000) {
                lastAuthRedirectAt = now;
                router.push({ name: 'login' });
                toast.warning(ERROR_MESSAGES[401]!);
            }
            return Promise.reject(error);
        }

        if (status === 422) {
            const errors = error.response?.data?.errors;
            if (errors) {
                const firstError = Object.values(errors).flat()[0] as string;
                toast.error(firstError || 'Please check your input.');
            } else {
                toast.error(error.response?.data?.message || 'Validation failed.');
            }
            return Promise.reject(error);
        }

        if (status && ERROR_MESSAGES[status]) {
            toast.error(ERROR_MESSAGES[status]!);
        } else if (status && status >= 500) {
            toast.error(ERROR_MESSAGES[500]!);
        } else if (!error.response) {
            toast.error('Network error. Please check your connection.');
        }

        return Promise.reject(error);
    },
);

export function useApi(): AxiosInstance {
    return api;
}
