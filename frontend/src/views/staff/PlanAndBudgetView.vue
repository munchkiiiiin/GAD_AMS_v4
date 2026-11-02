<template>
  <div class="app-wrapper app" style="width: 100%; overflow-x: auto;">
    <div class="gpb-container">
      <!-- TOP BAR -->
      <header class="topbar">
        <div class="topbar-brand">
          <span class="topbar-eyebrow">GAD Plan &amp; Budget Editor</span>
          <h1 class="topbar-title" id="orgTitle">{{ state.org.name || 'Untitled Organization' }}</h1>
        </div>
  
        <!-- nav removed -->
  
        <div class="topbar-actions">
          <router-link to="/staff/budget-allocation"><button class="topbar-btn primary">Allocation Worklist</button></router-link>
          <router-link to="/staff/budget-distribution"><button class="topbar-btn secondary btn-budget-dist">Budget Distribution</button></router-link>
          <router-link to="/staff/budget-history"><button class="topbar-btn secondary">Budget History</button></router-link>
          <button class="topbar-btn outline btn-baseline" @click="openBaselineModal">
            ⚙️ Baseline Amounts
          </button>
        <button id="btnExpandAll" class="topbar-btn outline" @click="expandAll">Expand All</button>
        <button id="btnCollapseAll" class="topbar-btn outline" @click="collapseAll">Collapse All</button>
        <button id="btnExport" class="topbar-btn primary" @click="exportToExcel" :disabled="exporting">
          <span>📥</span> {{ exporting ? 'Exporting…' : 'Export to Excel' }}
        </button>
        <button id="btnImportExcel" class="topbar-btn primary" @click="promptExcelImport">
           <span>📤</span> Import Excel
         </button>
        <button id="btnReset" v-if="!isReadOnly" class="topbar-btn danger" @click="resetToSeed">Reset</button>
      </div>
    </header>

    <!-- EXPORT STATUS BAR -->
    <div v-if="exportStatus.text" class="export-bar" :class="exportStatus.class">
      {{ exportStatus.text }}
      <button class="export-bar-close" @click="exportStatus = {text:'', class:''}">×</button>
    </div>

    <!-- ALWAYS VISIBLE PANELS -->
    <div class="panel-wrapper" style="display: flex; gap: 40px; flex-wrap: wrap;">
      <!-- Org Profile Panel -->
      <div class="drop-panel" style="flex: 1; min-width: 300px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <h3 style="margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Organization Profile</h3>
          <button class="topbar-btn primary" style="padding: 4px 12px; font-size: 12px;" @click="savePlan">Save Changes</button>
        </div>
        <div class="panel-grid">
          <div class="field"><label>Organization Name</label><input :disabled="isReadOnly" v-model="state.org.name" type="text"></div>
          <div class="field"><label>Organization Category</label><input :disabled="isReadOnly" v-model="state.org.category" type="text"></div>
          <div class="field"><label>Organization Hierarchy</label><input :disabled="isReadOnly" v-model="state.org.hierarchy" type="text"></div>
          <div class="field"><label>Fiscal Year</label><input :disabled="isReadOnly" v-model="state.org.year" type="number" min="2000" max="2100" step="1" placeholder="YYYY"></div>
          <div class="field"><label>Total Budget / GAA of Organization (₱)</label><input :disabled="isReadOnly" v-model.number="state.org.totalOrgBudget" type="number" step="0.01" min="0"></div>
        </div>
      </div>

      <!-- Budget Ledger Panel -->
      <div class="drop-panel" style="flex: 2; min-width: 300px;">
        <div class="ledger-inline">
          <div class="ledger-main">
            <div class="ledger-total-label">Total GAD Budget</div>
            <div class="ledger-big" id="ledgerTotal">{{ peso(ledger.total) }}</div>
            <div class="ledger-of">of <span class="mono" id="ledgerOrgTotal">{{ peso(state.org.totalOrgBudget) }}</span> total appropriation</div>
            <div class="ledger-pct mono" id="ledgerPct">{{ ledger.pct.toFixed(2) }}% of total budget</div>
            
            <div class="gauge">
              <div class="gauge-track">
                <div class="gauge-fill" id="gaugeFill" :style="{width: ledger.fillPct + '%'}"></div>
                <div class="gauge-mark" id="gaugeMark" :style="{left: ledger.markPct + '%'}"></div>
                <div class="gauge-mark-label" id="gaugeMarkLabel" :style="{left: ledger.markPct + '%'}">5% mandate</div>
              </div>
            </div>
            <div class="compliance-note" :class="ledger.complianceClass" id="complianceNote">{{ ledger.complianceNote }}</div>
            
            <div class="ledger-metrics-grid" style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px;">
                <div class="ledger-actual-cost">
                   <div class="ledger-sub-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary-bright); margin-bottom: 4px;">Actual Cost</div>
                   <div class="ledger-sub-val mono" style="font-size: 20px; font-weight: 700; color: var(--text);">{{ peso(budgetSummary.total_utilized) }}</div>
                   <div class="ledger-sub-note" style="font-size: 12px; color: var(--text-muted);">Verified Accomplishment Reports</div>
                </div>
                <div class="ledger-proposed-budget">
                   <div class="ledger-sub-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary-bright); margin-bottom: 4px;">Proposed Budget</div>
                   <div class="ledger-sub-val mono" style="font-size: 20px; font-weight: 700; color: var(--text);">{{ peso(budgetSummary.total_pending_approved) }}</div>
                   <div class="ledger-sub-note" style="font-size: 12px; color: var(--text-muted);">Approved Activity Designs</div>
                </div>
                <div class="ledger-remaining-budget">
                   <div class="ledger-sub-label" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary-bright); margin-bottom: 4px;">Remaining GAD Budget</div>
                   <div class="ledger-sub-val mono" style="font-size: 20px; font-weight: 700; color: var(--text);">{{ peso(ledger.total - budgetSummary.total_utilized) }}</div>
                   <div class="ledger-sub-note" style="font-size: 12px; color: var(--text-muted);">Total GAD Budget - Actual Cost</div>
                </div>
            </div>
          </div>
          <div class="ledger-breakdown">
            <div class="breakdown-row"><span>Client-Focused</span><span class="mono" id="sumClient">{{ peso(ledger.bySection.client) }}</span></div>
            <div class="breakdown-row"><span>Organization-Focused</span><span class="mono" id="sumOrg">{{ peso(ledger.bySection.org) }}</span></div>
            <div class="breakdown-row"><span>Attributed Program</span><span class="mono" id="sumAttributed">{{ peso(ledger.bySection.attributed) }}</span></div>
            <div class="breakdown-row border-top"><span>Primary Sources (GAA)</span><span class="mono" id="sumPrimary">{{ peso(ledger.primary) }}</span></div>
            <div class="breakdown-row"><span>Other Sources</span><span class="mono" id="sumOther">{{ peso(ledger.other) }}</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="main">
      <div class="page-header" style="display: none;">
      </div>

      <div class="toolbar">
        <input :disabled="isReadOnly" type="text" class="search" id="searchBox" v-model="searchQuery" placeholder="🔍  Search issues, activities, offices…">
        <div class="tabs" id="tabs">
          <button class="tab" :class="{active: currentTab === 'all'}" @click="currentTab = 'all'">All items</button>
          <button class="tab" :class="{active: currentTab === 'client'}" @click="currentTab = 'client'">Client-Focused</button>
          <button class="tab" :class="{active: currentTab === 'org'}" @click="currentTab = 'org'">Organization-Focused</button>
          <button class="tab" :class="{active: currentTab === 'attributed'}" @click="currentTab = 'attributed'">Attributed Program</button>
        </div>
      </div>

      <div id="itemsContainer">
        <section v-for="sec in SECTION_ORDER" :key="sec" class="section-block"
          :style="{ display: (currentTab === 'all' || currentTab === sec) ? '' : 'none' }">
          <div class="section-head">
            <div class="section-head-left">
              <span class="section-pill">{{ SECTION_SHORT[sec] }}</span>
              <h2>{{ SECTION_LABELS[sec] }}</h2>
            </div>
            <button class="add-item" v-if="!isReadOnly" @click="addItemInline(sec)">+ Add Item</button>
          </div>

          <div class="cards">
            <p v-if="!getSectionItems(sec).length" class="empty-note">
              No items yet. Click <strong>+ Add Item</strong> to get started.
            </p>

            <article v-for="item in getSectionItems(sec)" :key="item.id"
              class="card"
              :class="{expanded: expandedCards.includes(item.id), 'is-new': item._isNew}"
              :data-id="item.id"
              :style="{ display: matchesSearch(item) ? '' : 'none' }">

              <!-- Card header (click to expand/collapse) -->
              <header class="card-head" @click="toggleCard(item.id)">
                <span class="badge">{{ getItemNumber(item) }}</span>
                <div class="card-head-text">
                  <p class="card-title">{{ truncate(item.activity || item.mandate || '(untitled item)', 95) }}</p>
                  <p class="card-sub">{{ item.responsible || 'Unassigned unit' }}</p>
                </div>
                <span class="card-amount mono">{{ peso(itemSubtotal(item)) }}</span>
                <span v-if="item._dirty" class="unsaved-dot" title="Unsaved changes">●</span>
                <button class="chevron" aria-label="Expand item">⌄</button>
              </header>

              <!-- Card body -->
              <div class="card-body">
                <!-- NEW ITEM banner -->
                <div v-if="item._isNew" class="new-item-banner">
                  🆕 New item — fill in the details below, then click <strong>Save Item</strong> to store it.
                </div>

                <div class="field-grid">
                  <label class="full">Gender Issue / GAD Mandate<textarea :disabled="isReadOnly" v-model="item.mandate" rows="2" @input="markDirty(item)"></textarea>
                  </label>
                  <label>Cause of Gender Issue<textarea :disabled="isReadOnly" v-model="item.cause" rows="3" @input="markDirty(item)"></textarea>
                  </label>
                  <label>GAD Result Statement / Objective
                    <textarea :disabled="isReadOnly" v-model="item.result" rows="3" @input="markDirty(item)"></textarea>
                  </label>
                  <label>Relevant MFO / PAP / PPA
                    <textarea :disabled="isReadOnly" v-model="item.mfo" rows="2" @input="markDirty(item)"></textarea>
                  </label>
                  <label>GAD Activity
                    <textarea :disabled="isReadOnly" v-model="item.activity" rows="2" @input="markDirty(item)"></textarea>
                  </label>
                  <label class="full">Performance Indicators / Targets
                    <textarea :disabled="isReadOnly" v-model="item.indicators" rows="2" @input="markDirty(item)"></textarea>
                  </label>
                  <label class="full">Responsible Unit / Office
                    <input :disabled="isReadOnly" type="text" v-model="item.responsible" @input="markDirty(item)">
                  </label>
                </div>

                <!-- Budget editor -->
                <div class="budget-editor">
                  <div class="budget-editor-head">
                    <span>Budget Line</span><span>Amount (₱)</span><span>Source</span><span></span>
                  </div>
                  <div class="budget-lines">
                    <div v-for="l in item.budgetLines" :key="l.id" class="budget-line">
                      <input :disabled="isReadOnly" type="text" v-model="l.label" placeholder="e.g. Supplies and Materials" @input="markDirty(item)">
                      <input :disabled="isReadOnly" type="number" step="0.01" min="0" v-model.number="l.amount" @input="markDirty(item)">
                      <div class="source-col">
                        <select :disabled="isReadOnly" v-model="l.source" @change="markDirty(item)">
                          <option v-if="!SOURCE_OPTIONS.includes(l.source) && l.source" :value="l.source">{{ l.source }}</option>
                          <option v-for="s in SOURCE_OPTIONS" :key="s" :value="s">{{ s }}</option>
                        </select>
                        <input v-if="l.source === 'Other'" :disabled="isReadOnly" type="text" v-model="l.sourceOther" placeholder="Specify..." @input="markDirty(item)" class="source-spec">
                      </div>
                      <button class="remove-line" v-if="!isReadOnly" aria-label="Remove line" @click="removeBudgetLine(item, l.id)">×</button>
                    </div>
                  </div>
                  <button class="add-line" v-if="!isReadOnly" @click="addBudgetLine(item)">+ Add budget line</button>
                  <div class="item-subtotal">
                    Item subtotal <span class="subtotal-value mono">{{ peso(itemSubtotal(item)) }}</span>
                  </div>
                </div>

                <!-- Card action row -->
                <div class="card-actions">
                  <button class="delete-item" v-if="!isReadOnly" @click="deleteItem(item)">🗑 Remove</button>
                  <button class="save-item-btn" v-if="!isReadOnly" :class="{saving: item._saving}" @click="saveItem(item)" :disabled="item._saving">
                    <span v-if="item._saving">⏳ Saving…</span>
                    <span v-else-if="item._saved">✓ Saved</span>
                    <span v-else>💾 Save Item</span>
                  </button>
                </div>
              </div>
            </article>
          </div>
        </section>
      </div>

      <footer class="note">Reference: Republic Act No. 9710 (Magna Carta of Women) IRR Section 36(b) sets the GAD budget mandate at a minimum of 5% of an agency's total annual appropriations.</footer>
    </main>
    </div>

    <input :disabled="isReadOnly" type="file" ref="fileImport" accept="application/json" style="display:none" @change="handleFileImport">
    <input :disabled="isReadOnly" type="file" ref="excelImport" accept=".xlsx, .xls, .csv" style="display:none" @change="handleExcelImport">

    <PdfPreviewModal :isOpen="isPdfModalOpen" :fileUrl="pdfFileUrl" @close="closePdfModal" />

    <!-- BASELINE AMOUNTS MODAL -->
    <div v-if="isBaselineModalOpen" class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 9999;">
      <div class="modal-content baseline-modal-content">
        <h2 class="baseline-modal-title">Modify Baseline Amounts</h2>
        <div class="baseline-modal-grid">
          <div>
            <label class="baseline-label">Meals (Inside BSU)</label>
            <input type="number" v-model.number="baselineForm.meals_inside" class="baseline-input">
          </div>
          <div>
            <label class="baseline-label">Meals (Outside BSU)</label>
            <input type="number" v-model.number="baselineForm.meals_outside" class="baseline-input">
          </div>
          <div>
            <label class="baseline-label">Snacks (Inside BSU)</label>
            <input type="number" v-model.number="baselineForm.snacks_inside" class="baseline-input">
          </div>
          <div>
            <label class="baseline-label">Snacks (Outside BSU)</label>
            <input type="number" v-model.number="baselineForm.snacks_outside" class="baseline-input">
          </div>
          <div>
            <label class="baseline-label">PF/Honoraria per Speaker</label>
            <input type="number" step="0.01" v-model.number="baselineForm.pf_honoraria" class="baseline-input">
          </div>
          <div>
            <label class="baseline-label">Tokens per Recipient</label>
            <input type="number" v-model.number="baselineForm.tokens" class="baseline-input">
          </div>

          <div>
            <label class="baseline-label">Transportation Limit</label>
            <input type="number" v-model.number="baselineForm.transportation_limit" class="baseline-input">
          </div>
        </div>
        <div class="baseline-modal-actions">
          <button @click="isBaselineModalOpen = false" class="baseline-btn-cancel">Cancel</button>
          <button @click="saveBaselineAmounts" :disabled="savingBaselines" class="baseline-btn-save">
            {{ savingBaselines ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import Chart from 'chart.js/auto';
import api from '../../api';
import PdfPreviewModal from '../../components/PdfPreviewModal.vue';

export default {
  name: 'App',
  components: { PdfPreviewModal },
  setup() {
    const userRole = JSON.parse(localStorage.getItem('user'))?.user_role?.toLowerCase() || '';
    const isReadOnly = false;

    const SECTION_ORDER  = ['client', 'org', 'attributed'];
    const SECTION_LABELS = { client: 'Client-Focused Activities', org: 'Organization-Focused Activities', attributed: 'Attributed Program' };
    const SECTION_SHORT  = { client: 'CF', org: 'OF', attributed: 'AP' };
    const SOURCE_OPTIONS = ['GAA', 'Other'];
    const EXPORT_URL     = '/gpb/export-live'; // Vite proxy → http://localhost:8080

    const mandateStats = ref([]);
    const mandateStatsFilter = ref('all');
    const filteredMandateStats = computed(() => {
      if (mandateStatsFilter.value === 'all') return mandateStats.value;
      return mandateStats.value.filter(s => s.classification === mandateStatsFilter.value);
    });
    const loadingStats = ref(true);
    const statsChartCanvas = ref(null);
    let statsChartInstance = null;
    
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
      currentAllocationStat.value = stat;
      showAllocationModal.value = true;
      loadingAllocations.value = true;
      allocationsDirty.value = false;
      allocationsData.value = [];
      
      try {
        const res = await api.get(`/plan/mandate-allocations?gpb_ids=${stat.gpb_ids.join(',')}`);
        if (res.data.success) {
           allocationsData.value = res.data.data.map(d => ({ ...d, _expanded: true }));
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

    const getAllocatedElsewhere = (item) => {
       if (!item.allocations || !currentAllocationStat.value) return 0;
       return item.allocations.reduce((sum, al) => {
          if (!currentAllocationStat.value.gpb_ids.includes(parseInt(al.mandate_id))) {
              return sum + parseFloat(al.allocated_amount);
          }
          return sum;
       }, 0);
    };

    const saveAllocations = async () => {
       savingAllocations.value = true;
       
       const flatAllocs = [];
       for (const doc of allocationsData.value) {
           for (const item of doc.items) {
               let val = 0;
               let gpbLineId = item.gpb_budget_line_id;
               
               if (gpbLineId) {
                   val = parseFloat(item.amount) || 0;
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
    
    const scrollToStats = () => {
      const el = document.getElementById('mandate-statistics-section');
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
      }
    };
    
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
        nextTick(() => {
          renderStatsChart();
        });
      }
    };
    
    const renderStatsChart = () => {
      if (statsChartInstance) {
        statsChartInstance.destroy();
      }
      if (!statsChartCanvas.value || mandateStats.value.length === 0) return;
      
      const labels = mandateStats.value.map(s => {
         let title = s.activity || s.mandate || 'Untitled';
         return title.length > 25 ? title.substring(0, 25) + '...' : title;
      });
      const budgetData = mandateStats.value.map(s => s.budget);
      const utilizedData = mandateStats.value.map(s => s.utilized_budget);
      const remainingData = mandateStats.value.map(s => s.remaining_budget);
      
      const textColor = getComputedStyle(document.body).getPropertyValue('--text-secondary').trim() || 'rgba(255,255,255,0.7)';
      const gridColor = 'rgba(255,255,255,0.05)';
      
      const bgColors = mandateStats.value.map((_, i) => `hsl(${(i * 360) / Math.max(1, mandateStats.value.length)}, 70%, 60%)`);
      
      statsChartInstance = new Chart(statsChartCanvas.value, {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [
            {
              data: budgetData,
              backgroundColor: bgColors,
              borderWidth: 3,
              borderColor: 'rgba(0,0,0,0.6)'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            tooltip: {
              callbacks: {
                 label: (context) => {
                   const value = context.raw;
                   return ' ₱' + Number(value).toLocaleString('en-US', {minimumFractionDigits: 2});
                 },
                 title: (context) => {
                   const item = mandateStats.value[context[0].dataIndex];
                   return `Activity: ${item.activity}\nMandate: ${item.mandate}\nCause: ${item.cause}`;
                 }
              }
            },
            legend: {
              position: 'right',
              labels: {
                color: textColor
              }
            }
          }
        }
      });
    };

    // ─── Seed ───────────────────────────────────────────────────────────────
    function seedState() {
      return {
      isReadOnly,

        settings:{
          apiBaseUrl:''
        },
        org:{
          name:'Benguet State University',
          category:'State Universities and Colleges',
          hierarchy:'Benguet State University',
          year: new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', year: 'numeric' }).format(new Date()),
          totalOrgBudget:1062488000.00,
          otherSources:0.00,
          preparedByName:'Jude Laoagan Tayaben, GAD Director',
          approvedByName:'Kenneth Alip Laruan, President'
        },
        items:[
          {id:'c1',section:'client',mandate:'Republic Act No. 10931, Universal Access to Quality Tertiary Education Act Section 8 on Affirmative Action Program; CHED Memorandum Orders on GAD Mainstreaming in Higher Education Institutions (HEIs)',cause:'Extraordinary life situations due to disasters, calamities, and socio-cultural discrimination',result:'To promote equitable access and participation of both women and men from GIDAs in tertiary education through gender-responsive implementation of the Affirmative Action Agenda.',mfo:'Higher Education Program',activity:'Implementation of Affirmative Action Agenda',indicators:'Number of served disadvantaged students - 100% disadvantaged students',responsible:'OSS',budgetLines:[{id:'c1-l1',label:'PS Attribution',amount:500000,source:'GAA'},{id:'c1-l2',label:'Supplies and Materials',amount:200000,source:'GAA'}]},
          {id:'c2',section:'client',mandate:'Republic Act No. 10931, Universal Access to Quality Tertiary Education Act Section 8 on Affirmative Action Program; CHED Memorandum Orders on GAD Mainstreaming in Higher Education Institutions (HEIs)',cause:'High tuition and miscellaneous fees, compounded by socio-cultural expectations for women to prioritize domestic roles over education',result:'To promote gender equality in access to tertiary education by eliminating financial barriers for both male and female students.',mfo:'Higher Education Program',activity:'Provision of free tuition fee under RA 10931 to eligible male and female students of the university.',indicators:'Percentage of qualified students granted free tuition - 100% of qualified students granted free tuition.',responsible:'OSS, OUR, UHS',budgetLines:[{id:'c2-l1',label:'Tuition Fee',amount:131100000,source:'GAA'}]},
          {id:'c3',section:'client',mandate:'CHED Memorandum Order No. 01 series 2015',cause:'Limited activities to increase awareness of men and women students to GAD-related information (1st year students, transferees)',result:'To increase the students level of awareness and appreciation on GAD',mfo:'Higher Education Program',activity:'Conduct GAD orientation/ forum/ seminar to BSU 1st year/ transferees students (face to face/ online: 14 colleges)',indicators:'No. of students oriented on GAD - 4,000 students oriented on GAD (F:2750 M:1250)',responsible:'OSS, GAD Office, 3 Campuses (La Trinidad, Bokod & Buguias Campus)',budgetLines:[{id:'c3-l1',label:'Meals and Snack',amount:318800,source:'GAA'},{id:'c3-l2',label:'Supplies and Materials',amount:10000,source:'GAA'},{id:'c3-l3',label:'PS Attribution',amount:124563.26,source:'GAA'}]},
          {id:'c4',section:'client',mandate:'CHED Memorandum Order No. 01 series 2015',cause:'Student leaders have limited understanding on GAD in the University',result:'To empower student leaders regarding GAD responsive leadership (La Trinidad Campus, Bokod Campus and Buguias Campus)',mfo:'Higher Education Program',activity:'Continuous conduct of GAD responsive leadership training for student',indicators:'No. of training conducted to increase GAD awareness and responsiveness of students leaders - 2 training (Female:200 Male:100) (La Trinidad Campus, Bokod Campus and Buguias Campus)',responsible:'OSS',budgetLines:[{id:'c4-l1',label:'Supplies and Materials',amount:30000,source:'GAA'},{id:'c4-l2',label:'Snack',amount:20000,source:'GAA'},{id:'c4-l3',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'c5',section:'client',mandate:'Part VII of CHED Memorandum Order Number 1, series 2015 on Gender-Responsive Research and Extension Program',cause:'Presence of gender inequality, poverty and GAD-related concerns in the community',result:'To sustain GAD-related extension activities delivering technology transfer, Livelihood Program, Technical Assistance, and Advocacy to community partners to help promote gender equality, poverty reduction and sustainable development',mfo:'Extension Services / Research Services / Advance Education Services / Higher Education Services',activity:'Conduct of Extension project/ activities to partner organizational/ communities as component of Gender Responsive Extension Program (GREP) to partner organization/ communities',indicators:'No. of extension activities conducted within the year - 24 Extension program/project/ activities conducted within the year (Female:560 Male:500)',responsible:'Research and Extension, various offices/ colleges in the University/ external campuses',budgetLines:[{id:'c5-l1',label:'Seminar Package / Meals & Snacks / Fuel for Transportation / Vehicle Rental / Other Professional Services',amount:2500000,source:'GAA'},{id:'c5-l2',label:'PS Attribution',amount:1000000,source:'GAA'}]},
          {id:'c6',section:'client',mandate:'Limited access of PWDs to gender-responsive programs and services / DBM-DSWD Joint Circular No. 2003-01 (at least 1% of budget for senior citizen and PWD programs)',cause:'Limited access of PWDs to gender-responsive programs and services',result:'Improved access of PWDs to gender-responsive, inclusive, and empowering programs and services.',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Awareness of women PWDs who benefited from the program',indicators:'Number of women PWDs who benefited from the program - F/M. No. of GAD program/project/activity provided for PWD - At least 1 program/project/activity',responsible:'HRMO, OSS',budgetLines:[{id:'c6-l1',label:'PS Attribution',amount:100000,source:'GAA'},{id:'c6-l2',label:'Supplies & Materials / Meals & Snacks',amount:250000,source:'GAA'}]},
          {id:'c7',section:'client',mandate:'Lack of senior citizens access to gender-responsive programs and services / DBM-DSWD Joint Circular No. 2003-01 (at least 1% of budget for senior citizen and PWD programs)',cause:'Absence of sustainable and gender-responsive university programs for senior citizens.',result:'Improved access of senior citizens to gender-responsive, inclusive, and empowering programs and services',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Program: BSU Kalinga for women Senior Citizens',indicators:'Number of Programs provided for Senior Citizens - At least 1 program for BSU. Number of women senior citizens who benefited from the program - F21 M23',responsible:'GAD Office, Colleges, External Campuses',budgetLines:[{id:'c7-l1',label:'Supplies & Materials / Meals & Snacks',amount:250000,source:'GAA'}]},
          {id:'c8',section:'client',mandate:"Low number of women's participation in sports / MCW-IRR Section 14",cause:'Minimal attendance of female students to competitive and non-competitive sports',result:"To increase female students level of participation and awareness on Gender in Sports",mfo:'Higher Education Program',activity:'Participate in sports activities targeted for female students',indicators:"No. of sports activities supported through allocation of budget for sports and socio-cultural activities/ E-sports (i.e. Annual Women's Martial Arts Festival) - 2 sports activities (Female:20)",responsible:'CHK',budgetLines:[{id:'c8-l1',label:'Registration & Travelling Expenses',amount:80000,source:'GAA'},{id:'c8-l2',label:'Meals and Snacks',amount:60000,source:'GAA'},{id:'c8-l3',label:'PS Attribution',amount:20000,source:'GAA'}]},
          {id:'o9',section:'org',mandate:'Programs on Awards and Incentives for Service Excellence (PRAISE, CSC Res. No. 010112 and CSC MC No.1, s.2001); GFPS Memo Circular No. 2011-01; BOR Res. No. 2316, s.2014',cause:'Low recognition/appreciation on the Gender Mainstreaming in BSU',result:'Strengthen Gender Mainstreaming through recognition of GAD implementation in the University',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Provide recognition and award to GAD implementer and other GAD-related award (GAD implementer for students and employees, GAD Advocate Award)',indicators:'No. of award will be provided through BSU-PRAISE - At least 1 GAD Advocate award will be provided through BSU-PRAISE',responsible:'HRDO, HRMO, BSU-PRAISE Committee, GAD Office',budgetLines:[{id:'o9-l1',label:'Incentive GAD Advocate Award',amount:5000,source:'GAA'},{id:'o9-l2',label:'PS Attribution',amount:200000,source:'GAA'}]},
          {id:'o10',section:'org',mandate:'Limited application of GAD Mainstreaming (GM) in Instruction, Research, Extension and Production / Magna Carta of Women (RA 9710)',cause:'Low awareness among personnel in the University about GAD mainstreaming',result:'To enhance GAD mainstreaming in Administration, Academic, Research and Extension, Production',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Conduct GAD related Gender Mainstreaming capability building and competency acquisition',indicators:'No. of training/workshop/seminars conducted - 25 training/workshop/seminars/Learning and Development (F:1500 M:1000)',responsible:'GAD Office, HRDO, Research and Extension, OQAA, All Colleges with External Campuses',budgetLines:[{id:'o10-l1',label:'Seminar Package / Meals & Snacks / Fuel for Transportation / Vehicle Rental / Professional Services (La Trinidad, Bokod, Buguias)',amount:3500000,source:'GAA'},{id:'o10-l2',label:'PS Attribution',amount:500000,source:'GAA'}]},
          {id:'o11',section:'org',mandate:'Magna Carta of Women IRR Section 37 - Gender Mainstreaming as a Strategy for Implementing the Magna Carta of Women',cause:'Productivity of employees affected due to filial obligations, affecting promotion of women to higher positions or from participating in capability enhancement sessions',result:'Inadequate support services to personnel and students with children',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Operationalize of BSU College of Nursing Reproductive Health Care Center',indicators:'No. of maintained Reproductive Health Care Center - 1 maintained BSU CN Reproductive Health Care Center',responsible:'College of Nursing',budgetLines:[{id:'o11-l1',label:'Supplies and Materials',amount:20000,source:'GAA'},{id:'o11-l2',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'o12',section:'org',mandate:"Executive Order No. 340 s.1997 - Directing National Government Agencies and GOCCs to provide Day Care Services for their Employees' Children under five years of age",cause:'Problems of parents and students related to child care',result:'Ensure opportunities of personnel and students to have access on agency care services to children to avoid absenteeism',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Maintenance of Child Minding Center for working parents in ensuring that they have a safe place to leave their child while they are at their work places',indicators:'No. of established child minding center - Fully maintained new established and existing child minding centers at BSU La Trinidad, Bokod Campus and Buguias Campus',responsible:'GAD Office, External Campuses',budgetLines:[{id:'o12-l1',label:'Supplies and Materials',amount:130000,source:'GAA'},{id:'o12-l2',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'o13',section:'org',mandate:'Magna Carta of Women (RA 9710)',cause:'Low integration of gender mainstreaming of BSU',result:'To strengthen the GAD integration in the operations of BSU',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Create a Monitoring Team to conduct monitoring and evaluation of the utilization/outcome of GAD PAPs and ensure effectiveness of the GAD PAPs',indicators:'No. of monitoring and assessment meetings with reports conducted - 4 monitoring and assessment meetings with reports conducted',responsible:'GAD Office',budgetLines:[{id:'o13-l1',label:'Supplies and Materials',amount:10000,source:'GAA'},{id:'o13-l2',label:'PS Attribution',amount:320000,source:'GAA'}]},
          {id:'o14',section:'org',mandate:'Magna Carta of Women IRR Section 37-C - Creation and/or Strengthening of the GAD Focal Points (GFPs)',cause:'Low level of capacity of GFPS to develop and implement GAD programs and activities due to new members',result:"Capacitated GFPS members in order to implement GAD PAP's and advance GAD Mainstreaming (GM) in the University",mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'For GFPS/Secretariat: GMEF/HGDG/GPB/GAD Agenda/GAD Deepening Session and TOT among other related trainings and capacity building activities (Regional/National GAD-related trainings/seminars/forum/workshop)',indicators:'No. training/seminars/workshop attendance for each GFPS-member on GAD related updates and mandates - At least 1 each (Female:31, Male:15)',responsible:'GAD Office',budgetLines:[{id:'o14-l1',label:'GFPS TWG PAPs',amount:396000,source:'GAA'},{id:'o14-l2',label:'PS Attribution',amount:500000,source:'GAA'}]},
          {id:'o15',section:'org',mandate:"Section 37-C2 Rule VI of the Magna Carta of Women's IRR on duties and function of the GAD Focal Point System / Magna Carta of Women (RA 9710)",cause:'Compliance to provisions regarding regular monitoring of gender mainstreaming efforts',result:'To ensure operations of GAD Office as well as monitor and evaluate GM efforts of the University',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Regular coordination and meetings of GAD-GFPS (Execom, GFPS-TWG members and external campus TWG members) and emergency meeting when necessary',indicators:'No. of reports on regular meetings per campus - At least 6 reports available at year end, plus RGADC quarterly meeting/s',responsible:'GAD Office',budgetLines:[{id:'o15-l1',label:'Meals & Snack',amount:111720,source:'GAA'},{id:'o15-l2',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'o16',section:'org',mandate:'Duties and function of the GAD Focal Point System / CHED Memo 2015-1',cause:'No plantilla personnel assigned to plan, implement and monitor GAD PAPs on a full-time basis',result:'To ensure operations of GAD Office as well as monitor and evaluate GM efforts of the University',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:"Engage support staff to assist in the implementation of GFPS PPA's and Gender Mainstreaming in the university through rehiring of GAD staff and Student Assistant",indicators:'Salary of GAD Staff: Casual. No. of rehired personnel - At least 2 staff renewed/rehired (Casual) and at least 3 Student Assistant/SPES per semester',responsible:'GAD Office',budgetLines:[{id:'o16-l1',label:'Salary of GAD Staff and SPES / Student Assistant',amount:550000,source:'GAA'}]},
          {id:'o17',section:'org',mandate:'Low level of Awareness on Gender Mainstreaming (GM) among newly hired personnel / Magna Carta of Women (RA 9710), CHED Memo 2015-1',cause:'Lack of regular orientation and refresher training on gender sensitivity and GAD mandates',result:'To enhance awareness and understanding of gender concepts, GAD mandates, and gender-responsive work practices among newly hired and current personnel',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Conduct Gender Sensitivity Training (GST) for newly hired and current personnel (continuing activity)',indicators:'No. of trainings conducted - 1 training for at least 100% of newly hired personnel and 3 refresher trainings for current personnel',responsible:'GAD Office',budgetLines:[{id:'o17-l1',label:'Meals & Snack',amount:167200,source:'GAA'},{id:'o17-l2',label:'Token',amount:4000,source:'GAA'},{id:'o17-l3',label:'Professional Fee',amount:144528.32,source:'GAA'},{id:'o17-l4',label:'Supplies and Materials',amount:6000,source:'GAA'},{id:'o17-l5',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'o18',section:'org',mandate:'Part V, Rule II, Section 4 of CHED Memorandum Order No. 1 Series of 2015',cause:'Limited number of GAD library and related learning materials across various disciplines',result:'To increase the provision of adequate and accessible library and related learning materials across various disciplines and educational levels',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Provision of knowledge products (books, magazines, multi-media) for adequate and accessible library and related learning materials in support to gender-responsive Curriculum Programs',indicators:'No. of procured library and learning materials - 200 books',responsible:'ULIS',budgetLines:[{id:'o18-l1',label:'Books and Instructional Materials',amount:2500000,source:'GAA'},{id:'o18-l2',label:'PS Attribution',amount:100000,source:'GAA'}]},
          {id:'o19',section:'org',mandate:'Development and Dissemination of Gender and Development (GAD) Information, Education, and Communication (IEC) Materials',cause:'Presence of Gender Based Violence (GBV) issues/reports/cases in the university',result:'Institutionalize GAD mechanisms in the University and sustain awareness campaigns on sexual harassment and gender-based violence',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Development and Dissemination of Gender and Development (GAD) Information, Education, and Communication (IEC) Materials',indicators:'Official Publication with GAD articles/pictures. Maintained GAD Bulletin board - At least 8. Sector-specific downloadable knowledge products - At least 2',responsible:'UPAO, GAD Office',budgetLines:[{id:'o19-l1',label:'Shamag',amount:96000,source:'GAA'},{id:'o19-l2',label:'PS Attribution',amount:100000,source:'GAA'},{id:'o19-l3',label:'Supplies and Materials',amount:100000,source:'GAA'}]},
          {id:'o20',section:'org',mandate:'Institutionalizing GAD database and Sex-Disaggregated Database / Magna Carta of Women (RA 9710) Section 36 on Sex-Disaggregated Database',cause:'Minimal awareness and appreciation on the relevance of the centralized Sex-Disaggregated database',result:'To establish a centralized GAD-related database of the University',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Updating of Sex-Disaggregated Data (SDD) and other data related to personnel/students/clients for gender analysis and report preparation',indicators:'1 well-organized and maintained GAD database. Establishment of GAD-Database system per college/unit.',responsible:'ICT, GAD Office',budgetLines:[{id:'o20-l1',label:'PS Attribution',amount:100000,source:'GAA'},{id:'o20-l2',label:'Maintenance of SDD / Internet connection',amount:1000000,source:'GAA'}]},
          {id:'o21',section:'org',mandate:'Magna Carta for Women, Chapter IV Section 10 and RA 10121, Section 2 & 9',cause:"Limited resources of the DSWD and LGU to provide for students who are transient residents and limited appreciation on women's role in nation building among employees and students, especially new ones",result:'To ensure that disaster assistance provided to distressed students are gender-responsive',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Provision of gender-responsive services to employees and students who experienced crisis/disaster (e.g. distribution of hygiene kits for both women and men)',indicators:'No. of pax of the most affected employees/students during crises - 1,000',responsible:'GAD Office, HDRO, NSTP, various offices/all colleges in the University',budgetLines:[{id:'o21-l1',label:'Crisis pack (₱200/pack x 1,000 pax)',amount:200000,source:'GAA'},{id:'o21-l2',label:'PS TWG Members',amount:10000,source:'GAA'}]},
          {id:'o22',section:'org',mandate:'RA 9710 Sec.18 Special Leave for Women; RA 8187 Paternity Leave; RA 8972 Sec.8 Solo Parents Welfare Act; RA 9262 Sec.43 Anti-VAWC Act',cause:'Employees may require special leaves due to parental obligations, health concerns and other circumstances',result:'Enhanced support services for employees in need of special leaves',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Provision of gender leaves and conduct of Seminar on Gender Related Leaves for Newly Hired Employees',indicators:'No. of Maternity, Paternity, Solo parent, gynecological, VAWC leaves availed and 1 Seminar conducted (M:20 F:50) - 100%',responsible:'HRMO, CBOO, various offices/colleges in the University',budgetLines:[{id:'o22-l1',label:'PS Attribution',amount:1000000,source:'GAA'}]},
          {id:'o23',section:'org',mandate:"Proclamation 227 (Women's Role in History Month) and Proclamation 1172, s.2006 (18-Day Campaign to End VAW)",cause:"The need to highlight women's rights, their role in nation building, and to provide a platform against VAW, gender-based violence, Safe Spaces Act (RA 11313)",result:"To strengthen awareness of BSU students/employees on women's rights and their role in national development and nation building",mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:"Participation to 18-Day Campaign to end VAW and Women's Month Celebration/activities and programs organized by PCW and other agency/ies",indicators:'No. of activities conducted per campus - At least 1 per campus',responsible:'GAD Office, various offices/colleges in the University/external campus',budgetLines:[{id:'o23-l1',label:'Activities / Programs',amount:250000,source:'GAA'},{id:'o23-l2',label:'PS Attribution',amount:200000,source:'GAA'}]},
          {id:'o24',section:'org',mandate:'Productivity of employees affected due to filial obligations / Magna Carta of Women IRR Section 37',cause:'Inadequate support services for personnel/students with young children and breastfeeding mothers (RA 10028)',result:'Inadequate support services to personnel and students with children',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Establishment/maintenance of breastfeeding station established in the preceding years',indicators:'Fully maintained Lactation rooms - 100% at BSU La Trinidad, Bokod and Buguias Campus',responsible:'GAD Office, External Campuses',budgetLines:[{id:'o24-l1',label:'Supplies and Materials',amount:50000,source:'GAA'},{id:'o24-l2',label:'PS Attribution',amount:170000,source:'GAA'}]},
          {id:'o25',section:'org',mandate:'RA 9710, PCW-NEDA-DBM Joint Circular 2012-01, CSC MC No.12 s.2005',cause:'Lack of regular gender-related capacity-building activities and insufficient integration of gender sensitivity in employee development programs',result:'To enhance the gender awareness and sensitivity of BSU employees, enabling them to recognize and eliminate gender bias and stereotyping, and to foster a gender-responsive and equitable workplace',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Conduct of gender sensitivity orientations for BSU Personnel (continuing activity)',indicators:'No. of trainings conducted for BSU personnel - At least 3',responsible:'GAD Office',budgetLines:[{id:'o25-l1',label:'Supplies and Materials',amount:10000,source:'GAA'},{id:'o25-l2',label:'Meals & Snack / Professional Fee',amount:233796.24,source:'GAA'},{id:'o25-l3',label:'PS',amount:10000,source:'GAA'}]},
          {id:'o26',section:'org',mandate:'Establishment of Gender-Responsive Curricular Programs / Part V of CMO 01, s.2015',cause:'Limited subject for GAD Integration of Gender-Responsive Instruction and Curriculum Development',result:'Integration of gender mainstreaming in curriculum/subjects in all levels',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Preparation of syllabi and classroom teaching integrating gender perspective',indicators:'Number of faculty members integrating gender perspective in the syllabus - 567 permanent and 125 COS females/males',responsible:'GAD Office, GFPS-TWG members, all colleges',budgetLines:[{id:'o26-l1',label:'PS - 567 Teaching employees and COS',amount:51294972.71,source:'GAA'},{id:'o26-l2',label:'Teaching Overload',amount:7000000,source:'GAA'}]},
          {id:'o27',section:'org',mandate:'Section 37-C2 of the Magna Carta of Women (MCW) IRR - mandates establishment of a GAD Focal Point System (GFPS)',cause:'Sustained operations of the existing GAD Office - administrative, logistical, and financial support for day-to-day functioning; maintenance of GAD database; coordination of GFPS and GAD-related activities across colleges and units',result:'To ensure the continuous and efficient operation of a functional, gender-responsive GAD Office that leads, monitors, and evaluates GAD mainstreaming efforts in the university.',mfo:'Research Services / Extension Services / Advance Education Services / Higher Education Services',activity:'Sustaining Gender Mainstreaming and Institutional Support in the University',indicators:'Fully maintained GAD Office - 100%',responsible:'GAD Office',budgetLines:[{id:'o27-l1',label:'PS on Procurement Process',amount:30992.09,source:'GAA'},{id:'o27-l2',label:'PS Attribution: Execom & TWG members',amount:7219424,source:'GAA'},{id:'o27-l3',label:'Supplies, Equipment and Materials',amount:300000,source:'GAA'},{id:'o27-l4',label:'PS of GAD Director (50%)',amount:501954,source:'GAA'}]},
          {id:'a28',section:'attributed',mandate:'',cause:'',result:'',mfo:'',activity:'Transportation Equipment Outlay',indicators:'',responsible:'TASU, PMO, SPMO',budgetLines:[{id:'a28-l1',label:'Transportation Equipment Outlay',amount:12285000,source:'GAA'}]},
          {id:'a29',section:'attributed',mandate:'',cause:'',result:'',mfo:'',activity:'Repair and Maintenance of Office Building and other Structures',indicators:'',responsible:'PU, PMO, SPMO',budgetLines:[{id:'a29-l1',label:'Repair and Maintenance - Office Building and other Structures',amount:6402000,source:'GAA'}]},
          {id:'a30',section:'attributed',mandate:'',cause:'',result:'',mfo:'',activity:'Bamboo Industry Development for Environment Conservation and Countryside',indicators:'',responsible:'College of Forestry',budgetLines:[{id:'a30-l1',label:'Bamboo Industry Development for Environment Conservation and Countryside',amount:3750000,source:'GAA'}]},
          {id:'a31',section:'attributed',mandate:'',cause:'',result:'',mfo:'',activity:'Benguet State University Student Information and Accounting System (SIAS)',indicators:'',responsible:'ICT, PMO',budgetLines:[{id:'a31-l1',label:'Student Information and Accounting System (SIAS)',amount:5000000,source:'GAA'}]}
        ]
      };
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function peso(n) {
      n = Number(n) || 0;
      return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function truncate(s, n) {
      s = String(s || '');
      return s.length > n ? s.slice(0, n - 1) + '…' : s;
    }
    function itemSubtotal(item) {
      return (item?.budgetLines || []).reduce((s, l) => s + (Number(l?.amount) || 0), 0);
    }
    function uid(prefix) {
      return (prefix || 'id') + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
    }

    // ─── State ────────────────────────────────────────────────────────────────
    const state         = ref(seedState());
    const budgetSummary = ref({ total_budget: 0, total_utilized: 0, total_pending_approved: 0 });
    const currentTab    = ref('all');
    const searchQuery   = ref('');
    const expandedCards = ref([]);
    const saveStatus    = ref('Loading…');
    const exportStatus  = ref({ text: '', class: '' });
    const exporting     = ref(false);
    const topPanel      = ref(null);
    const fileImport    = ref(null);
    const excelImport   = ref(null);

    // Save chip
    const saveChipClass = computed(() => {
      if (saveStatus.value === 'Saved') return 'chip-ok';
      if (saveStatus.value === 'Error saving') return 'chip-warn';
      if (['Editing…', 'Saving…', 'Loading…'].some(s => saveStatus.value.includes(s.replace('…','')))) return 'chip-busy';
      return '';
    });

    // Tooltip explains save status
    const saveTooltip = computed(() => {
      if (saveStatus.value === 'Error saving') {
        return 'The backend server could not be reached or an error occurred while saving. Your recent edits may be lost.';
      }
      if (saveStatus.value === 'Saved') return 'Data is saved to the database.';
      return '';
    });

    // ─── Ledger ───────────────────────────────────────────────────────────────
    const ledger = computed(() => {
      let gaaTotal = 0;
      let otherLinesTotal = 0;

      state.value.items.forEach(i => {
        if (Array.isArray(i.budgetLines) && i.budgetLines.length > 0) {
          i.budgetLines.forEach(l => {
            const amt = Number(l.amount) || 0;
            const src = (l.source || 'GAA').toLowerCase().trim();
            if (src === 'other') {
              otherLinesTotal += amt;
            } else {
              gaaTotal += amt;
            }
          });
        } else {
          const amt = Number(i.budget) || 0;
          const src = (i.source || 'GAA').toLowerCase().trim();
          if (src === 'other') {
            otherLinesTotal += amt;
          } else {
            gaaTotal += amt;
          }
        }
      });

      const other       = otherLinesTotal;
      const primary     = gaaTotal;
      const total       = primary + other;
      const totalBudget = Number(state.value.org.totalOrgBudget) || 0;
      const pct         = totalBudget > 0 ? (total / totalBudget * 100) : 0;
      const bySection   = { client: 0, org: 0, attributed: 0 };
      state.value.items.forEach(i => { if (i.section in bySection) bySection[i.section] += itemSubtotal(i); });
      const scaleMax  = Math.max(25, pct * 1.15, 10);
      const fillPct   = Math.min(100, (pct / scaleMax) * 100);
      const markPct   = Math.min(100, (5 / scaleMax) * 100);
      const diff      = pct - 5;
      return {
      isReadOnly,

        total, other, primary, pct, bySection, fillPct, markPct,
        complianceClass: pct >= 5 ? 'good' : 'alert',
        complianceNote:  pct >= 5
          ? '✓ ' + diff.toFixed(2) + ' pts above the 5% GAD mandate (RA 9710 IRR, Sec. 36-b)'
          : '⚠ ' + Math.abs(diff).toFixed(2) + ' pts below the 5% minimum mandate',
      };
    });

    const otherSourcesTotal = computed(() => state.value.items.reduce((total, item) => {
      const lines = Array.isArray(item.budgetLines) ? item.budgetLines : [];
      if (lines.length) {
        return total + lines.reduce((sum, line) => (
          sum + (String(line.source || '').toLowerCase().trim() === 'other' ? (Number(line.amount) || 0) : 0)
        ), 0);
      }
      return total + (String(item.source || '').toLowerCase().trim() === 'other' ? (Number(item.budget) || 0) : 0);
    }, 0));

    // ─── Filtering ────────────────────────────────────────────────────────────
    function getSectionItems(sec) {
      return state.value.items.filter(i => i.section === sec);
    }
    function matchesSearch(item) {
      const q = searchQuery.value.trim().toLowerCase();
      if (!q) return true;
      return ['mandate','activity','responsible','cause','result','indicators','mfo']
        .some(f => item[f]?.toLowerCase().includes(q));
    }
    function getItemNumber(item) {
      let counter = 0;
      const q = searchQuery.value.toLowerCase();
      for (const it of state.value.items) {
        const matchTab    = currentTab.value === 'all' || it.section === currentTab.value;
        const matchSearch = !q || ['mandate','activity','responsible','cause','result'].some(f => it[f]?.toLowerCase().includes(q));
        if (matchTab && matchSearch) { counter++; if (it.id === item.id) return counter; }
      }
      return counter;
    }

    function toggleCard(id) {
      const idx = expandedCards.value.indexOf(id);
      if (idx > -1) expandedCards.value.splice(idx, 1);
      else expandedCards.value.push(id);
    }
    function expandAll()  { expandedCards.value = state.value.items.map(i => i.id); }
    function collapseAll(){ expandedCards.value = []; }

    // ─── Add item inline (like gad-plan-editor.html) ─────────────────────────
    // Creates a blank card directly in the list, auto-expanded, ready to fill.
    function addItemInline(section) {
      const newId = uid(section.charAt(0));
      const item = {
        id: newId,
        section,
        mandate: '', cause: '', result: '', mfo: '',
        activity: '', indicators: '', responsible: '',
        budgetLines: [{ id: uid('l'), label: '', amount: 0, source: 'GAA', sourceOther: '' }],
        _isNew: true,   // flag: not yet saved to DB
        _dirty: true,
        _saving: false,
        _saved: false,
      };
      state.value.items.push(item);
      expandedCards.value.push(newId);
      // Scroll to the new card and focus the first input field
      nextTick(() => {
        const el = document.querySelector(`[data-id="${newId}"]`);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          const firstInput = el.querySelector('textarea, input');
          if (firstInput) {
            // Slight delay to allow smooth scroll before focusing
            setTimeout(() => firstInput.focus(), 300);
          }
        }
      });
    }

    // ─── Mark item as having unsaved edits ────────────────────────────────────
    function markDirty(item) {
      item._dirty = true;
      item._saved = false;
    }

    // ─── Save a single item to the DB ─────────────────────────────────────────
    async function saveItem(item) {
      const { isConfirmed } = await Swal.fire({title: 'Save Item?', text: 'Are you sure you want to save this item?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Save'});
      if (!isConfirmed) return;
      
      item._saving = true;
      item._saved  = false;
      const totalBudget = (item.budgetLines || []).reduce((s, l) => s + Number(l.amount), 0);
      const dbPayload = {
        fiscal_year:  parseInt(state.value.org.year) || new Date().getFullYear(),
        section:      item.section,
        mandate:      item.mandate,
        cause:        item.cause,
        objective:    item.result,
        result:       item.result,
        ppa:          item.mfo,
        mfo:          item.mfo,
        activity:     item.activity,
        targets:      item.indicators,
        indicators:   item.indicators,
        budget:       totalBudget,
        source:       [...new Set((item.budgetLines || []).map(l => l.source || 'GAA'))].join(', ') || 'GAA',
        office:       item.responsible,
        responsible:  item.responsible,
        budget_lines: JSON.stringify(item.budgetLines),
        sort_order:   state.value.items.filter(i => i.section === item.section).indexOf(item) + 1,
      };

      try {
        let res;
        // If it has a numeric DB id, update; else create
        const numericId = item._dbId || (String(item.id).match(/^\d+$/) ? item.id : null);
        if (numericId) {
          res = await api.put(`/gpb/item/${numericId}`, { ...dbPayload, id: numericId });
        } else {
          res = await api.post('/gpb/item', dbPayload);
          if (res.status === 200 || res.status === 201) {
            const created = res.data;
            if (created?.id) item._dbId = created.id;
          }
        }
        item._isNew  = false;
        item._dirty  = false;
        item._saved  = true;
        // Auto-clear the ✓ after 3s
        setTimeout(() => { item._saved = false; }, 3000);
      } catch (e) {
        console.warn('DB save failed, data still in local state:', e);
        item._dirty = true;
      } finally {
        item._saving = false;
      }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────
    async function deleteItem(item) {
      const { isConfirmed } = await Swal.fire({title: 'Are you sure?', text: 'Remove this item and all its budget lines? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes'});
      if (!isConfirmed) return;

      if (item.id && !String(item.id).startsWith('i_')) {
        try { await api.delete(`/gpb/item/${item.id}`); } 
        catch (e) { console.error('Failed to delete from DB', e); }
      }

      state.value.items = state.value.items.filter(i => i.id !== item.id);
      const idx = expandedCards.value.indexOf(item.id);
      if (idx > -1) expandedCards.value.splice(idx, 1);
    }

    function addBudgetLine(item) {
      item.budgetLines.push({ id: uid('l'), label: '', amount: 0, source: 'GAA', sourceOther: '' });
      markDirty(item);
    }
    function removeBudgetLine(item, lineId) {
      if (item.budgetLines.length <= 1) { Swal.fire('Error', 'Each item needs at least one budget line.', 'error'); return; }
      const idx = item.budgetLines.findIndex(l => l.id === lineId);
      if (idx > -1) item.budgetLines.splice(idx, 1);
      markDirty(item);
    }

    // ─── Import ───────────────────────────────────────────────────────────────
    function handleFileImport(e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = async (evt) => {
        try {
          const imported = JSON.parse(evt.target.result);
          if (!imported.org || !Array.isArray(imported.items)) throw new Error('Invalid');
          imported.items = imported.items.map(it => ({ ...it, budgetLines: Array.isArray(it.budgetLines) ? it.budgetLines : [] }));
          const { isConfirmed } = await Swal.fire({title: 'Import this data?', text: 'This replaces your current plan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes'});
          if (isConfirmed) {
            state.value = imported;
          }
        } catch { Swal.fire('Error', 'Could not read that file as a valid GAD plan export.', 'error'); }
      };
      reader.readAsText(file);
      e.target.value = '';
    }

    // ─── Excel Import ─────────────────────────────────────────────────────────
    async function promptExcelImport() {
      const { isConfirmed } = await Swal.fire({
         title: 'Import Excel',
         html: `
           <p style="font-size: 16px; font-weight: 500; color: var(--text); margin-bottom: 20px;">You are about to import a GAD Plan & Budget Distribution Excel file.</p>
           <div style="background: rgba(245, 158, 11, 0.1); border: 2px solid rgba(245, 158, 11, 0.4); border-left: 6px solid #f59e0b; padding: 20px; border-radius: 8px; font-size: 15.5px; text-align: left; line-height: 1.6; margin-top: 18px; color: var(--text);">
             <b style="color: #d97706; display: block; font-size: 18px; margin-bottom: 12px;">⚠️ Important Notice</b>
             <ul style="margin: 0; padding-left: 22px; margin-bottom: 0px;">
                <li style="margin-bottom: 8px;">Only the standard 9 columns (Mandate, Cause, Objective, MFO/PAP, Activity, Indicators, Budget, Source, Office) are imported.</li>
                <li>For best results, it is highly recommended to convert your PDF to an Excel file as a <b>single table</b> rather than multiple disconnected tables. You may use <a href="https://www.adobe.com/ph_en/acrobat/online/pdf-to-excel.html" target="_blank" style="color: #2563eb; text-decoration: underline;">Adobe Acrobat Online</a> or any other PDF to Excel converter of your choice.</li>
             </ul>
           </div>
         `,
         width: 600,
         icon: 'info',
         showCancelButton: true,
         confirmButtonText: 'Select File',
         cancelButtonText: 'Cancel'
      });
      
      if (isConfirmed) {
         excelImport.value.click();
      }
    }

    function handleExcelImport(e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = async (evt) => {
        try {
          const data = new Uint8Array(evt.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const json = XLSX.utils.sheet_to_json(worksheet, { header: "A", defval: '' });

          if (!json || json.length === 0) throw new Error('Empty Excel file');

          const parseImportedNumber = (value) => {
             const parsed = parseFloat(String(value ?? '').replace(/[^0-9.-]+/g, ''));
             return Number.isFinite(parsed) ? parsed : 0;
          };
          const fiscalYearMatch = String(worksheet.A1?.v ?? '').match(/\b(20\d{2})\b/);
          const importedFiscalYear = fiscalYearMatch ? Number(fiscalYearMatch[1]) : (parseInt(state.value.org.year) || new Date().getFullYear());
          const importedTotalOrgBudget = parseImportedNumber(
            worksheet.D4?.v ?? worksheet.D4?.w ?? worksheet.C4?.v ?? worksheet.C4?.w
          );

          const importedItems = [];
          
          let currentSection = 'client';
          
          for (let i = 0; i < json.length; i++) {
             const row = json[i];
             
             const textA = (row['A'] || '').toString().toLowerCase();
             const textB = (row['B'] || '').toString().toLowerCase();
             const fullText = textA + " " + textB;
             
             if (fullText.includes('client-focused') || fullText.includes('client focused')) {
                 currentSection = 'client';
                 continue;
             } else if (fullText.includes('organization-focused') || fullText.includes('organization focused')) {
                 currentSection = 'org';
                 continue;
             } else if (fullText.includes('attributed program') || fullText.includes('attributed')) {
                 currentSection = 'attributed';
                 continue;
             }

             const mandate = (row['B'] || '').toString().trim();
             const cause = (row['C'] || '').toString().trim();
             const firstValue = (...keys) => keys.map(key => row[key]).find(value => value !== undefined && value !== null && String(value).trim() !== '') ?? '';
             const objective = String(firstValue('E')).trim();
             const ppa = String(firstValue('H')).trim();
             const activity = String(firstValue('J', 'M')).trim();
             const targets = String(firstValue('K', 'R')).trim();
             const rawBudget = String(firstValue('L', 'T')).trim();
             const rawSource = String(firstValue('N', 'W')).trim();
             const office = String(firstValue('O', 'X')).trim();

             if (!mandate && !activity && !ppa) continue;

             const isHeaderRow = 
                 mandate.replace(/\s+/g, '').toLowerCase() === 'genderissue/gadmandate' ||
                 activity.replace(/\s+/g, '').toLowerCase() === 'gadactivity' ||
                 cause.replace(/\s+/g, '').toLowerCase() === 'causeofgenderissue' ||
                 (mandate === '1' && activity === '5');
                 
             const isTotalRow = 
                 mandate.toLowerCase().includes('sub-total') || 
                 mandate.toLowerCase().includes('grand total') ||
                 cause.toLowerCase().includes('sub-total') || 
                 cause.toLowerCase().includes('grand total');

             if (isHeaderRow || isTotalRow) {
                 continue;
             }

             const budgetLines = [];
             const bPartsRaw = rawBudget.split('\n');
             const sParts = rawSource.split('\n');
             
             let bParts = [];
             let currentPart = '';
             for (let i = 0; i < bPartsRaw.length; i++) {
                 let bStr = bPartsRaw[i].trim();
                 if (!bStr) continue;
                 if (currentPart) currentPart += ' ' + bStr;
                 else currentPart = bStr;
                 
                 // If the concatenated string ends with a valid amount, save it and reset
                 if (/^[0-9.,]+$/.test(currentPart) || currentPart.match(/^(.*?)\s+([0-9]{1,3}(,[0-9]{3})+(\.[0-9]{1,2})?|[0-9]+\.[0-9]{1,2})$/)) {
                     bParts.push(currentPart);
                     currentPart = '';
                 } else if (i === bPartsRaw.length - 1) {
                     bParts.push(currentPart);
                 }
             }

             for (let j = 0; j < bParts.length; j++) {
                 const bStr = bParts[j].trim();
                 if (!bStr) continue;

                 let label = 'Imported Budget Line';
                 let amountStr = '';
                 
                 // If the string is strictly a number (with commas/decimals), it's the amount
                 if (/^[0-9.,]+$/.test(bStr)) {
                     amountStr = bStr;
                 } else {
                     // Look for a strict currency amount at the end of the string (must have comma or decimal)
                     const match = bStr.match(/^(.*?)\s+([0-9]{1,3}(,[0-9]{3})+(\.[0-9]{1,2})?|[0-9]+\.[0-9]{1,2})$/);
                     if (match) {
                         label = match[1].replace(/\s*-\s*$/, '').trim(); // Remove trailing dash if present
                         amountStr = match[2];
                     } else {
                         const dashIdx = bStr.lastIndexOf('-');
                         if (dashIdx > -1) {
                             label = bStr.substring(0, dashIdx).trim();
                             amountStr = bStr.substring(dashIdx + 1).trim();
                         } else {
                             label = bStr;
                             amountStr = '0';
                         }
                     }
                 }
                 
                 if (!label || label === 'Imported') label = 'Imported Budget Line';

                 const amount = parseFloat(amountStr.replace(/[^0-9.-]+/g,"")) || 0;
                 const sourceStr = sParts[j] ? sParts[j].trim() : (sParts[0] ? sParts[0].trim() : 'GAA');

                 budgetLines.push({
                     id: uid('l'),
                     label: label,
                     amount: amount,
                     source: sourceStr || 'GAA'
                 });
             }

             if (budgetLines.length === 0) {
                 budgetLines.push({
                     id: uid('l'),
                     label: 'Imported Budget Line',
                     amount: 0,
                     source: 'GAA'
                 });
             }
             
             importedItems.push({
                section: currentSection,
                mandate: mandate,
                cause: cause,
                result: objective,
                mfo: ppa,
                activity: activity,
                indicators: targets,
                responsible: office,
                budgetLines: budgetLines,
                fiscal_year: importedFiscalYear
             });
          }

          const currentFiscalYear = Number(new Intl.DateTimeFormat('en-US', {
             timeZone: 'Asia/Manila',
             year: 'numeric'
          }).format(new Date()));
          if (importedFiscalYear < currentFiscalYear) {
             const pastYearConfirmation = await Swal.fire({
                title: 'Past fiscal year detected',
                html: `This Excel file is from fiscal year <b>${importedFiscalYear}</b>, which is earlier than the current fiscal year <b>${currentFiscalYear}</b>.<br><br>Are you sure you want to import this past-year GAD Plan and Budget?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, import past year',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#9333ea'
             });
             if (!pastYearConfirmation.isConfirmed) return;
          }

          const { isConfirmed } = await Swal.fire({
             title: 'Preview import: ' + importedItems.length + ' items',
             html: `
               <p style="font-size: 16px; font-weight: 500; color: var(--text); margin-bottom: 16px;">This will create a new budget version and preserve the current version in Budget History.</p>
               <p style="font-size: 14px; color: var(--text); margin-bottom: 16px;">Fiscal year: <b>${importedFiscalYear}</b><br>Total Budget / GAA of Organization: <b>₱${importedTotalOrgBudget.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</b><br>Sections: ${[...new Set(importedItems.map(item => item.section))].join(', ')} · Budget lines: ${importedItems.reduce((sum, item) => sum + item.budgetLines.length, 0)}</p>
               <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 16px; border-radius: 8px; text-align: center;">
                  <b style="color: #b45309; font-size: 16px;">Please double-check the fiscal year, Total Budget / GAA of Organization, and all other imported details before continuing.</b>
               </div>
             `,
             width: 500,
             icon: 'question',
             showCancelButton: true,
             confirmButtonText: 'Create New Version'
          });

          if (isConfirmed) {
             Swal.fire({ title: 'Importing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
             
             const dbPayload = importedItems.map((item, idx) => ({
                fiscal_year: item.fiscal_year,
                section: item.section,
                mandate: item.mandate,
                cause: item.cause,
                objective: item.result,
                result: item.result,
                ppa: item.mfo,
                mfo: item.mfo,
                activity: item.activity,
                targets: item.indicators,
                indicators: item.indicators,
                budget: item.budgetLines[0].amount,
                source: item.budgetLines[0].source,
                office: item.responsible,
                responsible: item.responsible,
                budget_lines: JSON.stringify(item.budgetLines),
                sort_order: state.value.items.filter(i => i.section === item.section).length + idx + 1
             }));

             const res = await api.post('/gpb/import', { org: { ...state.value.org, year: importedFiscalYear, totalOrgBudget: importedTotalOrgBudget }, items: dbPayload });
             if (res.status === 201) {
                Swal.fire('Success', 'Imported ' + res.data.count + ' items successfully.', 'success');
                const importUrl = new URL(window.location.href);
                importUrl.searchParams.set('fiscal_year', String(importedFiscalYear));
                window.history.replaceState({}, '', importUrl);
                await loadFromAPI(importedFiscalYear);
             }
          }
        } catch(e) { 
           console.error(e);
           const importError = e?.message || e?.data?.message || e?.response?.data?.message || '';
           if (importError.toLowerCase().includes('duplicate import') || importError.toLowerCase().includes('same imported details')) {
             Swal.fire('Duplicate import blocked', importError, 'warning');
           } else {
             Swal.fire('Error', importError || 'Could not process the Excel file. Please ensure it follows the correct template format.', 'error');
           }
        }
      };
      reader.readAsArrayBuffer(file);
      e.target.value = '';
    }

    // ─── Excel export ─────────────────────────────────────────────────────────
    function setExportStatus(text, kind) { exportStatus.value = { text, class: kind || '' }; }
    async function exportToExcel() {
      if (!Array.isArray(state.value.items) || state.value.items.length === 0) {
        await Swal.fire({
          title: 'Nothing to export',
          text: 'Add at least one plan and budget item before exporting to Excel.',
          icon: 'warning',
          confirmButtonText: 'OK'
        });
        return;
      }

      const { isConfirmed } = await Swal.fire({
         title: 'Export to Excel',
         html: `
           <p style="font-size: 16px; font-weight: 500; color: var(--text); margin-bottom: 20px;">You are about to export this plan.</p>
         `,
         width: 500,
         icon: 'info',
         showCancelButton: true,
         confirmButtonText: 'Yes, Export',
         cancelButtonText: 'Cancel'
      });
      if (!isConfirmed) return;

      exporting.value = true;
      setExportStatus('');
      try {
        const response = await api.post('/gpb/export-live', state.value, { responseType: 'blob' });
        if (response.status !== 200) {
          let msg = 'Export failed (HTTP ' + response.status + ').';
          try { const err = await response.data.text(); const jsonErr = JSON.parse(err); if (jsonErr?.error) msg = jsonErr.error; } catch(_){}
          setExportStatus(msg, 'error'); return;
        }
        const blob     = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const disp     = response.headers['content-disposition'] || '';
        const match    = /filename="?([^"]+)"?/.exec(disp);
        const fallback = ((state.value.org.name || 'gad-plan').toLowerCase().replace(/[^a-z0-9]+/g,'-')) + '.xlsx';
        const filename = match ? match[1] : fallback;
        const dlUrl    = URL.createObjectURL(blob);
        const a        = document.createElement('a');
        a.href = dlUrl; a.download = filename;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(dlUrl);
        setExportStatus('✓ Downloaded ' + filename, 'success');
      } catch (err) {
        if (err.response?.status === 422) {
          let message = 'Add at least one plan and budget item before exporting to Excel.';
          try {
            const body = err.response.data instanceof Blob
              ? await err.response.data.text()
              : err.response.data;
            const parsed = typeof body === 'string' ? JSON.parse(body) : body;
            if (parsed?.error) message = parsed.error;
          } catch (_) {
            // Keep the user-friendly fallback when the error body is not JSON.
          }
          await Swal.fire('Nothing to export', message, 'warning');
        } else {
          console.error(err);
          setExportStatus('Could not reach the backend server. Is php spark serve running?', 'error');
        }
      } finally { exporting.value = false; }
    }

    // ─── Reset ────────────────────────────────────────────────────────────────
    async function resetToSeed() {
      const { isConfirmed } = await Swal.fire({title: 'Reset Plan?', text: 'The current plan will be preserved in Budget History and a new empty version will become active.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Create Empty Version'});
      if (!isConfirmed) return;
      
      const currentYear = parseInt(state.value.org.year);
      const yearToSet = isNaN(currentYear) ? new Date().getFullYear() : currentYear;
      
      state.value = {
        settings: { apiBaseUrl: '' },
        org: {
          name: state.value.org.name || '',
          category: state.value.org.category || '',
          hierarchy: state.value.org.hierarchy || '',
          year: String(yearToSet),
          totalOrgBudget: 0,
          otherSources: 0,
          preparedByName: '',
          approvedByName: ''
        },
        items: []
      };
      expandedCards.value = [];
      try { await api.post('/plan/reset', { year: yearToSet, org: state.value.org }); }
      catch(e) { Swal.fire('Reset failed', e.response?.data?.message || 'The plan was not reset.', 'error'); }
    }

    async function savePlan() {
      const { isConfirmed } = await Swal.fire({title: 'Save Changes?', text: 'Are you sure you want to save your changes?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Save'});
      if (!isConfirmed) return;
      await saveToAPI();
      Swal.fire('Saved!', 'Your changes have been successfully saved.', 'success');
    }

    async function loadFromAPI(yearOverride = null) {
      try {
        saveStatus.value = 'Loading…';
        const fiscalYear = yearOverride || new URLSearchParams(window.location.search).get('fiscal_year');
        const res = await api.get(fiscalYear ? `/plan?fiscal_year=${encodeURIComponent(fiscalYear)}` : '/plan');
        if (res.status === 200) {
          const data = res.data;
          // Prefill with current Philippine year if empty, invalid, or left as 2000 artifact
          const phYear = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', year: 'numeric' }).format(new Date());
          if (!data.org.year || data.org.year === '2000' || isNaN(parseInt(data.org.year))) {
            if (!data.org) data.org = {};
            data.org.year = phYear;
          }
          state.value = data;
          saveStatus.value = 'Saved';
        } else throw new Error('API error');
      } catch {
        saveStatus.value = 'Error loading plan';
        console.error('Could not load plan from database.');
      }
    }

    async function saveToAPI(cleanState) {
      const payload = cleanState || (() => {
        const c = JSON.parse(JSON.stringify(state.value));
        c.items = c.items.map(({ _isNew, _dirty, _saving, _saved, _dbId, ...rest }) => rest);
        return c;
      })();
      try {
        const res = await api.post('/plan', payload);
        if (res.status === 200 || res.status === 201) {
          saveStatus.value = 'Saved';
          fetchMandateStats();
        } else throw new Error('fail');
      } catch {
        // Could not reach backend
        saveStatus.value = 'Error saving';
        console.error('Could not save plan to database.');
      }
    }

    onMounted(async () => {
      fetchMandateStats();
      await loadFromAPI();
      try {
        const fiscalYear = new URLSearchParams(window.location.search).get('fiscal_year') || state.value.org.year;
        const res = await api.get(`/budget/summary?fiscal_year=${encodeURIComponent(fiscalYear)}`);
        if (res.data?.success) {
          budgetSummary.value = res.data.data;
        }
      } catch (e) {
        console.error('Error fetching budget summary:', e);
      }
    });
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

    // Baseline Amounts Modal Logic
    const isBaselineModalOpen = ref(false);
    const savingBaselines = ref(false);
    const baselineForm = reactive({
      meals_inside: 0,
      meals_outside: 0,
      snacks_inside: 0,
      snacks_outside: 0,
      pf_honoraria: 0,
      tokens: 0,
      materials: 0,
      transportation_limit: 0
    });

    const fetchBaselines = async () => {
      try {
        const res = await api.get('settings/baseline');
        if (res.data) {
          Object.assign(baselineForm, res.data);
        }
      } catch (e) {
        console.error('Failed to fetch baseline amounts', e);
      }
    };

    const openBaselineModal = () => {
      fetchBaselines();
      isBaselineModalOpen.value = true;
    };

    const saveBaselineAmounts = async () => {
      savingBaselines.value = true;
      try {
        await api.post('/settings/baseline', baselineForm);
        Swal.fire({ icon: 'success', title: 'Saved!', text: 'Baseline amounts updated successfully.', timer: 2000, showConfirmButton: false, background: '#1e293b', color: '#fff' });
        isBaselineModalOpen.value = false;
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save baseline amounts.', background: '#1e293b', color: '#fff' });
      } finally {
        savingBaselines.value = false;
      }
    };

    return {
      mandateStats, mandateStatsFilter, filteredMandateStats, loadingStats, statsChartCanvas, scrollToStats,
      isReadOnly,
      showAllocationModal,
      loadingAllocations,
      savingAllocations,
      allocationsData,
      currentAllocationStat,
      allocationsDirty,
      openAllocationModal,
      closeAllocationModal,
      markAllocationsDirty,
      getAllocatedElsewhere,
      saveAllocations,
      arVerifiedTotals,
      isPdfModalOpen, pdfFileUrl, openDocumentPreview, closePdfModal,
      isBaselineModalOpen, savingBaselines, baselineForm, openBaselineModal, saveBaselineAmounts,

      SECTION_ORDER, SECTION_LABELS, SECTION_SHORT, SOURCE_OPTIONS,
      state, budgetSummary, currentTab, searchQuery, expandedCards,
      saveStatus, saveChipClass, saveTooltip,
      exportStatus, exporting, topPanel, fileImport, excelImport,
      ledger, peso, truncate, itemSubtotal,
      getSectionItems, matchesSearch, getItemNumber,
      toggleCard, expandAll, collapseAll,
      addItemInline, markDirty, saveItem, savePlan,
      deleteItem, addBudgetLine, removeBudgetLine,
      handleFileImport, promptExcelImport, handleExcelImport, exportToExcel, resetToSeed,
    };
  }
};
</script>

<style scoped>
/* === GPB System — Light / Dark Mode, matching GAD_AMS palette === */
@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&display=swap');

.app-wrapper {
  /* Purple primary (Light Mode) */
  --primary:           #9333ea;
  --primary-dim:       rgba(147, 51, 234, 0.12);
  --primary-bright:    #7e22ce;
  --primary-container: rgba(147, 51, 234, 0.1);
  --primary-on:        #6b21a8;

  /* Green secondary */
  --secondary:         #16a34a;
  --secondary-dim:     #15803d;
  --secondary-on:      #052e16;

  /* Surfaces (Light Mode) */
  --bg:                transparent;
  --surface:           #ffffff;
  --surface-2:         #f8fafc;
  --surface-3:         #f1f5f9;
  --surface-4:         rgba(147, 51, 234, 0.08);

  /* Text (Light Mode) */
  --text:              #0f172a;
  --text-muted:        #475569;
  --text-dim:          #64748b;

  /* Borders (Light Mode) */
  --border:            #e2e8f0;
  --border-bright:     rgba(147, 51, 234, 0.35);

  /* Status */
  --error:             #dc2626;
  --error-bg:          #fef2f2;
  --success:           #16a34a;
  --warn:              #d97706;

  --shadow:            0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
  --shadow-strong:     0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
  --topbar-h:          64px;

  /* Specific element tokens */
  --topbar-bg:         #ffffff;
  --gpb-shadow:        0 10px 25px -5px rgba(0, 0, 0, 0.05);
  --card-shadow:       0 2px 8px rgba(0, 0, 0, 0.04);
  --input-bg:          #ffffff;
  --input-border:      #cbd5e1;
  --section-head-color: #0f172a;
  --pill-text:         #7e22ce;

  --tab-bg:            #f1f5f9;
  --tab-border:        #cbd5e1;
  --tab-text:          #475569;
  --tab-active-bg:     #9333ea;
  --tab-active-border: #9333ea;
  --tab-active-text:   #ffffff;

  --btn-dist-bg:       #f1f5f9;
  --btn-dist-border:   #cbd5e1;
  --btn-dist-text:     #334155;
  --btn-dist-hover:    #e2e8f0;

  --btn-base-bg:       rgba(147, 51, 234, 0.06);
  --btn-base-border:   #9333ea;
  --btn-base-text:     #7e22ce;
  --btn-base-hover:    rgba(147, 51, 234, 0.12);

  --btn-outline-bg:    #ffffff;
  --btn-outline-border: #cbd5e1;
  --btn-outline-text:  #475569;
  --btn-outline-hover-bg: #f8fafc;
  --btn-outline-hover-border: #7e22ce;
  --btn-outline-hover-text: #0f172a;

  --btn-pri-bg:        linear-gradient(135deg, #990dd1, #b979cc);
  --btn-pri-border:    1px solid transparent;
  --btn-pri-text:      #ffffff;
  --btn-pri-shadow:    0 2px 8px rgba(153, 13, 209, 0.25);
  --btn-pri-hover-bg:  linear-gradient(135deg, #7e22ce, #a855f7);
  --btn-pri-hover-shadow: 0 4px 16px rgba(153, 13, 209, 0.35);

  --btn-danger-bg:     #fef2f2;
  --btn-danger-border: 1px solid #fca5a5;
  --btn-danger-text:   #dc2626;
  --btn-danger-hover:  #fee2e2;

  --cat-pill-bg:       #f8fafc;
  --cat-pill-border:   #cbd5e1;
  --cat-pill-text:     #475569;

  --btn-cancel-bg:     #f1f5f9;
  --btn-cancel-border: 1px solid #cbd5e1;
  --btn-cancel-text:   #475569;
  --btn-cancel-hover:  #e2e8f0;

  /* Activity Card tokens (Light Mode) */
  --card-bg:           #ffffff;
  --card-border:       #e2e8f0;
  --card-border-hover: rgba(147, 51, 234, 0.35);
  --card-shadow:       0 2px 8px rgba(0, 0, 0, 0.04);
  --card-text:         #0f172a;
  --card-text-muted:   #475569;
  --card-text-dim:     #64748b;
  --card-amount:       #7e22ce;
  --card-badge-bg:     rgba(147, 51, 234, 0.1);
  --card-badge-border: rgba(147, 51, 234, 0.2);
  --card-badge-text:   #7e22ce;
  --card-input-bg:     #ffffff;
  --card-input-border: #cbd5e1;
  --card-input-text:   #0f172a;
  --card-editor-bg:    #f8fafc;
  --card-editor-border: #e2e8f0;
  --card-editor-head:  #64748b;
  --card-empty-bg:     #f8fafc;
  --card-empty-border: #e2e8f0;

  --baseline-modal-bg: #ffffff;
  --baseline-modal-border: #e2e8f0;
  --baseline-modal-text: #0f172a;
  --baseline-modal-title: #7e22ce;
  --baseline-input-bg: #f8fafc;
  --baseline-input-border: #cbd5e1;
}

:global(html.dark .app-wrapper),
:global(.dark .app-wrapper) {
  /* Purple accents for Dark Mode */
  --primary:           #c084fc;
  --primary-dim:       rgba(147, 51, 234, 0.2);
  --primary-bright:    #d8b4fe;
  --primary-container: rgba(147, 51, 234, 0.15);
  --primary-on:        #ffffff;

  /* Secondary */
  --secondary:         #4ade80;
  --secondary-dim:     #16a34a;
  --secondary-on:      #052e16;

  /* Outer page background remains white/light */
  --bg:                transparent;

  /* DARKEN THE WHOLE CARD CONTAINER (.gpb-container) */
  --surface:           #1e293b;
  --surface-2:         #0f172a;
  --surface-3:         rgba(15, 23, 42, 0.6);
  --surface-4:         rgba(147, 51, 234, 0.15);

  /* Whole Card Text (Dark Mode) */
  --text:              #ffffff;
  --text-muted:        #cbd5e1;
  --text-dim:          #94a3b8;

  /* Whole Card Borders */
  --border:            rgba(147, 51, 234, 0.25);
  --border-bright:     rgba(147, 51, 234, 0.4);

  /* Specific element tokens for the whole card */
  --topbar-bg:         #0f172a;
  --gpb-shadow:        0 10px 30px -5px rgba(0, 0, 0, 0.3), 0 0 15px rgba(147, 51, 234, 0.1);
  --input-bg:          #0f172a;
  --input-border:      rgba(147, 51, 234, 0.3);
  --section-head-color:#ffffff;
  --pill-text:         #deb7ff;

  --tab-bg:            rgba(15, 23, 42, 0.7);
  --tab-border:        rgba(147, 51, 234, 0.25);
  --tab-text:          #cbd5e1;
  --tab-active-bg:     rgba(147, 51, 234, 0.25);
  --tab-active-border: #c084fc;
  --tab-active-text:   #ffffff;

  --btn-dist-bg:       rgba(255, 255, 255, 0.1);
  --btn-dist-border:   rgba(255, 255, 255, 0.2);
  --btn-dist-text:     #ffffff;
  --btn-dist-hover:    rgba(255, 255, 255, 0.18);

  --btn-base-bg:       transparent;
  --btn-base-border:   #b979cc;
  --btn-base-text:     #b979cc;
  --btn-base-hover:    rgba(185, 121, 204, 0.15);

  --btn-outline-bg:    rgba(15, 23, 42, 0.6);
  --btn-outline-border: rgba(147, 51, 234, 0.3);
  --btn-outline-text:  #cbd5e1;
  --btn-outline-hover-bg: rgba(147, 51, 234, 0.15);
  --btn-outline-hover-border: #c084fc;
  --btn-outline-hover-text: #ffffff;

  --btn-pri-bg:        rgba(147, 51, 234, 0.25);
  --btn-pri-border:    1px solid #c084fc;
  --btn-pri-text:      #ffffff;
  --btn-pri-shadow:    0 2px 8px rgba(168, 85, 247, 0.3);
  --btn-pri-hover-bg:  #c084fc;
  --btn-pri-hover-shadow: 0 4px 16px rgba(168, 85, 247, 0.4);

  --btn-danger-bg:     rgba(220, 38, 38, 0.15);
  --btn-danger-border: 1px solid rgba(248, 113, 113, 0.3);
  --btn-danger-text:   #fca5a5;
  --btn-danger-hover:  rgba(220, 38, 38, 0.25);

  --cat-pill-bg:       #0f172a;
  --cat-pill-border:   rgba(147, 51, 234, 0.25);
  --cat-pill-text:     #cbd5e1;

  --btn-cancel-bg:     rgba(15, 23, 42, 0.6);
  --btn-cancel-border: 1px solid rgba(147, 51, 234, 0.25);
  --btn-cancel-text:   #cbd5e1;
  --btn-cancel-hover:  rgba(147, 51, 234, 0.15);

  /* Activities inside the whole card */
  --card-bg:           #0f172a;
  --card-border:       rgba(147, 51, 234, 0.3);
  --card-border-hover: rgba(192, 132, 252, 0.5);
  --card-shadow:       0 4px 20px rgba(0, 0, 0, 0.25);
  --card-text:         #ffffff;
  --card-text-muted:   #cbd5e1;
  --card-text-dim:     #94a3b8;
  --card-amount:       #d8b4fe;
  --card-badge-bg:     rgba(147, 51, 234, 0.25);
  --card-badge-border: rgba(147, 51, 234, 0.4);
  --card-badge-text:   #deb7ff;
  --card-input-bg:     #1e293b;
  --card-input-border: rgba(147, 51, 234, 0.3);
  --card-input-text:   #ffffff;
  --card-editor-bg:    rgba(30, 41, 59, 0.6);
  --card-editor-border: rgba(147, 51, 234, 0.25);
  --card-editor-head:  #94a3b8;
  --card-empty-bg:     #0f172a;
  --card-empty-border: rgba(147, 51, 234, 0.3);

  --baseline-modal-bg: #1e293b;
  --baseline-modal-border: #b979cc;
  --baseline-modal-text: #ffffff;
  --baseline-modal-title: #b979cc;
  --baseline-input-bg: #0f172a;
  --baseline-input-border: #475569;
}

*, *::before, *::after { box-sizing: border-box; }
.app-wrapper { margin: 0; padding: 0; background: var(--bg); }
.app-wrapper {
  color: var(--text);
  font-size: 15.5px;
  line-height: 1.6;
  min-height: calc(100vh - 80px);
}
.mono { font-family: 'IBM Plex Mono', monospace; }
h1, h2, h3 { margin: 0; font-weight: 800; }
button { font-family: inherit; cursor: pointer; }

.gpb-container {
  min-width: 1200px;
  margin: 32px 32px 0 32px;
  border-radius: 16px;
  overflow: hidden;
  border: 2px solid #eab308;
  box-shadow: var(--gpb-shadow), 0 0 15px rgba(234, 179, 8, 0.2);
  background: var(--surface);
  display: flex;
  flex-direction: column;
}
:global(html.dark) .gpb-container,
:global(.dark) .gpb-container,
:global(html.dark .gpb-container),
:global(.dark .gpb-container) {
  border: 1px solid var(--border) !important;
  box-shadow: var(--gpb-shadow) !important;
}

input, textarea, select {
  font-family: inherit;
  font-size: inherit;
  color: var(--text);
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: 8px;
  transition: border-color 0.15s, box-shadow 0.15s;
}
input:focus, textarea:focus, select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.18);
}
select option { background: #ffffff; color: #0f172a; }
:global(html.dark) select option,
:global(.dark) select option { background: #1e293b; color: #ffffff; }

/* ============================================================
   TOP BAR
   ============================================================ */
.app { display: flex; flex-direction: column; min-height: calc(100vh - 80px); }

.topbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 24px;
  background: var(--topbar-bg);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}

.topbar-brand {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 180px;
  margin-right: 6px;
  border-right: 1px solid var(--border);
  padding-right: 18px;
}
.topbar-eyebrow {
  font-size: 9.5px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--primary-bright);
  font-weight: 700;
  line-height: 1;
  margin-bottom: 3px;
}
.topbar-title {
  font-size: 14.5px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 240px;
}

/* Nav buttons */
.topbar-nav {
  display: flex;
  gap: 4px;
  align-items: center;
  flex: 1;
}
.nav-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 1px solid transparent;
  color: var(--text-muted);
  border-radius: 8px;
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.18s;
  white-space: nowrap;
}
.nav-btn:hover {
  background: var(--surface-3);
  border-color: var(--border-bright);
  color: var(--text);
}
.nav-btn.active {
  background: var(--primary-container);
  border-color: var(--primary-dim);
  color: var(--primary-on);
  box-shadow: 0 0 0 1px var(--primary-dim);
}
.nav-icon { font-size: 14px; }

/* Save chip */
.save-chip {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  background: var(--surface-3);
  color: var(--text-dim);
  border: 1px solid var(--border);
  white-space: nowrap;
  transition: all 0.3s;
  cursor: help;
}
.save-chip.chip-ok   { background: rgba(74,222,128,0.12); color: var(--secondary); border-color: rgba(74,222,128,0.2); }
.save-chip.chip-busy { background: rgba(168,85,247,0.12); color: var(--primary); border-color: rgba(168,85,247,0.2); }
.save-chip.chip-warn { background: rgba(251,191,36,0.1); color: var(--warn); border-color: rgba(251,191,36,0.2); }

/* Action buttons */
.topbar-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.topbar-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 700;
  border: 1px solid var(--border);
  transition: all 0.18s;
  white-space: nowrap;
}

@media (max-width: 1100px) {
  .topbar {
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
  }

  .topbar-brand {
    flex: 1 1 220px;
  }

  .topbar-actions {
    flex: 1 1 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 640px) {
  .topbar {
    padding: 10px 12px;
  }

  .topbar-brand {
    min-width: 0;
    width: 100%;
    border-right: 0;
    border-bottom: 1px solid var(--border);
    padding: 0 0 8px;
  }

  .topbar-title {
    max-width: 100%;
  }

  .topbar-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: 100%;
  }

  .topbar-actions > *,
  .topbar-actions .topbar-btn {
    width: 100%;
  }

  .topbar-actions .topbar-btn {
    justify-content: center;
    padding-left: 8px;
    padding-right: 8px;
  }
}
.btn-budget-dist {
  background: var(--btn-dist-bg);
  border: 1px solid var(--btn-dist-border);
  color: var(--btn-dist-text);
}
.btn-budget-dist:hover {
  background: var(--btn-dist-hover);
}

.btn-baseline {
  background: var(--btn-base-bg);
  border: 1px solid var(--btn-base-border);
  color: var(--btn-base-text);
}
.btn-baseline:hover {
  background: var(--btn-base-hover);
}

.topbar-btn.outline {
  background: var(--btn-outline-bg);
  border: 1px solid var(--btn-outline-border);
  color: var(--btn-outline-text);
}
.topbar-btn.outline:hover {
  background: var(--btn-outline-hover-bg);
  border-color: var(--btn-outline-hover-border);
  color: var(--btn-outline-hover-text);
}

.topbar-btn.primary {
  background: var(--btn-pri-bg);
  border: var(--btn-pri-border);
  color: var(--btn-pri-text);
  box-shadow: var(--btn-pri-shadow);
}
.topbar-btn.primary:hover:not(:disabled) {
  background: var(--btn-pri-hover-bg);
  transform: translateY(-1px);
  box-shadow: var(--btn-pri-hover-shadow);
}
.topbar-btn.primary:disabled { opacity: 0.5; cursor: not-allowed; }

.topbar-btn.danger {
  background: var(--btn-danger-bg);
  border: var(--btn-danger-border);
  color: var(--btn-danger-text);
}
.topbar-btn.danger:hover {
  background: var(--btn-danger-hover);
}

/* Export status bar */
.export-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 24px;
  font-size: 13px;
  font-weight: 600;
  animation: slideDown 0.2s ease;
}
.export-bar.success { background: rgba(74,222,128,0.12); color: var(--secondary); border-bottom: 1px solid rgba(74,222,128,0.2); }
.export-bar.error   { background: var(--error-bg); color: var(--error); border-bottom: 1px solid rgba(248,113,113,0.2); }
.export-bar-close   { background: none; border: none; color: inherit; font-size: 18px; opacity: 0.6; }
.export-bar-close:hover { opacity: 1; }

/* ============================================================
   DROP PANELS
   ============================================================ */
.panel-wrapper {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  padding: 20px 28px;
  animation: slideDown 0.2s ease;
}
@keyframes slideDown {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.panel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 14px;
}

.field { margin-bottom: 0; }
.field label {
  display: block;
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--primary-bright);
  margin-bottom: 5px;
}
.field input, .field textarea, .field select {
  width: 100%;
  padding: 9px 12px;
  font-size: 13.5px;
  resize: vertical;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  color: var(--text);
}
.field input::placeholder, .field textarea::placeholder { color: var(--text-dim); }

.ledger-inline {
  display: flex;
  gap: 36px;
  align-items: flex-start;
  flex-wrap: wrap;
}
.ledger-main { min-width: 260px; }
.ledger-total-label {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--primary-bright);
  margin-bottom: 4px;
}
.ledger-big {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 34px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.02em;
}
.ledger-of  { font-size: 12.5px; color: var(--text-muted); margin-top: 3px; }
.ledger-pct { font-size: 12.5px; color: var(--primary-bright); margin-top: 2px; }

.gauge { margin-top: 14px; }
.gauge-track {
  position: relative;
  height: 8px;
  background: var(--surface-3);
  border-radius: 6px;
  overflow: visible;
  margin-top: 6px;
  width: 260px;
}
.gauge-fill {
  position: absolute; left: 0; top: 0; height: 100%;
  background: linear-gradient(90deg, var(--secondary-dim), var(--secondary));
  border-radius: 6px;
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(74,222,128,0.3);
}
.gauge-mark { position: absolute; top: -4px; width: 2px; height: 16px; background: var(--text-muted); }
.gauge-mark-label {
  position: absolute; top: 15px;
  font-size: 10px; color: var(--text-dim);
  white-space: nowrap; transform: translateX(-50%);
}
.compliance-note {
  font-size: 12px; margin-top: 30px; padding: 7px 10px;
  border-radius: 7px; background: var(--surface-3); border: 1px solid var(--border);
}
.compliance-note.good  { color: var(--secondary); border-color: rgba(74,222,128,0.2); }
.compliance-note.alert { color: var(--error); border-color: rgba(248,113,113,0.2); }

.ledger-breakdown { flex: 1; min-width: 220px; }
.breakdown-row {
  display: flex; justify-content: space-between;
  font-size: 13px; padding: 7px 0;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
}
.breakdown-row:last-child { border-bottom: none; }
.breakdown-row.border-top { border-top: 1px solid var(--border-bright); margin-top: 4px; padding-top: 11px; color: var(--text); }
.breakdown-row .mono { color: var(--text); font-size: 13px; font-weight: 600; }

/* ============================================================
   MAIN CONTENT
   ============================================================ */
.main {
  padding: 28px 36px 80px;
  max-width: 1060px;
  width: 100%;
  margin: 0 auto;
}
.page-header { margin-bottom: 22px; }
.page-title  { font-size: 24px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
.page-sub    { color: var(--text-muted); font-size: 14px; margin: 0; }

/* ============================================================
   TOOLBAR
   ============================================================ */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin-bottom: 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 14px;
  box-shadow: var(--shadow);
}
.search {
  flex: 1; min-width: 180px;
  padding: 9px 13px; font-size: 14px;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  color: var(--text);
  border-radius: 8px;
}
.search::placeholder { color: var(--text-dim); }
.tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.tab {
  border: 1px solid var(--tab-border);
  background: var(--tab-bg);
  color: var(--tab-text);
  padding: 7px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.15s;
}
.tab:hover { border-color: var(--primary-bright); color: var(--primary-bright); }
.tab.active {
  background: var(--tab-active-bg);
  border-color: var(--tab-active-border);
  color: var(--tab-active-text);
}

/* ============================================================
   SECTIONS & CARDS
   ============================================================ */
.section-block { margin-bottom: 36px; }
.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  margin-bottom: 14px;
  border-bottom: 1px solid var(--border);
}
.section-head-left { display: flex; align-items: center; gap: 10px; }
.section-pill {
  background: var(--primary-container);
  color: var(--pill-text);
  border: 1px solid var(--primary-dim);
  border-radius: 6px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
}
.section-head h2 { font-size: 17px; color: var(--section-head-color); font-weight: 800; }

.add-item {
  background: var(--btn-pri-bg);
  border: var(--btn-pri-border);
  color: var(--btn-pri-text);
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 700;
  transition: all 0.18s;
  box-shadow: var(--btn-pri-shadow);
}
.add-item:hover {
  background: var(--btn-pri-hover-bg);
  transform: translateY(-1px);
  box-shadow: var(--btn-pri-hover-shadow);
}

.cards { display: flex; flex-direction: column; gap: 8px; }
.empty-note {
  color: var(--card-text-dim); font-size: 14px; font-style: italic;
  padding: 20px 16px;
  background: var(--card-empty-bg);
  border: 1px dashed var(--card-empty-border);
  border-radius: 10px;
  text-align: center;
}
.empty-note strong { color: var(--card-text-muted); font-style: normal; }

.card {
  box-shadow: var(--card-shadow), 0 0 10px rgba(234, 179, 8, 0.15);
  border: 2px solid #eab308;
  border-radius: 1rem;
  break-inside: avoid;
  margin-bottom: 1rem;
  background: var(--card-bg);
  color: var(--card-text);
  transition: all 0.2s ease;
}
.card:hover { border-color: #ca8a04; }
.card.expanded {
  border-color: #9333ea;
  box-shadow: 0 0 0 1px rgba(147, 51, 234, 0.25), var(--card-shadow);
}
:global(html.dark) .card,
:global(.dark) .card,
:global(html.dark .card),
:global(.dark .card) {
  border: 1px solid var(--card-border) !important;
  box-shadow: var(--card-shadow) !important;
}
:global(html.dark) .card:hover,
:global(.dark) .card:hover,
:global(html.dark .card:hover),
:global(.dark .card:hover) {
  border-color: var(--card-border-hover) !important;
}
:global(html.dark) .card.expanded,
:global(.dark) .card.expanded,
:global(html.dark .card.expanded),
:global(.dark .card.expanded) {
  border-color: #c084fc !important;
}
.card.is-new {
  border-color: var(--secondary-dim);
  box-shadow: 0 0 0 1px var(--secondary-dim), var(--shadow);
  animation: newCardPop 0.3s ease;
}
@keyframes newCardPop {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.card-head { display: flex; align-items: center; gap: 12px; padding: 13px 16px; cursor: pointer; }
.badge {
  font-family: 'IBM Plex Mono', monospace;
  background: var(--card-badge-bg);
  color: var(--card-badge-text);
  border: 1px solid var(--card-badge-border);
  min-width: 32px; height: 32px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12.5px; font-weight: 700; flex-shrink: 0;
}
.card-head-text { flex: 1; min-width: 0; }
.card-title {
  font-size: 16px; font-weight: 600; margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  color: var(--card-text);
}
.card-sub { font-size: 14px; color: var(--card-text-muted); margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-amount { font-family: 'IBM Plex Mono', monospace; color: var(--card-amount); font-size: 15.5px; white-space: nowrap; font-weight: 700; }
.unsaved-dot { color: var(--warn); font-size: 12px; line-height: 1; flex-shrink: 0; }
.chevron { background: none; border: none; font-size: 17px; color: var(--card-text-dim); transition: transform 0.2s ease; padding: 0 4px; }
.card.expanded .chevron { transform: rotate(180deg); }

.card-body { display: none; padding: 4px 16px 18px; border-top: 1px solid var(--card-border); }
.card.expanded .card-body { display: block; }

/* Fields inside card body */
.field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
.field-grid label { grid-column: span 1; font-size: 13px; font-weight: 700; color: var(--card-text-muted); display: block; text-transform: uppercase; letter-spacing: 0.06em; }
.field-grid label.full { grid-column: 1 / -1; }
.field-grid textarea, .field-grid input {
  width: 100%; margin-top: 5px;
  padding: 10px 12px;
  background: var(--card-input-bg);
  border: 1px solid var(--card-input-border);
  border-radius: 8px;
  resize: vertical;
  font-size: 15px;
  color: var(--card-input-text);
}
.field-grid textarea::placeholder, .field-grid input::placeholder { color: var(--card-text-dim); }

/* Budget editor */
.budget-editor {
  margin-top: 18px;
  background: var(--card-editor-bg);
  border: 1px solid var(--card-editor-border);
  border-radius: 10px;
  padding: 12px;
}
.budget-editor-head {
  display: grid;
  grid-template-columns: 1fr 150px 130px 34px;
  gap: 8px;
  font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
  color: var(--card-editor-head); font-weight: 700;
  padding: 0 2px 8px;
  border-bottom: 1px solid var(--card-editor-border);
  margin-bottom: 8px;
}
.budget-line {
  display: grid;
  grid-template-columns: 1fr 150px 130px 34px;
  gap: 8px; margin-bottom: 8px; align-items: center;
}
.source-col {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.source-spec {
  font-size: 12px !important;
  padding: 5px 8px !important;
}
.budget-line input, .budget-line select {
  padding: 9px 10px;
  background: var(--card-input-bg);
  border: 1px solid var(--card-input-border);
  border-radius: 7px;
  width: 100%; font-size: 14.5px;
  color: var(--card-input-text);
}
.remove-line { background: none; border: none; color: var(--error); font-size: 19px; line-height: 1; opacity: 0.7; transition: opacity 0.15s; }
.remove-line:hover { opacity: 1; }
.add-line {
  margin-top: 6px;
  background: none;
  border: 1px dashed var(--primary);
  color: var(--primary-bright);
  border-radius: 8px;
  padding: 7px 12px; font-size: 13px; font-weight: 600;
  transition: all 0.15s;
}
.add-line:hover { background: var(--primary-container); }
.item-subtotal {
  display: flex; justify-content: flex-end; gap: 8px;
  margin-top: 10px; font-size: 14px; color: var(--card-text-muted); font-weight: 600;
}
.subtotal-value { font-family: 'IBM Plex Mono', monospace; color: var(--card-text); font-weight: 700; }

/* Card action row */
.card-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--card-border);
}
.delete-item {
  background: var(--btn-danger-bg);
  border: var(--btn-danger-border);
  color: var(--btn-danger-text);
  border-radius: 8px;
  padding: 8px 14px; font-size: 13px; font-weight: 600;
  transition: all 0.15s;
}
.delete-item:hover { background: var(--btn-danger-hover); }

/* Save Item button on each card */
.save-item-btn {
  background: var(--btn-pri-bg);
  border: var(--btn-pri-border);
  color: var(--btn-pri-text);
  border-radius: 8px;
  padding: 9px 18px;
  font-size: 13.5px; font-weight: 700;
  transition: all 0.18s;
  box-shadow: var(--btn-pri-shadow);
}
.save-item-btn:hover:not(:disabled) {
  background: var(--btn-pri-hover-bg);
  transform: translateY(-1px);
  box-shadow: var(--btn-pri-hover-shadow);
}
.save-item-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.save-item-btn.saving { background: var(--surface-3); border: 1px solid var(--border); color: var(--text-muted); box-shadow: none; }

/* New item banner */
.new-item-banner {
  background: rgba(74,222,128,0.08);
  border: 1px solid rgba(74,222,128,0.2);
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: var(--secondary);
  margin: 12px 0 4px;
}

footer.note {
  margin-top: 28px;
  font-size: 12.5px; color: var(--text-dim);
  line-height: 1.55;
  border-top: 1px solid var(--border);
  padding-top: 18px;
}

/* ============================================================
   ADD ITEM MODAL
   ============================================================ */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.75);
  z-index: 200;
  display: flex; align-items: flex-start; justify-content: center;
  padding: 40px 20px;
  overflow-y: auto;
  animation: fadeIn 0.15s ease;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal {
  background: var(--surface);
  border: 1px solid var(--border-bright);
  border-radius: 16px;
  width: 100%; max-width: 560px;
  box-shadow: var(--shadow-strong);
  animation: slideUp 0.2s ease;
  overflow: hidden;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 22px 24px 16px;
  border-bottom: 1px solid var(--border);
  background: var(--surface-2);
}
.modal-header h3 { font-size: 17px; color: var(--text); font-weight: 800; }
.modal-sub { font-size: 12px; color: var(--text-muted); margin: 3px 0 0; }
.modal-close {
  background: var(--surface-3); border: 1px solid var(--border);
  color: var(--text-muted); width: 30px; height: 30px;
  border-radius: 8px; font-size: 18px;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.15s; flex-shrink: 0;
}
.modal-close:hover { background: var(--surface-4); color: var(--text); }

.modal-body {
  padding: 20px 24px;
  display: flex; flex-direction: column; gap: 15px;
  max-height: 60vh; overflow-y: auto;
}

/* Modal field overrides */
.modal-body .field label {
  font-size: 10.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.1em;
  color: var(--text-muted); margin-bottom: 6px;
}
.modal-body .field input,
.modal-body .field textarea,
.modal-body .field select {
  width: 100%; padding: 10px 13px;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: 9px; font-size: 13.5px;
  color: var(--text); resize: vertical;
}
.modal-body .field input::placeholder,
.modal-body .field textarea::placeholder { color: var(--text-dim); }
.req { color: var(--error); margin-left: 2px; }

/* Category pills */
.category-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.pill {
  flex: 1;
  background: var(--cat-pill-bg);
  border: 1.5px solid var(--cat-pill-border);
  color: var(--cat-pill-text);
  border-radius: 10px;
  padding: 10px 8px; font-size: 13px; font-weight: 700;
  text-align: center; min-width: 130px;
  transition: all 0.18s;
}
.pill:hover  { border-color: var(--primary-bright); color: var(--primary-bright); background: var(--primary-container); }
.pill.active { background: var(--primary-container); border-color: var(--primary); color: var(--pill-text); box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.25); }

/* Budget row in modal */
.modal-budget-inputs {
  display: grid;
  grid-template-columns: 1fr 140px 110px;
  gap: 8px;
  margin-top: 6px;
}
.modal-budget-inputs input,
.modal-budget-inputs select {
  padding: 9px 11px;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: 8px; font-size: 13px;
  color: var(--text);
}

.add-error {
  background: var(--error-bg);
  border: 1px solid rgba(248,113,113,0.3);
  color: var(--error);
  border-radius: 8px;
  padding: 9px 13px;
  font-size: 13px; font-weight: 600;
}

.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 16px 24px 20px;
  border-top: 1px solid var(--border);
  background: var(--surface-2);
}
.modal-btn {
  border-radius: 9px; padding: 10px 22px;
  font-size: 13.5px; font-weight: 700; border: none;
  transition: all 0.18s;
}
.modal-btn.cancel {
  background: var(--btn-cancel-bg);
  border: 1px solid var(--btn-cancel-border);
  color: var(--btn-cancel-text);
}
.modal-btn.cancel:hover { background: var(--btn-cancel-hover); color: var(--text); }

.modal-btn.save {
  background: var(--btn-pri-bg);
  border: var(--btn-pri-border);
  color: var(--btn-pri-text);
  box-shadow: var(--btn-pri-shadow);
}
.modal-btn.save:hover:not(:disabled) {
  background: var(--btn-pri-hover-bg); transform: translateY(-1px);
  box-shadow: var(--btn-pri-hover-shadow);
}
.modal-btn.save:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

:global(html.dark) .modal,
:global(.dark) .modal {
  background: #1e293b;
  border-color: rgba(147, 51, 234, 0.3);
}
:global(html.dark) .modal-header,
:global(.dark) .modal-header {
  background: #0f172a;
  border-bottom-color: rgba(147, 51, 234, 0.2);
}
:global(html.dark) .modal-header h3,
:global(.dark) .modal-header h3 {
  color: #ffffff;
}
:global(html.dark) .modal-sub,
:global(.dark) .modal-sub {
  color: #cbd5e1;
}
:global(html.dark) .modal-body .field label,
:global(.dark) .modal-body .field label {
  color: #cbd5e1;
}
:global(html.dark) .modal-body .field input,
:global(html.dark) .modal-body .field textarea,
:global(html.dark) .modal-body .field select,
:global(html.dark) .modal-budget-inputs input,
:global(html.dark) .modal-budget-inputs select,
:global(.dark) .modal-body .field input,
:global(.dark) .modal-body .field textarea,
:global(.dark) .modal-body .field select,
:global(.dark) .modal-budget-inputs input,
:global(.dark) .modal-budget-inputs select {
  background: #0f172a;
  color: #ffffff;
  border-color: rgba(147, 51, 234, 0.3);
}
:global(html.dark) .modal-footer,
:global(.dark) .modal-footer {
  background: #0f172a;
  border-top-color: rgba(147, 51, 234, 0.2);
}
:global(html.dark) .pill,
:global(.dark) .pill {
  background: #0f172a;
  border-color: rgba(147, 51, 234, 0.25);
  color: #cbd5e1;
}
:global(html.dark) .card select option,
:global(.dark) .card select option {
  background: #1e293b;
  color: #ffffff;
}

/* ============================================================
   BASELINE AMOUNTS MODAL
   ============================================================ */
.baseline-modal-content {
  background: var(--baseline-modal-bg);
  padding: 32px;
  border-radius: 12px;
  width: 600px;
  max-width: 90vw;
  border: 1px solid var(--baseline-modal-border);
  color: var(--baseline-modal-text);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}
.baseline-modal-title {
  margin-top: 0;
  margin-bottom: 24px;
  color: var(--baseline-modal-title);
}
.baseline-modal-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}
.baseline-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  color: var(--baseline-modal-text);
  opacity: 0.85;
  font-weight: 600;
}
.baseline-input {
  width: 100%;
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid var(--baseline-input-border);
  background: var(--baseline-input-bg);
  color: var(--baseline-modal-text);
  font-size: 14px;
}
.baseline-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
.baseline-btn-cancel {
  padding: 8px 16px;
  background: var(--btn-cancel-bg);
  border: 1px solid var(--btn-cancel-border);
  color: var(--btn-cancel-text);
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.15s;
}
.baseline-btn-cancel:hover {
  background: var(--btn-cancel-hover);
}
.baseline-btn-save {
  padding: 8px 16px;
  background: var(--btn-pri-bg);
  border: var(--btn-pri-border);
  color: var(--btn-pri-text);
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.15s;
}
.baseline-btn-save:hover {
  background: var(--btn-pri-hover-bg);
}

/* ============================================================
   SCROLLBAR
   ============================================================ */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--surface-2); }
::-webkit-scrollbar-thumb { background: var(--border-bright); border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: var(--primary-bright); }

/* ============================================================
   RESPONSIVE
   ============================================================ */

@media print {
  .topbar, .panel-wrapper, .toolbar, .export-bar { display: none !important; }
  .app-wrapper { background: #fff; color: #000; }
  .main { padding: 0; max-width: none; }
  .card { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); border: 1px solid var(--border); border-radius: 1rem; break-inside: avoid; margin-bottom: 1rem; background: var(--surface); color: var(--text); }
  .card-body { display: block !important; }
  .chevron, .add-item, .add-line, .delete-item, .remove-line { display: none !important; }
  .field-grid textarea, .field-grid input, .budget-line input, .budget-line select { border: none; background: none; padding: 2px 0; color: #000; }
}

</style>
