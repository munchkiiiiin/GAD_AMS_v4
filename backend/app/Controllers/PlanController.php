<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class PlanController extends ResourceController
{
    protected $modelName = 'App\Models\GpbModel';
    protected $format    = 'json';

    public function getPlan()
    {
        $db = \Config\Database::connect();
        $requestedYear = (int)($this->request->getGet('fiscal_year') ?: date('Y'));
        $versionService = new \App\Libraries\BudgetVersionService($db);
        $version = $versionService->active($requestedYear);

        // Get organization settings
        $orgData = [
            'name' => 'Benguet State University',
            'category' => 'State Universities and Colleges',
            'hierarchy' => 'Benguet State University',
            'year' => date('Y'),
            'totalOrgBudget' => 1062488000.00,
            'preparedByName' => 'Jude Laoagan Tayaben, GAD Director',
            'approvedByName' => 'Kenneth Alip Laruan, President',
        ];

        if (!$version && $db->tableExists('settings')) {
            $settingModel = new \App\Models\SettingModel();
            // Get the fiscal year of the items currently in the database
            $latestYear = $version['fiscal_year'] ?? $requestedYear;
            $settings = $settingModel->getByFiscalYear($latestYear);
            if (!empty($settings)) {
                // Only merge keys that are expected in orgData or just merge all
                foreach ($orgData as $k => $v) {
                    if ($k !== 'otherSources' && isset($settings[$k])) {
                        $orgData[$k] = $settings[$k];
                    }
                }
            }
        }
        if ($version && !empty($version['organization_data'])) {
            $versionOrganization = json_decode($version['organization_data'], true);
            if (is_array($versionOrganization)) {
                $orgData = array_merge($orgData, $versionOrganization);
            }
        }

        // Ensure year is strictly numeric so frontend number input displays it
        $orgData['year'] = preg_replace('/[^0-9]/', '', (string)$orgData['year']);
        if (empty($orgData['year'])) {
            $orgData['year'] = date('Y');
        }

        $itemQuery = $db->table('gpb_items');
        if ($version) {
            $itemQuery->where('plan_version_id', $version['id']);
        } else {
            $itemQuery->where('fiscal_year', $requestedYear);
        }
        $items = $itemQuery
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $restoredMonitoringByKey = [];
        if ($version && ($version['source'] ?? '') === 'restore' && $db->tableExists('budget_version_monitoring_snapshots')) {
            $snapshot = $db->table('budget_version_monitoring_snapshots')
                ->where('plan_version_id', $version['id'])->get()->getRowArray();
            foreach (json_decode($snapshot['rows_data'] ?? '[]', true) ?: [] as $snapshotRow) {
                if (!empty($snapshotRow['item_key'])) {
                    $restoredMonitoringByKey[$snapshotRow['item_key']] = $snapshotRow;
                }
            }
        }

        // Get actual accomplishment results for each mandate by looking up the linked Activity Designs
        $arDataQuery = $db->table('activity_design_mandates adm')
            ->select('adm.mandate_id as gpb_item_id, ar.id as ar_id, ar.male, ar.female')
            ->join('activity_design ad', 'ad.act_design_id = adm.act_design_id')
            ->join('activity_design_issues adi', 'adi.act_design_id = ad.act_design_id AND adi.issue_id = adm.mandate_id')
            ->join('accomplishment_report ar', 'ar.control_number = ad.control_number')
            ->where('ar.status', 'Verified')
            ->where('ar.deleted_at', null)
            ->get()->getResultArray();
            
            
        $arHeadcounts = [];
        foreach ($arDataQuery as $row) {
            $mandateId = $row['gpb_item_id'];
            $arId = $row['ar_id'];
            if (!isset($arHeadcounts[$mandateId])) {
                $arHeadcounts[$mandateId] = [
                    'male' => 0,
                    'female' => 0,
                    'seen_ars' => []
                ];
            }
            if (!in_array($arId, $arHeadcounts[$mandateId]['seen_ars'])) {
                $arHeadcounts[$mandateId]['male'] += (int)$row['male'];
                $arHeadcounts[$mandateId]['female'] += (int)$row['female'];
                $arHeadcounts[$mandateId]['seen_ars'][] = $arId;
            }
        }

        $arAllocationsQuery = $db->table('budget_item_mandate_allocations bima')
            ->select('bima.mandate_id, bima.gpb_budget_line_id, SUM(bima.allocated_amount) as total_utilized')
            ->join('accomplishment_budget_items abi', 'abi.id = bima.budget_item_id')
            ->join('accomplishment_report ar', 'ar.id = abi.accomplishment_report_id')
            ->where('bima.item_type', 'AR')
            ->where('ar.status', 'Verified')
            ->where('ar.deleted_at', null)
            ->groupBy('bima.mandate_id, bima.gpb_budget_line_id')
            ->get()->getResultArray();
            
        $utilizedBudgets = [];
        foreach ($arAllocationsQuery as $row) {
            $mId = $row['mandate_id'];
            $blId = $row['gpb_budget_line_id'];
            if (!isset($utilizedBudgets[$mId])) {
                $utilizedBudgets[$mId] = [];
            }
            $utilizedBudgets[$mId][$blId] = (float)$row['total_utilized'];
        }

        $processedItems = [];
        foreach ($items as $item) {
            $itemId = $item['id'];
            $budgetLines = isset($item['budget_lines']) ? json_decode($item['budget_lines'], true) : [];
            if (!is_array($budgetLines) || empty($budgetLines)) {
                $budgetLines = [];
            } else {
                foreach ($budgetLines as &$bl) {
                    $blId = $bl['id'] ?? null;
                    $bl['utilized_budget'] = 0;
                    if ($blId && isset($utilizedBudgets[$itemId][$blId])) {
                        $bl['utilized_budget'] = $utilizedBudgets[$itemId][$blId];
                    }
                }
                unset($bl);
            }

            $restoredMonitoring = $restoredMonitoringByKey[$item['item_key'] ?? ''] ?? null;
            if ($restoredMonitoring && $budgetLines) {
                foreach ($budgetLines as $lineIndex => &$budgetLine) {
                    $budgetLine['utilized_budget'] = $lineIndex === 0
                        ? (float)($restoredMonitoring['utilized'] ?? 0)
                        : 0;
                }
                unset($budgetLine);
            }

            $processedItems[] = [
                'id' => (string) $item['id'],
                'item_key' => $item['item_key'] ?? null,
                'section' => $item['section'],
                'mandate' => $item['mandate'],
                'cause' => $item['cause'],
                'result' => $item['result'] ?? $item['objective'],
                'mfo' => $item['mfo'] ?? $item['ppa'],
                'activity' => $item['activity'],
                'indicators' => $item['indicators'] ?? $item['targets'],
                'responsible' => $item['responsible'] ?? $item['office'],
                'budgetLines' => $budgetLines,
                'budget' => (float)($item['budget'] ?? 0),
                'source' => $item['source'] ?? 'GAA',
                'actualResult_m' => $restoredMonitoring
                    ? (int)($restoredMonitoring['actual_result_m'] ?? 0)
                    : (isset($arHeadcounts[$item['id']]) ? $arHeadcounts[$item['id']]['male'] : 0),
                'actualResult_f' => $restoredMonitoring
                    ? (int)($restoredMonitoring['actual_result_f'] ?? 0)
                    : (isset($arHeadcounts[$item['id']]) ? $arHeadcounts[$item['id']]['female'] : 0),
            ];
        }

        return $this->respond([
            'settings' => ['apiBaseUrl' => ''],
            'org' => $orgData,
            'items' => $processedItems,
            'version' => $version ? [
                'id' => (int)$version['id'],
                'fiscal_year' => (int)$version['fiscal_year'],
                'version_number' => (int)$version['version_number'],
                'status' => $version['status'],
            ] : null,
        ]);
    }

    public function savePlan()
    {
        $payload = $this->request->jwtPayload ?? null;
        if ($payload && !in_array($payload['role'] ?? '', ['admin', 'gad_staff', 'staff', 'superadmin'], true)) {
            return $this->failForbidden('Staff or Administrator access required to save plan.');
        }

        $db = \Config\Database::connect();
        $input = $this->request->getJSON(true);

        // Validate input
        if (!isset($input['org']) || !isset($input['items'])) {
            return $this->failValidationErrors('Org and items data are required');
        }

        $year = (int)($input['org']['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            return $this->failValidationErrors('A valid fiscal year is required.');
        }
        try {
            $service = new \App\Libraries\BudgetVersionService($db);
            $actorId = isset($payload['uid']) ? (int)$payload['uid'] : (isset($payload['id']) ? (int)$payload['id'] : null);
            $version = $service->saveInPlace($year, $input['org'], $input['items'], $actorId);
            return $this->respond(['status' => 'success', 'message' => 'Plan saved successfully', 'version' => $version]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->failValidationErrors($e->getMessage());
        }

        return $this->failServerError('Plan save did not complete.');
    }

    public function resetPlan()
    {
        $payload = $this->request->jwtPayload ?? [];
        if (!in_array($payload['role'] ?? '', ['admin', 'gad_staff', 'staff', 'superadmin'], true)) {
            return $this->failForbidden('Staff or Administrator access required to reset the plan.');
        }
        $input = $this->request->getJSON(true) ?: [];
        $year = (int)($input['year'] ?? $this->request->getGet('fiscal_year') ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            return $this->failValidationErrors('A valid fiscal year is required.');
        }
        try {
            $actorId = isset($payload['uid']) ? (int)$payload['uid'] : (isset($payload['id']) ? (int)$payload['id'] : null);
            $version = (new \App\Libraries\BudgetVersionService())->createReplacement(
                $year,
                is_array($input['org'] ?? null) ? $input['org'] : ['year' => $year],
                [],
                'reset',
                $actorId
            );
            return $this->respond(['status' => 'success', 'message' => 'A new empty plan version is active.', 'version' => $version]);
        } catch (\Throwable $e) {
            log_message('error', 'Plan reset failed: {message}', ['message' => $e->getMessage()]);
            return $this->failServerError('Plan reset failed and was rolled back.');
        }
    }

    public function getMandateStatistics()
    {
        $db = \Config\Database::connect();
        $fiscalYear = (int)($this->request->getGet('fiscal_year') ?: date('Y'));
        $version = $db->tableExists('budget_versions')
            ? (new \App\Libraries\BudgetVersionService($db))->active($fiscalYear)
            : null;
        
        // 1. Get all GPB Items
        $gpbQuery = $db->table('gpb_items');
        if ($version) {
            $gpbQuery->where('plan_version_id', $version['id']);
        } else {
            $gpbQuery->where('fiscal_year', $fiscalYear);
        }
        $gpbItems = $gpbQuery->get()->getResultArray();
        
        $mandateStats = [];
        
        // Group by mandate + cause + activity to ensure distinct entries
        foreach($gpbItems as $item) {
            $mandateName = trim($item['mandate']);
            $causeName = trim($item['cause']);
            $activityName = trim($item['activity']);
            
            if (empty($mandateName)) {
                $mandateName = $activityName ?: 'Attributed Program / Unspecified';
            }
            
            // Unique key for grouping
            $groupKey = md5($mandateName . '|' . $causeName . '|' . $activityName);
            
            if (!isset($mandateStats[$groupKey])) {
                $mandateStats[$groupKey] = [
                    'key' => $groupKey,
                    'classification' => $item['section'] ?? '',
                    'mandate' => $mandateName,
                    'cause' => $causeName,
                    'activity' => $activityName,
                    'budget' => 0.0,
                    'approved_ad_count' => 0,
                    'approved_ar_count' => 0,
                    'remaining_budget' => 0.0,
                    'utilized_budget' => 0.0,
                    'pending_budget' => 0.0,
                    'gpb_ids' => [],
                    'lineage_ids' => [],
                    'budget_lines' => []
                ];
            }
            
            // Add budget
            $mandateStats[$groupKey]['budget'] += (float)$item['budget'];
            $mandateStats[$groupKey]['gpb_ids'][] = (int)$item['id'];
            $lineageIds = [$item['id']];
            if (!empty($item['item_key'])) {
                $lineageIds = array_column(
                    $db->table('gpb_items')->select('id')->where('item_key', $item['item_key'])->get()->getResultArray(),
                    'id'
                );
            }
            $mandateStats[$groupKey]['lineage_ids'] = array_values(array_unique(array_merge(
                $mandateStats[$groupKey]['lineage_ids'],
                array_map('intval', $lineageIds)
            )));

            // Parse budget lines
            $bLines = isset($item['budget_lines']) ? json_decode($item['budget_lines'], true) : [];
            if (is_array($bLines)) {
                foreach ($bLines as $bl) {
                    $bl['pending_budget'] = 0.0;
                    $bl['utilized_budget'] = 0.0;
                    $mandateStats[$groupKey]['budget_lines'][] = $bl;
                }
            }
        }
        
        // Now calculate AD and AR for each group
        foreach($mandateStats as &$stat) {
            if (empty($stat['gpb_ids'])) {
                continue;
            }
            $lineageIds = $stat['lineage_ids'] ?: $stat['gpb_ids'];
            
            // Get all Approved ADs linked to these gpb_ids
            $adLinks = $db->table('activity_design_mandates adm')
                ->select('ad.act_design_id, ad.proposed_budget, ad.control_number')
                ->join('activity_design ad', 'ad.act_design_id = adm.act_design_id')
                ->join('activity_design_issues adi', 'adi.act_design_id = ad.act_design_id AND adi.issue_id = adm.mandate_id')
                ->whereIn('adm.mandate_id', $lineageIds)
                ->where('ad.status', 'Approved')
                ->where('ad.deleted_at', null)
                ->groupBy('ad.act_design_id')
                ->get()->getResultArray();
                
            $stat['approved_ad_count'] = count($adLinks);
            
            foreach($adLinks as $ad) {
                // Check AR
                $ar = $db->table('accomplishment_report')
                    ->where('control_number', $ad['control_number'])
                    ->where('status', 'Verified')
                    ->where('deleted_at', null)
                    ->get()->getRowArray();
                    
                if ($ar) {
                    $stat['approved_ar_count']++;
                    
                    $arAllocationsExist = $db->table('budget_item_mandate_allocations bima')
                        ->join('accomplishment_budget_items abi', 'abi.id = bima.budget_item_id')
                        ->where('bima.item_type', 'AR')
                        ->where('abi.accomplishment_report_id', $ar['id'])
                        ->countAllResults() > 0;
                        
                    if ($arAllocationsExist) {
                        $manualCostRow = $db->table('budget_item_mandate_allocations bima')
                            ->selectSum('bima.allocated_amount')
                            ->join('accomplishment_budget_items abi', 'abi.id = bima.budget_item_id')
                            ->where('bima.item_type', 'AR')
                            ->where('abi.accomplishment_report_id', $ar['id'])
                            ->whereIn('bima.mandate_id', $lineageIds)
                            ->get()->getRowArray();
                        $actualCost = $manualCostRow ? (float)$manualCostRow['allocated_amount'] : 0.0;
                    } else {
                        // Fallback to AD allocations if AR is unassigned
                        $manualPendingRow = $db->table('budget_item_mandate_allocations bima')
                            ->selectSum('bima.allocated_amount')
                            ->join('activity_budget_items abi', 'abi.id = bima.budget_item_id')
                            ->where('bima.item_type', 'AD')
                            ->where('abi.act_design_id', $ad['act_design_id'])
                            ->whereIn('bima.mandate_id', $lineageIds)
                            ->get()->getRowArray();
                        $actualCost = $manualPendingRow ? (float)$manualPendingRow['allocated_amount'] : 0.0;
                    }
                    
                    $stat['utilized_budget'] += $actualCost;
                } else {
                    // Sum only manually allocated activity_budget_items for this specific group of mandates
                    $manualPendingRow = $db->table('budget_item_mandate_allocations bima')
                        ->selectSum('bima.allocated_amount')
                        ->join('activity_budget_items abi', 'abi.id = bima.budget_item_id')
                        ->where('bima.item_type', 'AD')
                        ->where('abi.act_design_id', $ad['act_design_id'])
                        ->whereIn('bima.mandate_id', $lineageIds)
                        ->get()->getRowArray();
                        
                    $pendingCost = $manualPendingRow ? (float)$manualPendingRow['allocated_amount'] : 0.0;
                    $stat['pending_budget'] += $pendingCost;
                }
            }
            // Now, populate per-budget-line totals
            // For ARs (Utilized)
            $arLineCosts = $db->table('budget_item_mandate_allocations bima')
                ->select('bima.gpb_budget_line_id, SUM(bima.allocated_amount) as total_utilized')
                ->join('accomplishment_budget_items abi', 'abi.id = bima.budget_item_id')
                ->join('accomplishment_report ar', 'ar.id = abi.accomplishment_report_id')
                ->where('bima.item_type', 'AR')
                ->whereIn('bima.mandate_id', $lineageIds)
                ->where('ar.status', 'Verified')
                ->where('ar.deleted_at', null)
                ->where('bima.gpb_budget_line_id !=', null)
                ->groupBy('bima.gpb_budget_line_id')
                ->get()->getResultArray();

            foreach ($arLineCosts as $row) {
                foreach ($stat['budget_lines'] as &$bl) {
                    if ($bl['id'] === $row['gpb_budget_line_id']) {
                        $bl['utilized_budget'] += (float)$row['total_utilized'];
                    }
                }
            }

            // For ADs (Pending)
            // Note: AD is considered pending if there is NO verified AR for it.
            $adLineCosts = $db->table('budget_item_mandate_allocations bima')
                ->select('bima.gpb_budget_line_id, ad.control_number, bima.allocated_amount')
                ->join('activity_budget_items abi', 'abi.id = bima.budget_item_id')
                ->join('activity_design ad', 'ad.act_design_id = abi.act_design_id')
                ->where('bima.item_type', 'AD')
                ->whereIn('bima.mandate_id', $lineageIds)
                ->where('ad.status', 'Approved')
                ->where('ad.deleted_at', null)
                ->where('bima.gpb_budget_line_id !=', null)
                ->get()->getResultArray();

            foreach ($adLineCosts as $row) {
                // Check if this AD has a verified AR
                $hasVerifiedAR = $db->table('accomplishment_report')
                    ->where('control_number', $row['control_number'])
                    ->where('status', 'Verified')
                    ->where('deleted_at', null)
                    ->countAllResults() > 0;

                if (!$hasVerifiedAR) {
                    foreach ($stat['budget_lines'] as &$bl) {
                        if ($bl['id'] === $row['gpb_budget_line_id']) {
                            $bl['pending_budget'] += (float)$row['allocated_amount'];
                        }
                    }
                }
            }

            $stat['remaining_budget'] = $stat['budget'] - $stat['utilized_budget'] - $stat['pending_budget'];
            // Do not unset gpb_ids; frontend needs it for manual allocations
        }
        
        // Re-index array
        $finalStats = array_values($mandateStats);
        
        return $this->respond([
            'success' => true,
            'data' => $finalStats
        ]);
    }

    public function getMandateAllocations()
    {
        $gpbIds = $this->request->getGet('gpb_ids');
        if (empty($gpbIds)) return $this->fail('gpb_ids required');
        $gpbIds = explode(',', $gpbIds);

        $db = \Config\Database::connect();
        $selectedRows = $db->table('gpb_items')->select('item_key')->whereIn('id', $gpbIds)->get()->getResultArray();
        $lineageKeys = array_values(array_filter(array_column($selectedRows, 'item_key')));
        $lineageQuery = $db->table('gpb_items')->select('id')->whereIn('id', $gpbIds);
        if ($lineageKeys) {
            $lineageQuery->orWhereIn('item_key', $lineageKeys);
        }
        $lineageIds = $lineageQuery->get()->getResultArray();
        $lineageIds = array_values(array_unique(array_map('intval', array_column($lineageIds, 'id'))));
        if (!$lineageIds) {
            $lineageIds = array_map('intval', $gpbIds);
        }

        $adLinks = $db->table('activity_design_mandates adm')
            ->select('ad.act_design_id, ad.activity_title as title, ad.control_number, ad.attachment')
            ->join('activity_design ad', 'ad.act_design_id = adm.act_design_id')
            ->join('activity_design_issues adi', 'adi.act_design_id = ad.act_design_id')
            ->whereIn('adm.mandate_id', $lineageIds)
            ->whereIn('adi.issue_id', $lineageIds)
            ->where('ad.status', 'Approved')
            ->where('ad.deleted_at', null)
            ->groupBy('ad.act_design_id')
            ->get()->getResultArray();

        $documents = [];

        foreach($adLinks as $ad) {
            $ar = $db->table('accomplishment_report')
                ->where('control_number', $ad['control_number'])
                ->where('status', 'Verified')
                ->where('deleted_at', null)
                ->get()->getRowArray();

            if ($ar) {
                $items = $db->table('accomplishment_budget_items')
                    ->where('accomplishment_report_id', $ar['id'])
                    ->get()->getResultArray();
                
                foreach ($items as &$item) {
                    $allocations = $db->table('budget_item_mandate_allocations')
                        ->where('item_type', 'AR')
                        ->where('budget_item_id', $item['id'])
                        ->get()->getResultArray();
                    
                    foreach ($allocations as &$al) {
                        $gpbItem = $db->table('gpb_items')
                            ->select('mandate, cause, activity')
                            ->where('id', $al['mandate_id'])
                            ->get()->getRowArray();
                        if ($gpbItem) {
                            $mName = trim($gpbItem['mandate']);
                            $al['mandate_name'] = $mName ? $mName : ($gpbItem['activity'] ?: 'Attributed Program');
                            $al['cause_name'] = trim($gpbItem['cause']);
                            $al['activity_name'] = trim($gpbItem['activity']);
                        } else {
                            $al['mandate_name'] = 'Unknown Mandate';
                            $al['cause_name'] = '';
                            $al['activity_name'] = '';
                        }
                    }
                    
                    $item['allocations'] = $allocations;
                    
                    $allocatedToCurrent = 0;
                    $allocatedGpbLineId = null;
                    foreach ($allocations as $al) {
                        if (in_array($al['mandate_id'], $lineageIds)) {
                            $allocatedToCurrent += (float)$al['allocated_amount'];
                            if (!empty($al['gpb_budget_line_id'])) {
                                $allocatedGpbLineId = $al['gpb_budget_line_id'];
                            }
                        }
                    }
                    $item['allocated_to_current'] = $allocatedToCurrent;
                    $item['gpb_budget_line_id'] = $allocatedGpbLineId;
                }

                $documents[] = [
                    'type' => 'AR',
                    'id' => $ar['id'],
                    'title' => 'AR for ' . ($ad['title'] ?? $ad['control_number']),
                    'control_number' => $ar['control_number'],
                    'attachment' => $ar['attachment'],
                    'items' => $items
                ];
            } 
            
            // Only show the AD if it does NOT have a verified AND archived AR
            $hasVerifiedAndArchivedAR = ($ar && $ar['is_archived'] == 1);
            
            if (!$hasVerifiedAndArchivedAR) {
                $items = $db->table('activity_budget_items')
                    ->where('act_design_id', $ad['act_design_id'])
                    ->get()->getResultArray();

                foreach ($items as &$item) {
                    $allocations = $db->table('budget_item_mandate_allocations')
                        ->where('item_type', 'AD')
                        ->where('budget_item_id', $item['id'])
                        ->get()->getResultArray();
                    $item['allocations'] = $allocations;
                    
                    $allocatedToCurrent = 0;
                    $allocatedGpbLineId = null;
                    foreach ($allocations as $al) {
                        if (in_array($al['mandate_id'], $lineageIds)) {
                            $allocatedToCurrent += (float)$al['allocated_amount'];
                            if (!empty($al['gpb_budget_line_id'])) {
                                $allocatedGpbLineId = $al['gpb_budget_line_id'];
                            }
                        }
                    }
                    $item['allocated_to_current'] = $allocatedToCurrent;
                    $item['gpb_budget_line_id'] = $allocatedGpbLineId;
                }

                $documents[] = [
                    'type' => 'AD',
                    'id' => $ad['act_design_id'],
                    'title' => $ad['title'],
                    'control_number' => $ad['control_number'],
                    'attachment' => $ad['attachment'],
                    'items' => $items
                ];
            }
        }

        return $this->respond([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function getAllocationWorklist()
    {
        $db = \Config\Database::connect();
        $documents = [];

        $adRows = $db->table('activity_design ad')
            ->select('ad.act_design_id AS id, ad.activity_title AS title, ad.control_number, ad.attachment, ad.start_date, ad.status')
            ->where('ad.status', 'Approved')
            ->where('ad.deleted_at', null)
            ->orderBy('ad.start_date', 'DESC')
            ->get()->getResultArray();

        foreach ($adRows as $ad) {
            $archivedVerifiedAr = $db->table('accomplishment_report')
                ->where('control_number', $ad['control_number'])
                ->where('status', 'Verified')
                ->where('deleted_at', null)
                ->where('is_archived', 1)
                ->countAllResults() > 0;
            if ($archivedVerifiedAr) continue;
            $items = $db->table('activity_budget_items')->where('act_design_id', $ad['id'])->get()->getResultArray();
            $items = array_values(array_filter($items, static fn($item) => (float)($item['amount'] ?? 0) > 0));
            if (!$items) continue;
            $year = !empty($ad['start_date']) ? (int)date('Y', strtotime($ad['start_date'])) : null;
            $linkedMandateIds = $this->getLinkedMandateIdsForActivityDesign($db, (int)$ad['id'], $year);
            $documents[] = $this->formatAllocationWorklistDocument($db, 'AD', $ad, $items, $linkedMandateIds);
        }

        $arRows = $db->table('accomplishment_report')
            ->select('id, act_design_id, activity_title AS title, control_number, attachment, start_date, status')
            ->where('status', 'Verified')
            ->where('deleted_at', null)
            ->orderBy('start_date', 'DESC')
            ->get()->getResultArray();
        foreach ($arRows as $ar) {
            $items = $db->table('accomplishment_budget_items')->where('accomplishment_report_id', $ar['id'])->get()->getResultArray();
            $items = array_values(array_filter($items, static fn($item) => (float)($item['amount'] ?? 0) > 0));
            if (!$items) continue;
            $adId = (int)($ar['act_design_id'] ?? 0);
            if (!$adId) {
                $linkedAd = $db->table('activity_design')->select('act_design_id')->where('control_number', $ar['control_number'])->where('deleted_at', null)->get()->getRowArray();
                $adId = (int)($linkedAd['act_design_id'] ?? 0);
            }
            $year = !empty($ar['start_date']) ? (int)date('Y', strtotime($ar['start_date'])) : null;
            $linkedMandateIds = $adId ? $this->getLinkedMandateIdsForActivityDesign($db, $adId, $year) : [];
            $documents[] = $this->formatAllocationWorklistDocument($db, 'AR', $ar, $items, $linkedMandateIds);
        }

        usort($documents, static function ($a, $b) {
            return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
        });

        return $this->respond(['success' => true, 'data' => $documents]);
    }

    private function formatAllocationWorklistDocument($db, string $type, array $document, array $items, array $linkedMandateIds = []): array
    {
        $items = array_values(array_filter($items, static fn($item) => (float)($item['amount'] ?? 0) > 0));
        $total = 0.0;
        $allocated = 0.0;
        foreach ($items as &$item) {
            $item['amount'] = (float)($item['amount'] ?? 0);
            $item['allocations'] = $db->table('budget_item_mandate_allocations')
                ->where('item_type', $type)
                ->where('budget_item_id', $item['id'])
                ->get()->getResultArray();
            foreach ($item['allocations'] as &$allocation) {
                $mandate = $db->table('gpb_items')->select('mandate, cause, activity')->where('id', $allocation['mandate_id'])->get()->getRowArray();
                $allocation['mandate_name'] = trim($mandate['mandate'] ?? '') ?: ($mandate['activity'] ?? 'Unspecified mandate');
                $allocation['cause_name'] = $mandate['cause'] ?? '';
                $allocation['activity_name'] = $mandate['activity'] ?? '';
            }
            unset($allocation);
            $item['allocated_amount'] = array_sum(array_map(static fn($row) => (float)$row['allocated_amount'], $item['allocations']));
            $item['remaining_amount'] = max(0, $item['amount'] - $item['allocated_amount']);
            $total += $item['amount'];
            $allocated += min($item['amount'], $item['allocated_amount']);
        }
        unset($item);

        $remaining = max(0, $total - $allocated);
        $status = $remaining <= 0.009
            ? 'fully_allocated'
            : ($allocated > 0 ? 'partially_allocated' : 'unallocated');
        return [
            'type' => $type,
            'id' => (int)$document['id'],
            'title' => $document['title'] ?? '',
            'control_number' => $document['control_number'] ?? '',
            'attachment' => $document['attachment'] ?? null,
            'date' => $document['start_date'] ?? null,
            'fiscal_year' => !empty($document['start_date']) ? (int)date('Y', strtotime($document['start_date'])) : null,
            'linked_mandate_ids' => array_values(array_unique(array_map('intval', $linkedMandateIds))),
            'status' => $status,
            'total_budget' => $total,
            'allocated_amount' => $allocated,
            'remaining_amount' => $remaining,
            'items' => $items,
        ];
    }

    private function getLinkedMandateIdsForActivityDesign($db, int $activityDesignId, ?int $fiscalYear): array
    {
        if ($activityDesignId < 1) return [];
        $linkedRows = $db->table('activity_design_mandates adm')
            ->select('adm.mandate_id')
            ->join('activity_design_issues adi', 'adi.act_design_id = adm.act_design_id AND adi.issue_id = adm.mandate_id')
            ->where('adm.act_design_id', $activityDesignId)
            ->get()->getResultArray();
        $sourceIds = array_values(array_unique(array_map('intval', array_column($linkedRows, 'mandate_id'))));
        if (!$sourceIds) return [];

        $sourceRows = $db->table('gpb_items')->select('id, item_key')->whereIn('id', $sourceIds)->get()->getResultArray();
        $sourceKeys = array_values(array_filter(array_unique(array_column($sourceRows, 'item_key'))));
        $targetQuery = $db->table('gpb_items')->select('id, item_key');
        $version = ($fiscalYear && $db->tableExists('budget_versions'))
            ? (new \App\Libraries\BudgetVersionService($db))->active($fiscalYear)
            : null;
        if ($version) $targetQuery->where('plan_version_id', $version['id']);
        elseif ($fiscalYear) $targetQuery->where('fiscal_year', $fiscalYear);
        if ($sourceKeys) $targetQuery->groupStart()->whereIn('id', $sourceIds)->orWhereIn('item_key', $sourceKeys)->groupEnd();
        else $targetQuery->whereIn('id', $sourceIds);
        $targetRows = $targetQuery->get()->getResultArray();
        $ids = array_map('intval', array_column($targetRows, 'id'));
        return $ids;
    }

    public function getAllocationOptions()
    {
        $db = \Config\Database::connect();
        $fiscalYear = (int)($this->request->getGet('fiscal_year') ?: date('Y'));
        $version = $db->tableExists('budget_versions')
            ? (new \App\Libraries\BudgetVersionService($db))->active($fiscalYear)
            : null;
        $query = $db->table('gpb_items')->select('id, fiscal_year, section, mandate, cause, activity, budget, budget_lines');
        if ($version) $query->where('plan_version_id', $version['id']);
        else $query->where('fiscal_year', $fiscalYear);
        $rows = $query->orderBy('sort_order', 'ASC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['budget_lines'] = json_decode($row['budget_lines'] ?? '[]', true) ?: [];
        }
        unset($row);
        return $this->respond(['success' => true, 'data' => $rows]);
    }

    public function addAllocationBudgetLine()
    {
        $input = $this->request->getJSON(true) ?? [];
        $mandateId = (int)($input['mandate_id'] ?? 0);
        $fiscalYear = (int)($input['fiscal_year'] ?? 0);
        $label = trim((string)($input['label'] ?? ''));
        $amount = round((float)($input['amount'] ?? 0), 2);
        $source = trim((string)($input['source'] ?? 'GAA'));
        $sourceOther = trim((string)($input['source_other'] ?? ''));
        if ($mandateId < 1 || $fiscalYear < 2000 || $label === '' || mb_strlen($label) > 255 || $amount <= 0 || !in_array($source, ['GAA', 'Other'], true) || ($source === 'Other' && $sourceOther === '')) {
            return $this->failValidationErrors('Enter a valid description, positive amount, and budget source.');
        }

        $db = \Config\Database::connect();
        $query = $db->table('gpb_items')->where('id', $mandateId)->where('fiscal_year', $fiscalYear);
        $version = $db->tableExists('budget_versions')
            ? (new \App\Libraries\BudgetVersionService($db))->active($fiscalYear)
            : null;
        if ($version) $query->where('plan_version_id', $version['id']);
        $planItem = $query->get()->getRowArray();
        if (!$planItem) return $this->failValidationErrors('The selected mandate is not part of the active GAD Plan for this fiscal year.');

        $lines = json_decode($planItem['budget_lines'] ?? '[]', true);
        if (!is_array($lines)) $lines = [];
        $line = [
            'id' => 'l_' . bin2hex(random_bytes(8)),
            'label' => $label,
            'amount' => $amount,
            'source' => $source,
        ];
        if ($source === 'Other') $line['sourceOther'] = $sourceOther;
        $lines[] = $line;
        $totalBudget = array_sum(array_map(static fn($row) => (float)($row['amount'] ?? 0), $lines));
        $sources = array_values(array_unique(array_filter(array_map(static fn($row) => trim((string)($row['source'] ?? 'GAA')), $lines))));

        $db->transBegin();
        $updated = $db->table('gpb_items')->where('id', $mandateId)->where('fiscal_year', $fiscalYear)->update([
            'budget_lines' => json_encode($lines, JSON_UNESCAPED_UNICODE),
            'budget' => $totalBudget,
            'source' => implode(', ', $sources) ?: 'GAA',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$updated || $db->transStatus() === false) {
            $db->transRollback();
            return $this->failServerError('The budget line could not be saved. No changes were applied.');
        }
        $db->transCommit();
        return $this->respondCreated(['success' => true, 'data' => ['line' => $line, 'budget' => $totalBudget]]);
    }

    public function saveItemAllocation()
    {
        $input = $this->request->getJSON(true) ?? [];
        $itemId = (int)($input['budget_item_id'] ?? 0);
        $itemType = $input['item_type'] ?? '';
        $mandateId = (int)($input['mandate_id'] ?? 0);
        $lineId = (string)($input['gpb_budget_line_id'] ?? '');
        $amount = round((float)($input['allocated_amount'] ?? 0), 2);
        $isOverride = (bool)($input['is_override'] ?? false);
        if ($itemId < 1 || !in_array($itemType, ['AD', 'AR'], true) || $mandateId < 1 || $lineId === '' || $amount <= 0) {
            return $this->failValidationErrors('Choose a mandate, budget line, and allocation amount greater than zero.');
        }

        $db = \Config\Database::connect();
        $itemTable = $itemType === 'AD' ? 'activity_budget_items' : 'accomplishment_budget_items';
        $item = $db->table($itemTable)->where('id', $itemId)->get()->getRowArray();
        if (!$item) return $this->failNotFound('Budget item not found.');
        if (!$this->isEligibleAllocationItem($db, $itemType, $item)) return $this->failValidationErrors('This document is no longer eligible for allocation. Refresh the worklist and try again.');
        $planItem = $db->table('gpb_items')->where('id', $mandateId)->get()->getRowArray();
        if (!$planItem) return $this->failValidationErrors('Selected mandate is no longer available.');
        $docYear = $this->getAllocationDocumentYear($db, $itemType, $item);
        if ($docYear && !empty($planItem['fiscal_year']) && (int)$planItem['fiscal_year'] !== $docYear) return $this->failValidationErrors('Choose a mandate from the document’s fiscal year.');
        $linkedMandateIds = $this->getAllocationLinkedMandateIds($db, $itemType, $item, $docYear);
        if (!$isOverride && !in_array($mandateId, $linkedMandateIds, true)) return $this->failValidationErrors('Choose a mandate already linked to this document, or use the other-mandate fallback.');
        $isOverride = !in_array($mandateId, $linkedMandateIds, true);
        $lines = json_decode($planItem['budget_lines'] ?? '[]', true) ?: [];
        $line = null;
        foreach ($lines as $candidate) if ((string)($candidate['id'] ?? '') === $lineId) $line = $candidate;
        if (!$line) return $this->failValidationErrors('Selected budget line does not belong to this mandate.');

        $existing = $db->table('budget_item_mandate_allocations')->where('item_type', $itemType)->where('budget_item_id', $itemId)->get()->getResultArray();
        $otherAllocated = 0.0;
        $previousAmount = 0.0;
        foreach ($existing as $allocation) {
            if ((int)$allocation['mandate_id'] !== $mandateId) $otherAllocated += (float)$allocation['allocated_amount'];
            else $previousAmount += (float)$allocation['allocated_amount'];
        }
        $available = max(0, (float)$item['amount'] - $otherAllocated);
        if ($amount > $available + 0.009) return $this->failValidationErrors('Allocation exceeds the item’s remaining amount of ₱' . number_format($available, 2) . '.');

        $db->transBegin();
        $db->table('budget_item_mandate_allocations')->where('item_type', $itemType)->where('budget_item_id', $itemId)->where('mandate_id', $mandateId)->delete();
        $db->table('budget_item_mandate_allocations')->insert([
            'budget_item_id' => $itemId,
            'item_type' => $itemType,
            'mandate_id' => $mandateId,
            'gpb_budget_line_id' => $lineId,
            'allocated_amount' => $amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($itemType === 'AD') $this->syncVerifiedArAllocation($db, $item, $mandateId, $lineId, $amount);
        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->failServerError('Allocation could not be saved. No changes were applied.');
        }
        $db->transCommit();
        $payload = $this->request->jwtPayload ?? [];
        $actorId = (int)($payload['uid'] ?? $payload['id'] ?? 0);
        $parent = $itemType === 'AD'
            ? $db->table('activity_design')->select('activity_title AS title, control_number')->where('act_design_id', $item['act_design_id'])->get()->getRowArray()
            : $db->table('accomplishment_report')->select('activity_title AS title, control_number')->where('id', $item['accomplishment_report_id'])->get()->getRowArray();
        $this->logAllocationChange($actorId, $itemType, array_merge($item, $parent ?: []), $mandateId, $previousAmount, $amount, $isOverride);
        return $this->respond(['success' => true, 'message' => 'Allocation saved.']);
    }

    private function isEligibleAllocationItem($db, string $type, array $item): bool
    {
        if ($type === 'AD') {
            return $db->table('activity_design')->where('act_design_id', $item['act_design_id'])->where('status', 'Approved')->where('deleted_at', null)->countAllResults() > 0;
        }
        return $db->table('accomplishment_report')->where('id', $item['accomplishment_report_id'])->where('status', 'Verified')->where('deleted_at', null)->countAllResults() > 0;
    }

    private function getAllocationDocumentYear($db, string $type, array $item): ?int
    {
        if ($type === 'AD') $document = $db->table('activity_design')->select('start_date')->where('act_design_id', $item['act_design_id'])->get()->getRowArray();
        else $document = $db->table('accomplishment_report')->select('start_date')->where('id', $item['accomplishment_report_id'])->get()->getRowArray();
        return !empty($document['start_date']) ? (int)date('Y', strtotime($document['start_date'])) : null;
    }

    private function getAllocationLinkedMandateIds($db, string $type, array $item, ?int $fiscalYear): array
    {
        if ($type === 'AD') $activityDesignId = (int)($item['act_design_id'] ?? 0);
        else {
            $report = $db->table('accomplishment_report')->select('act_design_id, control_number')->where('id', $item['accomplishment_report_id'])->get()->getRowArray();
            $activityDesignId = (int)($report['act_design_id'] ?? 0);
            if (!$activityDesignId && !empty($report['control_number'])) {
                $ad = $db->table('activity_design')->select('act_design_id')->where('control_number', $report['control_number'])->where('deleted_at', null)->get()->getRowArray();
                $activityDesignId = (int)($ad['act_design_id'] ?? 0);
            }
        }
        return $this->getLinkedMandateIdsForActivityDesign($db, $activityDesignId, $fiscalYear);
    }

    private function logAllocationChange(int $actorId, string $type, array $item, int $mandateId, float $oldAmount, float $newAmount, bool $isOverride = false): void
    {
        if ($actorId < 1) return;
        $documentTitle = $item['title'] ?? ($item['control_number'] ?? 'document');
        $source = $isOverride ? ' · other-mandate fallback' : '';
        $detail = sprintf('%s · %s · %s · mandate #%d · %s → %s%s', $type, $documentTitle, $item['item_name'] ?? 'Budget item', $mandateId, number_format($oldAmount, 2), number_format($newAmount, 2), $source);
        \App\Models\ActivityLogModel::log($actorId, 'Budget Allocation', $detail);
    }

    private function syncVerifiedArAllocation($db, array $adItem, int $mandateId, string $lineId, float $amount, array $skipArItemIds = []): void
    {
        $ad = $db->table('activity_design')->where('act_design_id', $adItem['act_design_id'])->where('status', 'Approved')->where('deleted_at', null)->get()->getRowArray();
        if (!$ad) return;
        $ar = $db->table('accomplishment_report')->where('control_number', $ad['control_number'])->where('status', 'Verified')->where('deleted_at', null)->get()->getRowArray();
        if (!$ar) return;
        $query = $db->table('accomplishment_budget_items')->where('accomplishment_report_id', $ar['id'])->where('item_name', $adItem['item_name']);
        if (empty($adItem['sub_item'])) $query->groupStart()->where('sub_item', null)->orWhere('sub_item', '')->groupEnd();
        else $query->where('sub_item', $adItem['sub_item']);
        $arItem = $query->get()->getRowArray();
        if (!$arItem) return;
        if (in_array((int)$arItem['id'], array_map('intval', $skipArItemIds), true)) return;

        $db->table('budget_item_mandate_allocations')->where('budget_item_id', $arItem['id'])->where('item_type', 'AR')->where('mandate_id', $mandateId)->delete();
        $other = $db->table('budget_item_mandate_allocations')->where('budget_item_id', $arItem['id'])->where('item_type', 'AR')->selectSum('allocated_amount')->get()->getRowArray();
        $available = max(0, (float)$arItem['amount'] - (float)($other['allocated_amount'] ?? 0));
        $syncedAmount = min($amount, $available);
        if ($syncedAmount <= 0) return;
        $db->table('budget_item_mandate_allocations')->insert([
            'budget_item_id' => $arItem['id'], 'item_type' => 'AR', 'mandate_id' => $mandateId,
            'gpb_budget_line_id' => $lineId, 'allocated_amount' => $syncedAmount,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function saveMandateAllocations()
    {
        $input = $this->request->getJSON(true);
        $gpbIds = $input['gpb_ids'] ?? [];
        $allocations = $input['allocations'] ?? [];

        if (empty($gpbIds)) return $this->fail('gpb_ids required');

        $db = \Config\Database::connect();
        
        $targetMandateId = $gpbIds[0];

        foreach ($allocations as $alloc) {
            $itemId = $alloc['budget_item_id'];
            $itemType = $alloc['item_type'];
            $amount = (float)$alloc['allocated_amount'];
            $gpbLineId = $alloc['gpb_budget_line_id'] ?? null;

            // Find matching AR item if this is an AD item
            $arItemIdToSync = null;
            $arItemAmount = 0;
            if ($itemType === 'AD') {
                $adItem = $db->table('activity_budget_items')->where('id', $itemId)->get()->getRowArray();
                if ($adItem) {
                    $ad = $db->table('activity_design')->where('act_design_id', $adItem['act_design_id'])->get()->getRowArray();
                    if ($ad) {
                        $ar = $db->table('accomplishment_report')
                                 ->where('control_number', $ad['control_number'])
                                 ->where('status', 'Verified')
                                 ->where('deleted_at', null)
                                 ->get()->getRowArray();
                        if ($ar) {
                            $arItemQuery = $db->table('accomplishment_budget_items')
                                         ->where('accomplishment_report_id', $ar['id'])
                                         ->where('item_name', $adItem['item_name']);
                            if (empty($adItem['sub_item'])) {
                                $arItemQuery->groupStart()
                                            ->where('sub_item', null)
                                            ->orWhere('sub_item', '')
                                            ->groupEnd();
                            } else {
                                $arItemQuery->where('sub_item', $adItem['sub_item']);
                            }
                            $arItem = $arItemQuery->get()->getRowArray();
                            if ($arItem) {
                                $arItemIdToSync = $arItem['id'];
                                $arItemAmount = (float)$arItem['amount'];
                            }
                        }
                    }
                }
            }

            $db->table('budget_item_mandate_allocations')
               ->where('budget_item_id', $itemId)
               ->where('item_type', $itemType)
               ->whereIn('mandate_id', $gpbIds)
               ->delete();

            if ($arItemIdToSync) {
                $db->table('budget_item_mandate_allocations')
                   ->where('budget_item_id', $arItemIdToSync)
                   ->where('item_type', 'AR')
                   ->whereIn('mandate_id', $gpbIds)
                   ->delete();
            }

            if ($amount > 0) {
                $db->table('budget_item_mandate_allocations')->insert([
                    'budget_item_id' => $itemId,
                    'item_type' => $itemType,
                    'mandate_id' => $targetMandateId,
                    'gpb_budget_line_id' => $gpbLineId,
                    'allocated_amount' => $amount,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                if ($arItemIdToSync && $arItemAmount > 0) {
                    $db->table('budget_item_mandate_allocations')->insert([
                        'budget_item_id' => $arItemIdToSync,
                        'item_type' => 'AR',
                        'mandate_id' => $targetMandateId,
                        'gpb_budget_line_id' => $gpbLineId,
                        'allocated_amount' => $arItemAmount,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return $this->respond(['success' => true]);
    }
}
