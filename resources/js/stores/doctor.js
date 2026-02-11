import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useDoctorStore = defineStore('doctor', {
    state: () => ({
        dashboard: null,
        patients: [],
        notifications: [],
        alerts: [],
        loading: false,
        alertsLoading: false,
        error: null,
    }),

    actions: {
        async fetchDashboard() {
            this.loading = true;
            this.error = null;
            try {
                const api = useApi();
                const { data } = await api.get('/doctor/dashboard');
                this.dashboard = data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load dashboard';
            } finally {
                this.loading = false;
            }
        },

        async fetchPatients(search = '') {
            this.loading = true;
            this.error = null;
            try {
                const api = useApi();
                const params = search ? { search } : {};
                const { data } = await api.get('/doctor/patients', { params });
                this.patients = data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load patients';
            } finally {
                this.loading = false;
            }
        },

        async fetchAlerts() {
            this.alertsLoading = true;
            try {
                const api = useApi();
                const { data } = await api.get('/doctor/alerts');
                this.alerts = data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load alerts';
            } finally {
                this.alertsLoading = false;
            }
        },

        async fetchNotifications() {
            try {
                const api = useApi();
                const { data } = await api.get('/doctor/notifications');
                this.notifications = data.data;
            } catch (err) {
                this.error = err.response?.data?.error?.message || 'Failed to load notifications';
            }
        },
    },
});
