<?php
$prefillFormSetId = isset($prefillFormSetId) ? (string)$prefillFormSetId : '';
$importedTemplateId = isset($importedTemplateId) ? (string)$importedTemplateId : '';
?>
<style>
    .form-sets-wrap {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        gap: 16px;
    }
    .form-sets-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }
    .form-sets-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .form-sets-btn {
        /* Toolbar buttons use global .pdftimesaver-btn sizing */
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }
    .form-sets-btn.primary { background: #2563eb; color: #fff; }
    .form-sets-btn.secondary { background: #eef2f7; color: #111827; border: 1px solid #d1d5db; }
    .form-sets-input, .form-sets-select {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
    }
    .form-sets-input { min-width: 260px; }
    .form-sets-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: auto;
    }
    .form-sets-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 640px;
    }
    .form-sets-table th, .form-sets-table td {
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
        text-align: left;
        vertical-align: middle;
    }
    .form-sets-table th { background: #f8fafc; font-size: 13px; color: #111827; }
    .form-sets-empty { color: #64748b; font-size: 14px; padding: 12px 0; }
    .form-sets-muted { color: #64748b; font-size: 13px; }
    .form-sets-status { min-height: 20px; color: #0f766e; font-size: 13px; }
    .form-sets-section { margin-top: 14px; }
    .form-sets-actions-cell {
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .form-sets-btn.primary.form-sets-icon-btn,
    .pdftimesaver-icon-btn.pdftimesaver-btn {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .form-sets-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 14px;
    }
    .form-sets-footer .form-sets-status {
        flex: 1;
        text-align: center;
    }
    .form-set-editor-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .form-set-editor-title {
        margin: 0;
    }
    .form-sets-pager {
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }
    .form-sets-pager-info {
        color: #64748b;
        font-size: 13px;
    }
    @media (max-width: 960px) {
        .form-sets-footer {
            flex-wrap: wrap;
        }
        .form-sets-footer .form-sets-status {
            order: 3;
            flex: 0 0 100%;
            text-align: left;
        }
    }
</style>

<div class="form-sets-wrap">
    <div class="form-sets-card" id="formSetsSearchCard">
        <h2 style="margin:0 0 10px 0;">Form Sets Manager</h2>
        <p class="form-sets-muted" style="margin:0 0 12px 0;">Create and maintain default form sets (Phase 1: global sets).</p>
        <div class="form-sets-row">
            <button type="button" id="addFormSetBtn" class="pdftimesaver-btn form-sets-btn primary">Add</button>
            <input id="formSetSearchInput" type="text" class="form-sets-input" placeholder="Search for Form Set">
            <button type="button" id="searchFormSetsBtn" class="pdftimesaver-btn-secondary form-sets-btn secondary">Search</button>
            <button type="button" id="browseFormSetsBtn" class="pdftimesaver-btn-secondary form-sets-btn secondary">Browse</button>
        </div>
        <div id="formSetsListWrap" style="margin-top:12px;"></div>
    </div>

    <div class="form-sets-card" id="formSetEditorCard" style="display:none;">
        <div class="form-set-editor-head">
            <h3 id="formSetEditorTitle" class="form-set-editor-title">Add Form Set</h3>
            <button type="button" id="deleteFormSetBtn" class="form-delete-icon-btn" title="Delete this form set" aria-label="Delete this form set" style="display:none;">🗑️</button>
        </div>
        <div style="margin-top:12px;">
            <label for="formSetNameInput" class="form-sets-muted" style="display:block;margin-bottom:6px;">Form Set Name</label>
            <input id="formSetNameInput" type="text" class="form-sets-input" style="width:min(520px,100%);" placeholder="Set name">
        </div>
        <div class="form-sets-section">
            <h4 style="margin:0 0 8px 0;">Search for forms</h4>
            <div class="form-sets-row">
                <input id="formSearchInputSets" type="text" class="form-sets-input" style="flex:1;min-width:180px;" placeholder="Search forms...">
                <button type="button" id="importFormBtnSets" class="pdftimesaver-btn-secondary form-sets-btn secondary">Import Form</button>
            </div>
            <div id="availableFormsWrap" style="margin-top:10px;"></div>
        </div>
        <div class="form-sets-section">
            <h4 style="margin:0 0 8px 0;">Selected forms</h4>
            <div id="selectedFormsWrap"></div>
        </div>

        <div class="form-sets-footer">
            <button type="button" id="cancelFormSetEditBtn" class="pdftimesaver-btn pdftimesaver-btn-action form-sets-btn primary">Back</button>
            <div id="formSetsStatus" class="form-sets-status"></div>
            <button type="button" id="finishFormSetBtn" class="pdftimesaver-btn pdftimesaver-btn-action form-sets-btn primary">Finish</button>
        </div>
    </div>
</div>

<script>
(function () {
    const prefillFormSetId = <?php echo json_encode($prefillFormSetId, JSON_UNESCAPED_SLASHES); ?>;
    const importedTemplateId = <?php echo json_encode($importedTemplateId, JSON_UNESCAPED_SLASHES); ?>;
    const prefillFormSetName = <?php echo json_encode((string)($_GET['set_name'] ?? ''), JSON_UNESCAPED_SLASHES); ?>;
    const prefillSetTemplates = <?php echo json_encode((string)($_GET['set_templates'] ?? ''), JSON_UNESCAPED_SLASHES); ?>;
    const searchCard = document.getElementById('formSetsSearchCard');
    const editorCard = document.getElementById('formSetEditorCard');
    const formSetsListWrap = document.getElementById('formSetsListWrap');
    const formSetSearchInput = document.getElementById('formSetSearchInput');
    const formSetNameInput = document.getElementById('formSetNameInput');
    const deleteFormSetBtn = document.getElementById('deleteFormSetBtn');
    const formSearchInputSets = document.getElementById('formSearchInputSets');
    const availableFormsWrap = document.getElementById('availableFormsWrap');
    const selectedFormsWrap = document.getElementById('selectedFormsWrap');
    const statusEl = document.getElementById('formSetsStatus');
    const editorTitle = document.getElementById('formSetEditorTitle');

    const state = {
        formSets: [],
        forms: [],
        editor: { id: '', name: '', templateIds: [] },
        mode: 'search',
        browseFormSets: false,
        paging: {
            formSetsPage: 1,
            availableFormsPage: 1,
        },
    };
    const FORM_SETS_PAGE_SIZE = 10;
    const AVAILABLE_FORMS_PAGE_SIZE = 12;

    function escapeHtml(v) {
        return String(v || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setStatus(msg, isError) {
        if (!statusEl) return;
        statusEl.textContent = msg || '';
        statusEl.style.color = isError ? '#b91c1c' : '#0f766e';
    }

    function formLabel(row) {
        const tid = String(row.templateId || '').trim();
        const name = String(row.formName || '').trim();
        if (!name || name.toLowerCase() === tid.toLowerCase()) return tid;
        return name;
    }

    function renderFormSetsList(forceBrowse) {
        const q = String(formSetSearchInput ? formSetSearchInput.value : '').trim().toLowerCase();
        if (forceBrowse === true) {
            state.browseFormSets = true;
        }
        const isBrowse = state.browseFormSets;
        if (!isBrowse && !q) {
            formSetsListWrap.innerHTML = '<div class="form-sets-empty">Search only: type a form set name or ID, then click Search.</div>';
            return;
        }
        const rows = state.formSets.filter((row) => {
            if (isBrowse) return true;
            return [
                row.name,
                row.id,
                ...(Array.isArray(row.templateIds) ? row.templateIds : []),
            ].join(' ').toLowerCase().includes(q);
        });
        if (!rows.length) {
            formSetsListWrap.innerHTML = '<div class="form-sets-empty">No form sets found.</div>';
            return;
        }
        const totalPages = Math.max(1, Math.ceil(rows.length / FORM_SETS_PAGE_SIZE));
        state.paging.formSetsPage = Math.min(totalPages, Math.max(1, state.paging.formSetsPage));
        const pageStart = (state.paging.formSetsPage - 1) * FORM_SETS_PAGE_SIZE;
        const pageRows = rows.slice(pageStart, pageStart + FORM_SETS_PAGE_SIZE);
        formSetsListWrap.innerHTML = '<div class="form-sets-table-wrap"><table class="form-sets-table"><thead><tr><th>Name</th><th>Forms</th><th>Actions</th></tr></thead><tbody>' +
            pageRows.map((row) => {
                const count = Array.isArray(row.templateIds) ? row.templateIds.length : 0;
                return '<tr>' +
                    '<td><strong>' + escapeHtml(row.name || row.id) + '</strong><div class="form-sets-muted">' + escapeHtml(row.id) + '</div></td>' +
                    '<td>' + String(count) + '</td>' +
                    '<td><button class="pdftimesaver-btn-secondary pdftimesaver-btn-sm form-sets-btn secondary js-edit-form-set" data-id="' + escapeHtml(row.id) + '">Edit</button></td>' +
                '</tr>';
            }).join('') +
            '</tbody></table></div>' +
            '<div class="form-sets-pager">' +
                '<span class="form-sets-pager-info">Page ' + String(state.paging.formSetsPage) + ' of ' + String(totalPages) + '</span>' +
                '<button type="button" class="pdftimesaver-btn-secondary form-sets-btn secondary js-form-sets-page" data-dir="prev"' + (state.paging.formSetsPage <= 1 ? ' disabled' : '') + '>Prev</button>' +
                '<button type="button" class="pdftimesaver-btn-secondary form-sets-btn secondary js-form-sets-page" data-dir="next"' + (state.paging.formSetsPage >= totalPages ? ' disabled' : '') + '>Next</button>' +
            '</div>';
    }

    function renderAvailableForms() {
        const q = String(formSearchInputSets ? formSearchInputSets.value : '').trim().toLowerCase();
        if (!q) {
            availableFormsWrap.innerHTML = '<div class="form-sets-empty">Search only: type a form name, template ID, or location.</div>';
            return;
        }
        const rows = state.forms.filter((row) => {
            return [row.templateId, row.formName, row.sourceFileName, row.formLocation].join(' ').toLowerCase().includes(q);
        });
        if (!rows.length) {
            availableFormsWrap.innerHTML = '<div class="form-sets-empty">No matching forms.</div>';
            return;
        }
        const totalPages = Math.max(1, Math.ceil(rows.length / AVAILABLE_FORMS_PAGE_SIZE));
        state.paging.availableFormsPage = Math.min(totalPages, Math.max(1, state.paging.availableFormsPage));
        const pageStart = (state.paging.availableFormsPage - 1) * AVAILABLE_FORMS_PAGE_SIZE;
        const pageRows = rows.slice(pageStart, pageStart + AVAILABLE_FORMS_PAGE_SIZE);
        availableFormsWrap.innerHTML = '<div class="form-sets-table-wrap"><table class="form-sets-table"><thead><tr><th>Actions</th><th>Form</th><th>Template ID</th></tr></thead><tbody>' +
            pageRows.map((row) => {
                const inSet = state.editor.templateIds.includes(String(row.templateId));
                return '<tr>' +
                    '<td class="form-sets-actions-cell">' +
                        '<a class="pdftimesaver-icon-btn pdftimesaver-btn-secondary form-sets-icon-btn form-sets-btn secondary" href="?route=actions/form-template-pdf&template_id=' + encodeURIComponent(row.templateId) + '" target="_blank" rel="noopener" aria-label="View PDF" title="View PDF">&#128065;</a>' +
                        '<button class="pdftimesaver-icon-btn form-sets-icon-btn form-sets-btn ' + (inSet ? 'primary pdftimesaver-btn' : 'secondary pdftimesaver-btn-secondary') + ' js-add-form-to-set" data-template-id="' + escapeHtml(row.templateId) + '"' + (inSet ? ' disabled aria-label="Added" title="Added"' : ' aria-label="Add to List" title="Add to List"') + '>' + (inSet ? '&#10003;' : '&#43;') + '</button>' +
                    '</td>' +
                    '<td>' + escapeHtml(formLabel(row)) + '</td>' +
                    '<td><code>' + escapeHtml(row.templateId) + '</code></td>' +
                '</tr>';
            }).join('') +
            '</tbody></table></div>' +
            '<div class="form-sets-pager">' +
                '<span class="form-sets-pager-info">Page ' + String(state.paging.availableFormsPage) + ' of ' + String(totalPages) + '</span>' +
                '<button type="button" class="pdftimesaver-btn-secondary form-sets-btn secondary js-available-forms-page" data-dir="prev"' + (state.paging.availableFormsPage <= 1 ? ' disabled' : '') + '>Prev</button>' +
                '<button type="button" class="pdftimesaver-btn-secondary form-sets-btn secondary js-available-forms-page" data-dir="next"' + (state.paging.availableFormsPage >= totalPages ? ' disabled' : '') + '>Next</button>' +
            '</div>';
    }

    function renderSelectedForms() {
        const rows = state.editor.templateIds.map((tid) => state.forms.find((r) => String(r.templateId) === String(tid)) || { templateId: tid, formName: tid, sourceFileName: '' });
        if (!rows.length) {
            selectedFormsWrap.innerHTML = '<div class="form-sets-empty">No forms selected yet.</div>';
            return;
        }
        selectedFormsWrap.innerHTML = '<div class="form-sets-table-wrap"><table class="form-sets-table"><thead><tr><th>Actions</th><th>Order</th><th>Form</th></tr></thead><tbody>' +
            rows.map((row, idx) => {
                const canUp = idx > 0;
                const canDown = idx < rows.length - 1;
                return '<tr>' +
                    '<td class="form-sets-actions-cell">' +
                        '<a class="pdftimesaver-icon-btn pdftimesaver-btn-secondary form-sets-icon-btn form-sets-btn secondary" href="?route=actions/form-template-pdf&template_id=' + encodeURIComponent(row.templateId) + '" target="_blank" rel="noopener" aria-label="View PDF" title="View PDF">&#128065;</a>' +
                        '<button class="pdftimesaver-icon-btn pdftimesaver-btn-secondary form-sets-icon-btn form-sets-btn secondary js-move-form" data-template-id="' + escapeHtml(row.templateId) + '" data-dir="up" aria-label="Move Up" title="Move Up"' + (canUp ? '' : ' disabled') + '>&#9650;</button>' +
                        '<button class="pdftimesaver-icon-btn pdftimesaver-btn-secondary form-sets-icon-btn form-sets-btn secondary js-move-form" data-template-id="' + escapeHtml(row.templateId) + '" data-dir="down" aria-label="Move Down" title="Move Down"' + (canDown ? '' : ' disabled') + '>&#9660;</button>' +
                        '<button class="pdftimesaver-icon-btn pdftimesaver-btn-secondary form-sets-icon-btn form-sets-btn secondary js-remove-form" data-template-id="' + escapeHtml(row.templateId) + '" aria-label="Remove" title="Remove">&#10005;</button>' +
                    '</td>' +
                    '<td>' + String(idx + 1) + '</td>' +
                    '<td>' + escapeHtml(formLabel(row)) + '<div class="form-sets-muted"><code>' + escapeHtml(row.templateId) + '</code></div></td>' +
                '</tr>';
            }).join('') +
            '</tbody></table></div>';
    }

    function openEditor(row) {
        state.mode = 'edit';
        state.editor.id = String(row.id || '');
        state.editor.name = String(row.name || '');
        state.editor.templateIds = Array.isArray(row.templateIds) ? row.templateIds.slice() : [];
        editorTitle.textContent = state.editor.id ? 'Edit Form Set' : 'Add Form Set';
        if (deleteFormSetBtn) {
            deleteFormSetBtn.style.display = state.editor.id ? '' : 'none';
            deleteFormSetBtn.disabled = false;
        }
        formSetNameInput.value = state.editor.name;
        state.paging.availableFormsPage = 1;
        searchCard.style.display = 'none';
        editorCard.style.display = '';
        setStatus('');
        renderAvailableForms();
        renderSelectedForms();
        syncHistoryState();
    }

    function openSearch(resetFilter, skipHistory) {
        state.mode = 'search';
        state.editor.id = '';
        state.editor.name = '';
        state.editor.templateIds = [];
        state.browseFormSets = false;
        if (deleteFormSetBtn) {
            deleteFormSetBtn.style.display = 'none';
            deleteFormSetBtn.disabled = false;
        }
        if (resetFilter && formSetSearchInput) {
            formSetSearchInput.value = '';
        }
        setStatus('');
        searchCard.style.display = '';
        editorCard.style.display = 'none';
        renderFormSetsList();
        if (!skipHistory) {
            syncHistoryState();
        }
    }

    function syncHistoryState(replace) {
        const url = new URL(window.location.href);
        url.searchParams.set('route', 'form-sets-manager');
        if (state.mode === 'edit') {
            if (state.editor.id) {
                url.searchParams.set('set_id', state.editor.id);
            } else {
                url.searchParams.delete('set_id');
                url.searchParams.set('new', '1');
            }
        } else {
            url.searchParams.delete('set_id');
            url.searchParams.delete('new');
        }
        const nextState = {
            formSetsManager: true,
            mode: state.mode,
            setId: state.mode === 'edit' ? (state.editor.id || '') : '',
        };
        if (replace) {
            window.history.replaceState(nextState, '', url.toString());
            return;
        }
        window.history.pushState(nextState, '', url.toString());
    }

    function applyHistoryState(viewState) {
        const mode = String(viewState && viewState.mode ? viewState.mode : '');
        const setId = String(viewState && viewState.setId ? viewState.setId : '');
        if (mode === 'edit') {
            if (setId) {
                const row = state.formSets.find((r) => String(r.id) === setId);
                if (row) {
                    state.mode = 'edit';
                    state.editor.id = String(row.id || '');
                    state.editor.name = String(row.name || '');
                    state.editor.templateIds = Array.isArray(row.templateIds) ? row.templateIds.slice() : [];
                    editorTitle.textContent = state.editor.id ? 'Edit Form Set' : 'Add Form Set';
                    if (deleteFormSetBtn) {
                        deleteFormSetBtn.style.display = state.editor.id ? '' : 'none';
                        deleteFormSetBtn.disabled = false;
                    }
                    formSetNameInput.value = state.editor.name;
                    state.paging.availableFormsPage = 1;
                    searchCard.style.display = 'none';
                    editorCard.style.display = '';
                    setStatus('');
                    renderAvailableForms();
                    renderSelectedForms();
                    return;
                }
            }
            state.mode = 'edit';
            state.editor.id = '';
            state.editor.name = '';
            state.editor.templateIds = [];
            editorTitle.textContent = 'Add Form Set';
            if (deleteFormSetBtn) {
                deleteFormSetBtn.style.display = 'none';
                deleteFormSetBtn.disabled = false;
            }
            formSetNameInput.value = '';
            state.paging.availableFormsPage = 1;
            searchCard.style.display = 'none';
            editorCard.style.display = '';
            setStatus('');
            renderAvailableForms();
            renderSelectedForms();
            return;
        }
        openSearch(false, true);
    }

    function applyViewFromUrl() {
        const params = new URLSearchParams(window.location.search);
        if (params.has('set_id')) {
            const id = String(params.get('set_id') || '');
            applyHistoryState({ mode: 'edit', setId: id, formSetsManager: true });
            return;
        }
        if (params.get('new') === '1') {
            applyHistoryState({ mode: 'edit', setId: '', formSetsManager: true });
            return;
        }
        applyHistoryState({ mode: 'search', setId: '', formSetsManager: true });
    }

    async function loadAll() {
        const res = await fetch('?route=api/form-sets/list', { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Failed to load form sets.');
        }
        state.formSets = Array.isArray(data.formSets) ? data.formSets : [];
        state.forms = Array.isArray(data.forms) ? data.forms : [];
    }

    async function saveCurrentSet() {
        const name = String(formSetNameInput.value || '').trim();
        if (!name) {
            setStatus('Form set name is required.', true);
            formSetNameInput.focus();
            return;
        }
        const deduped = Array.from(new Set(state.editor.templateIds.filter(Boolean)));
        const payload = {
            id: state.editor.id,
            name: name,
            template_ids: deduped,
        };
        const res = await fetch('?route=api/form-sets/upsert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Failed to save form set.');
        }
        state.formSets = Array.isArray(data.formSets) ? data.formSets : state.formSets;
        setStatus('Saved form set.');
        window.location.assign('?route=form-sets-manager');
    }

    document.getElementById('addFormSetBtn').addEventListener('click', function () {
        openEditor({ id: '', name: '', templateIds: [] });
    });
    document.getElementById('searchFormSetsBtn').addEventListener('click', function () {
        state.browseFormSets = false;
        state.paging.formSetsPage = 1;
        renderFormSetsList();
    });
    formSetSearchInput.addEventListener('input', function () {
        state.browseFormSets = false;
        state.paging.formSetsPage = 1;
        renderFormSetsList();
    });
    document.getElementById('browseFormSetsBtn').addEventListener('click', function () {
        state.browseFormSets = true;
        state.paging.formSetsPage = 1;
        if (formSetSearchInput) {
            formSetSearchInput.value = '';
        }
        renderFormSetsList(true);
    });
    formSearchInputSets.addEventListener('input', function () {
        state.paging.availableFormsPage = 1;
        renderAvailableForms();
    });
    document.getElementById('cancelFormSetEditBtn').addEventListener('click', function () {
        openSearch(true);
    });
    document.getElementById('finishFormSetBtn').addEventListener('click', async function () {
        try {
            await saveCurrentSet();
        } catch (e) {
            setStatus(e && e.message ? e.message : 'Save failed.', true);
        }
    });
    if (deleteFormSetBtn) {
        deleteFormSetBtn.addEventListener('click', async function () {
            const id = String(state.editor.id || '').trim();
            if (!id) return;
            if (!window.confirm('Delete this form set? This cannot be undone.')) {
                return;
            }
            try {
                deleteFormSetBtn.disabled = true;
                const res = await fetch('?route=api/form-sets/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Failed to delete form set.');
                }
                state.formSets = Array.isArray(data.formSets) ? data.formSets : state.formSets.filter((row) => String(row.id) !== id);
                openSearch(true);
            } catch (e) {
                setStatus(e && e.message ? e.message : 'Delete failed.', true);
                deleteFormSetBtn.disabled = false;
            }
        });
    }
    document.getElementById('importFormBtnSets').addEventListener('click', function () {
        const currentName = encodeURIComponent(String(formSetNameInput.value || '').trim());
        const currentTemplates = encodeURIComponent((state.editor.templateIds || []).join(','));
        const currentId = String(state.editor.id || '');
        const returnTo = '?route=form-sets-manager'
            + (currentId ? ('&set_id=' + encodeURIComponent(currentId)) : '')
            + '&set_name=' + currentName
            + '&set_templates=' + currentTemplates
            + '&fm_step=1';
        window.location.assign('?route=form-new&finish_redirect=' + encodeURIComponent(returnTo));
    });

    formSetsListWrap.addEventListener('click', function (ev) {
        const pageBtn = ev.target.closest('.js-form-sets-page');
        if (pageBtn) {
            const dir = String(pageBtn.getAttribute('data-dir') || '');
            state.paging.formSetsPage += dir === 'prev' ? -1 : 1;
            renderFormSetsList(state.browseFormSets);
            return;
        }
        const btn = ev.target.closest('.js-edit-form-set');
        if (!btn) return;
        const id = String(btn.getAttribute('data-id') || '');
        const row = state.formSets.find((r) => String(r.id) === id);
        if (row) openEditor(row);
    });
    availableFormsWrap.addEventListener('click', function (ev) {
        const pageBtn = ev.target.closest('.js-available-forms-page');
        if (pageBtn) {
            const dir = String(pageBtn.getAttribute('data-dir') || '');
            state.paging.availableFormsPage += dir === 'prev' ? -1 : 1;
            renderAvailableForms();
            return;
        }
        const btn = ev.target.closest('.js-add-form-to-set');
        if (!btn) return;
        const tid = String(btn.getAttribute('data-template-id') || '');
        if (!tid || state.editor.templateIds.includes(tid)) return;
        state.editor.templateIds.push(tid);
        renderAvailableForms();
        renderSelectedForms();
        setStatus('Form added to set.');
    });
    selectedFormsWrap.addEventListener('click', function (ev) {
        const removeBtn = ev.target.closest('.js-remove-form');
        if (removeBtn) {
            const tid = String(removeBtn.getAttribute('data-template-id') || '');
            state.editor.templateIds = state.editor.templateIds.filter((v) => String(v) !== tid);
            renderAvailableForms();
            renderSelectedForms();
            return;
        }
        const moveBtn = ev.target.closest('.js-move-form');
        if (!moveBtn) return;
        const tid = String(moveBtn.getAttribute('data-template-id') || '');
        const dir = String(moveBtn.getAttribute('data-dir') || '');
        const idx = state.editor.templateIds.findIndex((v) => String(v) === tid);
        if (idx < 0) return;
        const swap = dir === 'up' ? idx - 1 : idx + 1;
        if (swap < 0 || swap >= state.editor.templateIds.length) return;
        const next = state.editor.templateIds.slice();
        const tmp = next[idx];
        next[idx] = next[swap];
        next[swap] = tmp;
        state.editor.templateIds = next;
        renderSelectedForms();
    });

    (async function init() {
        try {
            await loadAll();
            applyViewFromUrl();
            if (prefillFormSetId) {
                const row = state.formSets.find((r) => String(r.id) === prefillFormSetId);
                if (row) {
                    openEditor(row);
                }
            }
            if (!prefillFormSetId && (prefillFormSetName || prefillSetTemplates)) {
                const templateIds = String(prefillSetTemplates || '')
                    .split(',')
                    .map((v) => String(v || '').trim())
                    .filter(Boolean);
                openEditor({ id: '', name: String(prefillFormSetName || ''), templateIds: templateIds });
            }
            const importedTid = String(importedTemplateId || '').trim();
            if (importedTid) {
                if (editorCard.style.display === 'none') {
                    openEditor({ id: '', name: '', templateIds: [] });
                }
                if (!state.editor.templateIds.includes(importedTid)) {
                    state.editor.templateIds.push(importedTid);
                }
                renderAvailableForms();
                renderSelectedForms();
                setStatus('Imported form was added to this set.');
            }
            syncHistoryState(true);
            window.addEventListener('popstate', function () {
                applyViewFromUrl();
            });
        } catch (e) {
            formSetsListWrap.innerHTML = '<div class="form-sets-empty" style="color:#b91c1c;">' + escapeHtml(e && e.message ? e.message : 'Failed to load') + '</div>';
        }
    })();
})();
</script>
