import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import Landing from '@/views/Landing.vue';
import Login from '@/views/Login.vue';
import PatientProfile from '@/views/PatientProfile.vue';
import CompanionScribe from '@/views/CompanionScribe.vue';
import Processing from '@/views/Processing.vue';
import VisitView from '@/views/VisitView.vue';
import MedsDetail from '@/views/MedsDetail.vue';
import Feedback from '@/views/Feedback.vue';
import DoctorDashboard from '@/views/DoctorDashboard.vue';
import DoctorPatientDetail from '@/views/DoctorPatientDetail.vue';
import DemoMode from '@/views/DemoMode.vue';
import AnimationDemo from '@/views/AnimationDemo.vue';

const routes = [
    {
        path: '/',
        name: 'landing',
        component: Landing,
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
    },
    {
        path: '/profile',
        name: 'patient-profile',
        component: PatientProfile,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/scribe',
        name: 'companion-scribe',
        component: CompanionScribe,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/processing',
        name: 'processing',
        component: Processing,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/visits/:id',
        name: 'visit-view',
        component: VisitView,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/visits/:id/meds',
        name: 'meds-detail',
        component: MedsDetail,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/visits/:id/feedback',
        name: 'feedback',
        component: Feedback,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/doctor',
        name: 'doctor-dashboard',
        component: DoctorDashboard,
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/doctor/patients/:id',
        name: 'doctor-patient-detail',
        component: DoctorPatientDetail,
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/demo',
        name: 'demo-mode',
        component: DemoMode,
    },
    {
        path: '/demo/animations',
        name: 'animation-demo',
        component: AnimationDemo,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Try to restore session on first navigation
    await auth.init();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.role === 'doctor' && !auth.isDoctor) {
        return { name: 'landing' };
    }

    if (to.meta.role === 'patient' && !auth.isPatient) {
        return { name: 'landing' };
    }
});

export default router;
