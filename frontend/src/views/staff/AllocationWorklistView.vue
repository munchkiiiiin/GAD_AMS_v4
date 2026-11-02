<template>
  <section class="allocation-page">
    <header class="page-heading">
      <div>
        <p class="eyebrow">GAD STAFF · PLAN &amp; BUDGET</p>
        <h1>Budget Allocation Worklist</h1>
        <p class="intro">Find approved Activity Designs and verified Accomplishment Reports that still need budget allocation.</p>
      </div>
      <div class="page-actions">
        <label class="year-filter"><span>Fiscal year</span><select v-model="yearFilter" aria-label="Filter by fiscal year"><option v-for="year in availableYears" :key="year" :value="String(year)">{{ year }}</option></select></label>
        <button class="refresh-button" :disabled="loading" @click="loadWorklist">{{ loading ? 'Refreshing…' : 'Refresh list' }}</button>
      </div>
    </header>

    <div class="summary-grid">
      <button v-for="card in summaryCards" :key="card.key" class="summary-card" :class="[`summary-${card.key}`, { selected: statusFilter === card.key }]" @click="statusFilter = card.key">
        <div class="summary-card-top"><span class="summary-card-icon material-symbols-outlined" aria-hidden="true">{{ card.icon }}</span><span class="summary-card-badge">{{ card.badge }}</span></div>
        <strong class="summary-card-count">{{ card.count }}</strong><span class="summary-card-label">{{ card.displayLabel }}</span><small>{{ money(card.amount) }} {{ card.amountLabel }}</small>
      </button>
    </div>

    <div class="worklist-card">
      <div class="filters">
        <input v-model.trim="search" type="search" placeholder="Search title or control number" aria-label="Search documents">
        <select v-model="typeFilter" aria-label="Filter by document type"><option value="all">All document types</option><option value="AD">Activity Designs</option><option value="AR">Accomplishment Reports</option></select>
        <select v-model="statusFilter" aria-label="Filter by allocation status">
          <option value="all">All allocation statuses</option><option value="unallocated">Unallocated</option><option value="partially_allocated">Partially allocated</option><option value="fully_allocated">Fully allocated</option>
        </select>
        <label class="sort-filter"><span>Sort by</span><select v-model="sortBy" aria-label="Sort documents by"><option value="date_desc">Newest date</option><option value="date_asc">Oldest date</option><option value="title_asc">Title A–Z</option><option value="control_number">Control number</option><option value="budget_desc">Highest budget</option><option value="budget_asc">Lowest budget</option></select></label>
      </div>

      <div v-if="loading" class="state">Loading allocation worklist…</div>
      <div v-else-if="error" class="state error-state">{{ error }} <button @click="loadWorklist">Try again</button></div>
      <div v-else-if="filteredDocuments.length === 0" class="state">
        {{ documents.length ? 'No documents match these filters.' : 'No approved ADs or verified ARs are available for allocation.' }}
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead><tr><th>Document</th><th>Type</th><th>Document date</th><th class="number">Budget</th><th class="number">Allocated</th><th class="number">Remaining</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-for="doc in sortedDocuments" :key="doc.type + '-' + doc.id">
              <td><strong>{{ doc.title || 'Untitled document' }}</strong><small>{{ doc.control_number || 'No control number' }}</small></td>
              <td><span class="type-pill" :class="doc.type.toLowerCase()">{{ doc.type }}</span></td>
              <td>{{ formatDate(doc.date) }}</td>
              <td class="number">{{ money(doc.total_budget) }}</td><td class="number">{{ money(doc.allocated_amount) }}</td><td class="number" :class="{ outstanding: doc.remaining_amount > 0 }">{{ money(doc.remaining_amount) }}</td>
              <td><span class="status-pill" :class="doc.status">{{ statusLabel(doc.status) }}</span></td>
              <td><button class="row-action" @click="openDocument(doc)">{{ doc.status === 'fully_allocated' ? 'Review' : 'Allocate' }}</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <footer class="list-footer">Showing {{ filteredDocuments.length }} of {{ documents.length }} documents</footer>
    </div>

    <div v-if="activeDocument" class="dialog-backdrop" @click.self="closeAllocationDialog">
      <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="allocation-title">
        <header class="dialog-heading"><div><span class="type-pill" :class="activeDocument.type.toLowerCase()">{{ activeDocument.type }}</span><h2 id="allocation-title">{{ activeDocument.title }}</h2><p>{{ activeDocument.control_number }} · {{ statusLabel(activeDocument.status) }}</p></div><button class="close-button" aria-label="Close" :disabled="batchSaving" @click="closeAllocationDialog">×</button></header>
        <div class="document-summary"><span>Total budget <b>{{ money(activeDocument.total_budget) }}</b></span><span>Allocated <b>{{ money(activeDocument.allocated_amount) }}</b></span><span>Remaining <b>{{ money(activeDocument.remaining_amount) }}</b></span></div>
        <div v-if="loadingOptions" class="state">Loading mandates and budget lines…</div>
        <div v-else class="items-list">
          <div class="linked-note">Allocations default to mandates already connected to this document. You can choose another mandate for exceptions.</div>
          <p v-if="batchAllocationMessage" class="batch-result" role="status">{{ batchAllocationMessage }}</p>
          <div v-if="!options.length" class="item-error">No GAD Plan budget lines are available for fiscal year {{ activeDocument.fiscal_year || 'this document' }}. Activate or add the plan budget before allocating.</div>
          <article v-for="item in activeDocument.items" :key="item.id" class="item-row">
            <div class="item-title"><strong>{{ item.item_name || 'Budget item' }}<span v-if="item.sub_item"> · {{ item.sub_item }}</span></strong><small>{{ money(item.amount) }} total · {{ money(item.allocated_amount) }} allocated · {{ money(item.remaining_amount) }} available</small>
              <small v-if="item.allocations.length" class="existing-allocations">Existing: {{ allocationSummary(item.allocations) }}</small>
            </div>
            <div class="mandate-summary-row">
              <div class="mandate-summary" :class="{ 'is-empty': !selectedMandate(item) }">
                <template v-if="selectedMandate(item)">
                  <div class="mandate-summary-heading"><span class="mandate-classification">{{ classificationLabel(selectedMandate(item).section) }}</span><strong>{{ mandateName(selectedMandate(item)) }}</strong></div>
                  <p class="mandate-issue"><span>Connected issue</span>{{ selectedMandate(item).cause || 'No cause specified' }}</p>
                </template>
                <template v-else>
                  <div class="mandate-summary-heading"><span class="mandate-classification muted">Mandate</span><strong>{{ activeDocument.linked_mandate_ids.length ? 'Choose a linked mandate' : 'No linked mandate selected' }}</strong></div>
                  <p class="mandate-issue">{{ activeDocument.linked_mandate_ids.length ? 'Select a linked mandate to view its details.' : 'Choose another mandate to continue.' }}</p>
                </template>
              </div>
              <div class="mandate-actions">
                <button class="mandate-change-button" @click="openMandateModal(item)">{{ item._mandateId ? 'Change mandate' : 'Choose mandate' }}</button>
                <button v-if="item._mandateId && isItemOverride(activeDocument, item) && item._alignedMandateId" class="linked-mandate-toggle" @click="restoreAlignedMandate(activeDocument, item)">Change back to aligned mandate</button>
              </div>
            </div>
            <div class="allocation-fields">
              <div class="budget-line-control">
                <select :value="item._lineId" aria-label="Choose budget line" :disabled="!selectedMandate(item)" @change="handleBudgetLineSelection($event, item)">
                  <option value="">Choose budget line</option>
                  <option v-for="line in selectedMandate(item)?.budget_lines || []" :key="line.id" :value="String(line.id)">{{ budgetLineOptionLabel(line) }}</option>
                  <option value="__add_budget_line__">+ Add budget line…</option>
                </select>
              </div>
              <input v-model.number="item._amount" type="number" min="0" step="0.01" :max="availableForMandate(item)" :disabled="!item._mandateId" placeholder="Amount to allocate" aria-label="Allocation amount">
              <button :disabled="batchSaving || !canSaveItem(item) || savingItemId === item.id" @click="saveItem(activeDocument, item)">{{ savingItemId === item.id ? 'Allocating…' : 'Allocate' }}</button>
            </div>
            <small v-if="item._useOtherMandate && isItemOverride(activeDocument, item)" class="fallback-note">Other-mandate fallback is active. This exception will be recorded in the activity log.</small>
            <p v-if="item._error" class="item-error">{{ item._error }}</p>
          </article>
          <div v-if="!activeDocument.items.length" class="state">This document has no budget items to allocate.</div>
        </div>
        <footer class="dialog-footer"><button class="primary-button" :disabled="batchSaving || savingItemId !== null || !activeDocument.items.some(canSaveItem)" @click="allocateAll(activeDocument)">{{ batchSaving ? 'Allocating…' : 'Allocate all' }}</button><button class="secondary-button" :disabled="batchSaving" @click="closeAllocationDialog">Done</button></footer>
      </section>
    </div>

    <div v-if="mandateModalItem && activeDocument" class="mandate-selection-backdrop" @click.self="closeMandateModal">
      <section class="mandate-selection-dialog" role="dialog" aria-modal="true" aria-labelledby="mandate-selection-title">
        <header class="mandate-selection-header">
          <div><h2 id="mandate-selection-title">Choose a mandate</h2><p>Review the classification and connected issue before selecting.</p></div>
          <button class="close-button" aria-label="Close mandate selection" @click="closeMandateModal">×</button>
        </header>
        <div class="mandate-selection-body">
          <input v-model.trim="mandateSearch" type="search" placeholder="Search mandate or issue" aria-label="Search mandates">
          <div class="mandate-selection-list">
            <button v-for="mandate in mandateModalOptions" :key="mandate.id" class="mandate-choice" :class="{ selected: String(mandate.id) === String(mandateModalItem._mandateId) }" @click="chooseMandate(activeDocument, mandateModalItem, mandate)">
              <div class="mandate-choice-top"><span class="mandate-classification">{{ classificationLabel(mandate.section) }}</span><span class="mandate-choice-status" :class="{ aligned: isLinkedMandate(activeDocument, mandate) }">{{ isLinkedMandate(activeDocument, mandate) ? 'Aligned' : 'Other mandate' }}</span></div>
              <strong>{{ mandateName(mandate) }}</strong>
              <small><b>Connected issue</b>{{ mandate.cause || 'No cause specified' }}</small>
            </button>
            <p v-if="!mandateModalOptions.length" class="mandate-empty-state">No mandates match that search.</p>
          </div>
        </div>
        <footer class="mandate-selection-footer"><button class="secondary-button" @click="closeMandateModal">Cancel</button></footer>
      </section>
    </div>

    <div v-if="budgetLineModalItem && activeDocument" class="mandate-selection-backdrop" @click.self="closeBudgetLineModal">
      <section class="mandate-selection-dialog budget-line-dialog" role="dialog" aria-modal="true" aria-labelledby="budget-line-title">
        <header class="mandate-selection-header">
          <div><h2 id="budget-line-title">Add budget line</h2><p>This line will be saved to the selected mandate’s GAD Plan budget.</p></div>
          <button class="close-button" aria-label="Close budget line form" @click="closeBudgetLineModal">×</button>
        </header>
        <div class="mandate-selection-body budget-line-form">
          <p class="budget-line-mandate">{{ mandateName(selectedMandate(budgetLineModalItem)) }}</p>
          <label>Budget line description<input v-model.trim="budgetLineForm.label" type="text" maxlength="255" placeholder="e.g. Supplies and Materials"></label>
          <div class="budget-line-form-row">
            <label>Planned amount (₱)<input v-model.number="budgetLineForm.amount" type="number" min="0.01" step="0.01" placeholder="0.00"></label>
            <label>Source<select v-model="budgetLineForm.source"><option value="GAA">GAA</option><option value="Other">Other</option></select></label>
          </div>
          <label v-if="budgetLineForm.source === 'Other'">Specify source<input v-model.trim="budgetLineForm.sourceOther" type="text" maxlength="100" placeholder="Enter the fund source"></label>
          <p v-if="budgetLineError" class="item-error">{{ budgetLineError }}</p>
        </div>
        <footer class="mandate-selection-footer">
          <button class="secondary-button" :disabled="savingBudgetLine" @click="closeBudgetLineModal">Cancel</button>
          <button class="primary-button" :disabled="savingBudgetLine || !budgetLineForm.label || Number(budgetLineForm.amount) <= 0 || (budgetLineForm.source === 'Other' && !budgetLineForm.sourceOther.trim())" @click="saveBudgetLine">{{ savingBudgetLine ? 'Saving…' : 'Save budget line' }}</button>
        </footer>
      </section>
    </div>

  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../../api';

