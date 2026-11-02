<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group((ENVIRONMENT === 'production' ? '' : 'api'), function($routes) {

    // Lightweight keep-warm health check; intentionally public and side-effect free.
    $routes->get('health', 'Health::index');

    // ----------------------------------------------------------------
    // AUTH ROUTES (existing)
    // ----------------------------------------------------------------
    $routes->post('login', 'AuthController::login');
    $routes->post('register', 'AuthController::register');
    $routes->get('logout', 'AuthController::logout');
    $routes->post('forgot-password', 'AuthController::forgotPassword');
    $routes->post('reset-password', 'AuthController::resetPassword');
    $routes->options('forgot-password', 'AuthController::handleOptions');
    $routes->options('reset-password', 'AuthController::handleOptions');
    $routes->get('human-challenge', 'AuthController::getHumanChallenge');
    $routes->options('human-challenge', 'AuthController::handleOptions');

    // CORS preflight routes (existing)
    $routes->options('login', 'AuthController::handleOptions');
    $routes->options('register', 'AuthController::handleOptions');
    $routes->options('logout', 'AuthController::handleOptions');
    $routes->get('office_units', 'AuthController::getOffices');
    $routes->post('add_office', 'AuthController::addOffice');
    $routes->options('office_units', 'AuthController::handleOptions');
    $routes->options('add_office', 'AuthController::handleOptions');

    // ----------------------------------------------------------------
    // OFFICE MANAGEMENT ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('offices', 'AuthController::handleOptions');
    $routes->get('offices', 'OfficeController::index');
    $routes->post('offices', 'OfficeController::create');
    $routes->options('offices/(:num)', 'AuthController::handleOptions');
    $routes->put('offices/(:num)', 'OfficeController::update/$1');
    $routes->delete('offices/(:num)', 'OfficeController::delete/$1');
    $routes->get('users', 'AuthController::getAllUsers');
    $routes->options('users', 'AuthController::handleOptions');

    // ----------------------------------------------------------------
    // USER MANAGEMENT ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('users/suspend/(:num)', 'AuthController::handleOptions');
    $routes->post('users/suspend/(:num)', 'UserManagementController::suspend/$1');
    $routes->options('users/restore/(:num)', 'AuthController::handleOptions');
    $routes->post('users/restore/(:num)', 'UserManagementController::restore/$1');
    $routes->options('users/delete/(:num)', 'AuthController::handleOptions');
    $routes->post('users/delete/(:num)', 'UserManagementController::delete/$1');

    $routes->options('users/create', 'AuthController::handleOptions');
    $routes->post('users/create', 'UserManagementController::create');
    $routes->options('users/update/(:num)', 'AuthController::handleOptions');
    $routes->post('users/update/(:num)', 'UserManagementController::update/$1');

    // Self-registration approval (GAD staff approve or decline new accounts)
    $routes->options('users/approval/(:num)', 'AuthController::handleOptions');
    $routes->get('users/approval/(:num)', 'UserManagementController::getApproval/$1');
    $routes->options('users/approve/(:num)', 'AuthController::handleOptions');
    $routes->post('users/approve/(:num)', 'UserManagementController::approve/$1');
    $routes->options('users/decline/(:num)', 'AuthController::handleOptions');
    $routes->post('users/decline/(:num)', 'UserManagementController::decline/$1');

    $routes->options('users/profile', 'AuthController::handleOptions');
    $routes->get('users/profile', 'UserManagementController::getProfile');
    $routes->options('users/profile/(:num)', 'AuthController::handleOptions');
    $routes->get('users/profile/(:num)', 'UserManagementController::getUserProfile/$1');
    $routes->options('users/profile/update', 'AuthController::handleOptions');
    $routes->post('users/profile/update', 'UserManagementController::updateProfile');

    // ----------------------------------------------------------------
    // ACTIVITY LOGS ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('activity-logs', 'AuthController::handleOptions');
    $routes->get('activity-logs', 'ActivityLogController::index');

    // BACKUP MANAGEMENT ROUTES
    $routes->options('backups', 'AuthController::handleOptions');
    $routes->get('backups', 'BackupController::index', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->post('backups', 'BackupController::create', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('backups/(:segment)', 'AuthController::handleOptions');
    $routes->post('backups/restore-local', 'BackupController::restoreLocal', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('backups/restore-local', 'AuthController::handleOptions');
    $routes->get('backups/(:segment)/download', 'BackupController::download/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('backups/(:segment)/download', 'AuthController::handleOptions');
    $routes->get('backups/(:segment)/preview', 'BackupController::preview/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('backups/(:segment)/preview', 'AuthController::handleOptions');
    $routes->post('backups/(:segment)/restore', 'BackupController::restore/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('backups/(:segment)/restore', 'AuthController::handleOptions');
    $routes->delete('backups/(:segment)', 'BackupController::delete/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    // ----------------------------------------------------------------
    // CLOUDFLARE R2 STORAGE ROUTE (existing)
    // ----------------------------------------------------------------
    $routes->post('storage/ticket', 'StorageController::getUploadTicket');
    $routes->options('storage/ticket', 'AuthController::handleOptions');

    // ----------------------------------------------------------------
    // CONTACT INQUIRIES ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('contact', 'AuthController::handleOptions');
    $routes->post('contact', 'ContactController::submit');
    
    $routes->options('contact-inquiries', 'AuthController::handleOptions');
    $routes->get('contact-inquiries', 'ContactController::index');

    $routes->options('contact-inquiries/unread-count', 'AuthController::handleOptions');
    $routes->get('contact-inquiries/unread-count', 'ContactController::unreadCount');
    
    $routes->options('contact-inquiries/(:num)/read', 'AuthController::handleOptions');
    $routes->post('contact-inquiries/(:num)/read', 'ContactController::markAsRead/$1');

    $routes->options('contact-inquiries/(:num)/reply', 'AuthController::handleOptions');
    $routes->post('contact-inquiries/(:num)/reply', 'ContactController::reply/$1');

    $routes->options('contact-inquiries/(:num)', 'AuthController::handleOptions');
    $routes->delete('contact-inquiries/(:num)', 'ContactController::delete/$1');

    // ----------------------------------------------------------------
    // ACTIVITY DESIGN ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('submit-activity-design', 'AuthController::handleOptions');
    $routes->post('submit-activity-design', 'ActivityDesignController::submitDesign');
    $routes->options('activity-designs/submit', 'AuthController::handleOptions');
    $routes->post('activity-designs/submit', 'ActivityDesignController::submitDesign');

    // Activity Design draft autosave
    $routes->options('save-ad-draft', 'AuthController::handleOptions');
    $routes->post('save-ad-draft', 'ActivityDesignController::saveDraft');
    $routes->options('get-ad-draft/(:num)', 'AuthController::handleOptions');
    $routes->get('get-ad-draft/(:num)', 'ActivityDesignController::getDraft/$1');
    $routes->options('delete-ad-draft/(:num)', 'AuthController::handleOptions');
    $routes->delete('delete-ad-draft/(:num)', 'ActivityDesignController::deleteDraft/$1');

    $routes->options('activity-designs/trash/(:num)', 'AuthController::handleOptions');
    $routes->delete('activity-designs/trash/(:num)', 'ActivityDesignController::trash/$1');

    $routes->options('get-form-types', 'AuthController::handleOptions');
    $routes->get('get-form-types', 'ActivityDesignController::getFormTypes');
    
    $routes->options('get-gad-mandates', 'AuthController::handleOptions');
    $routes->get('get-gad-mandates', 'ActivityDesignController::getGADMandates');
    
    $routes->options('get-gender-issues', 'AuthController::handleOptions');
    $routes->get('get-gender-issues', 'ActivityDesignController::getGenderIssues');
    
    $routes->options('get-gender-issues/(:any)', 'AuthController::handleOptions');
    $routes->get('get-gender-issues/(:any)', 'ActivityDesignController::getGenderIssues/$1');
    
    $routes->options('get-activity-classifications', 'AuthController::handleOptions');
    $routes->get('get-activity-classifications', 'ActivityDesignController::getActivityClassifications');

    $routes->options('get-next-control-number', 'AuthController::handleOptions');
    $routes->get('get-next-control-number', 'ActivityDesignController::getNextControlNumber');

    $routes->options('activity-designs', 'ActivityDesignController::index');
    $routes->get('activity-designs', 'ActivityDesignController::index');

    $routes->options('activity-design/(:num)', 'ActivityDesignController::show/$1');
    $routes->get('activity-design/(:num)', 'ActivityDesignController::show/$1');

    // User-specific designs list
    $routes->options('activity-designs/(:num)', 'ActivityDesignController::getUserDesigns/$1');
    $routes->get('activity-designs/(:num)', 'ActivityDesignController::getUserDesigns/$1');

    // Update/revision
    $routes->options('update-design/(:num)', 'ActivityDesignController::updateDesign/$1');
    $routes->post('update-design/(:num)', 'ActivityDesignController::updateDesign/$1');

    // Modification requests
    $routes->options('activity-designs/(:num)/request-modification', 'AuthController::handleOptions');
    $routes->post('activity-designs/(:num)/request-modification', 'ActivityDesignController::requestModification/$1');
    $routes->options('activity-designs/(:num)/request-cancellation', 'AuthController::handleOptions');
    $routes->post('activity-designs/(:num)/request-cancellation', 'ActivityDesignController::requestCancellation/$1');
    $routes->options('activity-designs/(:num)/approve-modification', 'AuthController::handleOptions');
    $routes->post('activity-designs/(:num)/approve-modification', 'ActivityDesignController::approveModification/$1');
    $routes->options('activity-designs/(:num)/reject-modification', 'AuthController::handleOptions');
    $routes->post('activity-designs/(:num)/reject-modification', 'ActivityDesignController::rejectModification/$1');

    // Update deadline
    $routes->options('update-deadline/(:num)', 'AuthController::handleOptions');
    $routes->post('update-deadline/(:num)', 'ActivityDesignController::updateDeadline/$1');

    // Disapprove and Revert
    $routes->options('disapprove-design/(:num)', 'AuthController::handleOptions');
    $routes->post('disapprove-design/(:num)', 'ActivityDesignController::disapproveDesign/$1');
    $routes->options('cancel-design/(:num)', 'AuthController::handleOptions');
    $routes->post('cancel-design/(:num)', 'ActivityDesignController::cancelDesign/$1');
    $routes->options('revert-design/(:num)', 'AuthController::handleOptions');
    $routes->post('revert-design/(:num)', 'ActivityDesignController::revertDecision/$1');

    // ----------------------------------------------------------------
    // MANDATES & GENDER ISSUES ROUTES
    // ----------------------------------------------------------------
    $routes->options('mandates', 'AuthController::handleOptions');
    $routes->get('mandates', 'MandateController::index');
    $routes->post('mandates', 'MandateController::storeMandate');
    $routes->options('mandates/(:num)', 'AuthController::handleOptions');
    $routes->put('mandates/(:num)', 'MandateController::updateMandate/$1');
    $routes->delete('mandates/(:num)', 'MandateController::deleteMandate/$1');

    $routes->options('gender-issues', 'AuthController::handleOptions');
    $routes->post('gender-issues', 'MandateController::storeIssue');
    $routes->options('gender-issues/(:num)', 'AuthController::handleOptions');
    $routes->put('gender-issues/(:num)', 'MandateController::updateIssue/$1');
    $routes->delete('gender-issues/(:num)', 'MandateController::deleteIssue/$1');

    // ----------------------------------------------------------------
    // ACCOMPLISHMENT REPORT ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('submit-activity-report', 'AuthController::handleOptions');
    $routes->post('submit-activity-report', 'AccomplishmentReportController::submitReport');
    $routes->options('accomplishment-reports/submit', 'AuthController::handleOptions');
    $routes->post('accomplishment-reports/submit', 'AccomplishmentReportController::submitReport');
    $routes->options('accomplishment-reports/trash/(:num)', 'AuthController::handleOptions');
    $routes->delete('accomplishment-reports/trash/(:num)', 'AccomplishmentReportController::trash/$1');

    $routes->options('activity-reports', 'AccomplishmentReportController::index');
    $routes->get('activity-reports', 'AccomplishmentReportController::index');

    $routes->options('verified-reports', 'AuthController::handleOptions');
    $routes->get('verified-reports', 'AccomplishmentReportController::getVerifiedReports');

    $routes->options('activity-report/(:num)', 'AccomplishmentReportController::show/$1');
    $routes->get('activity-report/(:num)', 'AccomplishmentReportController::show/$1');

    // User-specific reports list
    $routes->options('activity-reports/(:num)', 'AccomplishmentReportController::getUserReports/$1');
    $routes->get('activity-reports/(:num)', 'AccomplishmentReportController::getUserReports/$1');

    // Update/revision
    $routes->options('update-report/(:num)', 'AccomplishmentReportController::updateReport/$1');
    $routes->post('update-report/(:num)', 'AccomplishmentReportController::updateReport/$1');

    // ----------------------------------------------------------------
    // APPROVED CONTROL NUMBERS (new)
    // ----------------------------------------------------------------
    $routes->options('approved-controls/(:num)', 'AuthController::handleOptions');
    $routes->get('approved-controls/(:num)', 'ApprovedControlsController::index/$1');
    $routes->options('get-next-control-number', 'AuthController::handleOptions');
    $routes->get('get-next-control-number', 'ActivityDesignController::getNextControlNumber');

    // ----------------------------------------------------------------
    // ARCHIVE ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('archives', 'ArchiveController::index');
    $routes->get('archives', 'ArchiveController::index');
    $routes->options('venues', 'AuthController::handleOptions');
    $routes->get('venues', 'VenueController::index');
    $routes->post('archive-design/(:num)', 'ArchiveController::archiveDesign/$1');
    $routes->post('archive-report/(:num)', 'ArchiveController::archiveReport/$1');

    // ----------------------------------------------------------------
    // ANALYTICS ROUTES
    // ----------------------------------------------------------------
    $routes->options('analytics/participants/(:num)', 'AuthController::handleOptions');
    $routes->get('analytics/participants/(:num)', 'AnalyticsController::getParticipants/$1');
    $routes->options('analytics/participants/user/(:num)/(:num)', 'AuthController::handleOptions');
    $routes->get('analytics/participants/user/(:num)/(:num)', 'AnalyticsController::getParticipantsByUser/$1/$2', ['filter' => 'jwtAuth']);

    // ----------------------------------------------------------------
    // ADMIN TRACKING ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('admin/twg-submissions', 'AuthController::handleOptions');
    $routes->get('admin/twg-submissions', 'ActivityDesignController::getTWGSubmissions');

    // ----------------------------------------------------------------
    // APPROVAL ROUTES (auto-archives on approval)
    // ----------------------------------------------------------------
    $routes->options('approve-design/(:num)', 'AuthController::handleOptions');
    $routes->post('approve-design/(:num)', 'ActivityDesignController::approveDesign/$1');

    $routes->options('approve-report/(:num)', 'AuthController::handleOptions');
    $routes->post('approve-report/(:num)', 'AccomplishmentReportController::approveReport/$1');

    // ----------------------------------------------------------------
    // REVISION ROUTES
    // ----------------------------------------------------------------
    $routes->options('revision-design/(:num)', 'AuthController::handleOptions');
    $routes->post('revision-design/(:num)', 'ActivityDesignController::revisionDesign/$1');

    $routes->options('revision-report/(:num)', 'AuthController::handleOptions');
    $routes->post('revision-report/(:num)', 'AccomplishmentReportController::revisionReport/$1');

    $routes->options('activity-design/mark-viewed/(:num)', 'AuthController::handleOptions');
    $routes->post('activity-design/mark-viewed/(:num)', 'ActivityDesignController::markViewed/$1');
    $routes->options('activity-design/unmark-viewed/(:num)', 'AuthController::handleOptions');
    $routes->post('activity-design/unmark-viewed/(:num)', 'ActivityDesignController::unmarkViewed/$1');

    $routes->options('accomplishment-report/mark-viewed/(:num)', 'AuthController::handleOptions');
    $routes->post('accomplishment-report/mark-viewed/(:num)', 'AccomplishmentReportController::markViewed/$1');
    $routes->options('accomplishment-report/unmark-viewed/(:num)', 'AuthController::handleOptions');
    $routes->post('accomplishment-report/unmark-viewed/(:num)', 'AccomplishmentReportController::unmarkViewed/$1');

    // ----------------------------------------------------------------
    // MESSAGING ROUTES
    // ----------------------------------------------------------------

    $routes->options('messages/send', 'AuthController::handleOptions');
    $routes->post('messages/send', 'MessageController::send');
    $routes->options('messages/announce', 'AuthController::handleOptions');
    $routes->post('messages/announce', 'MessageController::announce');
    $routes->options('messages/inbox/(:num)', 'AuthController::handleOptions');
    $routes->get('messages/inbox/(:num)', 'MessageController::getInbox/$1');
    $routes->options('messages/sent/(:num)', 'AuthController::handleOptions');
    $routes->get('messages/sent/(:num)', 'MessageController::getSent/$1');
    $routes->options('messages/read/(:num)', 'AuthController::handleOptions');
    $routes->post('messages/read/(:num)', 'MessageController::markAsRead/$1');

    $routes->options('messages/trashed/(:num)', 'AuthController::handleOptions');
    $routes->get('messages/trashed/(:num)', 'MessageController::getTrashed/$1');

    // ----------------------------------------------------------------
    // NOTIFICATION ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('notifications/unread', 'AuthController::handleOptions');
    $routes->get('notifications/unread', 'NotificationController::getUnread');
    
    $routes->options('notifications/all', 'AuthController::handleOptions');
    $routes->get('notifications/all', 'NotificationController::getAll');
    
    $routes->options('notifications/read/(:num)', 'AuthController::handleOptions');
    $routes->post('notifications/read/(:num)', 'NotificationController::markAsRead/$1');
    
    $routes->options('notifications/read-all', 'AuthController::handleOptions');
    $routes->post('notifications/read-all', 'NotificationController::markAllAsRead');

    $routes->options('messages/bulk-trash', 'AuthController::handleOptions');
    $routes->post('messages/bulk-trash', 'MessageController::bulkTrash');
    $routes->options('messages/bulk-restore', 'AuthController::handleOptions');
    $routes->post('messages/bulk-restore', 'MessageController::bulkRestore');
    
    $routes->options('messages/trash/(:num)', 'AuthController::handleOptions');
    $routes->post('messages/trash/(:num)', 'MessageController::trashMessage/$1');

    $routes->options('messages/restore/(:num)', 'AuthController::handleOptions');
    $routes->post('messages/restore/(:num)', 'MessageController::restoreMessage/$1');

    $routes->options('messages/permanently-delete', 'AuthController::handleOptions');
    $routes->post('messages/permanently-delete', 'MessageController::permanentlyDelete');

    $routes->options('messages/thread/(:num)', 'AuthController::handleOptions');
    $routes->get('messages/thread/(:num)', 'MessageController::getThread/$1');
    
    $routes->options('messages/unread-count/(:num)', 'AuthController::handleOptions');
    $routes->get('messages/unread-count/(:num)', 'MessageController::getUnreadCount/$1');

    // ----------------------------------------------------------------
    // BUDGET ROUTES
    // ----------------------------------------------------------------
    $routes->options('budget/summary', 'BudgetController::optionsHandler');
    $routes->get('budget/summary', 'BudgetController::getSummary');
    $routes->options('budget/gad-plan', 'BudgetController::optionsHandler');
    $routes->get('budget/gad-plan', 'BudgetController::getGadPlan');
    
    // Office Budget Utilization and Realignment Monitoring
    $routes->options('staff/budget-monitoring', 'BudgetController::optionsHandler');
    $routes->get('staff/budget-monitoring', 'BudgetController::getOfficeUtilization');
    $routes->options('college/budget-monitoring', 'BudgetController::optionsHandler');
    $routes->get('college/budget-monitoring', 'BudgetController::getOfficeUtilization');
    
    $routes->options('staff/budget-monitoring/update', 'BudgetController::optionsHandler');
    $routes->post('staff/budget-monitoring/update', 'BudgetController::updateOfficeBudget');
    
    $routes->options('staff/budget/available-mandates', 'BudgetController::optionsHandler');
    $routes->get('staff/budget/available-mandates', 'BudgetController::getAvailableMandates');
    
    $routes->options('staff/budget/realignment-logs', 'BudgetController::optionsHandler');
    $routes->get('staff/budget/realignment-logs', 'BudgetController::getRealignmentLogs');
    
    $routes->options('staff/budget/financial-meta', 'BudgetController::optionsHandler');
    $routes->get('staff/budget/financial-meta', 'BudgetController::getFinancialMeta');
    
    $routes->options('staff/budget/realign', 'BudgetController::optionsHandler');
    $routes->post('staff/budget/realign', 'BudgetController::executeRealignment');

    // ----------------------------------------------------------------
    // FILE SERVING ROUTES (serve PDFs from writable/uploads)
    // ----------------------------------------------------------------
    $routes->options('files/drafts/(:segment)', 'AuthController::handleOptions');
    $routes->get('files/drafts/(:segment)', 'FileController::serveDraft/$1');

    $routes->options('files/archived/(:segment)', 'AuthController::handleOptions');
    $routes->get('files/archived/(:segment)', 'FileController::serveArchived/$1');

    $routes->options('files/avatars/(:segment)', 'AuthController::handleOptions');
    $routes->get('files/avatars/(:segment)', 'FileController::serveAvatar/$1');

    $routes->options('files/news-iec/(:segment)', 'AuthController::handleOptions');
    $routes->get('files/news-iec/(:segment)', 'FileController::serveNewsIec/$1');

    // Share link for Facebook / Open Graph Meta Tags
    $routes->options('share/news-iec/(:num)', 'AuthController::handleOptions');
    $routes->get('share/news-iec/(:num)', 'ShareController::newsIec/$1');

    $routes->options('files/overwrite/(:segment)/(:segment)', 'AuthController::handleOptions');
    $routes->post('files/overwrite/(:segment)/(:segment)', 'FileController::overwrite/$1/$2');
    // Document Trash Endpoints
    $routes->options('documents/trashed', 'AuthController::handleOptions');
    $routes->get('documents/trashed', 'DocumentTrashController::getTrashedDocuments');
    $routes->options('documents/restore', 'AuthController::handleOptions');
    $routes->post('documents/restore', 'DocumentTrashController::restore');
    $routes->options('documents/permanently-delete', 'AuthController::handleOptions');
    $routes->post('documents/permanently-delete', 'DocumentTrashController::permanentlyDelete');
});
$routes->group((ENVIRONMENT === 'production' ? '' : 'api'), function($routes) {
    // ----------------------------------------------------------------
    // GPB PLAN AND BUDGET ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('plan', 'AuthController::handleOptions');
    $routes->get('plan', 'PlanController::getPlan', ['filter' => 'jwtAuth']);
    $routes->post('plan', 'PlanController::savePlan', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('plan/reset', 'AuthController::handleOptions');
    $routes->post('plan/reset', 'PlanController::resetPlan', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    $routes->options('budget-history', 'AuthController::handleOptions');
    $routes->get('budget-history', 'BudgetHistoryController::index', ['filter' => 'jwtAuth']);
    $routes->options('budget-history/(:num)', 'AuthController::handleOptions');
    $routes->get('budget-history/(:num)', 'BudgetHistoryController::show/$1', ['filter' => 'jwtAuth']);
    $routes->options('budget-history/(:num)/restore', 'AuthController::handleOptions');
    $routes->post('budget-history/(:num)/restore', 'BudgetHistoryController::restore/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    // ----------------------------------------------------------------
    // SETTINGS ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('settings/baseline', 'AuthController::handleOptions');
    $routes->get('settings/baseline', 'SettingController::getBaselineAmounts');
    $routes->post('settings/baseline', 'SettingController::updateBaselineAmounts');
    
    $routes->options('settings/system', 'AuthController::handleOptions');
    $routes->get('settings/system', 'SettingController::getSystemSettings');
    $routes->post('settings/system', 'SettingController::updateSystemSettings');
    
    $routes->options('settings/trigger-cleanup', 'AuthController::handleOptions');
    $routes->post('settings/trigger-cleanup', 'SettingController::triggerCleanup');
    
    $routes->options('plan/mandate-statistics', 'AuthController::handleOptions');
    $routes->get('plan/mandate-statistics', 'PlanController::getMandateStatistics');

    $routes->options('plan/mandate-allocations', 'AuthController::handleOptions');
    $routes->get('plan/mandate-allocations', 'PlanController::getMandateAllocations');
    $routes->post('plan/mandate-allocations', 'PlanController::saveMandateAllocations');
    $routes->options('plan/allocation-worklist', 'AuthController::handleOptions');
    $routes->get('plan/allocation-worklist', 'PlanController::getAllocationWorklist', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('plan/allocation-options', 'AuthController::handleOptions');
    $routes->get('plan/allocation-options', 'PlanController::getAllocationOptions', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('plan/mandate-budget-line', 'AuthController::handleOptions');
    $routes->post('plan/mandate-budget-line', 'PlanController::addAllocationBudgetLine', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('plan/item-allocation', 'AuthController::handleOptions');
    $routes->post('plan/item-allocation', 'PlanController::saveItemAllocation', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    $routes->options('gpb/export/(:num)', 'AuthController::handleOptions');
    $routes->get('gpb/export/(:num)', 'GpbExportController::export/$1');
    
    $routes->options('gpb/export-live', 'AuthController::handleOptions');
    $routes->post('gpb/export-live', 'GpbLiveExportController::export');

    // ----------------------------------------------------------------
    // NEWS & IEC ROUTES (new)
    // ----------------------------------------------------------------
    $routes->options('news-iec', 'AuthController::handleOptions');
    $routes->get('news-iec', 'NewsIecController::index');
    $routes->post('news-iec', 'NewsIecController::create', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->post('news-iec/(:num)', 'NewsIecController::update/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('news-iec/(:num)', 'AuthController::handleOptions');
    $routes->get('news-iec/(:num)', 'NewsIecController::show/$1');
    $routes->delete('news-iec/(:num)', 'NewsIecController::delete/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
});

$routes->group((ENVIRONMENT === 'production' ? '' : 'api'), function($routes) {
    $routes->options('gpb/import', 'AuthController::handleOptions');
    $routes->post('gpb/import', 'GpbController::import', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('gpb/item', 'AuthController::handleOptions');
    $routes->get('gpb/item', 'GpbController::index', ['filter' => 'jwtAuth']);
    $routes->post('gpb/item', 'GpbController::create', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('gpb/item/(:segment)', 'AuthController::handleOptions');
    $routes->get('gpb/item/(:segment)', 'GpbController::show/$1');
    $routes->put('gpb/item/(:segment)', 'GpbController::update/$1');
    $routes->delete('gpb/item/(:segment)', 'GpbController::delete/$1');

    // Annual Report Archives
    $routes->options('annual-reports/archive', 'AuthController::handleOptions');
    $routes->post('annual-reports/archive', 'AnnualReportArchiveController::archive', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->get('annual-reports/archive', 'AnnualReportArchiveController::index');
    $routes->options('annual-reports/archive/(:num)', 'AuthController::handleOptions');
    $routes->get('annual-reports/archive/(:num)', 'AnnualReportArchiveController::show/$1');
    $routes->delete('annual-reports/archive/(:num)', 'AnnualReportArchiveController::delete/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    // Holiday API Routes
    $routes->options('holidays', 'AuthController::handleOptions');
    $routes->get('holidays', 'HolidayController::index');
    $routes->post('holidays', 'HolidayController::create', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->options('holidays/(:num)', 'AuthController::handleOptions');
    $routes->get('holidays/(:num)', 'HolidayController::show/$1');
    $routes->put('holidays/(:num)', 'HolidayController::update/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    $routes->delete('holidays/(:num)', 'HolidayController::delete/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

    $routes->group('holidays', function ($routes) {
        $routes->options('/', 'AuthController::handleOptions');
        $routes->get('/', 'HolidayController::index');

        $routes->options('create', 'AuthController::handleOptions');
        $routes->post('create', 'HolidayController::create', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

        $routes->options('update/(:num)', 'AuthController::handleOptions');
        $routes->put('update/(:num)', 'HolidayController::update/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

        $routes->options('delete/(:num)', 'AuthController::handleOptions');
        $routes->delete('delete/(:num)', 'HolidayController::delete/$1', ['filter' => ['jwtAuth', 'staffOrAdmin']]);

        $routes->options('sync', 'AuthController::handleOptions');
        $routes->post('sync', 'HolidayController::sync', ['filter' => ['jwtAuth', 'staffOrAdmin']]);
    });

    // Venues Management
    $routes->options('venues', 'AuthController::handleOptions');
    $routes->get('venues', 'VenueController::index');
    $routes->post('venues', 'VenueController::create');
    $routes->options('venues/(:num)', 'AuthController::handleOptions');
    $routes->put('venues/(:num)', 'VenueController::update/$1');
    $routes->delete('venues/(:num)', 'VenueController::delete/$1');
});
