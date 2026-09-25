import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            // Pemilih tenant hidup di central domain, tempat sidebar aplikasi
            // tidak punya tujuan: seluruh isinya route subdomain.
            case name.startsWith('tenants/'):
                return null;
            // Panel super-admin juga mandiri: datanya lintas tenant dan
            // sidebar tenant tidak berlaku di sana.
            case name.startsWith('admin/'):
                return AdminLayout;
            // Halaman masuk membawa panel pengantarnya sendiri, jadi ia tidak
            // memakai kartu terpusat milik AuthLayout.
            case name === 'auth/Login':
            case name === 'auth/Register':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
