import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        isDoctor: (state) => state.user?.role === 'doctor',
        isPatient: (state) => state.user?.role === 'patient',
    },

    actions: {
        async login(email, password) {
            const api = useApi();
            const { data } = await api.post('/auth/login', { email, password });
            this.token = data.token;
            this.user = data.user;
        },

        async logout() {
            const api = useApi();
            await api.post('/auth/logout');
            this.user = null;
            this.token = null;
        },

        async fetchUser() {
            const api = useApi();
            const { data } = await api.get('/auth/user');
            this.user = data.data;
        },

        setDemoUser(role) {
            this.user = {
                id: 'demo',
                name: role === 'doctor' ? 'Dr. Demo' : 'Demo Patient',
                email: `demo-${role}@postvisit.ai`,
                role,
            };
            this.token = 'demo-token';
        },
    },
});
