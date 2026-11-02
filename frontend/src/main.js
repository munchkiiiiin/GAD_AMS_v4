import { createApp } from 'vue'
import '@fontsource/manrope/latin-400.css'
import '@fontsource/manrope/latin-500.css'
import '@fontsource/manrope/latin-600.css'
import '@fontsource/manrope/latin-700.css'
import '@fontsource/manrope/latin-800.css'
import 'material-symbols/outlined.css'
import './style.css'
import './assets/report-styles.css'
import App from './App.vue'
import router from './router'
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(App)
app.use(router)
app.component('VueDatePicker', VueDatePicker);
app.mount('#app')

if (import.meta.env.PROD && 'serviceWorker' in navigator && (window.isSecureContext || window.location.hostname === 'localhost')) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' })
      .catch((error) => {
        console.error('Unable to enable offline asset caching.', error);
      });
  });
} else if (import.meta.env.DEV && 'serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registrations = await navigator.serviceWorker.getRegistrations();
      const appServiceWorkerUrl = new URL('/sw.js', window.location.origin).href;
      const appRegistrations = registrations.filter((registration) =>
        [registration.active, registration.waiting, registration.installing]
          .some((worker) => worker?.scriptURL === appServiceWorkerUrl)
      );

      if (!appRegistrations.length) return;

      await Promise.all(appRegistrations.map((registration) => registration.unregister()));
      const cacheNames = await caches.keys();
      await Promise.all(cacheNames
        .filter((cacheName) => cacheName.startsWith('gad-ams-static-'))
        .map((cacheName) => caches.delete(cacheName)));
      window.location.reload();
    } catch (error) {
      console.warn('Unable to clear the app service worker for local development.', error);
    }
  });
}
