<template>
  <div class="app-wrapper app" :class="{ 'fit-screen': isFitScreen }" style="background: #ffffff; width: 100%; overflow-x: auto;">
    <div class="banner-wrapper">
      <header class="topbar">
        <div class="topbar-brand">
          <span class="topbar-eyebrow">GAD Budget Distribution</span>
          <h1 class="topbar-title">Budget Distribution by Mandate</h1>
        </div>
        <div class="topbar-actions">

          <router-link to="/staff/budget-allocation">
            <button class="topbar-btn outline">Budget Allocation Worklist</button>
          </router-link>

          <router-link to="/staff/budget">
            <button class="topbar-btn outline">📊 Budget Monitoring</button>
          </router-link>
          <router-link to="/staff/plan-and-budget">
            <button class="topbar-btn outline">← Back to Plan & Budget</button>
          </router-link>
        </div>
      </header>
    </div>

    <div id="mandate-statistics-section" class="card main-card">
      <div class="card-controls">
        <div class="card-title-row">
          <h2 class="card-title">
            GAD Budget Distribution by Mandate
          </h2>
          <div class="filter-group">
             <label class="filter-label">Filter by Classification:</label>
             <select v-model="mandateStatsFilter" class="filter-select">
               <option value="all">All Classifications</option>
               <option value="client">Client-Focused</option>
               <option value="org">Organization-Focused</option>
               <option value="attributed">Attributed Program</option>
             </select>
          </div>
        </div>

        <div class="stats-search-row">
          <div class="table-actions-left">
            <button @click="toggleFitScreen" class="btn-toggle-all" type="button" :title="isFitScreen ? 'Switch to scrollable view' : 'Fit all table columns to screen'">
              <span class="material-symbols-outlined text-[16px]">{{ isFitScreen ? 'open_in_full' : 'fit_screen' }}</span>
              <span>{{ isFitScreen ? 'Scroll View' : 'Fit to Screen' }}</span>
            </button>
            <button 
              @click="toggleAllRows" 
              class="btn-toggle-all"
              type="button"
              :title="allExpanded ? 'Collapse all budget line breakdowns' : 'Expand all budget line breakdowns'"
            >
              <span class="material-symbols-outlined text-[16px]">{{ allExpanded ? 'unfold_less' : 'unfold_more' }}</span>
              <span>{{ allExpanded ? 'Collapse All Lines' : 'Expand All Lines' }}</span>
            </button>
            <span class="results-counter">
              Showing <b>{{ filteredMandateStats.length }}</b> of {{ mandateStats.length }} mandates
            </span>
          </div>

          <div class="search-wrap" :class="{ 'search-active': searchQuery }">
            <span class="material-symbols-outlined search-icon">search</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search mandate, cause, activity..."
              class="search-input"
            />
            <button v-if="searchQuery" @click="searchQuery = ''" class="search-clear-btn" type="button">Clear</button>
          </div>
        </div>
      </div>

      <div v-if="loadingStats" class="state-message-box">
        Loading statistics...
      </div>
      <div v-else-if="mandateStats.length === 0" class="empty-state-card">
        <span class="empty-icon">📭</span>
        <h3 class="empty-title">No Mandate Data Available</h3>
        <p class="empty-desc">The statistics are generated from your saved GAD Plan.<br>Please go to <router-link to="/staff/plan-and-budget" class="plan-link">Plan & Budget</router-link> and click <b>"Save Plan"</b> first to generate statistics.</p>
      </div>
      <div v-else>
         <div v-if="filteredMandateStats.length === 0" class="state-message-box">
           <span style="font-size: 1.5rem; display: block; margin-bottom: 8px;">🔍</span>
           No mandates match your search or filter criteria.
         </div>
         <div v-else class="mandate-table-container">
           <table class="mandate-table">
             <thead>
               <tr class="mandate-th-row">
                 <th class="th-expand text-center" style="width: 44px;"></th>
                 <th class="th-idx text-center" style="width: 48px;">#</th>
                 <th class="th-mandate text-left" style="min-width: 280px;">Gender Issue / Mandate</th>
                 <th class="th-activity text-left" style="min-width: 220px;">GAD Activity</th>
                 <th class="th-docs text-center" style="width: 130px;">Approved Docs</th>
                 <th class="th-amount text-right" style="width: 150px;">GAD Budget</th>
                 <th class="th-amount text-right" style="width: 150px;">Utilized (AR)</th>
                 <th class="th-amount text-right" style="width: 140px;">Pending (AD)</th>
                 <th class="th-amount text-right" style="width: 160px;">Remaining</th>
                 <th class="th-actions text-center" style="width: 195px;">Actions</th>
               </tr>
             </thead>
             <tbody>
               <template v-for="(stat, idx) in filteredMandateStats" :key="stat.key || idx">
                <tr 
                  class="mandate-row"
                  :class="{ 'row-expanded': isExpanded(stat.key || idx) }"
                  @click="toggleRow(stat.key || idx)"
                >
                  <!-- Expand toggle -->
                  <td class="td-cell text-center" @click.stop="toggleRow(stat.key || idx)">
                    <button 
                      type="button"
                      class="expand-toggle-btn" 
                      :class="{ 'is-rotated': isExpanded(stat.key || idx) }"
                      :title="isExpanded(stat.key || idx) ? 'Collapse Details' : 'Expand Details'"
                    >
                      <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                  </td>

                  <!-- # Index -->
                  <td class="td-cell text-center font-mono text-muted">
                    {{ idx + 1 }}
                  </td>

                  <!-- Mandate & Classification & Cause -->
                  <td class="td-cell text-left">
                    <div class="mandate-cell">
                      <div class="pill-row">
                        <span class="classification-pill" :class="getClassificationClass(stat.classification)">
                          {{ getClassificationLabel(stat.classification) }}
                        </span>
                      </div>
                      <div class="mandate-name-text">{{ stat.mandate || 'N/A' }}</div>
                      <div v-if="stat.cause" class="mandate-cause-box">
                        <span class="cause-prefix">Cause:</span> {{ stat.cause }}
                      </div>
                    </div>
                  </td>

                  <!-- GAD Activity -->
                  <td class="td-cell text-left">
                    <div class="activity-text">{{ stat.activity || 'N/A' }}</div>
                  </td>

                  <!-- Approved Docs -->
                  <td class="td-cell text-center">
                    <div class="docs-badge-stack">
                       <span class="badge-doc badge-ad" :title="stat.approved_ad_count + ' Approved Activity Designs'">
                        {{ stat.approved_ad_count }} AD{{ stat.approved_ad_count === 1 ? '' : 's' }}
                      </span>
                      <span class="badge-doc badge-ar" :title="stat.approved_ar_count + ' Approved Accomplishment Reports'">
                        {{ stat.approved_ar_count }} AR{{ stat.approved_ar_count === 1 ? '' : 's' }}
                      </span>
                    </div>
                  </td>

                  <!-- GAD Budget -->
                  <td class="td-cell text-right">
                    <div class="amount-val mono font-bold">₱{{ formatCurrency(stat.budget) }}</div>
                    <div v-if="stat.budget_lines && stat.budget_lines.length > 0" class="lines-count-hint">
                      {{ stat.budget_lines.length }} line item{{ stat.budget_lines.length === 1 ? '' : 's' }}
                    </div>
                  </td>

                  <!-- Utilized (AR) -->
                  <td class="td-cell text-right">
                    <div class="amount-val mono text-green font-semibold">₱{{ formatCurrency(stat.utilized_budget) }}</div>
                    <div v-if="stat.budget > 0" class="util-percent-hint text-green">
                      {{ ((stat.utilized_budget / stat.budget) * 100).toFixed(1) }}%
                    </div>
                  </td>

                  <!-- Pending (AD) -->
                  <td class="td-cell text-right">
                    <div class="amount-val mono text-yellow">₱{{ formatCurrency(stat.pending_budget) }}</div>
                  </td>

                  <!-- Remaining -->
                  <td class="td-cell text-right">
                    <div 
                      class="amount-val mono font-bold"
                      :class="stat.remaining_budget < 0 ? 'text-red font-extrabold' : 'text-blue'"
                    >
                      {{ stat.remaining_budget < 0 ? '-' : '' }}₱{{ formatCurrency(Math.abs(stat.remaining_budget)) }}
                    </div>
                    <span v-if="stat.remaining_budget < 0" class="deficit-chip">DEFICIT</span>
                  </td>

                  <!-- Actions -->
                  <td class="td-cell text-center" @click.stop>
                    <div class="actions-flex">
                      <button 
                        type="button"
                        @click="openAllocationModal(stat)" 
                        class="tbl-action-btn btn-alloc"
                        title="Manage Accomplishment Report Allocations"
                      >
                        <span class="material-symbols-outlined text-[15px]">tune</span>
                        <span>Allocate</span>
                      </button>
                      <button 
                        type="button"
                        @click="toggleRow(stat.key || idx)" 
                        class="tbl-action-btn btn-lines-toggle"
                        :class="{ 'btn-lines-active': isExpanded(stat.key || idx) }"
                        :title="isExpanded(stat.key || idx) ? 'Hide Lines' : 'View Lines'"
                      >
                        <span class="material-symbols-outlined text-[15px]">{{ isExpanded(stat.key || idx) ? 'unfold_less' : 'unfold_more' }}</span>
                        <span>{{ (stat.budget_lines?.length || 0) }} Lines</span>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Expanded Breakdown Drawer Row -->
                <tr v-if="isExpanded(stat.key || idx)" class="drawer-tr">
                  <td colspan="10" class="drawer-td">
                    <div class="drawer-wrapper">
                      <div class="drawer-top-bar">
                        <div class="drawer-heading-group">
                          <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">table_rows</span>
                          <span class="drawer-title">Budget Lines Breakdown</span>
                          <span class="drawer-subtitle">— {{ stat.mandate }}</span>
                        </div>
                        <div class="drawer-mini-metrics mono">
                          <span class="metric-pill metric-budget">
                            <span class="m-lbl">Budget:</span>
                            <span class="m-val">₱{{ formatCurrency(stat.budget) }}</span>
                          </span>
                          <span class="metric-sep">/</span>
                          <span class="metric-pill metric-utilized">
                            <span class="m-lbl">Utilized:</span>
                            <span class="m-val">₱{{ formatCurrency(stat.utilized_budget) }}</span>
                          </span>
                          <span class="metric-sep">/</span>
                          <span class="metric-pill metric-pending">
                            <span class="m-lbl">Pending:</span>
                            <span class="m-val">₱{{ formatCurrency(stat.pending_budget) }}</span>
                          </span>
                          <span class="metric-sep">/</span>
                          <span class="metric-pill" :class="stat.remaining_budget < 0 ? 'metric-deficit' : 'metric-remaining'">
                            <span class="m-lbl">Remaining:</span>
                            <span class="m-val">{{ stat.remaining_budget < 0 ? '-' : '' }}₱{{ formatCurrency(Math.abs(stat.remaining_budget)) }}</span>
                          </span>
                        </div>
                      </div>

                      <!-- Subtable -->
                      <div v-if="stat.budget_lines && stat.budget_lines.length > 0" class="subtable-container">
                        <table class="nested-table">
                          <thead>
                            <tr>
                              <th class="text-center" style="width: 44px;">#</th>
                              <th class="text-left">Budget Line Item</th>
                              <th class="text-right" style="width: 170px;">Original Budget</th>
                              <th class="text-right" style="width: 170px;">Utilized (AR)</th>
                              <th class="text-right" style="width: 170px;">Pending (AD)</th>
                              <th class="text-right" style="width: 170px;">Remaining Balance</th>
                              <th class="text-center" style="width: 140px;">Utilization Rate</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(bl, bidx) in stat.budget_lines" :key="bl.id || bidx">
                              <td class="text-center font-mono text-muted">{{ bidx + 1 }}</td>
                              <td class="text-left font-semibold text-main">{{ bl.label || 'Unnamed Line' }}</td>
                              <td class="text-right font-mono">₱{{ formatCurrency(bl.amount) }}</td>
                              <td class="text-right font-mono text-green font-semibold">₱{{ formatCurrency(bl.utilized_budget) }}</td>
                              <td class="text-right font-mono text-yellow">₱{{ formatCurrency(bl.pending_budget) }}</td>
                              <td 
                                class="text-right font-mono font-bold"
                                :class="((bl.amount || 0) - (bl.utilized_budget || 0) - (bl.pending_budget || 0)) < 0 ? 'text-red' : 'text-blue'"
                              >
                                ₱{{ formatCurrency((bl.amount || 0) - (bl.utilized_budget || 0) - (bl.pending_budget || 0)) }}
                              </td>
                              <td class="text-center">
                                <div class="rate-progress-cell">
                                  <div class="rate-track">
                                    <div 
                                      class="rate-fill"
                                      :style="{ width: Math.min(100, Math.max(0, (bl.amount > 0 ? (bl.utilized_budget / bl.amount) * 100 : 0))) + '%' }"
                                    ></div>
                                  </div>
                                  <span class="rate-text font-mono">
                                    {{ bl.amount > 0 ? ((bl.utilized_budget / bl.amount) * 100).toFixed(0) : 0 }}%
                                  </span>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div v-else class="drawer-empty-state">
                        <span class="material-symbols-outlined empty-drawer-icon">info</span>
                        <span>No itemized budget lines recorded in the GAD Plan for this mandate.</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
            <tfoot class="mandate-tfoot">
              <tr>
                <td colspan="4" class="tfoot-cell text-left font-bold">
                  Total Summary ({{ filteredMandateStats.length }} Mandate{{ filteredMandateStats.length === 1 ? '' : 's' }})
                </td>
                <td class="tfoot-cell text-center">
                  <div class="docs-badge-stack justify-center">
                    <span class="badge-doc badge-ad">{{ totalApprovedAds }} ADs</span>
                    <span class="badge-doc badge-ar">{{ totalApprovedArs }} ARs</span>
                  </div>
                </td>
                <td class="tfoot-cell text-right font-mono font-bold">
                  ₱{{ formatCurrency(totalBudget) }}
                </td>
                <td class="tfoot-cell text-right font-mono font-bold text-green">
                  ₱{{ formatCurrency(totalUtilized) }}
                </td>
                <td class="tfoot-cell text-right font-mono font-bold text-yellow">
                  ₱{{ formatCurrency(totalPending) }}
                </td>
                <td 
                  class="tfoot-cell text-right font-mono font-bold"
                  :class="totalRemaining < 0 ? 'text-red' : 'text-blue'"
                >
                  {{ totalRemaining < 0 ? '-' : '' }}₱{{ formatCurrency(Math.abs(totalRemaining)) }}
                </td>
                <td class="tfoot-cell"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Allocation Modal -->
    <div v-if="showAllocationModal" class="modal-backdrop" @click.self="closeAllocationModal">
      <div class="allocation-modal-card">
        <h2 class="modal-title">Budget Allocations</h2>
        <p class="modal-subtitle">
          Assign specific Accomplishment Report budgets to this mandate.
        </p>

        <div v-if="loadingAllocations" class="modal-loading-box">Loading...</div>
        <div v-else>
          <div v-if="currentAllocationStat && currentAllocationStat.budget_lines && currentAllocationStat.budget_lines.length > 0" class="modal-section-mb">
             <h3 class="modal-subheading">Planned Budget Lines</h3>
             <table class="modal-table">
               <thead>
                 <tr>
                   <th>Budget Line</th>
                   <th>Original Amount</th>
                   <th>Utilized (AR)</th>
                 </tr>
               </thead>
               <tbody>
                 <tr v-for="bl in currentAllocationStat.budget_lines" :key="bl.id">
                   <td class="modal-td-bold">{{ bl.label || 'Unnamed Line' }}</td>
                   <td>₱{{ Number(bl.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
                   <td class="text-green">₱{{ Number(bl.utilized_budget || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
                 </tr>
               </tbody>
             </table>
          </div>

          <div v-if="arVerifiedTotals && arVerifiedTotals.length > 0" class="modal-section-mb">
            <div class="modal-subheading-border">
               Actual Expenditures Breakdown (Verified ARs)
            </div>
            <table class="modal-table">
               <thead>
                 <tr>
                   <th>Expenditure Item</th>
                   <th>Total Cost</th>
                 </tr>
               </thead>
               <tbody>
                 <tr v-for="(tv, idx) in arVerifiedTotals" :key="idx">
                    <td class="modal-td-bold">{{ tv.name }}</td>
                    <td class="text-green font-mono font-bold">₱{{ Number(tv.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
                 </tr>
               </tbody>
            </table>
          </div>

          <div v-if="allocationsData.length === 0" class="modal-empty-box">
            No approved Accomplishment Reports found for this mandate.
          </div>
          <div v-else>
            <div v-for="doc in allocationsData" :key="doc.type + doc.id" class="doc-accordion-box">
              <div class="doc-header" @click="doc._expanded = !doc._expanded">
                <div class="doc-title-row">
                   <span :class="doc.type === 'AR' ? 'badge-ar' : 'badge-ad'">[{{ doc.type }}]</span>
                   <span class="doc-name">{{ doc.title || doc.control_number }}</span>
                   <button v-if="doc.attachment" @click.stop="openDocumentPreview(doc.attachment, doc.type)" class="preview-doc-btn" title="Preview Document">
                     Click here to preview document
                   </button>
                </div>
                <span class="accordion-arrow">{{ doc._expanded ? '▼' : '▶' }}</span>
              </div>
              
              <div v-if="doc._expanded" class="doc-items-container">
                <table class="items-table">
                  <thead>
                    <tr>
                      <th>Item Name</th>
                      <th>Total Cost</th>
                      <th>Allocated To (Budget Line)</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in doc.items" :key="item.id">
                      <td>{{ item.item_name }} <span v-if="item.sub_item" class="sub-item-text">- {{ item.sub_item }}</span></td>
                      <td class="font-mono">₱{{ Number(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</td>
                      <td>
                        <div v-if="item.amount <= 0">
                          <span class="text-muted text-xs">N/A</span>
                        </div>
                        <div v-else-if="getAllocatedElsewhere(item) >= item.amount && !item.gpb_budget_line_id" style="display: flex; flex-direction: column; gap: 4px;">
                          <select class="item-alloc-select item-alloc-disabled" disabled title="Locked: This budget item has been fully assigned to other mandates. It cannot be allocated here.">
                             <option>🔒 Locked (₱0.00 available)</option>
                          </select>
                          <span class="text-xs" style="font-size: 0.7rem; color: #ef4444; font-weight: 600;">Fully allocated elsewhere</span>
                        </div>
                        <div v-else style="display: flex; flex-direction: column; gap: 8px;">
                          <select v-model="item.gpb_budget_line_id" class="item-alloc-select" @change="onBudgetLineChange(item)">
                             <option :value="null">-- Not Allocated --</option>
                             <option v-for="bl in (currentAllocationStat?.budget_lines || [])" :key="bl.id" :value="bl.id">
                                {{ bl.label }} (₱{{ Number(bl.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}) }})
                             </option>
                          </select>
                          <div v-if="item.gpb_budget_line_id" style="display: flex; align-items: center; gap: 4px;">
                            <span class="text-xs text-muted">₱</span>
                            <input type="number" v-model.number="item.allocated_to_current" class="item-alloc-select" style="padding: 4px 8px;" @input="validateAllocation(item)" :max="Math.max(0, item.amount - getAllocatedElsewhere(item))" min="0" step="0.01" placeholder="Amount">
                          </div>
                        </div>
                      </td>
                      <td class="text-xs">
                         <span v-if="item.amount <= 0" class="text-muted" title="This item has no cost to allocate.">No Cost</span>
                         <div v-else>
                             <div v-if="item.gpb_budget_line_id">
                                <span class="status-assigned">Assigned</span>
                             </div>
                             <div v-else-if="getAllocatedElsewhere(item) >= item.amount" class="status-locked" title="This budget item has been fully assigned to other mandates. It cannot be assigned here unless it is removed from the other mandate first.">
                                🔒 Locked
                             </div>
                             <div v-else>
                                <span class="text-muted">Unassigned</span>
                             </div>
                             
                             <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                 (Max allowed: ₱{{ Number(item.amount - getAllocatedElsewhere(item)).toLocaleString('en-US', {minimumFractionDigits: 2}) }})
                             </div>

                             <div v-if="item.allocations && item.allocations.filter(a => !(currentAllocationStat?.gpb_ids || []).includes(parseInt(a.mandate_id))).length > 0" class="mt-2" style="font-size: 0.7rem; border-top: 1px dashed var(--border-color, #cbd5e1); padding-top: 4px;">
                                 <strong style="opacity: 0.8;">Allocated Elsewhere:</strong>
                                 <div v-for="al in item.allocations.filter(a => !(currentAllocationStat?.gpb_ids || []).includes(parseInt(a.mandate_id)))" :key="al.id" style="margin-top: 4px; padding-left: 6px; border-left: 2px solid var(--border-color, #94a3b8);">
                                     <div style="font-weight: 600; opacity: 0.9;">{{ al.mandate_name }}</div>
                                     <div v-if="al.cause_name" style="opacity: 0.75; font-style: italic; margin-bottom: 2px;">{{ al.cause_name }}</div>
                                     <div v-if="al.activity_name" style="opacity: 0.75; margin-bottom: 2px;">• {{ al.activity_name }}</div>
                                     <div style="color: #10b981; font-family: monospace; font-weight: 600;">₱{{ Number(al.allocated_amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</div>
                                 </div>
                             </div>
                         </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
           <button @click="closeAllocationModal" class="btn-cancel">Cancel</button>
           <button @click="saveAllocations" :disabled="savingAllocations || !allocationsDirty" class="btn-save" :style="{ opacity: allocationsDirty ? 1 : 0.5 }">
             {{ savingAllocations ? 'Saving...' : 'Save Allocations' }}
           </button>
        </div>
      </div>
    </div>

    <PdfPreviewModal :isOpen="isPdfModalOpen" :fileUrl="pdfFileUrl" @close="closePdfModal" />


  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';
import api from '../../api';
import PdfPreviewModal from '../../components/PdfPreviewModal.vue';

export default {
  name: 'BudgetDistributionView',
  components: { PdfPreviewModal },
  setup() {
    const mandateStats = ref([]);
    const mandateStatsFilter = ref('all');
    const searchQuery = ref('');
    const isFitScreen = ref(false);
    const toggleFitScreen = () => { isFitScreen.value = !isFitScreen.value; };



    const filteredMandateStats = computed(() => {
      let list = mandateStats.value;
      if (mandateStatsFilter.value !== 'all') {
        list = list.filter(s => s.classification === mandateStatsFilter.value);
      }
      const q = searchQuery.value.trim().toLowerCase();
      if (q) {
        list = list.filter(s =>
          (s.mandate || '').toLowerCase().includes(q) ||
          (s.cause || '').toLowerCase().includes(q) ||
          (s.activity || '').toLowerCase().includes(q)
        );
      }
      return list;
    });
    const expandedRows = ref(new Set());
    const toggleRow = (key) => {
      if (expandedRows.value.has(key)) {
        expandedRows.value.delete(key);
      } else {
        expandedRows.value.add(key);
      }
      expandedRows.value = new Set(expandedRows.value);
    };
    const isExpanded = (key) => expandedRows.value.has(key);
    const allExpanded = computed(() => {
      return filteredMandateStats.value.length > 0 && expandedRows.value.size === filteredMandateStats.value.length;
    });
    const toggleAllRows = () => {
      if (allExpanded.value) {
        expandedRows.value = new Set();
      } else {
        expandedRows.value = new Set(filteredMandateStats.value.map((s, idx) => s.key || idx));
      }
    };

    const formatCurrency = (val) => {
      return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const getClassificationLabel = (val) => {
      if (!val) return 'Unclassified';
      const v = String(val).toLowerCase();
      if (v === 'client') return 'Client-Focused';
      if (v === 'org') return 'Org-Focused';
      if (v === 'attributed') return 'Attributed Program';
      return val;
    };

    const getClassificationClass = (val) => {
      const v = String(val).toLowerCase();
      if (v === 'client') return 'pill-client';
      if (v === 'org') return 'pill-org';
      if (v === 'attributed') return 'pill-attributed';
      return 'pill-default';
    };

    const totalBudget = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.budget) || 0), 0);
    });
    const totalUtilized = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.utilized_budget) || 0), 0);
    });
    const totalPending = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.pending_budget) || 0), 0);
    });
    const totalRemaining = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.remaining_budget) || 0), 0);
    });
    const totalApprovedAds = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.approved_ad_count) || 0), 0);
    });
    const totalApprovedArs = computed(() => {
      return filteredMandateStats.value.reduce((sum, s) => sum + (Number(s.approved_ar_count) || 0), 0);
    });

    const loadingStats = ref(true);
    
    const fetchMandateStats = async () => {
      loadingStats.value = true;
      try {
        const response = await api.get('/plan/mandate-statistics');
        if (response.data.success) {
          mandateStats.value = response.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch mandate stats', err);
      } finally {
        loadingStats.value = false;
      }
    };

    const showAllocationModal = ref(false);
    const loadingAllocations = ref(false);
    const savingAllocations = ref(false);
    const allocationsData = ref([]);
    const currentAllocationStat = ref(null);
    const allocationsDirty = ref(false);

    const arVerifiedTotals = computed(() => {
      const totals = {};
      for (const doc of allocationsData.value) {
        if (doc.type === 'AR') {
          for (const item of doc.items) {
            const name = item.item_name || 'Unspecified Item';
            if (!totals[name]) {
              totals[name] = 0;
            }
            totals[name] += parseFloat(item.amount) || 0;
          }
        }
      }
      return Object.entries(totals).map(([name, amount]) => ({ name, amount }));
    });

    const openAllocationModal = async (stat) => {
      if (stat && stat.gpb_ids) {
        stat.gpb_ids = stat.gpb_ids.map(id => parseInt(id));
      }
      currentAllocationStat.value = stat;
      showAllocationModal.value = true;
      loadingAllocations.value = true;
      allocationsDirty.value = false;
      allocationsData.value = [];
      
      try {
        const res = await api.get(`/plan/mandate-allocations?gpb_ids=${stat.gpb_ids.join(',')}`);
        if (res.data.success) {
           allocationsData.value = res.data.data.filter(d => d.type === 'AR').map(d => ({ ...d, _expanded: true }));
        } else {
           Swal.fire('Error', res.data.message || 'Failed to load allocations.', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Network error while loading allocations.', 'error');
      } finally {
        loadingAllocations.value = false;
      }
    };

    const closeAllocationModal = () => {
       showAllocationModal.value = false;
       currentAllocationStat.value = null;
    };

    const markAllocationsDirty = () => { allocationsDirty.value = true; };

    const onBudgetLineChange = (item) => {
       markAllocationsDirty();
       const maxAllowed = item.amount - getAllocatedElsewhere(item);
       if (item.gpb_budget_line_id && maxAllowed <= 0) {
          item.gpb_budget_line_id = null;
          item.allocated_to_current = 0;
          Swal.fire({
              icon: 'warning',
              title: 'Item Locked',
              text: 'This budget item has been fully allocated to other mandates and cannot be allocated here.',
              confirmButtonColor: '#b979cc'
          });
          return;
       }
       if (item.gpb_budget_line_id && (!item.allocated_to_current || item.allocated_to_current <= 0)) {
          item.allocated_to_current = maxAllowed;
       }
    };

    const isCurrentMandate = (mandateId) => {
       if (!currentAllocationStat.value || !currentAllocationStat.value.gpb_ids) return false;
       const targetId = String(mandateId);
       return currentAllocationStat.value.gpb_ids.some(id => String(id) === targetId);
    };

    const getAllocatedElsewhere = (item) => {
       if (!item.allocations || !currentAllocationStat.value) return 0;
       return item.allocations.reduce((sum, al) => {
          if (!isCurrentMandate(al.mandate_id)) {
              return sum + parseFloat(al.allocated_amount || 0);
          }
          return sum;
       }, 0);
    };

    const validateAllocation = (item) => {
       markAllocationsDirty();
       const maxAllowed = Math.max(0, item.amount - getAllocatedElsewhere(item));
       if (maxAllowed <= 0) {
           item.gpb_budget_line_id = null;
           item.allocated_to_current = 0;
           Swal.fire({
               icon: 'warning',
               title: 'Item Locked',
               text: 'This budget item is locked because it is fully allocated to other mandates.',
               confirmButtonColor: '#b979cc'
           });
           return;
       }
       if (item.allocated_to_current > maxAllowed) {
           Swal.fire({
               icon: 'warning',
               title: 'Exceeds Maximum Allowed',
               text: `The allocated amount cannot exceed the available cost of ₱${Number(maxAllowed).toLocaleString('en-US', {minimumFractionDigits: 2})}.`,
               confirmButtonColor: '#b979cc'
           });
           item.allocated_to_current = maxAllowed;
       }
    };

    const saveAllocations = async () => {
       // Validate all items before saving to ensure no limits are bypassed
       for (const doc of allocationsData.value) {
           for (const item of doc.items) {
               const allocatedElsewhere = getAllocatedElsewhere(item);
               const maxAllowed = Math.max(0, item.amount - allocatedElsewhere);

               // If this item is locked because it is fully allocated to other mandates,
               // ensure it is not allocated to the current mandate and skip validation so it doesn't block saving
               if (allocatedElsewhere >= item.amount) {
                   item.gpb_budget_line_id = null;
                   item.allocated_to_current = 0;
                   continue;
               }

               if (item.gpb_budget_line_id) {
                   const val = parseFloat(item.allocated_to_current) || 0;
                   if (val > maxAllowed) {
                       Swal.fire('Validation Error', `Allocated amount for "${item.item_name}" exceeds the maximum allowed (₱${Number(maxAllowed).toLocaleString('en-US', {minimumFractionDigits: 2})}).`, 'error');
                       return;
                   }
               }
           }
       }

       savingAllocations.value = true;
       
       const flatAllocs = [];
       for (const doc of allocationsData.value) {
           for (const item of doc.items) {
               let val = 0;
               let gpbLineId = item.gpb_budget_line_id;
               
               if (gpbLineId) {
                   val = parseFloat(item.allocated_to_current) || 0;
               }

               flatAllocs.push({
                   budget_item_id: item.id,
                   item_type: doc.type,
                   allocated_amount: val,
                   gpb_budget_line_id: gpbLineId
               });
           }
       }

       try {
          const res = await api.post('/plan/mandate-allocations', {
             gpb_ids: currentAllocationStat.value.gpb_ids,
             allocations: flatAllocs
          });
          if (res.data.success) {
             allocationsDirty.value = false;
             closeAllocationModal();
             await fetchMandateStats();
             Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Allocations saved successfully', showConfirmButton: false, timer: 3000 });
          } else {
             Swal.fire('Error', res.data.message || 'Failed to save allocations.', 'error');
          }
       } catch (err) {
          Swal.fire('Error', 'Network error while saving allocations.', 'error');
       } finally {
          savingAllocations.value = false;
       }
    };

    const isPdfModalOpen = ref(false);
    const pdfFileUrl = ref('');

    const openDocumentPreview = (attachment, type) => {
      if (attachment) {
        let fileName = attachment;
        if (typeof attachment === 'string' && attachment.startsWith('[')) {
           try {
               const parsed = JSON.parse(attachment);
               if (parsed.length > 0) fileName = parsed[0];
           } catch(e) {}
        }
        pdfFileUrl.value = `${import.meta.env.VITE_API_BASE_URL.replace(/\/api\/?$/, '')}/api/files/archived/${fileName}`;
        isPdfModalOpen.value = true;
      }
    };
    const closePdfModal = () => {
      isPdfModalOpen.value = false;
      pdfFileUrl.value = '';
    };

    onMounted(async () => {
      fetchMandateStats();
    });

    return {
      mandateStats, mandateStatsFilter, searchQuery, filteredMandateStats, loadingStats,
      expandedRows, toggleRow, isExpanded, allExpanded, toggleAllRows, formatCurrency,
      getClassificationLabel, getClassificationClass, totalBudget, totalUtilized, totalPending,
      totalRemaining, totalApprovedAds, totalApprovedArs,
      fetchMandateStats,
      showAllocationModal, loadingAllocations, savingAllocations, allocationsData, currentAllocationStat,
      allocationsDirty, openAllocationModal, closeAllocationModal, markAllocationsDirty, onBudgetLineChange,
      getAllocatedElsewhere, isCurrentMandate, validateAllocation, saveAllocations, arVerifiedTotals,
      isPdfModalOpen, pdfFileUrl, openDocumentPreview, closePdfModal,
      isFitScreen, toggleFitScreen
    };
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&display=swap');

*, *::before, *::after { box-sizing: border-box; }

.app-wrapper {
  margin: 0;
  padding: 0;
  background: #ffffff;
  color: #0f172a;
  font-size: 15.5px;
  line-height: 1.6;
  min-height: calc(100vh - 80px);
}
.font-mono { font-family: 'IBM Plex Mono', monospace; }
.font-bold { font-weight: 700; }
.text-xs { font-size: 0.75rem; }
.text-muted { color: #64748b; }

/* Top Banner */
.banner-wrapper {
  min-width: 1200px;
  margin: 32px 32px 0 32px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  background: #ffffff;
  display: flex;
  flex-direction: column;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 24px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
}

.topbar-brand {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 180px;
  margin-right: 6px;
  border-right: 1px solid #e2e8f0;
  padding-right: 18px;
}
.topbar-eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #7e22ce;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
}
.topbar-title {
  font-size: 1.125rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  white-space: nowrap;
}

.topbar-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-shrink: 0;
}
.topbar-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 0.8125rem;
  font-weight: 700;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  transition: all 0.2s;
  cursor: pointer;
}
.topbar-btn:hover {
  background: #f8fafc;
  border-color: #94a3b8;
  color: #0f172a;
}

/* Main Card */
.main-card {
  min-width: 1200px;
  margin: 24px 32px 32px 32px;
  padding: 24px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
}

.card-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
}
.card-title {
  color: #0f172a;
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.filter-label {
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 600;
}
.filter-select {
  background: #f8fafc url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%237e22ce' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E") no-repeat right 0.75rem center/1.25rem 1.25rem;
  appearance: none;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  padding: 6px 32px 6px 12px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-select:focus {
  border-color: #9333ea;
  background-color: #ffffff;
  box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.15);
}
.filter-select option {
  background: #ffffff;
  color: #0f172a;
}

/* Stats and Search Row */
.stats-search-row {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 16px;
  align-items: stretch;
  margin-bottom: 24px;
}
.stat-pill {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.stat-icon-wrap {
  background: rgba(147, 51, 234, 0.1);
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #7e22ce;
  border: 1px solid rgba(147, 51, 234, 0.2);
}
.stat-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.stat-title {
  font-size: 0.6875rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.stat-count {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.search-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  transition: all 0.2s;
}
.search-wrap.search-active, .search-wrap:focus-within {
  border-color: #9333ea;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.15);
}
.search-icon {
  color: #64748b;
  font-size: 20px;
  flex-shrink: 0;
}
.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 12px 0;
  color: #0f172a;
  font-size: 0.875rem;
  outline: none;
}
.search-clear-btn {
  background: #e2e8f0;
  border: 1px solid #cbd5e1;
  color: #475569;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}
.search-clear-btn:hover {
  background: #cbd5e1;
  color: #0f172a;
}

/* State Boxes */
.state-message-box {
  text-align: center;
  color: #64748b;
  padding: 40px;
}
.empty-state-card {
  text-align: center;
  color: #64748b;
  padding: 40px;
}
.empty-icon {
  font-size: 2.25rem;
  display: block;
  margin-bottom: 12px;
}
.empty-title {
  color: #0f172a;
  font-weight: 800;
  margin-bottom: 8px;
}
.empty-desc {
  font-size: 0.9rem;
}
.plan-link {
  color: #7e22ce;
  font-weight: 600;
  text-decoration: underline;
}

/* Stats Summary Strip */
.stats-summary-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}
.stat-pill {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: all 0.2s ease;
}
.stat-pill:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.stat-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(126, 34, 206, 0.1);
  color: #7e22ce;
}
.stat-icon-wrap.stat-purple {
  background: rgba(147, 51, 234, 0.1);
  color: #9333ea;
}
.stat-icon-wrap.stat-emerald {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}
.stat-icon-wrap.stat-blue {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
}
.stat-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.stat-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
}
.stat-count {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

/* Table Toolbar Actions */
.table-actions-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.btn-toggle-all {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #334155;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-toggle-all:hover {
  background: #e2e8f0;
  color: #0f172a;
}
.results-counter {
  font-size: 0.85rem;
  color: #64748b;
}
.results-counter b {
  color: #7e22ce;
}

/* Mandate Table Container */
.mandate-table-container {
  width: 100%;
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-top: 8px;
}

.app-wrapper.fit-screen .mandate-table {
  table-layout: fixed;
  min-width: 0;
}

.app-wrapper.fit-screen .mandate-table th,
.app-wrapper.fit-screen .mandate-table td {
  padding-left: 6px;
  padding-right: 6px;
  font-size: 0.78rem;
  overflow-wrap: anywhere;
}

.app-wrapper.fit-screen .mandate-table .th-mandate,
.app-wrapper.fit-screen .mandate-table .th-activity {
  min-width: 0 !important;
}
.mandate-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
  min-width: 1100px;
}
.mandate-th-row {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}
.mandate-table th {
  padding: 14px 16px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #475569;
  white-space: nowrap;
}
.mandate-row {
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.mandate-row:hover {
  background-color: #f8fafc;
}
.mandate-row.row-expanded {
  background-color: #faf5ff;
}
.td-cell {
  padding: 14px 16px;
  vertical-align: middle;
  color: #1e293b;
}

/* Expand toggle chevron */
.expand-toggle-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  padding: 4px;
  border-radius: 6px;
  transition: transform 0.2s ease, color 0.2s ease;
}
.expand-toggle-btn:hover {
  color: #7e22ce;
  background: rgba(126, 34, 206, 0.08);
}
.expand-toggle-btn.is-rotated {
  transform: rotate(90deg);
  color: #7e22ce;
}

