<template>
  <div class="min-h-screen bg-slate-50 flex flex-col overflow-x-clip w-full">
    <!-- Top Navbar for Desktop/Tablet -->
    <DashboardNavbar 
      :menuItems="staffMenu"
      :user="user"
      @toggle-mobile-menu="isSidebarOpen = true"
    />

    <!-- Mobile Sidebar Overlay & Component (Only visible on small screens) -->
    <div class="xl:hidden">
      <div v-if="isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-black/50 z-40"></div>
      <DashboardSidebar
        :isOpen="isSidebarOpen"
        @close="isSidebarOpen = false"
        roleLabel="GAD Staff"
        :menuItems="staffMenu"
        :user="user"
        @logout="handleLogout"
      />
    </div>

    <main :class="['flex-grow w-full min-w-0 overflow-x-hidden', ($route.path.includes('/plan-and-budget') || $route.path.includes('/budget-distribution')) ? 'p-0' : 'p-4 md:p-10']">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import DashboardNavbar from '../components/DashboardNavbar.vue';
import DashboardSidebar from '../components/DashboardSidebar.vue';

const router = useRouter();
const isSidebarOpen = ref(false);
const isHeaderHidden = ref(false);
const lastScrollY = ref(0);
const user = ref({});

const handleScroll = () => {
  const currentScrollY = window.scrollY;
  if (currentScrollY > lastScrollY.value && currentScrollY > 50) {
    isHeaderHidden.value = true;
  } else {
    isHeaderHidden.value = false;
  }
  lastScrollY.value = currentScrollY;
};

const staffMenu = ref([
  { label: 'Dashboard', icon: 'dashboard', href: '/staff/dashboard' },
  { label: 'New Submission', icon: 'add', href: '/staff/submit' },
  { label: 'Post', icon: 'post_add', href: '/staff/publish-news-iec' },
  {
    label: 'Documents', icon: 'folder',
    children: [
      { label: 'Submitted List', icon: 'list', href: '/staff/submitted-list' },
      { label: 'Activity Design List', icon: 'list', href: '/staff/ad-list' },
      { label: 'Accomplishment Report List', icon: 'list', href: '/staff/ar-list' },
      { label: 'Approved Documents', icon: 'archive', href: '/staff/archive' },
      { label: 'Document Trash Bin', icon: 'delete', href: '/staff/trashbin' }
    ]
  },
  {
    label: 'Plan & Budget', icon: 'gavel',
    children: [
      { label: 'Plan and Budget', icon: 'gavel', href: '/staff/plan-and-budget' },
      { label: 'Budget Allocation Worklist', icon: 'task_alt', href: '/staff/budget-allocation' },
      { label: 'Budget Distribution by Mandate', icon: 'pie_chart', href: '/staff/budget-distribution' },
      { label: 'Report Monitoring', icon: 'description', href: '/staff/reports' },
      { label: 'Budget Monitoring', icon: 'payments', href: '/staff/budget' },
      { label: 'Budget History', icon: 'history', href: '/staff/budget-history' }
    ]
  },
  {
    label: 'System & Controls', icon: 'admin_panel_settings',
    children: [
      { label: 'Campus Resources', icon: 'business_center', href: '/staff/campus-resources' },
      { label: 'User Management', icon: 'manage_accounts', href: '/staff/user-management' },
      { label: 'Activity Logs', icon: 'history', href: '/staff/activity-logs' },
      { label: 'Backup Management', icon: 'cloud_sync', href: '/staff/backups' }
    ]
  }
]);

// Notifications now handled directly in DashboardNavbar

const handleLogout = async () => {
  try {
    await api.get('logout');
  } catch (err) {
    console.error('Logout failed:', err);
  } finally {
    localStorage.removeItem('user');
    localStorage.removeItem('authToken');
    router.push('/login');
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  user.value = JSON.parse(localStorage.getItem('user') || '{}');
  if (!user.value.id || user.value.role !== 'gad_staff') {
    router.push('/login');
  }
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
/* No custom layout styles needed; handled by Tailwind */
</style>