const documents = ref([]);
const options = ref([]);
const optionsYear = ref(null);
const loading = ref(true);
const loadingOptions = ref(false);
const error = ref('');
const search = ref('');
const typeFilter = ref('all');
const currentYear = new Date().getFullYear();
const yearFilter = ref(String(currentYear));
const statusFilter = ref('all');
const sortBy = ref('date_desc');
const activeDocument = ref(null);
const mandateModalItemId = ref('');
const mandateSearch = ref('');
const budgetLineModalItemId = ref('');
const budgetLineForm = ref({ label: '', amount: '', source: 'GAA', sourceOther: '' });
const budgetLineError = ref('');
const savingBudgetLine = ref(false);
const savingItemId = ref(null);
const batchSaving = ref(false);
const batchAllocationMessage = ref('');

const statusKeys = ['unallocated', 'partially_allocated', 'fully_allocated'];
const summaryCards = computed(() => [
  { key: 'all', label: 'All documents', displayLabel: 'BUDGET DOCUMENTS', badge: 'OVERVIEW', icon: 'account_balance', count: documents.value.length, amount: documents.value.reduce((sum, d) => sum + Number(d.remaining_amount || 0), 0), amountLabel: 'remaining' },
  ...statusKeys.map(key => {
    const matches = documents.value.filter(d => d.status === key);
    const fullyAllocated = key === 'fully_allocated';
    const cardDetails = {
      unallocated: { displayLabel: 'AWAITING ALLOCATION', badge: 'PENDING', icon: 'hourglass_top' },
      partially_allocated: { displayLabel: 'PARTIALLY ALLOCATED', badge: 'IN PROGRESS', icon: 'trending_up' },
      fully_allocated: { displayLabel: 'FULLY ALLOCATED', badge: 'COMPLETE', icon: 'task_alt' }
    }[key];
    return { key, label: statusLabel(key), ...cardDetails, count: matches.length, amount: matches.reduce((sum, d) => sum + Number(fullyAllocated ? d.allocated_amount || 0 : d.remaining_amount || 0), 0), amountLabel: fullyAllocated ? 'allocated budget' : 'remaining' };
  })
]);
const filteredDocuments = computed(() => documents.value.filter(doc => {
  const query = search.value.toLowerCase();
  return (statusFilter.value === 'all' || doc.status === statusFilter.value)
    && (typeFilter.value === 'all' || doc.type === typeFilter.value)
    && (yearFilter.value === 'all' || String(doc.fiscal_year || '') === yearFilter.value)
    && (!query || `${doc.title} ${doc.control_number}`.toLowerCase().includes(query));
}));
const sortedDocuments = computed(() => [...filteredDocuments.value].sort((a, b) => {
  if (sortBy.value === 'date_asc') return new Date(a.date || 0) - new Date(b.date || 0);
  if (sortBy.value === 'title_asc') return String(a.title || '').localeCompare(String(b.title || ''), 'en', { sensitivity: 'base' });
  if (sortBy.value === 'control_number') return String(a.control_number || '').localeCompare(String(b.control_number || ''), 'en', { numeric: true, sensitivity: 'base' });
  if (sortBy.value === 'budget_desc') return Number(b.total_budget || 0) - Number(a.total_budget || 0);
  if (sortBy.value === 'budget_asc') return Number(a.total_budget || 0) - Number(b.total_budget || 0);
  return new Date(b.date || 0) - new Date(a.date || 0);
}));
const availableYears = computed(() => [...new Set([currentYear, ...documents.value.map(doc => Number(doc.fiscal_year)).filter(Boolean)])].sort((a, b) => b - a));
const mandateModalItem = computed(() => activeDocument.value?.items?.find(item => String(item.id) === String(mandateModalItemId.value)) || null);
const budgetLineModalItem = computed(() => activeDocument.value?.items?.find(item => String(item.id) === String(budgetLineModalItemId.value)) || null);
const mandateModalOptions = computed(() => {
  const query = mandateSearch.value.toLocaleLowerCase();
  return options.value
    .filter(mandate => !query || `${mandateName(mandate)} ${mandate.cause || ''} ${classificationLabel(mandate.section)}`.toLocaleLowerCase().includes(query))
    .sort((a, b) => Number(isLinkedMandate(activeDocument.value, b)) - Number(isLinkedMandate(activeDocument.value, a)));
});