/* Mandate Cell */
.mandate-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.pill-row {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 2px;
}
.classification-pill {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
}
.pill-client {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
.pill-org {
  background: #faf5ff;
  color: #7e22ce;
  border: 1px solid #e9d5ff;
}
.pill-attributed {
  background: #fdf2f8;
  color: #be185d;
  border: 1px solid #fbcfe8;
}
.pill-default {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.mandate-name-text {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.925rem;
  line-height: 1.4;
}
.mandate-cause-box {
  font-size: 0.775rem;
  color: #64748b;
  line-height: 1.35;
  margin-top: 2px;
}
.cause-prefix {
  font-weight: 700;
  color: #8b5cf6;
}

/* Activity text */
.activity-text {
  font-size: 0.85rem;
  color: #334155;
  line-height: 1.4;
}

/* Docs badge stack */
.docs-badge-stack {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.badge-doc {
  font-size: 0.725rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  white-space: nowrap;
}
.badge-doc.badge-ad {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}
.badge-doc.badge-ar {
  background: #d1fae5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

/* Amounts */
.amount-val {
  font-size: 0.95rem;
}
.lines-count-hint {
  font-size: 0.7rem;
  color: #94a3b8;
  margin-top: 2px;
}
.util-percent-hint {
  font-size: 0.725rem;
  font-weight: 700;
  margin-top: 2px;
}
.deficit-chip {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 800;
  background: #fee2e2;
  color: #b91c1c;
  padding: 1px 6px;
  border-radius: 4px;
  margin-top: 2px;
}

/* Actions in Table */
.actions-flex {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.tbl-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 0.775rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.btn-alloc {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #2563eb;
}
.btn-alloc:hover {
  background: #2563eb;
  color: #ffffff;
}
.btn-lines-toggle {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
}
.btn-lines-toggle:hover,
.btn-lines-toggle.btn-lines-active {
  background: #7e22ce;
  border-color: #7e22ce;
  color: #ffffff;
}

/* Drawer Row */
.drawer-tr {
  background: #fbfbfe;
  border-bottom: 2px solid #e2e8f0;
}
.drawer-td {
  padding: 16px 20px !important;
}
.drawer-wrapper {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
}
.drawer-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 14px;
}
.drawer-heading-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.drawer-title {
  font-weight: 800;
  color: #0f172a;
  font-size: 0.925rem;
}
.drawer-subtitle {
  color: #64748b;
  font-size: 0.85rem;
}
.drawer-mini-metrics {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.825rem;
  flex-wrap: wrap;
}
.metric-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
.metric-pill .m-lbl {
  color: #64748b;
  font-weight: 600;
  font-size: 0.775rem;
}
.metric-pill .m-val {
  font-weight: 700;
  color: #0f172a;
}
.metric-budget .m-val {
  color: #2563eb;
}
.metric-utilized .m-val {
  color: #059669;
}
.metric-pending .m-val {
  color: #d97706;
}
.metric-remaining .m-val {
  color: #0284c7;
}
.metric-deficit {
  background: #fef2f2 !important;
  border-color: #fecaca !important;
}
.metric-deficit .m-lbl {
  color: #b91c1c !important;
}
.metric-deficit .m-val {
  color: #dc2626 !important;
}
.metric-sep {
  color: #94a3b8;
  font-weight: 700;
  opacity: 0.5;
}

/* Sub-table */
.subtable-container {
  overflow-x: auto;
}
.nested-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.nested-table thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.nested-table th {
  padding: 10px 14px;
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
}
.nested-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
}
.nested-table tbody tr:last-child {
  border-bottom: none;
}
.nested-table td {
  padding: 10px 14px;
  vertical-align: middle;
}
.rate-progress-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
}
.rate-track {
  width: 70px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}
.rate-fill {
  height: 100%;
  background: #10b981;
  border-radius: 4px;
}
.rate-text {
  font-size: 0.75rem;
  font-weight: 700;
  color: #059669;
  min-width: 34px;
  text-align: right;
}
.drawer-empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 18px;
  color: #64748b;
  font-size: 0.85rem;
  background: #f8fafc;
  border-radius: 6px;
}

/* Table Footer */
.mandate-tfoot {
  background: #f8fafc;
  border-top: 2px solid #cbd5e1;
}
.tfoot-cell {
  padding: 14px 16px;
  font-size: 0.875rem;
  color: #0f172a;
  white-space: nowrap;
}

.manage-alloc-btn {
  width: 100%;
  padding: 10px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #2563eb;
  font-weight: 700;
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.manage-alloc-btn:hover {
  background: rgba(59, 130, 246, 0.2);
  color: #1d4ed8;
}

/* Modal */
.modal-backdrop {
  z-index: 1000;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}
.allocation-modal-card {
  width: 100%;
  max-width: 840px;
  max-height: 90vh;
  overflow-y: auto;
  background: #ffffff;
  padding: 28px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
.modal-title {
  margin-bottom: 8px;
  color: #0f172a;
  font-weight: 800;
  font-size: 1.35rem;
}
.modal-subtitle {
  color: #64748b;
  margin-bottom: 24px;
  font-size: 0.9rem;
}
.modal-loading-box {
  padding: 20px;
  text-align: center;
  color: #64748b;
}
.modal-section-mb {
  margin-bottom: 24px;
}
.modal-subheading {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 12px;
}
.modal-subheading-border {
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 12px;
  color: #0f172a;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 8px;
}
.modal-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.modal-table thead tr {
  background: #f8fafc;
  text-align: left;
  color: #475569;
}
.modal-table th {
  padding: 10px;
  font-weight: 700;
}
.modal-table tbody tr {
  border-top: 1px solid #e2e8f0;
}
.modal-table td {
  padding: 10px;
  color: #334155;
}
.modal-td-bold {
  color: #0f172a !important;
  font-weight: 600;
}
.modal-empty-box {
  padding: 20px;
  text-align: center;
  color: #64748b;
}

.doc-accordion-box {
  margin-bottom: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.doc-header {
  background: #f8fafc;
  padding: 12px 16px;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
}
.doc-title-row {
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.badge-ar { color: #10b981; font-weight: 700; }
.badge-ad { color: #d97706; font-weight: 700; }
.doc-name { font-weight: 600; }
.preview-doc-btn {
  background: transparent;
  border: none;
  color: #2563eb;
  cursor: pointer;
  text-decoration: underline;
  font-size: 0.85rem;
  padding: 0 4px;
}
.accordion-arrow {
  color: #64748b;
  font-size: 0.75rem;
}
.doc-items-container {
  padding: 16px;
  background: #ffffff;
}
.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.items-table thead tr {
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
  color: #64748b;
}
.items-table th {
  padding: 8px;
  font-weight: 600;
}
.items-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
}
.items-table td {
  padding: 12px 8px;
  color: #0f172a;
}
.sub-item-text {
  color: #64748b;
  font-size: 0.8rem;
}
.item-alloc-select {
  width: 200px;
  padding: 6px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  border-radius: 6px;
  outline: none;
  font-size: 0.85rem;
}
.item-alloc-select:disabled,
.item-alloc-disabled {
  background: #f1f5f9 !important;
  color: #94a3b8 !important;
  cursor: not-allowed !important;
  border-color: #cbd5e1 !important;
  opacity: 0.8;
}
.status-assigned { color: #10b981; font-weight: 600; }
.status-locked { color: #ef4444; font-weight: 600; }

.modal-footer {
  margin-top: 24px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  border-top: 1px solid #e2e8f0;
  padding-top: 16px;
}
.btn-cancel {
  padding: 8px 16px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.btn-cancel:hover {
  background: #f8fafc;
  color: #0f172a;
}
.btn-save {
  padding: 8px 18px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-save:hover:not(:disabled) {
  background: #1d4ed8;
}
</style>

<style>
/* ==========================================================================
   Dark Mode Overrides for Budget Distribution by Mandate
   Outer background remains white; ONLY the cards darken!
   ========================================================================== */
html.dark .banner-wrapper,
.dark .banner-wrapper {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
  border-color: rgba(185, 121, 204, 0.25) !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25) !important;
}

html.dark .topbar,
.dark .topbar {
  background: transparent !important;
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
}

html.dark .topbar-brand,
.dark .topbar-brand {
  border-right-color: rgba(185, 121, 204, 0.2) !important;
}

html.dark .topbar-eyebrow,
.dark .topbar-eyebrow {
  color: #c084fc !important;
}

html.dark .topbar-title,
.dark .topbar-title {
  color: #ffffff !important;
}

html.dark .topbar-btn,
.dark .topbar-btn {
  background: rgba(0, 0, 0, 0.3) !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  color: #cbd5e1 !important;
}
html.dark .topbar-btn:hover,
.dark .topbar-btn:hover {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #ffffff !important;
}

html.dark .main-card,
.dark .main-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
  border-color: rgba(185, 121, 204, 0.25) !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25) !important;
}

html.dark .card-title,
.dark .card-title {
  color: #ffffff !important;
}

html.dark .filter-label,
.dark .filter-label {
  color: #cbd5e1 !important;
}

html.dark .filter-select,
.dark .filter-select {
  background: rgba(0, 0, 0, 0.4) url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23b979cc' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E") no-repeat right 0.75rem center/1.25rem 1.25rem !important;
  border-color: rgba(185, 121, 204, 0.4) !important;
  color: #ffffff !important;
}
html.dark .filter-select option,
.dark .filter-select option {
  background: #1e293b !important;
  color: #ffffff !important;
}

html.dark .stat-pill,
.dark .stat-pill {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1)) !important;
  border-color: rgba(99, 102, 241, 0.3) !important;
}
html.dark .stat-icon-wrap,
.dark .stat-icon-wrap {
  background: rgba(99, 102, 241, 0.2) !important;
  color: #a5b4fc !important;
  border-color: rgba(99, 102, 241, 0.3) !important;
}
html.dark .stat-title,
.dark .stat-title {
  color: #cbd5e1 !important;
}
html.dark .stat-count,
.dark .stat-count {
  color: #ffffff !important;
}

html.dark .search-wrap,
.dark .search-wrap {
  background: rgba(0, 0, 0, 0.3) !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
}
html.dark .search-wrap.search-active,
.dark .search-wrap.search-active,
html.dark .search-wrap:focus-within,
.dark .search-wrap:focus-within {
  border-color: rgba(185, 121, 204, 0.8) !important;
  background: rgba(0, 0, 0, 0.5) !important;
}
html.dark .search-icon,
.dark .search-icon {
  color: #cbd5e1 !important;
}
html.dark .search-input,
.dark .search-input {
  color: #ffffff !important;
}
html.dark .search-clear-btn,
.dark .search-clear-btn {
  background: rgba(255, 255, 255, 0.08) !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #cbd5e1 !important;
}

html.dark .state-message-box,
.dark .state-message-box {
  color: #cbd5e1 !important;
}
html.dark .empty-state-card,
.dark .empty-state-card {
  color: #cbd5e1 !important;
}
html.dark .empty-title,
.dark .empty-title {
  color: #ffffff !important;
}
html.dark .plan-link,
.dark .plan-link {
  color: #c084fc !important;
}

/* Table Dark Mode Overrides */
html.dark .stats-summary-strip .stat-pill,
.dark .stats-summary-strip .stat-pill {
  background: rgba(0, 0, 0, 0.25) !important;
  border-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .stat-title,
.dark .stat-title {
  color: #94a3b8 !important;
}
html.dark .stat-count,
.dark .stat-count {
  color: #ffffff !important;
}
html.dark .btn-toggle-all,
.dark .btn-toggle-all {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  color: #e2e8f0 !important;
}
html.dark .btn-toggle-all:hover,
.dark .btn-toggle-all:hover {
  background: rgba(255, 255, 255, 0.1) !important;
  color: #ffffff !important;
}

html.dark .mandate-table-container,
.dark .mandate-table-container {
  background: rgba(0, 0, 0, 0.2) !important;
  border-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .mandate-th-row,
.dark .mandate-th-row {
  background: rgba(0, 0, 0, 0.35) !important;
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .mandate-table th,
.dark .mandate-table th {
  color: #cbd5e1 !important;
}
html.dark .mandate-row,
.dark .mandate-row {
  border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
html.dark .mandate-row:hover,
.dark .mandate-row:hover {
  background-color: rgba(255, 255, 255, 0.04) !important;
}
html.dark .mandate-row.row-expanded,
.dark .mandate-row.row-expanded {
  background-color: rgba(126, 34, 206, 0.12) !important;
}
html.dark .td-cell,
.dark .td-cell {
  color: #e2e8f0 !important;
}
html.dark .mandate-name-text,
.dark .mandate-name-text {
  color: #ffffff !important;
}
html.dark .mandate-cause-box,
.dark .mandate-cause-box {
  color: #94a3b8 !important;
}
html.dark .activity-text,
.dark .activity-text {
  color: #cbd5e1 !important;
}

html.dark .pill-client,
.dark .pill-client {
  background: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
  border-color: rgba(59, 130, 246, 0.35) !important;
}
html.dark .pill-org,
.dark .pill-org {
  background: rgba(168, 85, 247, 0.2) !important;
  color: #d8b4fe !important;
  border-color: rgba(168, 85, 247, 0.35) !important;
}
html.dark .pill-attributed,
.dark .pill-attributed {
  background: rgba(236, 72, 153, 0.2) !important;
  color: #f472b6 !important;
  border-color: rgba(236, 72, 153, 0.35) !important;
}
html.dark .pill-default,
.dark .pill-default {
  background: rgba(255, 255, 255, 0.05) !important;
  color: #cbd5e1 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

html.dark .badge-doc.badge-ad,
.dark .badge-doc.badge-ad {
  background: rgba(217, 119, 6, 0.2) !important;
  color: #fcd34d !important;
  border-color: rgba(217, 119, 6, 0.35) !important;
}
html.dark .badge-doc.badge-ar,
.dark .badge-doc.badge-ar {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

html.dark .btn-lines-toggle,
.dark .btn-lines-toggle {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  color: #cbd5e1 !important;
}

html.dark .drawer-tr,
.dark .drawer-tr {
  background: rgba(0, 0, 0, 0.15) !important;
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .drawer-wrapper,
.dark .drawer-wrapper {
  background: #1e293b !important;
  border-color: rgba(185, 121, 204, 0.25) !important;
}
html.dark .drawer-top-bar,
.dark .drawer-top-bar {
  border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
html.dark .drawer-title,
.dark .drawer-title {
  color: #ffffff !important;
}
html.dark .drawer-subtitle,
.dark .drawer-subtitle {
  color: #cbd5e1 !important;
}
html.dark .metric-pill,
.dark .metric-pill {
  background: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}
html.dark .metric-pill .m-lbl,
.dark .metric-pill .m-lbl {
  color: #94a3b8 !important;
}
html.dark .metric-budget .m-val,
.dark .metric-budget .m-val {
  color: #93c5fd !important;
}
html.dark .metric-utilized .m-val,
.dark .metric-utilized .m-val {
  color: #6ee7b7 !important;
}
html.dark .metric-pending .m-val,
.dark .metric-pending .m-val {
  color: #fcd34d !important;
}
html.dark .metric-remaining .m-val,
.dark .metric-remaining .m-val {
  color: #38bdf8 !important;
}
html.dark .metric-deficit,
.dark .metric-deficit {
  background: rgba(239, 68, 68, 0.15) !important;
  border-color: rgba(239, 68, 68, 0.35) !important;
}
html.dark .metric-deficit .m-lbl,
.dark .metric-deficit .m-lbl {
  color: #fca5a5 !important;
}
html.dark .metric-deficit .m-val,
.dark .metric-deficit .m-val {
  color: #f87171 !important;
}
html.dark .metric-sep,
.dark .metric-sep {
  color: #64748b !important;
}
html.dark .nested-table,
.dark .nested-table {
  border-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .nested-table thead tr,
.dark .nested-table thead tr {
  background: rgba(0, 0, 0, 0.25) !important;
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .nested-table th,
.dark .nested-table th {
  color: #cbd5e1 !important;
}
html.dark .nested-table tbody tr,
.dark .nested-table tbody tr {
  border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
html.dark .nested-table td,
.dark .nested-table td {
  color: #e2e8f0 !important;
}
html.dark .rate-track,
.dark .rate-track {
  background: rgba(255, 255, 255, 0.1) !important;
}
html.dark .drawer-empty-state,
.dark .drawer-empty-state {
  background: rgba(0, 0, 0, 0.2) !important;
  color: #94a3b8 !important;
}
html.dark .mandate-tfoot,
.dark .mandate-tfoot {
  background: rgba(0, 0, 0, 0.35) !important;
  border-top-color: rgba(185, 121, 204, 0.3) !important;
}
html.dark .tfoot-cell,
.dark .tfoot-cell {
  color: #ffffff !important;
}


/* Modal dark overrides */
html.dark .allocation-modal-card,
.dark .allocation-modal-card {
  background: #1e293b !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
}
html.dark .modal-title,
.dark .modal-title {
  color: #ffffff !important;
}
html.dark .modal-subtitle,
.dark .modal-subtitle {
  color: #94a3b8 !important;
}
html.dark .modal-subheading,
.dark .modal-subheading,
html.dark .modal-subheading-border,
.dark .modal-subheading-border {
  color: #ffffff !important;
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .modal-table,
.dark .modal-table {
  border-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .modal-table thead tr,
.dark .modal-table thead tr {
  background: rgba(0, 0, 0, 0.25) !important;
  color: #cbd5e1 !important;
}
html.dark .modal-table tbody tr,
.dark .modal-table tbody tr {
  border-top-color: rgba(255, 255, 255, 0.05) !important;
}
html.dark .modal-table td,
.dark .modal-table td {
  color: #cbd5e1 !important;
}
html.dark .modal-td-bold,
.dark .modal-td-bold {
  color: #ffffff !important;
}

html.dark .doc-accordion-box,
.dark .doc-accordion-box {
  border-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .doc-header,
.dark .doc-header {
  background: rgba(0, 0, 0, 0.25) !important;
}
html.dark .doc-title-row,
.dark .doc-title-row {
  color: #ffffff !important;
}
html.dark .doc-items-container,
.dark .doc-items-container {
  background: rgba(255, 255, 255, 0.02) !important;
}
html.dark .items-table thead tr,
.dark .items-table thead tr {
  border-bottom-color: rgba(185, 121, 204, 0.2) !important;
  color: #94a3b8 !important;
}
html.dark .items-table tbody tr,
.dark .items-table tbody tr {
  border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
html.dark .items-table td,
.dark .items-table td {
  color: #ffffff !important;
}
html.dark .sub-item-text,
.dark .sub-item-text {
  color: #94a3b8 !important;
}
html.dark .item-alloc-select,
.dark .item-alloc-select {
  background: #1e293b !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  color: #ffffff !important;
}
html.dark .item-alloc-select:disabled,
.dark .item-alloc-select:disabled,
html.dark .item-alloc-disabled,
.dark .item-alloc-disabled {
  background: rgba(255, 255, 255, 0.05) !important;
  color: #64748b !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}
html.dark .modal-footer,
.dark .modal-footer {
  border-top-color: rgba(185, 121, 204, 0.2) !important;
}
html.dark .btn-cancel,
.dark .btn-cancel {
  background: transparent !important;
  border-color: rgba(185, 121, 204, 0.3) !important;
  color: #cbd5e1 !important;
}
html.dark .btn-cancel:hover,
.dark .btn-cancel:hover {
  background: rgba(255, 255, 255, 0.05) !important;
  color: #ffffff !important;
}
</style>
