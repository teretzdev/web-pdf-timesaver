<?php
$firmDefaultFields = is_array($firmDefaultFields ?? null) ? $firmDefaultFields : [];
$attorneyFieldRows = is_array($attorneyFieldRows ?? null) ? $attorneyFieldRows : [];
$attorneyFieldOrder = array_flip([
    'attorney_name',
    'attorney_bar_number',
    'attorney_firm',
    'attorney_street',
    'attorney_city',
    'attorney_state',
    'attorney_zip',
    'attorney_phone',
    'attorney_fax',
    'attorney_email',
]);
usort($attorneyFieldRows, static function (array $a, array $b) use ($attorneyFieldOrder): int {
    $aLink = (string)($a['linkId'] ?? '');
    $bLink = (string)($b['linkId'] ?? '');
    $aRank = $attorneyFieldOrder[$aLink] ?? 999;
    $bRank = $attorneyFieldOrder[$bLink] ?? 999;
    if ($aRank !== $bRank) {
        return $aRank <=> $bRank;
    }
    return strcasecmp((string)($a['displayName'] ?? ''), (string)($b['displayName'] ?? ''));
});
?>

<style>
    .firm-defaults-card {
        background: #fff;
        border: 1px solid #dbe3ec;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .firm-defaults-top { margin-bottom: 10px; }
    .firm-defaults-help {
        color: #475569;
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 24px 0;
        line-height: 1.5;
    }
    .firm-defaults-table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: auto;
    }
    .firm-defaults-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 820px;
    }
    .firm-defaults-table th {
        background: #f8fafc;
        color: #1e293b;
        font-size: 13px;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    .firm-defaults-table td {
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .firm-defaults-list {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
    }
    .firm-defaults-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
        align-items: start;
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .firm-defaults-row:last-child {
        border-bottom: 0;
    }
    .firm-defaults-value-cell {
        min-width: 0;
    }
    .firm-defaults-table th:first-child {
        text-align: right;
    }
    .firm-defaults-label-cell {
        text-align: left;
        white-space: normal;
        font-weight: 600;
        color: #334155;
    }
    .firm-defaults-input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
    }
    .firm-defaults-input:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .firm-defaults-status {
        margin-top: 12px;
        min-height: 18px;
        font-size: 13px;
        color: #0f766e;
    }
    .firm-defaults-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .firm-defaults-save-btn {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .firm-defaults-save-btn:hover {
        background: #eef2f7;
    }
    .firm-defaults-save-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .firm-defaults-empty {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 8px;
        color: #475569;
        padding: 16px;
    }
    .firm-defaults-muted {
        color: #64748b;
        font-size: 12px;
    }
    .firm-attorneys-card {
        margin-top: 24px;
    }
    .firm-attorney-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 12px;
        background: #fff;
        overflow: hidden;
    }
    .firm-attorney-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .firm-attorney-title {
        font-weight: 700;
        color: #0f172a;
    }
    .firm-attorney-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .firm-attorney-body {
        padding: 14px;
    }
    .firm-attorney-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .firm-attorney-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 4px;
    }
    @media (max-width: 768px) {
        .firm-attorney-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .firm-defaults-row { padding: 10px; }
        .firm-defaults-label-cell {
            font-weight: 700;
            margin-bottom: 0;
            overflow: visible;
            text-overflow: clip;
            word-break: break-word;
        }
    }
    /* Keep firm-defaults usable on narrow desktop/tablet widths even if sidebar is present. */
    @media (max-width: 1024px) {
        html, body {
            overflow-x: hidden;
        }
        .pdftimesaver-sidebar {
            transform: translateX(-100%) !important;
        }
        .pdftimesaver-main-content {
            margin-left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .pdftimesaver-content-body {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        .firm-defaults-card {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
    }
</style>

<div class="firm-defaults-card">
    <div class="firm-defaults-top">
        <h2 style="margin:0; font-size:22px; color:#0f172a;">Firm Information</h2>
    </div>
    <p class="firm-defaults-help">
        Set default firm values used by the importer. Fields are typed by Field Manager configuration. Values save automatically a short time after you change a field (debounced), or when you leave a field.
    </p>

    <div class="firm-defaults-finish-callout" style="margin: 0 0 16px 0; padding: 14px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; max-width: 720px;">
        <strong style="color: #0f172a; display: block; margin-bottom: 8px;">What does “Save All” do?</strong>
        <p class="firm-defaults-muted" style="margin: 0; font-size: 14px; line-height: 1.5; color: #334155;">
            <strong>Save All</strong> immediately saves every field that still has a delayed (pending) autosave—clearing the short timers and sending the current values to the server right away. Use it before closing the tab or navigating elsewhere if you want to be sure nothing is still waiting to post. It does not reset or discard your values.
        </p>
    </div>

    <div class="firm-defaults-actions">
        <button id="firmDefaultsSaveAllBtn" type="button" class="firm-defaults-save-btn" title="Save all pending autosaves now">Save All</button>
    </div>
    <div id="firmDefaultsTableContainer"></div>
    <div id="firmDefaultsStatus" class="firm-defaults-status" aria-live="polite"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const state = {
        fields: <?php echo json_encode(array_values($firmDefaultFields), JSON_UNESCAPED_SLASHES); ?>
    };
    const saveTimers = Object.create(null);
    const pendingValues = Object.create(null);
    const saveInFlight = Object.create(null);
    const tableContainer = document.getElementById('firmDefaultsTableContainer');
    const statusEl = document.getElementById('firmDefaultsStatus');
    const saveAllBtn = document.getElementById('firmDefaultsSaveAllBtn');
    let isFinishingAll = false;

    function setStatus(message, isError) {
        statusEl.textContent = message || '';
        statusEl.style.color = isError ? '#b91c1c' : '#0f766e';
    }

    function esc(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function isIsoDate(raw) {
        return /^\d{4}-\d{2}-\d{2}$/.test(String(raw || '').trim());
    }

    function valueInputHtml(field) {
        const id = esc(String(field.id || ''));
        const type = String(field.fieldType || 'text').toLowerCase();
        const value = String(field.value || '');
        if (type === 'select') {
            return '<span class="firm-defaults-muted">Select defaults not supported yet.</span>';
        }
        if (type === 'checkbox') {
            const checked = ['1', 'true', 'yes', 'on'].includes(value.trim().toLowerCase()) ? ' checked' : '';
            return '<input class="js-default-input" data-kind="checkbox" data-id="' + id + '" type="checkbox"' + checked + '>';
        }
        if (type === 'number') {
            return '<input class="firm-defaults-input js-default-input" data-kind="text" data-id="' + id + '" type="number" step="any" value="' + esc(value) + '">';
        }
        if (type === 'date') {
            const normalized = isIsoDate(value) ? value : '';
            return '<input class="firm-defaults-input js-default-input" data-kind="text" data-id="' + id + '" type="date" value="' + esc(normalized) + '">';
        }
        if (type === 'email') {
            return '<input class="firm-defaults-input js-default-input" data-kind="text" data-id="' + id + '" type="email" value="' + esc(value) + '">';
        }
        if (type === 'phone') {
            return '<input class="firm-defaults-input js-default-input" data-kind="text" data-id="' + id + '" type="tel" value="' + esc(value) + '">';
        }
        return '<input class="firm-defaults-input js-default-input" data-kind="text" data-id="' + id + '" type="text" value="' + esc(value) + '">';
    }

    function renderTable() {
        const rows = Array.isArray(state.fields) ? state.fields : [];
        if (!rows.length) {
            tableContainer.innerHTML = '<div class="firm-defaults-empty">No firm fields found yet. Add firm fields in Field Manager first.</div>';
            return;
        }
        const body = rows.map(function (field) {
            const id = esc(String(field.id || ''));
            const name = esc(String(field.displayName || '').trim().replace(/\s+/g, ' '));
            return '<div class="firm-defaults-row" data-id="' + id + '">' +
                '<div class="firm-defaults-label-cell">' + name + ':</div>' +
                '<div class="firm-defaults-value-cell">' + valueInputHtml(field) + '</div>' +
            '</div>';
        }).join('');
        tableContainer.innerHTML = '<div class="firm-defaults-list">' + body + '</div>';
    }

    async function saveFieldValue(id, value) {
        const res = await fetch('?route=api/firm-defaults/update-value', {
            method: 'POST',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, value: value })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to save default');
        }
        const row = data.field || {};
        state.fields = state.fields.map(function (f) {
            return String(f.id || '') === String(row.id || '') ? Object.assign({}, f, row) : f;
        });
    }

    function readInputValue(input) {
        if (!input) return '';
        const kind = String(input.getAttribute('data-kind') || 'text');
        return kind === 'checkbox' ? (input.checked ? '1' : '') : String(input.value || '');
    }

    async function finishSave(id) {
        const rowId = String(id || '');
        if (!rowId) return;
        if (!Object.prototype.hasOwnProperty.call(pendingValues, rowId)) return;
        if (saveInFlight[rowId]) return;
        const value = pendingValues[rowId];
        delete pendingValues[rowId];
        saveInFlight[rowId] = true;
        try {
            setStatus('Saving...');
            await saveFieldValue(rowId, value);
            setStatus('Default value saved.');
        } catch (error) {
            setStatus(error.message || 'Failed to save default.', true);
        } finally {
            saveInFlight[rowId] = false;
            if (Object.prototype.hasOwnProperty.call(pendingValues, rowId)) {
                void finishSave(rowId);
            }
        }
    }

    function queueSave(id, value, immediate) {
        const rowId = String(id || '');
        if (!rowId) return;
        pendingValues[rowId] = value;
        if (saveTimers[rowId]) {
            clearTimeout(saveTimers[rowId]);
            delete saveTimers[rowId];
        }
        if (immediate) {
            void finishSave(rowId);
            return;
        }
        saveTimers[rowId] = setTimeout(function () {
            delete saveTimers[rowId];
            void finishSave(rowId);
        }, 450);
    }

    async function finishAllPendingSaves() {
        if (isFinishingAll) return;
        isFinishingAll = true;
        if (saveAllBtn) saveAllBtn.disabled = true;
        Object.keys(saveTimers).forEach(function (rowId) {
            clearTimeout(saveTimers[rowId]);
            delete saveTimers[rowId];
        });
        const ids = Object.keys(pendingValues);
        for (const rowId of ids) {
            await finishSave(rowId);
        }
        isFinishingAll = false;
        if (saveAllBtn) saveAllBtn.disabled = false;
    }

    async function reloadFieldsFromApi() {
        try {
            const response = await fetch('?route=api/firm-defaults/fields&_ts=' + Date.now(), { cache: 'no-store' });
            const payload = await response.json();
            if (response.ok && payload && payload.success && Array.isArray(payload.fields)) {
                state.fields = payload.fields;
                renderTable();
            }
        } catch (_error) {
            // Keep current in-memory state if reload fails
        }
    }

    document.addEventListener('input', function (event) {
        const input = event.target.closest('.js-default-input[data-id]');
        if (!input) return;
        const id = String(input.getAttribute('data-id') || '');
        if (!id) return;
        queueSave(id, readInputValue(input), false);
    });

    document.addEventListener('change', function (event) {
        const input = event.target.closest('.js-default-input[data-id]');
        if (!input) return;
        const id = String(input.getAttribute('data-id') || '');
        if (!id) return;
        queueSave(id, readInputValue(input), true);
    });

    document.addEventListener('focusout', function (event) {
        const input = event.target.closest('.js-default-input[data-id]');
        if (!input) return;
        const id = String(input.getAttribute('data-id') || '');
        if (!id) return;
        queueSave(id, readInputValue(input), true);
    });

    window.addEventListener('beforeunload', function () {
        void finishAllPendingSaves();
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            void finishAllPendingSaves();
        }
    });

    document.addEventListener('click', function (event) {
        const anchor = event.target.closest('a[href]');
        if (!anchor) return;
        const href = String(anchor.getAttribute('href') || '');
        if (href === '' || href.startsWith('#') || Object.keys(pendingValues).length === 0) return;
        event.preventDefault();
        void finishAllPendingSaves().then(function () {
            window.location.href = href;
        });
    }, true);

    if (saveAllBtn) {
        saveAllBtn.addEventListener('click', function () {
            void finishAllPendingSaves().then(function () {
                setStatus('All changes saved.');
            });
        });
    }

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            void reloadFieldsFromApi();
        }
    });

    renderTable();
    void reloadFieldsFromApi();
});
</script>

