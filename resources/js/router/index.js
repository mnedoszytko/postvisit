import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import Landing from '@/views/Landing.vue';
import Login from '@/views/Login.vue';
import Register from '@/views/Register.vue';
import PatientProfile from '@/views/PatientProfile.vue';
const HealthDashboard = () => import('@/views/HealthDashboard.vue');
import CompanionScribe from '@/views/CompanionScribe.vue';
import Processing from '@/views/Processing.vue';
import VisitView from '@/views/VisitView.vue';
import MedsDetail from '@/views/MedsDetail.vue';
import Feedback from '@/views/Feedback.vue';
import DoctorDashboard from '@/views/DoctorDashboard.vue';
import DoctorPatientDetail from '@/views/DoctorPatientDetail.vue';
import DemoMode from '@/views/DemoMode.vue';
const ScenarioPicker = () => import('@/views/ScenarioPicker.vue');
const Showcase = () => import('@/views/Showcase.vue');
const ShowcaseEhr = () => import('@/views/ShowcaseEhr.vue');
const ShowcaseEbm = () => import('@/views/ShowcaseEbm.vue');
const ShowcaseTeaser = () => import('@/views/ShowcaseTeaser.vue');
const ShowcaseTech = () => import('@/views/ShowcaseTech.vue');

const routes = [
    {
        path: '/',
        name: 'landing',
        component: Landing,
    },
    {
        path: '/showcase',
        name: 'showcase',
        component: Showcase,
    },
    {
        path: '/showcase/ehr',
        name: 'showcase-ehr',
        component: ShowcaseEhr,
    },
    {
        path: '/showcase/ebm',
        name: 'showcase-ebm',
        component: ShowcaseEbm,
    },
    {
        path: '/showcase/teaser',
        name: 'showcase-teaser',
        component: ShowcaseTeaser,
    },
    {
        path: '/showcase/tech',
        name: 'showcase-tech',
        component: ShowcaseTech,
    },
    {
        path: '/showcase/teaser2',
        name: 'showcase-teaser2',
        component: () => import('@/views/ShowcaseTeaser2.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
    },
    {
        path: '/profile',
        name: 'patient-profile',
        component: PatientProfile,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/health',
        name: 'health-dashboard',
        component: HealthDashboard,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/scribe',
        name: 'companion-scribe',
        component: CompanionScribe,
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/record',
        redirect: '/scribe',
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
        path: '/doctor/patients',
        name: 'doctor-patients',
        component: () => import('@/views/DoctorPatients.vue'),
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/doctor/patients/:id',
        name: 'doctor-patient-detail',
        component: DoctorPatientDetail,
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/doctor/patients/:patientId/visits/:visitId',
        name: 'doctor-visit-detail',
        component: () => import('@/views/DoctorVisitDetail.vue'),
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/doctor/audit',
        name: 'doctor-audit-log',
        component: () => import('@/views/DoctorAuditLog.vue'),
        meta: { requiresAuth: true, role: 'doctor' },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('@/views/Settings.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/demo',
        name: 'demo-mode',
        component: DemoMode,
    },
    {
        path: '/demo/scenarios',
        name: 'scenario-picker',
        component: ScenarioPicker,
    },
    {
        path: '/library',
        name: 'medical-library',
        component: () => import('@/views/MedicalLibrary.vue'),
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/agents',
        name: 'agents',
        component: () => import('@/views/AgentsPage.vue'),
        meta: { requiresAuth: true, role: 'patient' },
    },
    {
        path: '/medical-lookup',
        name: 'medical-lookup',
        component: () => import('@/views/MedicalLookupDemo.vue'),
    },
    {
        path: '/privacy',
        name: 'privacy',
        component: () => import('@/views/LegalPage.vue'),
    },
    {
        path: '/privacy-policy',
        redirect: '/privacy',
    },
    {
        path: '/terms',
        name: 'terms',
        component: () => import('@/views/LegalPage.vue'),
    },
    {
        path: '/legal',
        name: 'legal',
        component: () => import('@/views/LegalPage.vue'),
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
