import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

let csrfFetched = false;

api.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method) && !csrfFetched) {
        await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
        csrfFetched = true;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            csrfFetched = false;
        }
        return Promise.reject(error);
    },
);

export function useApi() {
    return api;
}
