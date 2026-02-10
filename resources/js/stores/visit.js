import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useVisitStore = defineStore('visit', {
    state: () => ({
        currentVisit: null,
        visits: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchVisit(id) {
            this.loading = true;
            this.error = null;
            try {
                const api = useApi();
                const { data } = await api.get(`/visits/${id}`);
                this.currentVisit = data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load visit';
            } finally {
                this.loading = false;
            }
        },

        async fetchVisits(patientId) {
            this.loading = true;
            this.error = null;
            try {
                const api = useApi();
                const { data } = await api.get(`/patients/${patientId}/visits`);
                this.visits = data.data || [];
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load visits';
            } finally {
                this.loading = false;
            }
        },
    },
});
