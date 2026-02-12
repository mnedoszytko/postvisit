/** Build-time constants injected by Vite (vite.config.js define) */
declare const __GIT_HASH__: string;
declare const __BUILD_TIME__: string;

/** Shim: allow importing .vue SFCs without lang="ts" */
declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<object, object, unknown>;
    export default component;
}

/** Auth user shape (matches auth.js store state) */
interface AuthUser {
    id: string;
    name: string;
    email: string;
    role: 'doctor' | 'patient' | 'admin';
    patient_id?: string;
    patient?: { id: string };
    practitioner_id?: string;
}

/** Shim: auth store — will be removed when store is converted to TS */
declare module '@/stores/auth' {
    export function useAuthStore(): {
        user: AuthUser | null;
        token: string | null;
        initialized: boolean;
        isAuthenticated: boolean;
        isDoctor: boolean;
        isPatient: boolean;
        register: (name: string, email: string, password: string, passwordConfirmation: string, role: string) => Promise<void>;
        login: (email: string, password: string) => Promise<void>;
        logout: () => Promise<void>;
        fetchUser: () => Promise<void>;
        init: () => Promise<void>;
        setDemoUser: (role: string) => void;
    };
}

/** Shim: toast store — will be removed when store is converted to TS */
declare module '@/stores/toast' {
    import type { Ref } from 'vue';
    export function useToastStore(): {
        toasts: Ref<Array<{ id: number; message: string; type: string }>>;
        add: (message: string, type?: string, duration?: number) => void;
        remove: (id: number) => void;
        success: (message: string, duration?: number) => void;
        error: (message: string, duration?: number) => void;
        warning: (message: string, duration?: number) => void;
        info: (message: string, duration?: number) => void;
    };
}

/** Shim: router — will be removed when router is converted to TS */
declare module '@/router' {
    import type { Router } from 'vue-router';
    const router: Router;
    export default router;
}
