import { computed, type ComputedRef } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

interface AuthReturn {
    isAuthenticated: ComputedRef<boolean>;
    isDoctor: ComputedRef<boolean>;
    isPatient: ComputedRef<boolean>;
    user: ComputedRef<Record<string, unknown> | null>;
    requireAuth: () => boolean;
    requireRole: (role: string) => boolean;
}

export function useAuth(): AuthReturn {
    const auth = useAuthStore();
    const router = useRouter();

    const isAuthenticated = computed(() => auth.isAuthenticated);
    const isDoctor = computed(() => auth.isDoctor);
    const isPatient = computed(() => auth.isPatient);
    const user = computed(() => auth.user);

    function requireAuth(): boolean {
        if (!auth.isAuthenticated) {
            router.push({ name: 'login' });
            return false;
        }
        return true;
    }

    function requireRole(role: string): boolean {
        if (!requireAuth()) return false;
        if (auth.user?.role !== role) {
            router.push({ name: 'landing' });
            return false;
        }
        return true;
    }

    return { isAuthenticated, isDoctor, isPatient, user, requireAuth, requireRole };
}