function money(value) { return `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; }
function statusLabel(status) { return ({ all: 'All documents', unallocated: 'Unallocated', partially_allocated: 'Partially allocated', fully_allocated: 'Fully allocated' })[status] || status; }
function formatDate(value) { if (!value) return '—'; const date = new Date(value); return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }); }
function mandateName(mandate) { return mandate ? (mandate.mandate || mandate.activity || 'Unspecified mandate') : ''; }
function mandateTitle(mandate) { return mandate ? [mandateName(mandate), mandate.cause].filter(Boolean).join(' · ') : ''; }
function classificationLabel(section) { return ({ client_focused: 'Client Focused', organization_focused: 'Organization Focused', attributed_program: 'Attributed Program', client: 'Client Focused', org: 'Organization Focused', attributed: 'Attributed Program' })[section] || 'Classification not set'; }
function budgetLineOptionLabel(line) { return `${line.label || 'Budget line'} · ${money(line.amount)}`; }
function selectedMandate(item) { return options.value.find(m => String(m.id) === String(item._mandateId)); }
function isLinkedMandate(doc, mandate) { return Boolean(doc && mandate && (doc.linked_mandate_ids || []).map(String).includes(String(mandate.id))); }
function isItemOverride(doc, item) { return !((doc.linked_mandate_ids || []).map(String).includes(String(item._mandateId))); }
function openMandateModal(item) { mandateModalItemId.value = String(item.id); mandateSearch.value = ''; }
function closeMandateModal() { mandateModalItemId.value = ''; mandateSearch.value = ''; }
function openBudgetLineModal(item) {
  budgetLineModalItemId.value = String(item.id);
  budgetLineForm.value = { label: '', amount: '', source: 'GAA', sourceOther: '' };
  budgetLineError.value = '';
}
function handleBudgetLineSelection(event, item) {
  const value = event.target.value;
  if (value === '__add_budget_line__') {
    event.target.value = item._lineId || '';
    openBudgetLineModal(item);
    return;
  }
  item._lineId = value;
}
function closeBudgetLineModal() {
  if (savingBudgetLine.value) return;
  budgetLineModalItemId.value = '';
  budgetLineError.value = '';
}
function chooseMandate(doc, item, mandate) {
  const mandateId = String(mandate.id);
  const aligned = isLinkedMandate(doc, mandate);
  item._mandateId = mandateId;
  item._lineId = '';
  item._useOtherMandate = !aligned;
  if (aligned) item._alignedMandateId = mandateId;
  closeMandateModal();
}
function restoreAlignedMandate(doc, item) {
  if (!doc || !item._alignedMandateId) return;
  const alignedId = String(item._alignedMandateId);
  if (!options.value.some(mandate => String(mandate.id) === alignedId)) return;
  item._mandateId = alignedId;
  item._lineId = '';
  item._useOtherMandate = false;
}
function availableForMandate(item) {
  const other = (item.allocations || []).reduce((sum, allocation) => sum + (String(allocation.mandate_id) === String(item._mandateId) ? 0 : Number(allocation.allocated_amount || 0)), 0);
  return Math.max(0, Number(item.amount || 0) - other);
}
function canSaveItem(item) { return Boolean(item._mandateId && item._lineId && Number(item._amount) > 0 && Number(item._amount) <= availableForMandate(item)); }
function allocationSummary(allocations) { return allocations.map(a => `${a.mandate_name || `Mandate #${a.mandate_id}`} ${money(a.allocated_amount)}`).join(', '); }

async function loadWorklist() {
  loading.value = true; error.value = '';
  try {
    const response = await api.get('/plan/allocation-worklist');
    documents.value = (response.data.data || []).map(doc => ({ ...doc, linked_mandate_ids: (doc.linked_mandate_ids || []).map(String), items: (doc.items || []).filter(item => Number(item.amount || 0) > 0).map(item => {
      const existing = item.allocations?.[0];
      const linkedIds = (doc.linked_mandate_ids || []).map(String);
      const existingMandate = existing ? String(existing.mandate_id) : '';
      const useOther = Boolean(existing && !linkedIds.includes(existingMandate));
      const defaultMandate = existingMandate || (linkedIds.length === 1 ? linkedIds[0] : '');
      const alignedMandate = linkedIds.includes(existingMandate) ? existingMandate : (linkedIds[0] || '');
      return { ...item, _mandateId: defaultMandate, _alignedMandateId: alignedMandate, _lineId: existing?.gpb_budget_line_id ? String(existing.gpb_budget_line_id) : '', _amount: Number(existing?.allocated_amount ?? item.remaining_amount ?? 0), _useOtherMandate: useOther, _error: '' };
    }) }));
  } catch (err) { error.value = err?.message || 'Could not load the allocation worklist. Please try again.'; }
  finally { loading.value = false; }
}
async function loadOptions(fiscalYear = new Date().getFullYear(), force = false) {
  if (!force && options.value.length && optionsYear.value === Number(fiscalYear)) return;
  loadingOptions.value = true;
  try { const response = await api.get(`/plan/allocation-options?fiscal_year=${Number(fiscalYear)}`); options.value = response.data.data || []; optionsYear.value = Number(fiscalYear); }
  catch (err) { error.value = err?.message || 'Could not load mandates and budget lines.'; }
  finally { loadingOptions.value = false; }
}
function closeAllocationDialog() {
  if (batchSaving.value) return;
  activeDocument.value = null;
  batchAllocationMessage.value = '';
}
async function openDocument(doc) { batchAllocationMessage.value = ''; activeDocument.value = doc; await loadOptions(doc.fiscal_year); }
async function saveBudgetLine() {
  const item = budgetLineModalItem.value;
  const mandate = item ? selectedMandate(item) : null;
  const label = budgetLineForm.value.label.trim();
  const amount = Number(budgetLineForm.value.amount);
  const sourceOther = budgetLineForm.value.sourceOther.trim();
  if (!activeDocument.value || !item || !mandate || !label || !Number.isFinite(amount) || amount <= 0 || (budgetLineForm.value.source === 'Other' && !sourceOther)) {
    budgetLineError.value = 'Enter a description, a positive planned amount, and the required source.';
    return;
  }

  savingBudgetLine.value = true;
  budgetLineError.value = '';
  try {
    const response = await api.post('/plan/mandate-budget-line', {
      mandate_id: Number(mandate.id),
      fiscal_year: Number(activeDocument.value.fiscal_year),
      label,
      amount,
      source: budgetLineForm.value.source,
      source_other: budgetLineForm.value.source === 'Other' ? sourceOther : ''
    });
    const createdLine = response.data?.data?.line;
    if (!createdLine?.id) throw new Error('The line was saved, but its details were not returned. Refresh the list and try again.');
    const cachedMandate = options.value.find(option => String(option.id) === String(mandate.id));
    if (cachedMandate) {
      cachedMandate.budget_lines = [...(cachedMandate.budget_lines || []), createdLine];
      cachedMandate.budget = Number(response.data?.data?.budget ?? cachedMandate.budget ?? 0);
    }
    item._lineId = String(createdLine.id);
    budgetLineModalItemId.value = '';
    budgetLineError.value = '';
    await loadOptions(activeDocument.value.fiscal_year, true);
  } catch (err) {
    budgetLineError.value = err?.message || 'Could not save the new budget line.';
  } finally {
    savingBudgetLine.value = false;
  }
}
async function saveItem(doc, item) {
  if (!canSaveItem(item)) return;
  savingItemId.value = item.id; item._error = '';
  try {
    await api.post('/plan/item-allocation', { budget_item_id: item.id, item_type: doc.type, mandate_id: Number(item._mandateId), gpb_budget_line_id: item._lineId, allocated_amount: Number(item._amount), is_override: isItemOverride(doc, item) });
    await loadWorklist();
    const refreshed = documents.value.find(row => docKey(row) === docKey(doc));
    if (refreshed) activeDocument.value = refreshed;
    await loadOptions(doc.fiscal_year);
  } catch (err) { item._error = err?.message || 'Could not save this allocation.'; }
  finally { savingItemId.value = null; }
}
async function allocateAll(doc) {
  if (!doc || batchSaving.value) return;
  const readyItems = doc.items.filter(canSaveItem);
  const skippedCount = doc.items.length - readyItems.length;
  if (!readyItems.length) {
    batchAllocationMessage.value = 'No items are ready to allocate. Choose a mandate, budget line, and valid amount first.';
    return;
  }

  batchSaving.value = true;
  batchAllocationMessage.value = '';
  const failures = [];
  let allocatedCount = 0;
  try {
    for (const item of readyItems) {
      item._error = '';
      try {
        await api.post('/plan/item-allocation', { budget_item_id: item.id, item_type: doc.type, mandate_id: Number(item._mandateId), gpb_budget_line_id: item._lineId, allocated_amount: Number(item._amount), is_override: isItemOverride(doc, item) });
        allocatedCount += 1;
      } catch (err) {
        const message = err?.message || 'Could not save this allocation.';
        failures.push({ id: String(item.id), name: item.item_name || 'Budget item', message });
      }
    }

    await loadWorklist();
    const refreshed = documents.value.find(row => docKey(row) === docKey(doc));
    if (refreshed) {
      for (const failure of failures) {
        const failedItem = refreshed.items.find(row => String(row.id) === failure.id);
        if (failedItem) failedItem._error = failure.message;
      }
      activeDocument.value = refreshed;
    }
    await loadOptions(doc.fiscal_year);

    const parts = [`${allocatedCount} allocated`];
    if (skippedCount) parts.push(`${skippedCount} skipped (incomplete or invalid)`);
    if (failures.length) parts.push(`${failures.length} failed: ${failures.map(failure => failure.name).join(', ')}`);
    batchAllocationMessage.value = parts.join(' · ');
  } finally {
    batchSaving.value = false;
  }
}
onMounted(loadWorklist);
</script>

<style scoped>
.allocation-page{max-width:1500px;margin:0 auto;padding:28px 24px 48px;color:#172033}.page-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.eyebrow{font-size:11px;font-weight:800;letter-spacing:.12em;color:#7e22ce;margin:0 0 8px}.page-heading h1{font-size:clamp(25px,3vw,34px);margin:0;color:#111827}.intro{color:#64748b;margin:8px 0 0;max-width:720px}.refresh-button,.row-action,.primary-button{border:0;border-radius:9px;background:#6d28d9;color:white;padding:10px 16px;font-weight:700;cursor:pointer}.refresh-button:disabled{opacity:.6}.summary-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}.summary-card{text-align:left;border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:16px;cursor:pointer;color:#475569}.summary-card.selected{border-color:#8b5cf6;box-shadow:0 0 0 2px #ede9fe}.summary-card span,.summary-card small{display:block;font-size:12px}.summary-card strong{display:block;font-size:27px;color:#111827;margin:6px 0}.summary-card small{color:#64748b}.worklist-card{background:white;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 8px 25px #0f172a0a}.filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:16px;border-bottom:1px solid #e2e8f0}.filters input[type=search]{flex:1;min-width:210px}.filters input,.filters select,.allocation-fields select,.allocation-fields input{border:1px solid #cbd5e1;border-radius:8px;padding:10px 11px;background:white;color:#1e293b}.filters select{min-width:175px}.select-all{display:flex;align-items:center;gap:7px;color:#475569;font-size:13px}.bulk-bar{display:flex;align-items:center;gap:12px;padding:11px 16px;background:#f5f3ff;border-bottom:1px solid #ddd6fe;color:#4c1d95}.bulk-bar button{border:0;border-radius:7px;background:#6d28d9;color:white;font-weight:700;padding:8px 12px;cursor:pointer}.bulk-bar .text-button{background:transparent;color:#6d28d9}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:950px}th,td{text-align:left;padding:13px 12px;border-bottom:1px solid #edf0f4;font-size:13px}th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}td strong,td small{display:block}td small{color:#64748b;margin-top:4px}.number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.outstanding{font-weight:800;color:#b45309}.type-pill,.status-pill{display:inline-flex;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800;white-space:nowrap}.type-pill.ad{background:#ede9fe;color:#6d28d9}.type-pill.ar{background:#dcfce7;color:#15803d}.status-pill.unallocated{background:#fee2e2;color:#b91c1c}.status-pill.partially_allocated{background:#fef3c7;color:#92400e}.status-pill.fully_allocated{background:#dcfce7;color:#166534}.status-pill.needs_review{background:#e2e8f0;color:#475569}.row-action{padding:7px 11px;font-size:12px;white-space:nowrap}.state{padding:36px 18px;text-align:center;color:#64748b}.error-state{color:#b91c1c}.error-state button{margin-left:8px;color:#6d28d9;border:0;background:none;text-decoration:underline;cursor:pointer}.list-footer{padding:12px 16px;color:#64748b;font-size:12px;background:#f8fafc}.dialog-backdrop{position:fixed;inset:0;background:#0f172ab8;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}.dialog{background:white;border-radius:15px;width:min(1050px,100%);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 70px #0004}.dialog-heading{padding:20px 22px;display:flex;justify-content:space-between;gap:18px;border-bottom:1px solid #e2e8f0}.dialog-heading h2{font-size:20px;margin:9px 0 3px;color:#111827}.dialog-heading p{margin:0;color:#64748b;font-size:13px}.close-button{align-self:flex-start;border:0;background:transparent;font-size:28px;color:#64748b;cursor:pointer}.document-summary{display:flex;gap:24px;padding:12px 22px;background:#f8fafc;color:#64748b;font-size:13px;flex-wrap:wrap}.document-summary b{color:#172033;margin-left:5px}.items-list{overflow:auto;padding:4px 22px 14px}.item-row{padding:16px 0;border-bottom:1px solid #e2e8f0}.item-title strong,.item-title small{display:block}.item-title small{margin-top:4px;color:#64748b}.existing-allocations{color:#6d28d9!important}.allocation-fields{display:grid;grid-template-columns:1.2fr 1.2fr .8fr auto;gap:8px;margin-top:11px}.allocation-fields select,.allocation-fields input{min-width:0}.allocation-fields input:disabled{background:#f1f5f9}.allocation-fields button{border:0;border-radius:8px;background:#6d28d9;color:#fff;padding:0 16px;font-weight:700;cursor:pointer}.allocation-fields button:disabled,.primary-button:disabled{opacity:.45;cursor:not-allowed}.item-error{color:#b91c1c;font-size:12px;margin:7px 0 0}.dialog-footer{padding:14px 22px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:9px}.secondary-button{border:1px solid #cbd5e1;background:white;border-radius:8px;padding:9px 16px;font-weight:700;color:#334155;cursor:pointer}.bulk-dialog{width:min(760px,100%)}.bulk-summary{padding:0 22px;color:#475569}.bulk-fields{padding:0 22px 14px;grid-template-columns:1fr 1fr}.bulk-preview{padding:0 22px 14px;max-height:38vh;overflow:auto}.bulk-preview div{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #edf0f4;font-size:12px}.bulk-preview b{white-space:nowrap}.bulk-dialog>.item-error{padding:0 22px}.primary-button{padding:10px 16px}@media(max-width:900px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.summary-grid .summary-card:first-child{grid-column:span 2}.page-heading{align-items:flex-start}.allocation-fields{grid-template-columns:1fr 1fr}.allocation-fields button{min-height:40px}}@media(max-width:560px){.allocation-page{padding:20px 12px}.page-heading{display:block}.refresh-button{margin-top:14px}.summary-grid{gap:8px}.summary-card{padding:12px}.filters{align-items:stretch}.filters input[type=search],.filters select{width:100%}.select-all{padding:6px 0}.dialog-backdrop{padding:8px}.dialog{max-height:96vh}.dialog-heading,.document-summary{padding-left:15px;padding-right:15px}.items-list{padding-left:15px;padding-right:15px}.allocation-fields,.bulk-fields{grid-template-columns:1fr}.bulk-bar{flex-wrap:wrap}.bulk-preview{padding-left:15px;padding-right:15px}}
.linked-note{padding:12px 0;color:#475569;font-size:13px}.fallback-toggle{display:inline-block;margin-top:9px;border:0;background:transparent;padding:0;color:#6d28d9;font-size:12px;font-weight:700;text-decoration:underline;cursor:pointer}.fallback-note{display:block;color:#a16207;margin-top:4px}.bulk-fallback-control{padding:0 22px 12px}.bulk-fallback-control small{display:block;color:#a16207;margin-top:4px}.bulk-bar button:disabled{opacity:.5;cursor:not-allowed}.allocation-fields{min-width:0}.allocation-fields select{width:100%;max-width:100%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.allocation-fields{align-items:start}
.allocation-fields select{box-sizing:border-box;height:43px;min-height:43px}
.allocation-fields>input,.allocation-fields>button{box-sizing:border-box;height:43px;min-height:43px;align-self:start}
.mandate-summary-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:12px;margin-top:9px}
.mandate-summary{min-width:0;padding:7px 10px;border:1px solid #e8edf3;border-radius:9px;background:#f8fafc}
.mandate-summary-heading{display:flex;align-items:center;gap:8px;min-width:0}
.mandate-summary-heading strong{min-width:0;color:#1e293b;font-size:12.5px;line-height:1.25;overflow-wrap:anywhere}
.mandate-classification{flex:none;border-radius:999px;background:#ede9fe;padding:3px 7px;color:#6d28d9;font-size:9px;font-weight:800;letter-spacing:.02em}
.mandate-classification.muted{background:#e2e8f0;color:#475569}
.mandate-issue{display:flex;gap:7px;margin:3px 0 0;color:#64748b;font-size:10.5px;line-height:1.25;overflow-wrap:anywhere}
.mandate-issue span{flex:none;color:#94a3b8;font-weight:700}
.mandate-actions{display:flex;flex-direction:row;align-items:center;justify-content:flex-end;gap:8px;min-width:0;flex-wrap:wrap}
.mandate-change-button{border:1px solid #ddd6fe;border-radius:8px;background:#fff;padding:6px 8px;color:#6d28d9;font-size:11px;font-weight:700;white-space:nowrap;cursor:pointer}
.linked-mandate-toggle{border:0;background:transparent;padding:2px 0;color:#64748b;font-size:10px;white-space:nowrap;cursor:pointer}
.allocation-fields{grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;align-items:start}
.budget-line-control{display:flex;min-width:0;flex-direction:column;align-items:stretch}
.summary-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
.bulk-fields{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
.mandate-selection-backdrop{position:fixed;inset:0;z-index:1200;display:flex;align-items:center;justify-content:center;padding:18px;background:#0f172ab8}
.mandate-selection-dialog{display:flex;flex-direction:column;width:min(680px,100%);max-height:min(82vh,760px);overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#fff;box-shadow:0 24px 70px #0005}
.mandate-selection-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:17px 20px;border-bottom:1px solid #e2e8f0}
.mandate-selection-header h2{margin:0;color:#172033;font-size:18px}
.mandate-selection-header p{margin:4px 0 0;color:#64748b;font-size:12px}
.mandate-selection-body{display:flex;min-height:0;flex-direction:column;padding:13px 20px}
.mandate-selection-body>input{box-sizing:border-box;width:100%;height:40px;flex:none;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;color:#1e293b}
.mandate-selection-list{min-height:0;overflow-y:auto;margin-top:9px}
.mandate-choice{display:block;width:100%;margin:0 0 7px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#1e293b;text-align:left;cursor:pointer}
.mandate-choice:hover,.mandate-choice:focus-visible,.mandate-choice.selected{border-color:#c4b5fd;background:#faf8ff;outline:none}
.mandate-choice-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:5px}
.mandate-choice>strong{display:block;font-size:13px;line-height:1.4;overflow-wrap:anywhere}
.mandate-choice>small{display:flex;gap:7px;margin-top:4px;color:#64748b;font-size:11px;line-height:1.4;overflow-wrap:anywhere}
.mandate-choice>small b{flex:none;color:#94a3b8}
.mandate-choice-status{flex:none;color:#a16207;font-size:10px;font-weight:800}
.mandate-choice-status.aligned{color:#15803d}
.mandate-empty-state{margin:0;padding:25px 10px;color:#64748b;text-align:center;font-size:13px}
.mandate-selection-footer{display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid #e2e8f0}
.budget-line-dialog{width:min(540px,100%)}
.budget-line-form{gap:10px}
.budget-line-mandate{margin:0;padding:8px 10px;border-radius:7px;background:#f8fafc;color:#475569;font-size:12px;font-weight:700}
.budget-line-form label{display:flex;flex-direction:column;gap:5px;color:#475569;font-size:12px;font-weight:700}
.budget-line-form input,.budget-line-form select{box-sizing:border-box;width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:8px 10px;color:#1e293b;font:inherit;font-weight:400}
.budget-line-form-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px}
@media(max-width:900px){.mandate-summary-row{grid-template-columns:minmax(0,1fr) minmax(220px,260px)}.allocation-fields{grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto}}
@media(max-width:560px){.mandate-summary-row{grid-template-columns:1fr;gap:7px}.mandate-summary-heading{align-items:flex-start;flex-direction:column;gap:4px}.mandate-actions{justify-content:flex-start}.allocation-fields,.bulk-fields{grid-template-columns:1fr}.allocation-fields>button{min-height:43px}.mandate-selection-backdrop{padding:8px}.mandate-selection-dialog{max-height:92vh}.mandate-selection-header,.mandate-selection-body{padding-left:14px;padding-right:14px}.mandate-selection-footer{padding:10px 14px}}
:global(html.dark) .allocation-page{color:#e2e8f0;color-scheme:dark}
:global(html.dark) .page-heading h1,:global(html.dark) .summary-card strong{color:#f8fafc}
:global(html.dark) .eyebrow{color:#c084fc}
:global(html.dark) .intro,:global(html.dark) .summary-card,:global(html.dark) .summary-card small,:global(html.dark) .list-footer{color:#cbd5e1}
:global(html.dark) .summary-card,:global(html.dark) .worklist-card{border-color:#334155;background:#1e293b;box-shadow:0 8px 25px #0002}
:global(html.dark) .summary-card.selected{border-color:#a78bfa;box-shadow:0 0 0 2px #7c3aed55}
:global(html.dark) .filters{border-color:#334155}
:global(html.dark) .filters input,:global(html.dark) .filters select,:global(html.dark) .allocation-fields select,:global(html.dark) .allocation-fields input,:global(html.dark) .mandate-selection-body>input{border-color:#475569;background:#0f172a;color:#f1f5f9;color-scheme:dark}
:global(html.dark) .filters input::placeholder,:global(html.dark) .allocation-fields input::placeholder{color:#94a3b8}
:global(html.dark) th{background:#0f172a;color:#94a3b8}
:global(html.dark) th,:global(html.dark) td{border-color:#334155}
:global(html.dark) td{color:#e2e8f0}
:global(html.dark) td small{color:#94a3b8}
:global(html.dark) .number{color:#e2e8f0}
:global(html.dark) .outstanding{color:#fbbf24}
:global(html.dark) .type-pill.ad{background:#3b2a61;color:#d8b4fe}
:global(html.dark) .type-pill.ar{background:#123d2b;color:#86efac}
:global(html.dark) .status-pill.unallocated{background:#4c1d24;color:#fda4af}
:global(html.dark) .status-pill.partially_allocated{background:#4a3414;color:#fcd34d}
:global(html.dark) .status-pill.fully_allocated{background:#123d2b;color:#86efac}
:global(html.dark) .state,:global(html.dark) .list-footer{color:#cbd5e1}
:global(html.dark) .list-footer{background:#0f172a}
:global(html.dark) .dialog,:global(html.dark) .mandate-selection-dialog{border:1px solid #334155;background:#0f172a;color:#e2e8f0;box-shadow:0 24px 70px #0008}
:global(html.dark) .dialog-heading,:global(html.dark) .mandate-selection-header,:global(html.dark) .dialog-footer,:global(html.dark) .mandate-selection-footer{border-color:#334155}
:global(html.dark) .dialog-heading h2,:global(html.dark) .mandate-selection-header h2{color:#f8fafc}
:global(html.dark) .dialog-heading p,:global(html.dark) .mandate-selection-header p,:global(html.dark) .bulk-summary{color:#cbd5e1}
:global(html.dark) .document-summary{background:#1e293b;color:#cbd5e1}
:global(html.dark) .document-summary b{color:#f8fafc}
:global(html.dark) .item-row{border-color:#334155}
:global(html.dark) .item-title small{color:#94a3b8}
:global(html.dark) .existing-allocations{color:#c4b5fd!important}
:global(html.dark) .linked-note{color:#cbd5e1}
:global(html.dark) .mandate-summary{border-color:#334155;background:#1e293b}
:global(html.dark) .mandate-summary-heading strong{color:#f1f5f9}
:global(html.dark) .mandate-classification{background:#3b2a61;color:#d8b4fe}
:global(html.dark) .mandate-classification.muted{background:#334155;color:#cbd5e1}
:global(html.dark) .mandate-issue{color:#cbd5e1}
:global(html.dark) .mandate-issue span{color:#94a3b8}
:global(html.dark) .mandate-change-button{border-color:#6d28d9;background:#1e293b;color:#d8b4fe}
:global(html.dark) .linked-mandate-toggle{color:#cbd5e1}
:global(html.dark) .fallback-note{color:#fcd34d}
:global(html.dark) .mandate-choice{border-color:#334155;background:#1e293b;color:#e2e8f0}
:global(html.dark) .mandate-choice:hover,:global(html.dark) .mandate-choice:focus-visible,:global(html.dark) .mandate-choice.selected{border-color:#8b5cf6;background:#312e4b}
:global(html.dark) .mandate-choice>small{color:#cbd5e1}
:global(html.dark) .mandate-choice>small b{color:#94a3b8}
:global(html.dark) .mandate-choice-status.aligned{color:#86efac}
:global(html.dark) .mandate-choice-status{color:#fcd34d}
:global(html.dark) .mandate-empty-state{color:#cbd5e1}
:global(html.dark) .secondary-button{border-color:#475569;background:#1e293b;color:#e2e8f0}
:global(html.dark) .secondary-button:hover{background:#334155}
:global(html.dark) .budget-line-mandate{background:#1e293b;color:#cbd5e1}
:global(html.dark) .budget-line-form label{color:#cbd5e1}
:global(html.dark) .budget-line-form input,:global(html.dark) .budget-line-form select{border-color:#475569;background:#1e293b;color:#f1f5f9;color-scheme:dark}
:global(html.dark) .budget-line-form input::placeholder{color:#94a3b8}
:global(html.dark) .allocation-fields input:disabled{border-color:#334155;background:#1e293b;color:#94a3b8}
:global(html.dark) .item-error{color:#fda4af}
.batch-result{margin:8px 0;padding:9px 11px;border:1px solid #c4b5fd;border-radius:8px;background:#f5f3ff;color:#5b21b6;font-size:12px}
:global(html.dark) .batch-result{border-color:#6d28d9;background:#312e4b;color:#ddd6fe}
.page-actions{display:flex;align-items:flex-end;gap:10px;flex:none}
.year-filter,.sort-filter{display:flex;align-items:center;gap:8px;color:#64748b;font-size:12px;font-weight:700;white-space:nowrap}
.year-filter select,.sort-filter select{box-sizing:border-box;min-height:43px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:10px 11px;color:#1e293b;font:inherit;font-size:13px;font-weight:500}
.year-filter select{min-width:98px}
.sort-filter{margin-left:auto}
:global(html.dark) .year-filter,:global(html.dark) .sort-filter{color:#cbd5e1}
:global(html.dark) .year-filter select,:global(html.dark) .sort-filter select{border-color:#475569;background:#0f172a;color:#f1f5f9;color-scheme:dark}
@media(max-width:900px){.page-actions{align-items:flex-start}.year-filter{flex-direction:column;align-items:flex-start;gap:4px}}
@media(max-width:560px){.page-actions{justify-content:space-between;align-items:flex-end}.year-filter{flex-direction:row;align-items:center}.filters .sort-filter{width:100%;justify-content:space-between;margin-left:0}.sort-filter select{min-width:0;flex:1}}
.summary-card.summary-all{--summary-accent:#7c3aed;--summary-tint:#f5f3ff}
.summary-card.summary-unallocated{--summary-accent:#dc2626;--summary-tint:#fef2f2}
.summary-card.summary-partially_allocated{--summary-accent:#b45309;--summary-tint:#fffbeb}
.summary-card.summary-fully_allocated{--summary-accent:#15803d;--summary-tint:#f0fdf4}
.summary-card[class*="summary-"]{border-color:color-mix(in srgb,var(--summary-accent) 25%,#e2e8f0);border-left:4px solid var(--summary-accent);background:var(--summary-tint)}
.summary-card[class*="summary-"] span,.summary-card[class*="summary-"] strong{color:var(--summary-accent)}
.summary-card[class*="summary-"].selected{border-color:var(--summary-accent);box-shadow:0 0 0 2px var(--summary-tint)}
:global(html.dark) .summary-card.summary-all{--summary-accent:#c4b5fd;--summary-tint:#302548}
:global(html.dark) .summary-card.summary-unallocated{--summary-accent:#fca5a5;--summary-tint:#3b2229}
:global(html.dark) .summary-card.summary-partially_allocated{--summary-accent:#fcd34d;--summary-tint:#3a301b}
:global(html.dark) .summary-card.summary-fully_allocated{--summary-accent:#86efac;--summary-tint:#19352b}
:global(html.dark) .summary-card[class*="summary-"]{border-color:color-mix(in srgb,var(--summary-accent) 35%,#334155);background:var(--summary-tint)}
:global(html.dark) .summary-card[class*="summary-"].selected{border-color:var(--summary-accent);box-shadow:0 0 0 2px color-mix(in srgb,var(--summary-accent) 20%,transparent)}
.summary-card[class*="summary-"]{display:flex;min-height:192px;flex-direction:column;align-items:stretch;justify-content:flex-start;border:1px solid color-mix(in srgb,var(--summary-accent) 52%,#e2e8f0);border-left-width:1px;border-radius:16px;background:#fff;padding:20px;box-shadow:0 8px 22px #0f172a08}
.summary-card-top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.summary-card-icon{display:grid!important;width:46px;height:46px;place-items:center;border-radius:13px;background:var(--summary-tint);color:var(--summary-accent);font-size:25px!important}
.summary-card-badge{display:inline-flex!important;align-items:center;min-height:27px;border:1px solid color-mix(in srgb,var(--summary-accent) 42%,#e2e8f0);border-radius:999px;background:var(--summary-tint);padding:3px 10px;color:var(--summary-accent);font-size:10px!important;font-weight:800;letter-spacing:.04em}
.summary-card[class*="summary-"] .summary-card-count{margin:13px 0 0;color:#111827;font-size:32px;line-height:1.1}
.summary-card[class*="summary-"] .summary-card-label{display:block;margin-top:7px;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.055em}
.summary-card[class*="summary-"] small{margin-top:9px;color:#64748b;font-size:12px}
:global(html.dark) .summary-card[class*="summary-"]{border-color:color-mix(in srgb,var(--summary-accent) 46%,#334155);background:#1e293b;box-shadow:0 8px 22px #0002}
:global(html.dark) .summary-card[class*="summary-"] .summary-card-count{color:#f8fafc}
:global(html.dark) .summary-card[class*="summary-"] .summary-card-label,:global(html.dark) .summary-card[class*="summary-"] small{color:#cbd5e1}
@media(max-width:900px){.summary-card[class*="summary-"]{min-height:178px;padding:16px}}
.summary-card[class*="summary-"]{min-height:150px;padding:14px 16px}
.summary-card-icon{width:38px;height:38px;border-radius:11px;font-size:21px!important}
.summary-card-badge{min-height:23px;padding:2px 8px;font-size:9px!important}
.summary-card[class*="summary-"] .summary-card-count{margin-top:8px;font-size:28px}
.summary-card[class*="summary-"] .summary-card-label{margin-top:4px;font-size:10px}
.summary-card[class*="summary-"] small{margin-top:6px;font-size:11px}
@media(max-width:900px){.summary-card[class*="summary-"]{min-height:140px;padding:12px 14px}}
:global(html.dark) .allocation-page{background:#0f172a;color:#e2e8f0;color-scheme:dark}
:global(html.dark) .page-heading h1{color:#f8fafc}
:global(html.dark) .intro{color:#aab8cc}
:global(html.dark) .eyebrow{color:#c084fc}
:global(html.dark) .worklist-card{border-color:#334155;background:linear-gradient(145deg,#1a1a2e 0%,#16213e 100%);box-shadow:0 16px 36px #02061755}
:global(html.dark) .filters{border-color:#334155;background:#111b30}
:global(html.dark) .filters input,:global(html.dark) .filters select,:global(html.dark) .page-actions select,:global(html.dark) .allocation-fields select,:global(html.dark) .allocation-fields input{border-color:#475569;background:#0b1120;color:#e2e8f0;color-scheme:dark}
:global(html.dark) .filters input::placeholder,:global(html.dark) .allocation-fields input::placeholder{color:#8493aa}
:global(html.dark) .filters input:focus,:global(html.dark) .filters select:focus,:global(html.dark) .page-actions select:focus,:global(html.dark) .allocation-fields select:focus,:global(html.dark) .allocation-fields input:focus,:global(html.dark) .mandate-selection-body>input:focus,:global(html.dark) .budget-line-form input:focus,:global(html.dark) .budget-line-form select:focus{border-color:#c084fc;outline:none;box-shadow:0 0 0 3px #a855f733}
:global(html.dark) .filters select option,:global(html.dark) .page-actions select option,:global(html.dark) .allocation-fields select option,:global(html.dark) .budget-line-form select option{background:#0b1120;color:#e2e8f0}
:global(html.dark) .table-wrap{background:#0f172a}
:global(html.dark) table{background:#0f172a}
:global(html.dark) th{border-color:#334155;background:#111b30;color:#aab8cc}
:global(html.dark) td{border-color:#29364b;color:#e2e8f0}
:global(html.dark) tbody tr{background:#0f172a}
:global(html.dark) tbody tr:nth-child(even){background:#111b30}
:global(html.dark) tbody tr:hover{background:#1b2940}
:global(html.dark) td strong{color:#f1f5f9}
:global(html.dark) td small{color:#94a3b8}
:global(html.dark) .status-pill.unallocated{border:1px solid #7f303b;background:#3a2029;color:#fda4af}
:global(html.dark) .status-pill.partially_allocated{border:1px solid #785a22;background:#3a301b;color:#fcd34d}
:global(html.dark) .status-pill.fully_allocated{border:1px solid #286646;background:#19352b;color:#86efac}
:global(html.dark) .type-pill.ad{border:1px solid #59417e;background:#302548;color:#d8b4fe}
:global(html.dark) .type-pill.ar{border:1px solid #286646;background:#19352b;color:#86efac}
:global(html.dark) .row-action,:global(html.dark) .refresh-button,:global(html.dark) .primary-button,:global(html.dark) .allocation-fields button{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 4px 12px #581c8740}
:global(html.dark) .row-action:hover,:global(html.dark) .refresh-button:hover,:global(html.dark) .primary-button:hover,:global(html.dark) .allocation-fields button:hover{background:linear-gradient(135deg,#8b5cf6,#7c3aed)}
:global(html.dark) .row-action:focus-visible,:global(html.dark) .refresh-button:focus-visible,:global(html.dark) .primary-button:focus-visible,:global(html.dark) .allocation-fields button:focus-visible,:global(html.dark) .secondary-button:focus-visible,:global(html.dark) .mandate-change-button:focus-visible{outline:2px solid #c084fc;outline-offset:2px}
:global(html.dark) .dialog-backdrop,:global(html.dark) .mandate-selection-backdrop{background:#020617d9;backdrop-filter:blur(3px)}
:global(html.dark) .dialog,:global(html.dark) .mandate-selection-dialog{border:1px solid #39465d;background:linear-gradient(145deg,#1a1a2e 0%,#111b30 100%);color:#e2e8f0;box-shadow:0 28px 80px #0009}
:global(html.dark) .dialog-heading,:global(html.dark) .mandate-selection-header,:global(html.dark) .dialog-footer,:global(html.dark) .mandate-selection-footer{border-color:#334155;background:#151f33}
:global(html.dark) .dialog-heading h2,:global(html.dark) .mandate-selection-header h2{color:#f8fafc}
:global(html.dark) .dialog-heading p,:global(html.dark) .mandate-selection-header p,:global(html.dark) .bulk-summary{color:#aab8cc}
:global(html.dark) .close-button{color:#aab8cc}
:global(html.dark) .close-button:hover{color:#f8fafc}
:global(html.dark) .document-summary{border-color:#334155;background:#0f172a;color:#aab8cc}
:global(html.dark) .document-summary b{color:#f8fafc}
:global(html.dark) .items-list{background:#0f172a}
:global(html.dark) .item-row{border-color:#334155}
:global(html.dark) .item-title strong{color:#f1f5f9}
:global(html.dark) .item-title small,:global(html.dark) .linked-note{color:#aab8cc}
:global(html.dark) .mandate-summary{border-color:#39465d;background:#172338}
:global(html.dark) .mandate-summary-heading strong{color:#f1f5f9}
:global(html.dark) .mandate-classification{background:#302548;color:#d8b4fe}
:global(html.dark) .mandate-classification.muted{background:#26354b;color:#cbd5e1}
:global(html.dark) .mandate-issue{color:#aab8cc}
:global(html.dark) .mandate-issue span,:global(html.dark) .mandate-choice>small b{color:#94a3b8}
:global(html.dark) .mandate-change-button{border-color:#7652a8;background:#211b35;color:#d8b4fe}
:global(html.dark) .mandate-change-button:hover{border-color:#c084fc;background:#302548}
:global(html.dark) .linked-mandate-toggle{color:#aab8cc}
:global(html.dark) .fallback-note{color:#fcd34d}
:global(html.dark) .mandate-selection-body>input,:global(html.dark) .budget-line-form input,:global(html.dark) .budget-line-form select{border-color:#475569;background:#0b1120;color:#e2e8f0;color-scheme:dark}
:global(html.dark) .mandate-choice{border-color:#334155;background:#172338;color:#e2e8f0}
:global(html.dark) .mandate-choice:hover,:global(html.dark) .mandate-choice:focus-visible,:global(html.dark) .mandate-choice.selected{border-color:#8b5cf6;background:#282341;box-shadow:0 0 0 2px #7c3aed33}
:global(html.dark) .mandate-choice>small{color:#aab8cc}
:global(html.dark) .mandate-choice-status.aligned{color:#86efac}
:global(html.dark) .mandate-choice-status{color:#fcd34d}
:global(html.dark) .mandate-empty-state{color:#aab8cc}
:global(html.dark) .budget-line-mandate{border:1px solid #334155;background:#172338;color:#cbd5e1}
:global(html.dark) .budget-line-form label{color:#cbd5e1}
:global(html.dark) .secondary-button{border-color:#475569;background:#172338;color:#e2e8f0}
:global(html.dark) .secondary-button:hover{border-color:#64748b;background:#26354b}
:global(html.dark) .state{color:#aab8cc}
:global(html.dark) .error-state,:global(html.dark) .item-error{color:#fda4af}
:global(html.dark) .list-footer{border-color:#334155;background:#111b30;color:#94a3b8}
:global(html.dark) .batch-result{border-color:#7652a8;background:#211b35;color:#e9d5ff}
:global(html.dark) .allocation-fields input:disabled{border-color:#334155;background:#172338;color:#8493aa}
:global(html.dark) .summary-card[class*="summary-"]{background:#172338}
:global(html.dark) .summary-card[class*="summary-"] .summary-card-label,:global(html.dark) .summary-card[class*="summary-"] small{color:#b6c2d3}
:global(html.dark) .summary-card.summary-all{--summary-tint:#302548}
:global(html.dark) .summary-card.summary-unallocated{--summary-tint:#3b2229}
:global(html.dark) .summary-card.summary-partially_allocated{--summary-tint:#3a301b}
:global(html.dark) .summary-card.summary-fully_allocated{--summary-tint:#19352b}
</style>
