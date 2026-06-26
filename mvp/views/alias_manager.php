<?php
$formImporterAliases = is_array($formImporterAliases ?? null) ? array_values($formImporterAliases) : [];
$formCustomFields = is_array($formCustomFields ?? null) ? array_values($formCustomFields) : [];
?>

<style>
    .alias-manager-card {
        background: #fff;
        border: 1px solid #dbe3ec;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    .alias-manager-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: end;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .alias-manager-help {
        color: #475569;
        font-size: 14px;
        margin: 0 0 12px 0;
        line-height: 1.5;
    }
    .alias-manager-search {
        width: min(420px, 100%);
    }
    .alias-builder {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 14px;
        margin-bottom: 14px;
    }
    .alias-builder-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        align-items: end;
    }
    .alias-builder-full {
        grid-column: 1 / -1;
    }
    .alias-builder-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        color: #334155;
        font-weight: 700;
    }
    .alias-help-tip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14px;
        height: 14px;
        margin-left: 6px;
        border-radius: 999px;
        border: 1px solid #94a3b8;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
        cursor: help;
        user-select: none;
        vertical-align: middle;
    }
    .alias-builder-help {
        margin: 0 0 10px 0;
        color: #475569;
        font-size: 13px;
        line-height: 1.45;
    }
    .alias-inline-help {
        margin-top: 4px;
        font-size: 11px;
        color: #64748b;
        line-height: 1.35;
    }
    .alias-builder textarea {
        min-height: 76px;
        resize: vertical;
    }
    .alias-builder-options {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .alias-builder-options label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #334155;
    }
    .alias-builder-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }
    .alias-manager-table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: auto;
    }
    .alias-manager-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1120px;
    }
    .alias-manager-table th {
        background: #f8fafc;
        color: #1e293b;
        font-size: 12px;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .alias-manager-table td {
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .alias-manager-input,
    .alias-manager-select {
        width: 100%;
        padding: 7px 9px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
        background: #fff;
    }
    .alias-manager-input:focus-visible,
    .alias-manager-select:focus-visible,
    .alias-manager-btn:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .alias-manager-checkbox {
        width: 16px;
        height: 16px;
    }
    .alias-manager-btn {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .alias-manager-btn.primary {
        background: #1976d2;
        border-color: #1976d2;
        color: #fff;
    }
    .alias-manager-btn.danger {
        border-color: #fecaca;
        color: #b91c1c;
        background: #fff;
    }
    .alias-manager-related {
        font-size: 12px;
        color: #475569;
        line-height: 1.35;
    }
    .alias-manager-related .missing {
        color: #b91c1c;
        font-weight: 700;
    }
    .alias-manager-status {
        margin-top: 12px;
        min-height: 18px;
        font-size: 13px;
        color: #0f766e;
    }
    .alias-manager-empty {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px;
        color: #475569;
    }
    .alias-manager-id-note {
        margin-top: 6px;
        font-size: 11px;
        color: #64748b;
    }
    .alias-manager-id-note code {
        font-size: 11px;
    }
    @media (max-width: 768px) {
        .alias-manager-top {
            align-items: stretch;
        }
        .alias-builder-grid {
            grid-template-columns: 1fr;
        }
        .alias-builder-actions {
            justify-content: flex-start;
        }
    }
</style>

<div class="alias-manager-card">
    <div class="alias-manager-top">
        <div>
            <h2 style="margin:0; font-size:22px; color:#0f172a;">Alias Manager</h2>
            <p class="alias-manager-help">Manage fallback aliases used by Form Importer. These entries replace hardcoded alias rules.</p>
        </div>
        <div class="button-group" style="display:flex; gap:8px; align-items:center;">
            <input id="aliasSearchInput" class="pdftimesaver-input alias-manager-search" type="search" placeholder="Quick search (link id, pattern, description, related field)">
            <button id="addAliasBtn" type="button" class="alias-manager-btn primary">Add Alias</button>
        </div>
    </div>

    <div class="alias-builder">
        <h3 style="margin:0 0 6px 0; font-size:17px; color:#0f172a;">Entry Builder</h3>
        <p class="alias-builder-help">Use plain-language examples (like "case number" or "party 1"). The builder creates the pattern for you, and treats numbers as wildcards.</p>
        <div class="alias-builder-grid">
            <div>
                <label for="builderLinkId" class="alias-builder-label">Link to field <span class="alias-help-tip" title="Choose which custom field this alias will populate when a form field key matches.">?</span></label>
                <select id="builderLinkId" class="alias-manager-select"></select>
            </div>
            <div>
                <label for="builderDescription" class="alias-builder-label">Description (optional) <span class="alias-help-tip" title="Human-readable purpose of the rule, shown in Alias Manager search and review.">?</span></label>
                <input id="builderDescription" class="alias-manager-input" type="text" placeholder="What this alias handles">
            </div>
            <div class="alias-builder-full">
                <label for="builderPhrases" class="alias-builder-label">Match examples (comma or newline separated) <span class="alias-help-tip" title="Enter plain-language key examples. The builder converts these to a regex pattern automatically. Numbers are treated as wildcard digits (party 1 also matches party 2).">?</span></label>
                <textarea id="builderPhrases" class="alias-manager-input" placeholder="case number, party 1, petitioner #"></textarea>
            </div>
            <div class="alias-builder-full alias-builder-options">
                <label title="If checked, alias only applies when the linked custom field currently has a non-empty value."><input id="builderRequiresValue" class="alias-manager-checkbox" type="checkbox"> Needs value present</label>
                <label title="Turn rule on/off without deleting it. Disabled rules are ignored by auto-matching."><input id="builderEnabled" class="alias-manager-checkbox" type="checkbox" checked> Enabled</label>
                <label for="builderComponentType" style="display:inline-flex; align-items:center; gap:6px;">Input type
                    <select id="builderComponentType" class="alias-manager-select" style="width:auto; min-width:140px;">
                        <option value="text" selected>Text (incl. textarea)</option>
                        <option value="textarea">Textarea only</option>
                        <option value="checkable">Checkbox / radio</option>
                        <option value="any">Any</option>
                    </select>
                </label>
                <label for="builderPriority" style="display:inline-flex; align-items:center; gap:6px;">Priority
                    <input id="builderPriority" class="alias-manager-input" type="number" min="1" max="9999" value="100" style="width:90px;">
                </label>
                <label for="builderScopeType" style="display:inline-flex; align-items:center; gap:6px;">Where this rule applies
                    <select id="builderScopeType" class="alias-manager-select" style="width:auto; min-width:140px;">
                        <option value="global" selected>All forms</option>
                        <option value="form_family">Only one form family</option>
                        <option value="template">Only one template</option>
                    </select>
                </label>
                <label for="builderScopeValue" style="display:inline-flex; align-items:center; gap:6px;">Which family/template
                    <input id="builderScopeValue" class="alias-manager-input" type="text" placeholder="Not needed for All forms" style="width:220px;">
                </label>
                <label for="builderPageMode" style="display:inline-flex; align-items:center; gap:6px;">Pages
                    <select id="builderPageMode" class="alias-manager-select" style="width:auto; min-width:150px;">
                        <option value="any" selected>All pages</option>
                        <option value="first">First page only</option>
                        <option value="last">Last page only</option>
                        <option value="only">Only pages listed</option>
                        <option value="except">Skip listed pages</option>
                    </select>
                </label>
                <label for="builderPageValue" style="display:inline-flex; align-items:center; gap:6px;">Page list (for only/skip)
                    <input id="builderPageValue" class="alias-manager-input" type="text" placeholder="e.g. 2,4,6" style="width:180px;" disabled>
                </label>
                <label for="builderNumberMode" style="display:inline-flex; align-items:center; gap:6px;">Numbering
                    <select id="builderNumberMode" class="alias-manager-select" style="width:auto; min-width:150px;">
                        <option value="any" selected>Any number</option>
                        <option value="first">First only</option>
                        <option value="last">Last only</option>
                        <option value="only">Only numbers listed</option>
                        <option value="except">Skip numbers listed</option>
                    </select>
                </label>
                <label for="builderNumberValue" style="display:inline-flex; align-items:center; gap:6px;">Number list (for only/skip)
                    <input id="builderNumberValue" class="alias-manager-input" type="text" placeholder="e.g. 2,4" style="width:170px;" disabled>
                </label>
            </div>
            <div class="alias-builder-full alias-inline-help" id="builderScopeHelpText">All forms: leave “Which family/template” empty. All pages: leave page list empty.</div>
            <div class="alias-builder-full alias-inline-help" id="builderNumberHintText">Detecting numbering examples from current form…</div>
            <div class="alias-builder-full">
                <label for="builderPatternPreview" class="alias-builder-label">Generated pattern preview <span class="alias-help-tip" title="Regex that will be saved. You can fine-tune it in the table after creating the row.">?</span></label>
                <input id="builderPatternPreview" class="alias-manager-input" type="text" readonly placeholder="Generated automatically">
            </div>
            <div class="alias-builder-full alias-builder-actions">
                <button id="builderCreateBtn" type="button" class="alias-manager-btn primary">Create Alias Row</button>
            </div>
        </div>
    </div>

    <div id="aliasTableContainer"></div>
    <div id="aliasManagerStatus" class="alias-manager-status" aria-live="polite"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const state = {
        aliases: <?php echo json_encode($formImporterAliases, JSON_UNESCAPED_SLASHES); ?>,
        catalog: <?php echo json_encode($formCustomFields, JSON_UNESCAPED_SLASHES); ?>,
        query: '',
        savedSignatures: Object.create(null),
        autoSaveTimers: Object.create(null),
        autoSaveInFlight: Object.create(null),
        autoSaveQueued: Object.create(null),
    };
    const tableContainer = document.getElementById('aliasTableContainer');
    const statusEl = document.getElementById('aliasManagerStatus');
    const searchEl = document.getElementById('aliasSearchInput');
    const addBtn = document.getElementById('addAliasBtn');
    const builderLinkEl = document.getElementById('builderLinkId');
    const builderDescriptionEl = document.getElementById('builderDescription');
    const builderPhrasesEl = document.getElementById('builderPhrases');
    const builderPatternEl = document.getElementById('builderPatternPreview');
    const builderRequiresValueEl = document.getElementById('builderRequiresValue');
    const builderEnabledEl = document.getElementById('builderEnabled');
    const builderComponentTypeEl = document.getElementById('builderComponentType');
    const builderPriorityEl = document.getElementById('builderPriority');
    const builderScopeTypeEl = document.getElementById('builderScopeType');
    const builderScopeValueEl = document.getElementById('builderScopeValue');
    const builderPageModeEl = document.getElementById('builderPageMode');
    const builderPageValueEl = document.getElementById('builderPageValue');
    const builderNumberModeEl = document.getElementById('builderNumberMode');
    const builderNumberValueEl = document.getElementById('builderNumberValue');
    const builderScopeHelpTextEl = document.getElementById('builderScopeHelpText');
    const builderNumberHintTextEl = document.getElementById('builderNumberHintText');
    const builderCreateBtn = document.getElementById('builderCreateBtn');

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

    function catalogByLink() {
        const by = Object.create(null);
        (Array.isArray(state.catalog) ? state.catalog : []).forEach((row) => {
            const lid = String(row.linkId || '').trim().toLowerCase();
            if (!lid || by[lid]) return;
            by[lid] = row;
        });
        return by;
    }

    function filteredAliases() {
        const q = String(state.query || '').trim().toLowerCase();
        if (!q) return Array.isArray(state.aliases) ? state.aliases.slice() : [];
        return (Array.isArray(state.aliases) ? state.aliases : []).filter((row) => {
            const hay = [
                row.id,
                row.linkId,
                row.pattern,
                row.scopeType,
                row.scopeValue,
                row.pageMode,
                row.pageValue,
                row.numberMode,
                row.numberValue,
                row.description,
                row.linkedField && row.linkedField.displayName,
                row.linkedField && row.linkedField.location,
            ].map((v) => String(v || '').toLowerCase()).join(' ');
            return hay.includes(q);
        });
    }

    function relatedCellHtml(row, byLink) {
        const lid = String(row.linkId || '').trim().toLowerCase();
        const linked = row.linkedField || byLink[lid] || null;
        if (!linked) {
            return '<div class="alias-manager-related"><span class="missing">Missing link target</span><br><code>' + esc(lid) + '</code></div>';
        }
        const name = esc(String(linked.displayName || lid));
        const location = esc(String(linked.location || ''));
        const value = esc(String(linked.value || ''));
        return '<div class="alias-manager-related"><strong>' + name + '</strong>' +
            (location ? ' (' + location + ')' : '') +
            '<br><span>Current value: ' + (value || '—') + '</span></div>';
    }

    function humanizeToken(raw) {
        return String(raw || '')
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function normalizeComponentType(value) {
        const v = String(value || '').trim().toLowerCase();
        if (v === 'text' || v === 'textarea' || v === 'checkable' || v === 'any') {
            return v;
        }
        return 'any';
    }

    function normalizePriority(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return 100;
        return Math.max(1, Math.min(9999, Math.round(n)));
    }

    function normalizeScopeType(value) {
        const v = String(value || '').trim().toLowerCase();
        if (v === 'global' || v === 'form_family' || v === 'template') {
            return v;
        }
        return 'global';
    }

    function normalizeScopeValue(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .slice(0, 120);
    }

    function normalizePageMode(value) {
        const v = String(value || '').trim().toLowerCase();
        if (v === 'any' || v === 'first' || v === 'last' || v === 'only' || v === 'except') {
            return v;
        }
        return 'any';
    }

    function normalizePageValue(value) {
        const parts = String(value || '').split(/[^0-9]+/).map((item) => Number(item)).filter((n) => Number.isFinite(n) && n >= 1 && n <= 9999);
        const unique = [];
        const seen = Object.create(null);
        parts.forEach((n) => {
            const k = String(Math.round(n));
            if (!seen[k]) {
                seen[k] = true;
                unique.push(k);
            }
        });
        return unique.join(',');
    }

    function pageModeLabel(value) {
        const v = normalizePageMode(value);
        if (v === 'first') return 'First page';
        if (v === 'last') return 'Last page';
        if (v === 'only') return 'Only listed pages';
        if (v === 'except') return 'Skip listed pages';
        return 'All pages';
    }

    function pageModeOptions(selectedValue) {
        const selected = normalizePageMode(selectedValue || 'any');
        const opts = [
            { value: 'any', label: 'All pages' },
            { value: 'first', label: 'First page only' },
            { value: 'last', label: 'Last page only' },
            { value: 'only', label: 'Only pages listed' },
            { value: 'except', label: 'Skip listed pages' },
        ];
        return opts.map((opt) => {
            const selectedAttr = opt.value === selected ? ' selected' : '';
            return '<option value="' + esc(opt.value) + '"' + selectedAttr + '>' + esc(opt.label) + '</option>';
        }).join('');
    }

    function normalizeNumberMode(value) {
        const v = String(value || '').trim().toLowerCase();
        if (v === 'any' || v === 'first' || v === 'last' || v === 'only' || v === 'except') {
            return v;
        }
        return 'any';
    }

    function normalizeNumberValue(value) {
        const parts = String(value || '').split(/[^0-9]+/).map((item) => Number(item)).filter((n) => Number.isFinite(n) && n >= 1 && n <= 9999);
        const unique = [];
        const seen = Object.create(null);
        parts.forEach((n) => {
            const k = String(Math.round(n));
            if (!seen[k]) {
                seen[k] = true;
                unique.push(k);
            }
        });
        return unique.join(',');
    }

    function numberModeLabel(value) {
        const v = normalizeNumberMode(value);
        if (v === 'first') return 'First only';
        if (v === 'last') return 'Last only';
        if (v === 'only') return 'Only listed';
        if (v === 'except') return 'Skip listed';
        return 'Any number';
    }

    function numberModeOptions(selectedValue) {
        const selected = normalizeNumberMode(selectedValue || 'any');
        const opts = [
            { value: 'any', label: 'Any number' },
            { value: 'first', label: 'First only' },
            { value: 'last', label: 'Last only' },
            { value: 'only', label: 'Only numbers listed' },
            { value: 'except', label: 'Skip numbers listed' },
        ];
        return opts.map((opt) => {
            const selectedAttr = opt.value === selected ? ' selected' : '';
            return '<option value="' + esc(opt.value) + '"' + selectedAttr + '>' + esc(opt.label) + '</option>';
        }).join('');
    }

    function scopeTypeLabel(value) {
        const v = normalizeScopeType(value);
        if (v === 'form_family') return 'One form family';
        if (v === 'template') return 'One template';
        return 'All forms';
    }

    function scopeTypeOptions(selectedValue) {
        const selected = normalizeScopeType(selectedValue || 'global');
        const opts = [
            { value: 'global', label: 'All forms' },
            { value: 'form_family', label: 'Only one form family' },
            { value: 'template', label: 'Only one template' },
        ];
        return opts.map((opt) => {
            const selectedAttr = opt.value === selected ? ' selected' : '';
            return '<option value="' + esc(opt.value) + '"' + selectedAttr + '>' + esc(opt.label) + '</option>';
        }).join('');
    }

    function applyBuilderScopeState() {
        if (!builderScopeTypeEl || !builderScopeValueEl || !builderPageModeEl || !builderPageValueEl || !builderNumberModeEl || !builderNumberValueEl) return;
        const type = normalizeScopeType(builderScopeTypeEl.value);
        const pageMode = normalizePageMode(builderPageModeEl.value);
        const numberMode = normalizeNumberMode(builderNumberModeEl.value);
        if (type === 'global') {
            builderScopeValueEl.disabled = true;
            builderScopeValueEl.value = '';
            builderScopeValueEl.placeholder = 'Not needed for All forms';
        } else {
            builderScopeValueEl.disabled = false;
            if (type === 'form_family') {
                builderScopeValueEl.placeholder = 'Example: fl100';
            } else {
                builderScopeValueEl.placeholder = 'Example: t_fl100_gc120';
            }
        }
        const pageNeedsList = pageMode === 'only' || pageMode === 'except';
        builderPageValueEl.disabled = !pageNeedsList;
        if (!pageNeedsList) {
            builderPageValueEl.value = '';
            builderPageValueEl.placeholder = 'Not needed';
        } else {
            builderPageValueEl.placeholder = 'e.g. 2,4,6';
        }
        const numberNeedsList = numberMode === 'only' || numberMode === 'except';
        builderNumberValueEl.disabled = !numberNeedsList;
        if (!numberNeedsList) {
            builderNumberValueEl.value = '';
            builderNumberValueEl.placeholder = 'Not needed';
        } else {
            builderNumberValueEl.placeholder = 'e.g. 2,4';
        }
        if (builderScopeHelpTextEl) {
            const scopeHelp = type === 'global'
                ? 'All forms: leave “Which family/template” empty.'
                : (type === 'form_family'
                    ? 'One form family: use a family id like fl100.'
                    : 'One template: use the exact template id.');
            const pageHelp = pageMode === 'any'
                ? 'All pages: leave page list empty.'
                : (pageMode === 'first'
                    ? 'First page only.'
                    : (pageMode === 'last'
                        ? 'Last page only.'
                        : (pageMode === 'only'
                            ? 'Only listed pages: enter page numbers like 2,4.'
                            : 'Skip listed pages: enter page numbers like 2,4.')));
            const numberHelp = numberMode === 'any'
                ? 'Any number: no number list needed.'
                : (numberMode === 'first'
                    ? 'First number only.'
                    : (numberMode === 'last'
                        ? 'Last number only.'
                        : (numberMode === 'only'
                            ? 'Only listed numbers: enter values like 2,4.'
                            : 'Skip listed numbers: enter values like 2,4.')));
            builderScopeHelpTextEl.textContent = scopeHelp + ' ' + pageHelp + ' ' + numberHelp;
        }
    }

    function componentTypeLabel(value) {
        const v = normalizeComponentType(value);
        if (v === 'text') return 'Text';
        if (v === 'textarea') return 'Textarea';
        if (v === 'checkable') return 'Checkbox / radio';
        return 'Any';
    }

    function componentTypeOptions(selectedValue) {
        const selected = normalizeComponentType(selectedValue || 'any');
        const opts = [
            { value: 'text', label: 'Text (incl. textarea)' },
            { value: 'textarea', label: 'Textarea only' },
            { value: 'checkable', label: 'Checkbox / radio' },
            { value: 'any', label: 'Any' },
        ];
        return opts.map((opt) => {
            const selectedAttr = opt.value === selected ? ' selected' : '';
            return '<option value="' + esc(opt.value) + '"' + selectedAttr + '>' + esc(opt.label) + '</option>';
        }).join('');
    }

    function linkOptions(selectedLinkId) {
        const selected = String(selectedLinkId || '').toLowerCase();
        const options = [];
        const rows = Array.isArray(state.catalog) ? state.catalog.slice() : [];
        rows.sort((a, b) => String(a.displayName || '').localeCompare(String(b.displayName || '')));
        rows.forEach((row) => {
            const lid = String(row.linkId || '').trim().toLowerCase();
            if (!lid) return;
            const label = String(row.displayName || humanizeToken(lid));
            const location = humanizeToken(String(row.location || ''));
            const value = String(row.value || '').trim();
            const selectedAttr = lid === selected ? ' selected' : '';
            const text = `${label}${location ? ' - ' + location : ''}${value ? ' - value set' : ''}`;
            options.push('<option value="' + esc(lid) + '"' + selectedAttr + '>' + esc(text) + '</option>');
        });
        return options.join('');
    }

    function updateBuilderLinkOptions() {
        if (!builderLinkEl) return;
        builderLinkEl.innerHTML = linkOptions(String(builderLinkEl.value || ''));
        if (!builderLinkEl.value) {
            const first = (Array.isArray(state.catalog) && state.catalog.length) ? String(state.catalog[0].linkId || '') : '';
            builderLinkEl.value = first;
        }
    }

    function escapeRegex(value) {
        return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function tokenizePhrase(phrase) {
        const raw = String(phrase || '').trim().toLowerCase();
        if (!raw) return [];
        return raw.split(/[^a-z0-9#]+/i).map((item) => item.trim()).filter(Boolean);
    }

    function phraseTokenToPattern(token) {
        const raw = String(token || '').trim().toLowerCase();
        if (!raw) return '';
        // Treat explicit wildcard markers and numeric chunks as "any digits".
        if (raw === '#' || raw === 'num' || raw === 'number') {
            return '\\d+';
        }
        return escapeRegex(raw).replace(/\d+/g, '\\d+');
    }

    function buildPatternFromPhrases(text) {
        const rawItems = String(text || '').split(/[\n,]+/).map((item) => item.trim()).filter(Boolean);
        const parts = [];
        const seen = Object.create(null);
        rawItems.forEach((item) => {
            const tokens = tokenizePhrase(item);
            if (!tokens.length) return;
            const flexible = tokens.map((token) => phraseTokenToPattern(token)).filter(Boolean).join('[^a-z0-9]*');
            if (!flexible || seen[flexible]) return;
            seen[flexible] = true;
            parts.push(flexible);
        });
        if (!parts.length) {
            return '';
        }
        return '(?:' + parts.join('|') + ')';
    }

    function updateBuilderPatternPreview() {
        if (!builderPatternEl || !builderPhrasesEl) return;
        builderPatternEl.value = buildPatternFromPhrases(builderPhrasesEl.value);
    }

    function currentTemplateIdFromUrl() {
        try {
            const url = new URL(window.location.href);
            const v1 = String(url.searchParams.get('template_id') || '').trim();
            if (v1) return v1;
            const v2 = String(url.searchParams.get('templateId') || '').trim();
            if (v2) return v2;
        } catch (e) {
            // ignore URL parsing issues
        }
        return '';
    }

    function renderBuilderNumberHints(hintsPayload) {
        if (!builderNumberHintTextEl) return;
        const hints = Array.isArray(hintsPayload && hintsPayload.hints) ? hintsPayload.hints : [];
        const templateId = String(hintsPayload && hintsPayload.templateId || '').trim();
        if (!hints.length) {
            builderNumberHintTextEl.textContent = 'Numbering hint: keys with digits are parsed by series, e.g. party_1_name -> series party_#_name, index 1.';
            return;
        }
        const top = hints.slice(0, 2).map((item) => {
            const series = String(item.series || '').trim();
            const nums = Array.isArray(item.numbers) ? item.numbers.slice(0, 4).join(',') : '';
            return series + (nums ? ' [' + nums + ']' : '');
        }).join(' | ');
        builderNumberHintTextEl.textContent = 'Numbering examples' + (templateId ? ' (' + templateId + ')' : '') + ': ' + top;
    }

    async function loadBuilderNumberHints() {
        if (!builderNumberHintTextEl) return;
        const templateId = currentTemplateIdFromUrl();
        const query = templateId ? '&template_id=' + encodeURIComponent(templateId) : '';
        try {
            const response = await fetch('?route=api/form-importer/numbering-hints' + query + '&_ts=' + Date.now(), { cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.error) ? data.error : 'Failed to load numbering hints');
            }
            renderBuilderNumberHints(data);
        } catch (e) {
            renderBuilderNumberHints({ hints: [] });
        }
    }

    function createAliasDraftFromBuilder() {
        if (!builderLinkEl || !builderPhrasesEl) return null;
        const linkId = String(builderLinkEl.value || '').trim().toLowerCase();
        const pattern = buildPatternFromPhrases(builderPhrasesEl.value);
        const pageMode = normalizePageMode(builderPageModeEl ? builderPageModeEl.value : 'any');
        const pageValue = (pageMode === 'only' || pageMode === 'except')
            ? normalizePageValue(builderPageValueEl ? builderPageValueEl.value : '')
            : '';
        const numberMode = normalizeNumberMode(builderNumberModeEl ? builderNumberModeEl.value : 'any');
        const numberValue = (numberMode === 'only' || numberMode === 'except')
            ? normalizeNumberValue(builderNumberValueEl ? builderNumberValueEl.value : '')
            : '';
        if (!linkId || !pattern) {
            return null;
        }
        if ((pageMode === 'only' || pageMode === 'except') && !pageValue) {
            return null;
        }
        if ((numberMode === 'only' || numberMode === 'except') && !numberValue) {
            return null;
        }
        const phrases = String(builderPhrasesEl.value || '').split(/[\n,]+/).map((item) => item.trim()).filter(Boolean);
        const firstPhrase = phrases.length ? phrases[0] : '';
        const typedDescription = builderDescriptionEl ? String(builderDescriptionEl.value || '').trim() : '';
        const baseName = typedDescription || firstPhrase || linkId;
        const baseId = ('alias_' + baseName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')).replace(/_+/g, '_');
        const id = ensureUniqueAliasId(baseId === 'alias_' ? 'alias_entry' : baseId);
        const fallbackDescription = typedDescription || (firstPhrase ? humanizeToken(firstPhrase) + ' alias' : '');
        return {
            id: id,
            linkId: linkId,
            pattern: pattern,
            requiresValue: !!(builderRequiresValueEl && builderRequiresValueEl.checked),
            enabled: !builderEnabledEl || !!builderEnabledEl.checked,
            componentType: normalizeComponentType(builderComponentTypeEl ? builderComponentTypeEl.value : 'text'),
            priority: normalizePriority(builderPriorityEl ? builderPriorityEl.value : 100),
            scopeType: normalizeScopeType(builderScopeTypeEl ? builderScopeTypeEl.value : 'global'),
            scopeValue: normalizeScopeType(builderScopeTypeEl ? builderScopeTypeEl.value : 'global') === 'global'
                ? ''
                : normalizeScopeValue(builderScopeValueEl ? builderScopeValueEl.value : ''),
            pageMode: pageMode,
            pageValue: pageValue,
            numberMode: numberMode,
            numberValue: numberValue,
            description: fallbackDescription,
            linkedField: null,
        };
    }

    function ensureUniqueAliasId(baseId) {
        const base = String(baseId || 'alias_entry').trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '') || 'alias_entry';
        const used = Object.create(null);
        (Array.isArray(state.aliases) ? state.aliases : []).forEach((row) => {
            const id = String(row && row.id || '').trim().toLowerCase();
            if (id) used[id] = true;
        });
        if (!used[base]) {
            return base;
        }
        let index = 2;
        while (index < 10000) {
            const candidate = `${base}_${index}`;
            if (!used[candidate]) {
                return candidate;
            }
            index++;
        }
        return `${base}_${Date.now()}`;
    }

    function aliasPayloadSignature(payload) {
        return JSON.stringify({
            id: String(payload.id || ''),
            link_id: String(payload.link_id || ''),
            pattern: String(payload.pattern || ''),
            component_type: normalizeComponentType(payload.component_type || 'any'),
            priority: normalizePriority(payload.priority || 100),
            scope_type: normalizeScopeType(payload.scope_type || 'global'),
            scope_value: normalizeScopeType(payload.scope_type || 'global') === 'global' ? '' : normalizeScopeValue(payload.scope_value || ''),
            page_mode: normalizePageMode(payload.page_mode || 'any'),
            page_value: (normalizePageMode(payload.page_mode || 'any') === 'only' || normalizePageMode(payload.page_mode || 'any') === 'except')
                ? normalizePageValue(payload.page_value || '')
                : '',
            number_mode: normalizeNumberMode(payload.number_mode || 'any'),
            number_value: (normalizeNumberMode(payload.number_mode || 'any') === 'only' || normalizeNumberMode(payload.number_mode || 'any') === 'except')
                ? normalizeNumberValue(payload.number_value || '')
                : '',
            requires_value: !!payload.requires_value,
            enabled: !!payload.enabled,
            description: String(payload.description || ''),
        });
    }

    function rebuildSavedSignatureIndex() {
        state.savedSignatures = Object.create(null);
        (Array.isArray(state.aliases) ? state.aliases : []).forEach((row) => {
            if (!row || typeof row !== 'object') return;
            const id = String(row.id || '').trim();
            if (!id) return;
            const payload = {
                id: id,
                link_id: String(row.linkId || ''),
                pattern: String(row.pattern || ''),
                component_type: normalizeComponentType(row.componentType || 'any'),
                priority: normalizePriority(row.priority || 100),
                scope_type: normalizeScopeType(row.scopeType || 'global'),
                scope_value: String(row.scopeValue || ''),
                page_mode: normalizePageMode(row.pageMode || 'any'),
                page_value: String(row.pageValue || ''),
                number_mode: normalizeNumberMode(row.numberMode || 'any'),
                number_value: String(row.numberValue || ''),
                requires_value: !!row.requiresValue,
                enabled: !Object.prototype.hasOwnProperty.call(row, 'enabled') || !!row.enabled,
                description: String(row.description || ''),
            };
            state.savedSignatures[id] = aliasPayloadSignature(payload);
        });
    }

    function applyScopeInputState(tr) {
        if (!tr) return;
        const scopeTypeEl = tr.querySelector('.js-alias-scope-type');
        const scopeValueEl = tr.querySelector('.js-alias-scope-value');
        if (!scopeTypeEl || !scopeValueEl) return;
        const scopeType = normalizeScopeType(scopeTypeEl.value);
        const isGlobal = scopeType === 'global';
        scopeValueEl.disabled = isGlobal;
        if (isGlobal) {
            scopeValueEl.value = '';
        }
    }

    function applyPageInputState(tr) {
        if (!tr) return;
        const pageModeEl = tr.querySelector('.js-alias-page-mode');
        const pageValueEl = tr.querySelector('.js-alias-page-value');
        if (!pageModeEl || !pageValueEl) return;
        const pageMode = normalizePageMode(pageModeEl.value);
        const needsList = pageMode === 'only' || pageMode === 'except';
        pageValueEl.disabled = !needsList;
        if (!needsList) {
            pageValueEl.value = '';
        }
    }

    function applyNumberInputState(tr) {
        if (!tr) return;
        const numberModeEl = tr.querySelector('.js-alias-number-mode');
        const numberValueEl = tr.querySelector('.js-alias-number-value');
        if (!numberModeEl || !numberValueEl) return;
        const numberMode = normalizeNumberMode(numberModeEl.value);
        const needsList = numberMode === 'only' || numberMode === 'except';
        numberValueEl.disabled = !needsList;
        if (!needsList) {
            numberValueEl.value = '';
        }
    }

    function getAliasPayloadFromRow(tr) {
        if (!tr) return { payload: null, error: 'Missing row element.' };
        const id = String(tr.getAttribute('data-alias-id') || '');
        const linkIdEl = tr.querySelector('.js-alias-link-id');
        const patternEl = tr.querySelector('.js-alias-pattern');
        const componentTypeEl = tr.querySelector('.js-alias-component-type');
        const priorityEl = tr.querySelector('.js-alias-priority');
        const scopeTypeEl = tr.querySelector('.js-alias-scope-type');
        const scopeValueEl = tr.querySelector('.js-alias-scope-value');
        const pageModeEl = tr.querySelector('.js-alias-page-mode');
        const pageValueEl = tr.querySelector('.js-alias-page-value');
        const numberModeEl = tr.querySelector('.js-alias-number-mode');
        const numberValueEl = tr.querySelector('.js-alias-number-value');
        const requiresValueEl = tr.querySelector('.js-alias-requires-value');
        const enabledEl = tr.querySelector('.js-alias-enabled');
        const descriptionEl = tr.querySelector('.js-alias-description');
        const normalizedScopeType = normalizeScopeType(scopeTypeEl ? scopeTypeEl.value : 'global');
        const normalizedPageMode = normalizePageMode(pageModeEl ? pageModeEl.value : 'any');
        const normalizedNumberMode = normalizeNumberMode(numberModeEl ? numberModeEl.value : 'any');
        const payload = {
            id: id,
            link_id: linkIdEl ? String(linkIdEl.value || '') : '',
            pattern: patternEl ? String(patternEl.value || '').trim() : '',
            component_type: normalizeComponentType(componentTypeEl ? componentTypeEl.value : 'any'),
            priority: normalizePriority(priorityEl ? priorityEl.value : 100),
            scope_type: normalizedScopeType,
            scope_value: normalizedScopeType === 'global' ? '' : normalizeScopeValue(scopeValueEl ? scopeValueEl.value : ''),
            page_mode: normalizedPageMode,
            page_value: normalizedPageMode === 'only' || normalizedPageMode === 'except' ? normalizePageValue(pageValueEl ? pageValueEl.value : '') : '',
            number_mode: normalizedNumberMode,
            number_value: normalizedNumberMode === 'only' || normalizedNumberMode === 'except' ? normalizeNumberValue(numberValueEl ? numberValueEl.value : '') : '',
            requires_value: !!(requiresValueEl && requiresValueEl.checked),
            enabled: !!(enabledEl && enabledEl.checked),
            description: descriptionEl ? String(descriptionEl.value || '').trim() : '',
        };
        if (!payload.link_id || !payload.pattern) {
            return { payload, error: 'Link ID and pattern are required.' };
        }
        if (payload.scope_type !== 'global' && !payload.scope_value) {
            return { payload, error: 'Scope value is required for Form family or Template scope.' };
        }
        if ((payload.page_mode === 'only' || payload.page_mode === 'except') && !payload.page_value) {
            return { payload, error: 'Page list is required when page mode is only/skip.' };
        }
        if ((payload.number_mode === 'only' || payload.number_mode === 'except') && !payload.number_value) {
            return { payload, error: 'Number list is required when numbering mode is only/skip.' };
        }
        return { payload, error: '' };
    }

    function scheduleAliasAutoSave(tr, delayMs = 900) {
        if (!tr) return;
        const id = String(tr.getAttribute('data-alias-id') || '');
        if (!id) return;
        if (state.autoSaveTimers[id]) {
            window.clearTimeout(state.autoSaveTimers[id]);
        }
        state.autoSaveTimers[id] = window.setTimeout(() => {
            delete state.autoSaveTimers[id];
            void autoSaveAliasRow(tr);
        }, Math.max(120, delayMs));
    }

    async function autoSaveAliasRow(tr) {
        if (!tr) return;
        const id = String(tr.getAttribute('data-alias-id') || '');
        if (!id) return;
        const { payload, error } = getAliasPayloadFromRow(tr);
        if (!payload) return;
        if (error) {
            // Edge case: keep editing uninterrupted; show soft guidance only.
            setStatus('Autosave paused for ' + id + ': ' + error, true);
            return;
        }
        const signature = aliasPayloadSignature(payload);
        if (state.savedSignatures[id] && state.savedSignatures[id] === signature) {
            return;
        }
        if (state.autoSaveInFlight[id]) {
            state.autoSaveQueued[id] = true;
            return;
        }
        state.autoSaveInFlight[id] = true;
        try {
            await saveAliasFromRow(tr, { isAutoSave: true, payloadOverride: payload });
            state.savedSignatures[id] = signature;
            setStatus('Autosaved ' + id + '.');
        } catch (err) {
            setStatus((err && err.message) ? err.message : 'Autosave failed for ' + id + '.', true);
        } finally {
            state.autoSaveInFlight[id] = false;
            if (state.autoSaveQueued[id]) {
                delete state.autoSaveQueued[id];
                const latestTr = document.querySelector('tr[data-alias-id="' + id + '"]');
                if (latestTr) {
                    scheduleAliasAutoSave(latestTr, 220);
                }
            }
        }
    }

    function renderTable() {
        const rows = filteredAliases();
        const byLink = catalogByLink();
        if (!rows.length) {
            tableContainer.innerHTML = '<div class="alias-manager-empty">No aliases found for current search.</div>';
            return;
        }
        const body = rows.map((row) => {
            const id = esc(String(row.id || ''));
            const linkId = esc(String(row.linkId || ''));
            const pattern = esc(String(row.pattern || ''));
            const description = esc(String(row.description || ''));
            const componentType = normalizeComponentType(row.componentType || 'any');
            const priority = normalizePriority(row.priority || 100);
            const scopeType = normalizeScopeType(row.scopeType || 'global');
            const scopeValue = esc(String(row.scopeValue || ''));
            const pageMode = normalizePageMode(row.pageMode || 'any');
            const pageValue = esc(String(row.pageValue || ''));
            const numberMode = normalizeNumberMode(row.numberMode || 'any');
            const numberValue = esc(String(row.numberValue || ''));
            const stats = row && typeof row.stats === 'object' ? row.stats : {};
            const hits = Number(stats.hits || 0);
            const manualOverrides = Number(stats.manualOverrides || 0);
            const statsSuffix = hits > 0
                ? ' - Hits: ' + esc(String(hits)) + ' - Manual overrides: ' + esc(String(manualOverrides))
                : '';
            const requiresValue = !!row.requiresValue ? ' checked' : '';
            const enabled = !Object.prototype.hasOwnProperty.call(row, 'enabled') || !!row.enabled ? ' checked' : '';
            return '<tr data-alias-id="' + id + '">' +
                '<td><select class="alias-manager-select js-alias-link-id" title="Custom field this alias should populate.">' + linkOptions(linkId) + '</select></td>' +
                '<td><input class="alias-manager-input js-alias-pattern" type="text" value="' + pattern + '" placeholder="Regex pattern" title="Regex tested against incoming form field keys."></td>' +
                '<td><select class="alias-manager-select js-alias-component-type" title="Only match keys for this input component type.">' + componentTypeOptions(componentType) + '</select></td>' +
                '<td><input class="alias-manager-input js-alias-priority" type="number" min="1" max="9999" value="' + esc(String(priority)) + '" title="Lower number wins when multiple aliases match the same field."></td>' +
                '<td><select class="alias-manager-select js-alias-scope-type" title="Choose where this rule should run: all forms, one family, or one template.">' + scopeTypeOptions(scopeType) + '</select></td>' +
                '<td><input class="alias-manager-input js-alias-scope-value" type="text" value="' + scopeValue + '" placeholder="' + (scopeType === 'template' ? 'Example: t_fl100_gc120' : 'Example: fl100') + '" title="When scope is not All forms, enter the family id (fl100) or exact template id (t_fl100_gc120)."' + (scopeType === 'global' ? ' disabled' : '') + '></td>' +
                '<td><select class="alias-manager-select js-alias-page-mode" title="Restrict alias matching by page: all, first, last, only listed, or skip listed.">' + pageModeOptions(pageMode) + '</select></td>' +
                '<td><input class="alias-manager-input js-alias-page-value" type="text" value="' + pageValue + '" placeholder="e.g. 2,4,6" title="Used when Pages is set to only listed or skip listed."' + ((pageMode === 'only' || pageMode === 'except') ? '' : ' disabled') + '></td>' +
                '<td><select class="alias-manager-select js-alias-number-mode" title="Restrict alias matching by numeric index in field key: any, first, last, only listed, or skip listed.">' + numberModeOptions(numberMode) + '</select></td>' +
                '<td><input class="alias-manager-input js-alias-number-value" type="text" value="' + numberValue + '" placeholder="e.g. 2,4" title="Used when Numbering is set to only listed or skip listed."' + ((numberMode === 'only' || numberMode === 'except') ? '' : ' disabled') + '></td>' +
                '<td style="text-align:center;"><input class="alias-manager-checkbox js-alias-requires-value" type="checkbox"' + requiresValue + ' title="Require linked field value to be non-empty before alias can apply."></td>' +
                '<td style="text-align:center;"><input class="alias-manager-checkbox js-alias-enabled" type="checkbox"' + enabled + ' title="Enable or disable this rule without deleting it."></td>' +
                '<td><input class="alias-manager-input js-alias-description" type="text" value="' + description + '" placeholder="Friendly name / purpose">' +
                '<div class="alias-manager-id-note">Internal ID: <code>' + id + '</code> - Matches: ' + esc(componentTypeLabel(componentType)) + ' - Scope: ' + esc(scopeTypeLabel(scopeType)) + ' - Pages: ' + esc(pageModeLabel(pageMode)) + ((pageMode === 'only' || pageMode === 'except') && pageValue ? ' (' + pageValue + ')' : '') + ' - Numbering: ' + esc(numberModeLabel(numberMode)) + ((numberMode === 'only' || numberMode === 'except') && numberValue ? ' (' + numberValue + ')' : '') + statsSuffix + '</div></td>' +
                '<td>' + relatedCellHtml(row, byLink) + '</td>' +
                '<td style="white-space:nowrap;">' +
                    '<button type="button" class="alias-manager-btn danger js-delete-alias" title="Delete this alias rule permanently.">Delete</button>' +
                '</td>' +
            '</tr>';
        }).join('');
        tableContainer.innerHTML =
            '<div class="alias-manager-table-wrap">' +
                '<table class="alias-manager-table">' +
                    '<thead><tr>' +
                        '<th title="Custom field that receives the value when this alias matches.">Link Target</th>' +
                        '<th title="Regex pattern tested against incoming form field keys.">Pattern</th>' +
                        '<th title="Restrict rule to text, textarea, checkable, or any input type.">Input Type</th>' +
                        '<th title="Lower priority number wins if multiple rules match the same field.">Priority</th>' +
                        '<th title="Where this rule applies: all forms, one family, or one template.">Where this rule applies</th>' +
                        '<th title="If not All forms, enter a family id (fl100) or exact template id (t_fl100_gc120).">Which family/template</th>' +
                        '<th title="Restrict alias matching to all pages, first/last page, specific pages, or skip pages.">Pages</th>' +
                        '<th title="Page numbers used for only/skip page rules (e.g. 2,4,6).">Page list</th>' +
                        '<th title="Restrict alias matching by numeric index in field keys.">Numbering</th>' +
                        '<th title="Number list used for only/skip numbering rules (e.g. 2,4).">Number list</th>' +
                        '<th title="If checked, linked field must already contain a value.">Needs Value</th>' +
                        '<th title="Disabled rules are ignored during auto-matching.">Enabled</th>' +
                        '<th title="Friendly name for searching and maintenance.">Name / Purpose</th>' +
                        '<th title="Current linked-field metadata and value status.">Related Data</th>' +
                        '<th title="Delete this alias rule permanently.">Actions</th>' +
                    '</tr></thead>' +
                    '<tbody>' + body + '</tbody>' +
                '</table>' +
            '</div>';
    }

    async function refreshFromApi() {
        const response = await fetch('?route=api/form-importer/aliases&_ts=' + Date.now(), { cache: 'no-store' });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to load aliases');
        }
        state.aliases = Array.isArray(data.aliases) ? data.aliases : [];
        state.catalog = Array.isArray(data.catalog) ? data.catalog : state.catalog;
        rebuildSavedSignatureIndex();
        updateBuilderLinkOptions();
        renderTable();
    }

    async function saveAliasFromRow(tr, options = {}) {
        if (!tr) return;
        const id = String(tr.getAttribute('data-alias-id') || '');
        const usePayload = options && options.payloadOverride ? options.payloadOverride : null;
        const built = usePayload ? { payload: usePayload, error: '' } : getAliasPayloadFromRow(tr);
        const payload = built.payload;
        if (!payload) {
            throw new Error('Could not read alias row.');
        }
        if (built.error) {
            throw new Error(built.error);
        }
        const response = await fetch('?route=api/form-importer/upsert-alias', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to save alias');
        }
        state.aliases = Array.isArray(data.aliases) ? data.aliases : state.aliases;
        rebuildSavedSignatureIndex();
        renderTable();
        if (!(options && options.isAutoSave)) {
            setStatus('Alias saved.');
        }
    }

    async function deleteAliasById(id) {
        const response = await fetch('?route=api/form-importer/delete-alias', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error((data && data.error) ? data.error : 'Failed to delete alias');
        }
        state.aliases = Array.isArray(data.aliases) ? data.aliases : state.aliases;
        rebuildSavedSignatureIndex();
        renderTable();
        setStatus('Alias deleted.');
    }

    addBtn.addEventListener('click', function () {
        const newId = ensureUniqueAliasId('alias_new_entry');
        const firstLink = (Array.isArray(state.catalog) && state.catalog.length) ? String(state.catalog[0].linkId || '') : '';
        state.aliases.unshift({
            id: newId,
            linkId: firstLink,
            pattern: '',
            componentType: 'text',
            priority: 100,
            scopeType: 'global',
            scopeValue: '',
            pageMode: 'any',
            pageValue: '',
            numberMode: 'any',
            numberValue: '',
            requiresValue: false,
            enabled: true,
            description: '',
            linkedField: null,
        });
        rebuildSavedSignatureIndex();
        renderTable();
        setStatus('New alias row added. It will autosave when required fields are valid.');
    });

    if (builderPhrasesEl) {
        builderPhrasesEl.addEventListener('input', updateBuilderPatternPreview);
    }
    if (builderScopeTypeEl) {
        builderScopeTypeEl.addEventListener('change', applyBuilderScopeState);
    }
    if (builderPageModeEl) {
        builderPageModeEl.addEventListener('change', applyBuilderScopeState);
    }
    if (builderNumberModeEl) {
        builderNumberModeEl.addEventListener('change', applyBuilderScopeState);
    }

    if (builderCreateBtn) {
        builderCreateBtn.addEventListener('click', function () {
            const draft = createAliasDraftFromBuilder();
            if (!draft) {
                setStatus('Choose a link target, add match examples, and fill required page/number lists for only/skip modes.', true);
                return;
            }
            state.aliases.unshift(draft);
            renderTable();
            const draftRow = document.querySelector('tr[data-alias-id="' + draft.id + '"]');
            if (draftRow) {
                scheduleAliasAutoSave(draftRow, 80);
            }
            setStatus('Alias draft created from builder. Autosaving now.');
        });
    }

    searchEl.addEventListener('input', function () {
        state.query = String(searchEl.value || '');
        renderTable();
    });

    document.addEventListener('click', function (event) {
        const deleteBtn = event.target.closest('.js-delete-alias');
        if (!deleteBtn) return;
        const tr = deleteBtn.closest('tr[data-alias-id]');
        if (!tr) return;
        const id = String(tr.getAttribute('data-alias-id') || '');
        if (!id) return;
        if (!window.confirm('Delete this alias entry?')) return;
        void deleteAliasById(id).catch((error) => setStatus(error.message || 'Failed to delete alias.', true));
    });

    document.addEventListener('change', function (event) {
        const tr = event.target && event.target.closest ? event.target.closest('tr[data-alias-id]') : null;
        if (!tr) return;
        if (event.target.closest('.js-alias-scope-type')) {
            applyScopeInputState(tr);
        }
        if (event.target.closest('.js-alias-page-mode')) {
            applyPageInputState(tr);
        }
        if (event.target.closest('.js-alias-number-mode')) {
            applyNumberInputState(tr);
        }
        if (
            event.target.closest('.js-alias-link-id') ||
            event.target.closest('.js-alias-component-type') ||
            event.target.closest('.js-alias-priority') ||
            event.target.closest('.js-alias-scope-type') ||
            event.target.closest('.js-alias-scope-value') ||
            event.target.closest('.js-alias-page-mode') ||
            event.target.closest('.js-alias-page-value') ||
            event.target.closest('.js-alias-number-mode') ||
            event.target.closest('.js-alias-number-value') ||
            event.target.closest('.js-alias-requires-value') ||
            event.target.closest('.js-alias-enabled')
        ) {
            scheduleAliasAutoSave(tr, 240);
        }
    });

    document.addEventListener('input', function (event) {
        const tr = event.target && event.target.closest ? event.target.closest('tr[data-alias-id]') : null;
        if (!tr) return;
        if (
            event.target.closest('.js-alias-pattern') ||
            event.target.closest('.js-alias-description') ||
            event.target.closest('.js-alias-scope-value') ||
            event.target.closest('.js-alias-page-value') ||
            event.target.closest('.js-alias-number-value')
        ) {
            scheduleAliasAutoSave(tr, 900);
        }
    });

    updateBuilderLinkOptions();
    applyBuilderScopeState();
    updateBuilderPatternPreview();
    void loadBuilderNumberHints();
    rebuildSavedSignatureIndex();
    renderTable();
    void refreshFromApi().catch((error) => setStatus(error.message || 'Failed to load aliases.', true));
});
</script>