<div class="firm-defaults-card firm-attorneys-card">
    <div class="firm-defaults-top">
        <h2 style="margin:0; font-size:22px; color:#0f172a;">Attorneys</h2>
    </div>
    <p class="firm-defaults-help">
        Maintain your firm&rsquo;s attorney roster. These records populate the attorney picker on Project View. Field definitions come from Field Manager &rarr; Attorney Information Fields.
    </p>
    <div class="firm-defaults-actions">
        <button id="addAttorneyBtn" type="button" class="firm-defaults-save-btn">Add Attorney</button>
    </div>
    <div id="attorneyRosterContainer"></div>
    <div id="attorneyRosterStatus" class="firm-defaults-status" aria-live="polite"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldRows = <?php echo json_encode(array_values($attorneyFieldRows), JSON_UNESCAPED_SLASHES); ?>;
    const rosterContainer = document.getElementById('attorneyRosterContainer');
    const rosterStatus = document.getElementById('attorneyRosterStatus');
    const addBtn = document.getElementById('addAttorneyBtn');
    let attorneys = [];
    let editingDraftId = '';

    function setRosterStatus(message, isError) {
        rosterStatus.textContent = message || '';
        rosterStatus.style.color = isError ? '#b91c1c' : '#0f766e';
    }

    function esc(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fieldValuesFromDom(attorneyId) {
        const out = {};
        const safeId = String(attorneyId || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        document.querySelectorAll('.js-attorney-roster-field[data-attorney-id="' + safeId + '"]').forEach(function (input) {
            const fid = String(input.getAttribute('data-field-id') || '');
            if (!fid) return;
            out[fid] = String(input.value || '');
        });
        return out;
    }

    function renderRoster() {
        if (!fieldRows.length) {
            rosterContainer.innerHTML = '<div class="firm-defaults-empty">No attorney fields configured yet. Add Attorney Information fields in Field Manager first.</div>';
            return;
        }
        const rows = Array.isArray(attorneys) ? attorneys : [];
        if (!rows.length && !editingDraftId) {
            rosterContainer.innerHTML = '<div class="firm-defaults-empty">No attorneys yet. Click Add Attorney to create one.</div>';
            return;
        }
        const cards = rows.map(function (row) {
            return buildAttorneyCard(row.id, row.displayName || 'Attorney', row.fieldValues || {});
        });
        if (editingDraftId) {
            cards.unshift(buildAttorneyCard(editingDraftId, 'New Attorney', {}));
        }
        rosterContainer.innerHTML = cards.join('');
    }

    function buildAttorneyCard(attorneyId, title, fieldValues) {
        const fieldsHtml = fieldRows.map(function (field) {
            const fid = String(field.id || '');
            const label = String(field.displayName || fid);
            const value = String((fieldValues && fieldValues[fid]) || '');
            return '<div class="firm-attorney-field">' +
                '<label>' + esc(label) + '</label>' +
                '<input type="text" class="firm-defaults-input js-attorney-roster-field" data-attorney-id="' + esc(attorneyId) + '" data-field-id="' + esc(fid) + '" value="' + esc(value) + '">' +
            '</div>';
        }).join('');
        const isDraft = String(attorneyId).indexOf('draft_') === 0;
        return '<div class="firm-attorney-item" data-attorney-id="' + esc(attorneyId) + '">' +
            '<div class="firm-attorney-head">' +
                '<div class="firm-attorney-title">' + esc(title) + '</div>' +
                '<div class="firm-attorney-actions">' +
                    '<button type="button" class="firm-defaults-save-btn js-save-attorney">Save</button>' +
                    (isDraft
                        ? '<button type="button" class="firm-defaults-save-btn js-cancel-attorney">Cancel</button>'
                        : '<button type="button" class="firm-defaults-save-btn js-delete-attorney">Remove</button>') +
                '</div>' +
            '</div>' +
            '<div class="firm-attorney-body"><div class="firm-attorney-grid">' + fieldsHtml + '</div></div>' +
        '</div>';
    }

    async function loadAttorneys() {
        const res = await fetch('?route=api/attorneys/list&_ts=' + Date.now(), { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to load attorneys');
        }
        attorneys = Array.isArray(data.attorneys) ? data.attorneys : [];
        renderRoster();
    }

    async function saveAttorney(attorneyId) {
        const payload = {
            fieldValues: fieldValuesFromDom(attorneyId)
        };
        if (String(attorneyId).indexOf('draft_') !== 0) {
            payload.id = attorneyId;
        }
        const res = await fetch('?route=api/attorneys/upsert', {
            method: 'POST',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to save attorney');
        }
        editingDraftId = '';
        await loadAttorneys();
        setRosterStatus('Attorney saved.');
    }

    async function deleteAttorney(attorneyId) {
        if (!window.confirm('Remove this attorney from the roster? Existing projects keep their saved copy.')) return;
        const res = await fetch('?route=api/attorneys/delete', {
            method: 'POST',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: attorneyId })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to remove attorney');
        }
        await loadAttorneys();
        setRosterStatus('Attorney removed.');
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            if (editingDraftId) return;
            editingDraftId = 'draft_' + Date.now();
            renderRoster();
        });
    }

    rosterContainer.addEventListener('click', function (event) {
        const saveBtn = event.target.closest('.js-save-attorney');
        if (saveBtn) {
            const card = saveBtn.closest('[data-attorney-id]');
            const attorneyId = card ? String(card.getAttribute('data-attorney-id') || '') : '';
            if (!attorneyId) return;
            setRosterStatus('Saving...');
            void saveAttorney(attorneyId).catch(function (err) {
                setRosterStatus(err.message || 'Save failed.', true);
            });
            return;
        }
        const deleteBtn = event.target.closest('.js-delete-attorney');
        if (deleteBtn) {
            const card = deleteBtn.closest('[data-attorney-id]');
            const attorneyId = card ? String(card.getAttribute('data-attorney-id') || '') : '';
            if (!attorneyId) return;
            void deleteAttorney(attorneyId).catch(function (err) {
                setRosterStatus(err.message || 'Remove failed.', true);
            });
            return;
        }
        const cancelBtn = event.target.closest('.js-cancel-attorney');
        if (cancelBtn) {
            editingDraftId = '';
            renderRoster();
        }
    });

    void loadAttorneys().catch(function (err) {
        setRosterStatus(err.message || 'Failed to load attorneys.', true);
    });
});
</script>
