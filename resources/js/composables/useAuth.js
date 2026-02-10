import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

export function useAuth() {
    const auth = useAuthStore();
    const router = useRouter();

    const isAuthenticated = computed(() => auth.isAuthenticated);
    const isDoctor = computed(() => auth.isDoctor);
    const isPatient = computed(() => auth.isPatient);
    const user = computed(() => auth.user);

    function requireAuth() {
        if (!auth.isAuthenticated) {
            router.push({ name: 'login' });
            return false;
        }
        return true;
    }

    function requireRole(role) {
        if (!requireAuth()) return false;
        if (auth.user?.role !== role) {
            router.push({ name: 'landing' });
            return false;
        }
        return true;
    }

    return { isAuthenticated, isDoctor, isPatient, user, requireAuth, requireRole };
}
