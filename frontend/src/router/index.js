import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
const routes = [
  { path: '/', name: 'home', component: HomeView },
  { path: '/login', name: 'login', component: LoginView },
  { path: '/register', name: 'register', component: RegisterView },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView },
  { path: '/reset-password', name: 'reset-password', component: () => import('../views/ResetPasswordView.vue') },
  { path: '/about', name: 'about', component: () => import('../views/AboutView.vue') },
  { path: '/resources', name: 'resources', component: () => import('../views/ResourcesView.vue') },
  { path: '/gad-corner', name: 'gad-corner', component: () => import('../views/GADCornerView.vue') },
  { path: '/gad-corner/:id', name: 'gad-corner-post', component: () => import('../views/GadCornerPostView.vue') },
  { path: '/gad-corner/tags/:tag', name: 'gad-corner-tags', component: () => import('../views/TagResultsView.vue') },
  { path: '/contact', name: 'contact', component: () => import('../views/ContactView.vue') },
  { path: '/user-approval/:id', name: 'user-approval', component: () => import('../views/UserApprovalView.vue') },

  // Legacy bookmarks → nested routes
  { path: '/college/submit-accomplishment', redirect: '/college/submit-report' },
  { path: '/staff/reviews', redirect: '/staff/submitted-list' },

  // Admin (nested layout + full workflows)
  {
    path: '/admin',
    component: () => import('../views/AdminDashboard.vue'),
    children: [
      { path: '', redirect: '/admin/dashboard' },
      { path: 'dashboard', name: 'admin-dashboard', component: () => import('../views/admin/AdminDashboardContent.vue') },
      { path: 'submitted-list', name: 'admin-submitted', component: () => import('../views/admin/SubmittedListView.vue') },
      { path: 'ad-list', name: 'admin-activity-designs', component: () => import('../views/admin/AdListView.vue') },
      { path: 'ar-list', name: 'admin-accomplishment-reports', component: () => import('../views/admin/ARListView.vue') },
      { path: 'ad-view/:id', name: 'admin-ad-view', component: () => import('../views/admin/ADView.vue') },
      { path: 'ar-view/:id', name: 'admin-ar-view', component: () => import('../views/admin/ARView.vue') },
      { path: 'ar-review/:id', name: 'admin-ar-review', component: () => import('../views/admin/ARReview.vue') },
      { path: 'ad-review/:id', name: 'admin-ad-review', component: () => import('../views/admin/ADReview.vue') },
      { path: 'ad-revision/:id', name: 'admin-ad-revision', component: () => import('../views/admin/ADRevision.vue') },
      { path: 'archive', name: 'admin-archive', component: () => import('../views/admin/ArchiveView.vue') },
      { path: 'trash-bin', name: 'admin-trash-bin', component: () => import('../views/admin/DocumentTrashBin.vue') },
      { path: 'plan-and-budget', name: 'admin-plan-and-budget', component: () => import('../views/admin/PlanAndBudgetView.vue') },
      { path: 'budget-history', name: 'admin-budget-history', component: () => import('../views/BudgetHistoryView.vue') },
      { path: 'budget-distribution', name: 'admin-budget-distribution', component: () => import('../views/admin/BudgetDistributionView.vue') },
      { path: 'reports', name: 'admin-reports', component: () => import('../views/admin/ReportsView.vue') },
      { path: 'annual-report-archives', name: 'admin-annual-report-archives', component: () => import('../views/AnnualReportArchiveView.vue') },
      { path: 'annual-report-view/:id', name: 'admin-annual-report-view', component: () => import('../views/AnnualReportView.vue') },
      { path: 'user-manual', name: 'admin-user-manual', component: () => import('../views/admin/UserManualView.vue') },
      { path: 'budget', name: 'admin-budget', component: () => import('../views/admin/BudgetView.vue') },
      { path: 'data-privacy-policy', name: 'admin-privacy-policy', component: () => import('../views/admin/PrivacyPolicyView.vue') },
      { path: 'messages', name: 'admin-messages', component: () => import('../views/admin/MessagesView.vue') },
      { path: 'contact-inquiries', name: 'admin-contact-inquiries', component: () => import('../views/admin/ContactInquiriesView.vue') },
      { path: 'user-management', name: 'admin-user-management', component: () => import('../views/admin/UserManagementView.vue') },
      { path: 'activity-logs', name: 'admin-activity-logs', component: () => import('../views/admin/ActivityLogsView.vue') },
      { path: 'backups', name: 'admin-backups', component: () => import('../views/BackupManagementView.vue') },
      { path: 'campus-resources', name: 'admin-campus-resources', component: () => import('../views/admin/CampusResourcesView.vue') },
      { path: 'holidays-management', name: 'admin-holidays-management', component: () => import('../views/admin/HolidaysManagementView.vue') },
      { path: 'settings', name: 'admin-settings', component: () => import('../views/UserSettingsView.vue') },
    ]
  },

  // College / TWG
  {
    path: '/college',
    component: () => import('../views/CollegeDashboard.vue'),
    children: [
      { path: '', redirect: '/college/dashboard' },
      { path: 'submit', name: 'college-submit-hub', component: () => import('../views/college/SubmitView.vue') },
      { path: 'submit-design', name: 'college-submit-ad', component: () => import('../views/college/SubmitADView.vue') },
      { path: 'submit-report', name: 'college-submit-ar', component: () => import('../views/college/SubmitARView.vue') },
      { path: 'dashboard', name: 'college-dashboard', component: () => import('../views/college/CollegeDashboardContent.vue') },
      { path: 'submitted-list', name: 'college-submitted-list', component: () => import('../views/college/SubmittedListView.vue') },
      { path: 'ad-view/:id', name: 'college-ad-view', component: () => import('../views/college/ADView.vue') },
      { path: 'ar-view/:id', name: 'college-ar-view', component: () => import('../views/college/ARView.vue') },
      { path: 'ar-revision/:id', name: 'college-ar-revision', component: () => import('../views/college/ARRevision.vue') },
      { path: 'ad-revision/:id', name: 'college-ad-revision', component: () => import('../views/college/ADRevision.vue') },
      { path: 'archive', name: 'college-archive', component: () => import('../views/college/ArchiveView.vue') },
      { path: 'trashbin', name: 'college-trashbin', component: () => import('../views/college/DocumentTrashBin.vue') },
      { path: 'plan-and-budget', name: 'college-plan-and-budget', component: () => import('../views/college/PlanAndBudgetView.vue') },
      { path: 'budget-distribution', name: 'college-budget-distribution', component: () => import('../views/college/BudgetDistributionView.vue') },
      { path: 'budget', name: 'college-budget', component: () => import('../views/college/BudgetView.vue') },
      { path: 'user-manual', name: 'college-user-manual', component: () => import('../views/college/UserManualView.vue') },
      { path: 'data-privacy-policy', name: 'college-privacy-policy', component: () => import('../views/college/PrivacyPolicyView.vue') },
      { path: 'tech-assist', name: 'college-tech-assist', component: () => import('../views/PlaceholderContent.vue') },
      { path: 'messages', name: 'college-messages', component: () => import('../views/college/MessagesView.vue') },
      { path: 'activity-logs', name: 'college-activity-logs', component: () => import('../views/college/ActivityLogsView.vue') },
      { path: 'settings', name: 'college-settings', component: () => import('../views/UserSettingsView.vue') },
    ]
  },

  // Staff
  {
    path: '/staff',
    component: () => import('../views/StaffDashboard.vue'),
    children: [
      { path: '', redirect: '/staff/dashboard' },
      { path: 'submit', name: 'staff-submit-hub', component: () => import('../views/staff/SubmitView.vue') },
      { path: 'submit-design', name: 'staff-submit-ad', component: () => import('../views/staff/SubmitADView.vue') },
      { path: 'submit-report', name: 'staff-submit-ar', component: () => import('../views/staff/SubmitARView.vue') },
      { path: 'dashboard', name: 'staff-dashboard', component: () => import('../views/staff/StaffDashboardContent.vue') },
      { path: 'submitted-list', name: 'staff-submitted-list', component: () => import('../views/staff/SubmittedListView.vue') },
      { path: 'ad-list', name: 'staff-ad-list', component: () => import('../views/staff/AdListView.vue') },
      { path: 'ar-list', name: 'staff-ar-list', component: () => import('../views/staff/ARListView.vue') },
      { path: 'ad-view/:id', name: 'staff-ad-view', component: () => import('../views/staff/ADView.vue') },
      { path: 'ar-view/:id', name: 'staff-ar-view', component: () => import('../views/staff/ARView.vue') },
      { path: 'ad-revision/:id', name: 'staff-ad-revision', component: () => import('../views/staff/ADRevision.vue') },
      { path: 'ar-revision/:id', name: 'staff-ar-revision', component: () => import('../views/staff/ARRevision.vue') },
      { path: 'archive', name: 'staff-archive', component: () => import('../views/staff/ArchiveView.vue') },
      { path: 'trashbin', name: 'staff-trashbin', component: () => import('../views/staff/DocumentTrashBin.vue') },
      { path: 'plan-and-budget', name: 'staff-plan-and-budget', component: () => import('../views/staff/PlanAndBudgetView.vue') },
      { path: 'budget-allocation', name: 'staff-budget-allocation', component: () => import('../views/staff/AllocationWorklistView.vue') },
      { path: 'budget-history', name: 'staff-budget-history', component: () => import('../views/BudgetHistoryView.vue') },
      { path: 'budget-distribution', name: 'staff-budget-distribution', component: () => import('../views/staff/BudgetDistributionView.vue') },
      { path: 'reports', name: 'staff-reports', component: () => import('../views/staff/ReportsView.vue') },
      { path: 'annual-report-archives', name: 'staff-annual-report-archives', component: () => import('../views/AnnualReportArchiveView.vue') },
      { path: 'annual-report-view/:id', name: 'staff-annual-report-view', component: () => import('../views/AnnualReportView.vue') },
      { path: 'budget', name: 'staff-budget', component: () => import('../views/staff/BudgetView.vue') },
      { path: 'user-manual', name: 'staff-user-manual', component: () => import('../views/staff/UserManualView.vue') },
      { path: 'data-privacy-policy', name: 'staff-privacy-policy', component: () => import('../views/staff/PrivacyPolicyView.vue') },
      { path: 'messages', name: 'staff-messages', component: () => import('../views/staff/MessagesView.vue') },
      { path: 'contact-inquiries', name: 'staff-contact-inquiries', component: () => import('../views/admin/ContactInquiriesView.vue') },
      { path: 'user-management', name: 'staff-user-management', component: () => import('../views/staff/UserManagementView.vue') },
      { path: 'activity-logs', name: 'staff-activity-logs', component: () => import('../views/staff/ActivityLogsView.vue') },
      { path: 'backups', name: 'staff-backups', component: () => import('../views/BackupManagementView.vue') },
      { path: 'campus-resources', name: 'staff-campus-resources', component: () => import('../views/staff/CampusResourcesView.vue') },
      { path: 'settings', name: 'staff-settings', component: () => import('../views/UserSettingsView.vue') },
      { path: 'publish-news-iec', name: 'staff-publish-news-iec', component: () => import('../views/staff/PublishNewsIecView.vue') },
    ]
  },
  
  // Catch-all route for 404 Not Found
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue') },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to) {
    if (to.hash) {
      return {
        el: to.hash,
        behavior: 'smooth'
      };
    }

    return { top: 0 };
  }
})

