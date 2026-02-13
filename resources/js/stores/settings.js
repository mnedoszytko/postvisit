import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useSettingsStore = defineStore('settings', {
    state: () => ({
        currentTier: 'opus46',
        tiers: [],
        loading: false,
    }),

    getters: {
        activeTier: (state) => state.tiers.find(t => t.value === state.currentTier) || null,
        tierLabel: (state) => {
            const tier = state.tiers.find(t => t.value === state.currentTier);
            return tier ? tier.short_label : 'Opus 4.6';
        },
    },

    actions: {
        async fetchTier() {
            const api = useApi();
            try {
                const { data } = await api.get('/settings/ai-tier');
                this.currentTier = data.data.current;
                this.tiers = data.data.tiers;
            } catch (err) {
                console.error('Fetch AI tier failed:', err);
            }
        },

        async setTier(tierValue) {
            const api = useApi();
            this.loading = true;
            try {
                const { data } = await api.put('/settings/ai-tier', { tier: tierValue });
                this.currentTier = data.data.current;
                // Update the active flag in tiers
                this.tiers = this.tiers.map(t => ({
                    ...t,
                    active: t.value === data.data.current,
                }));
            } finally {
                this.loading = false;
            }
        },
    },
});