// ---------------------------------------------------------------------------
// Global Navigation Guard
// Protects /admin, /staff, and /college routes.
// Checks: (1) user is logged in, (2) user has the right role for the section.
// ---------------------------------------------------------------------------
router.beforeEach((to, from, next) => {
  const userStr = localStorage.getItem('user');
  const token   = localStorage.getItem('authToken');
  const user    = userStr ? JSON.parse(userStr) : null;

  const isLoggedIn = !!(user && user.id && token);

  const isAdminRoute   = to.path.startsWith('/admin');
  const isStaffRoute   = to.path.startsWith('/staff');
  const isCollegeRoute = to.path.startsWith('/college');
  const isApprovalRoute = to.path.startsWith('/user-approval');

  // Account approval page — staff/admin only.
  if (isApprovalRoute) {
    if (!isLoggedIn) {
      return next('/login');
    }
    const approvalRole = (user.role || '').toLowerCase();
    if (approvalRole !== 'admin' && approvalRole !== 'gad_staff') {
      return next('/login');
    }
    return next();
  }

  // If not a protected route, allow through
  if (!isAdminRoute && !isStaffRoute && !isCollegeRoute) {
    // Already logged in? Redirect away from login/register
    if (isLoggedIn && (to.path === '/login' || to.path === '/register')) {
      const safeRole = (user.role || '').toLowerCase();
      if (safeRole === 'admin')     return next('/admin/dashboard');
      if (safeRole === 'gad_staff') return next('/staff/dashboard');
      return next('/college/dashboard');
    }
    return next();
  }

  // Not logged in — redirect to login
  if (!isLoggedIn) {
    return next('/login');
  }

  const safeRole = (user.role || '').toLowerCase();

  // Role-based section access
  if (isAdminRoute && safeRole !== 'admin') {
    return next('/login');
  }
  if (isStaffRoute && safeRole !== 'gad_staff') {
    return next('/login');
  }
  if (isCollegeRoute && !['twg', 'non-twg', 'college'].includes(safeRole)) {
    return next('/login');
  }

  next();
});

export default router
