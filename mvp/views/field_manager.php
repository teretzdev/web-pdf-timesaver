<?php
$fieldManagerFields = is_array($fieldManagerFields ?? null) ? $fieldManagerFields : [];
$fieldTypes = is_array($fieldTypes ?? null) ? $fieldTypes : ['text', 'number', 'date', 'checkbox', 'select', 'email', 'phone'];

$groupedFields = [
    'firm' => [],
    'attorney' => [],
    'client' => [],
    'court' => [],
    'case' => [],
];
foreach ($fieldManagerFields as $row) {
    $location = strtolower((string)($row['location'] ?? 'firm'));
    if (!array_key_exists($location, $groupedFields)) {
        continue;
    }
    $groupedFields[$location][] = [
        'id' => (string)($row['id'] ?? ''),
        'linkId' => (string)($row['linkId'] ?? ''),
        'displayName' => (string)($row['displayName'] ?? ''),
        'fieldType' => strtolower((string)($row['fieldType'] ?? 'text')),
        'matchingTag' => (string)($row['matchingTag'] ?? ($row['linkId'] ?? '')),
        'location' => $location,
        'isSystem' => !empty($row['isSystem']),
        'sampleText' => (string)($row['value'] ?? ''),
    ];
}
?>

<style>
    .field-manager-wrap {
        max-width: 1200px;
        margin: 0 auto;
    }

    .field-manager-card {
        background: #fff;
        border: 1px solid #e3e7eb;
        border-radius: 10px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(16, 24, 40, 0.04);
    }

    .field-manager-title {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 14px;
    }

    .field-manager-crumbs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .field-manager-crumb {
        border: 1px solid #d5dbe3;
        background: #f8fafc;
        color: #374151;
        padding: 7px 11px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .field-manager-crumb.active {
        background: #1976d2;
        border-color: #1976d2;
        color: #fff;
    }

    .field-manager-crumb:focus-visible,
    .field-manager-btn:focus-visible,
    .field-manager-input:focus-visible {
        outline: 2px solid #1d4ed8;
        outline-offset: 2px;
    }

    .field-manager-info {
        color: #374151;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.6;
        margin: 0;
        max-width: 980px;
    }

    .field-manager-hr {
        margin: 18px 0 16px 0;
        border-top: 1px solid #e3e7eb;
    }

    .field-manager-section {
        display: none;
    }

    .field-manager-section.active {
        display: block;
    }

    .field-manager-section h3 {
        font-size: 19px;
        color: #111827;
        margin: 0 0 12px 0;
        font-weight: 700;
    }

    .field-manager-add-row {
        margin-bottom: 12px;
    }

    .field-manager-btn {
        border: none;
        border-radius: 6px;
        padding: 9px 14px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .field-manager-btn.primary {
        background: #1976d2;
        color: #fff;
    }

    .field-manager-btn.secondary {
        background: #eef2f7;
        color: #1f2937;
        border: 1px solid #d4dbe4;
    }

    .field-manager-btn.danger {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 7px 10px;
        font-size: 12px;
    }

    .field-manager-empty {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px;
        color: #475569;
    }

    .field-manager-table-wrap {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .field-manager-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .field-manager-table th {
        background: #f9fafb;
        color: #111827;
        text-align: left;
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid #e5e7eb;
    }

    .field-manager-table td {
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .field-manager-input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
        color: #111827;
        background: #fff;
    }

    .field-manager-nav {
        border-top: 1px solid #e5e7eb;
        margin-top: 20px;
        padding-top: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .field-manager-status {
        font-size: 13px;
        color: #0f766e;
        min-height: 18px;
    }

    @media (max-width: 900px) {
        .field-manager-title {
            font-size: 24px;
        }
    }
</style>

<div class="field-manager-wrap">
    <div class="field-manager-card">
        <h2 id="wizardPageTitle" class="field-manager-title">Introduction</h2>

        <div class="field-manager-crumbs" id="wizardCrumbs">
            <button type="button" class="field-manager-crumb active" data-step="1">Introduction</button>
            <button type="button" class="field-manager-crumb" data-step="2">Firm Fields</button>
            <button type="button" class="field-manager-crumb" data-step="3">Attorney Information Fields</button>
            <button type="button" class="field-manager-crumb" data-step="4">Client Fields</button>
            <button type="button" class="field-manager-crumb" data-step="5">Court Information Fields</button>
            <button type="button" class="field-manager-crumb" data-step="6">Case Fields</button>
        </div>

        <p id="wizardInfoText" class="field-manager-info">
            Use the next screen to manage the fields available for pre-assigned values. Note: This step is for field selection only. You'll enter the specific values for these forms later in the process.
        </p>

        <div class="field-manager-hr"></div>

        <section class="field-manager-section active" data-step="1"></section>

        <section class="field-manager-section" data-step="2">
            <h3>Firm Fields</h3>
            <div class="field-manager-add-row">
                <button type="button" class="field-manager-btn primary js-add-field" data-location="firm">Add Field</button>
            </div>
            <p style="margin:0 0 10px 0; font-size:12px; color:#64748b;">Tip: "Matching Tag Text" supports wildcards: <code>*</code> any text, <code>?</code> one character, <code>#</code> digits. Examples: <code>party_*_name</code>, <code>address*</code>, <code>zip????</code>, <code>phone_#</code>.</p>
            <div class="field-manager-table-wrap" id="tableWrap-firm"></div>
            <div class="field-manager-empty" id="empty-firm" hidden>
                No firm fields yet. Click "Add Field" to create your first firm field.
            </div>
        </section>

        <section class="field-manager-section" data-step="3">
            <h3>Attorney Information Fields</h3>
            <div class="field-manager-add-row">
                <button type="button" class="field-manager-btn primary js-add-field" data-location="attorney">Add Field</button>
            </div>
            <p style="margin:0 0 10px 0; font-size:12px; color:#64748b;">Permanent attorney fields (name, bar number, firm, address, phone, fax, email) mapped to AttyInfo/attorney caption blocks. Manage the attorney roster on Firm Information; each project stores its own copy.</p>
            <div class="field-manager-table-wrap" id="tableWrap-attorney"></div>
            <div class="field-manager-empty" id="empty-attorney" hidden>
                No attorney fields yet. Click "Add Field" to create your first attorney field.
            </div>
        </section>

        <section class="field-manager-section" data-step="4">
            <h3>Client Fields</h3>
            <div class="field-manager-add-row">
                <button type="button" class="field-manager-btn primary js-add-field" data-location="client">Add Field</button>
            </div>
            <p style="margin:0 0 10px 0; font-size:12px; color:#64748b;">Tip: Use the <strong>Sample text</strong> column to store example strings for test-form output. Other tips — &quot;Matching Tag Text&quot; supports wildcards: <code>*</code> any text, <code>?</code> one character, <code>#</code> digits. Examples: <code>party_*_name</code>, <code>address*</code>, <code>zip????</code>, <code>phone_#</code>.</p>
            <div class="field-manager-table-wrap" id="tableWrap-client"></div>
            <div class="field-manager-empty" id="empty-client" hidden>
                No client fields yet. Click "Add Field" to create your first client field.
            </div>
        </section>

        <section class="field-manager-section" data-step="5">
            <h3>Court Information Fields</h3>
            <div class="field-manager-add-row">
                <button type="button" class="field-manager-btn primary js-add-field" data-location="court">Add Field</button>
            </div>
            <p style="margin:0 0 10px 0; font-size:12px; color:#64748b;">Permanent court fields (name, county, street, mailing, city/state/ZIP, phone, branch/department/room/floor) used when filling forms. Matching tags map to CourtInfo/court caption blocks and remain editable for display/mapping.</p>
            <div class="field-manager-table-wrap" id="tableWrap-court"></div>
            <div class="field-manager-empty" id="empty-court" hidden>
                No court fields yet. Click "Add Field" to create your first court field.
            </div>
        </section>

        <section class="field-manager-section" data-step="6">
            <h3>Case Fields</h3>
            <div class="field-manager-add-row">
                <button type="button" class="field-manager-btn primary js-add-field" data-location="case">Add Field</button>
            </div>
            <p style="margin:0 0 10px 0; font-size:12px; color:#64748b;">Tip: Use the <strong>Sample text</strong> column to store example strings for test-form output. Other tips — &quot;Matching Tag Text&quot; supports wildcards: <code>*</code> any text, <code>?</code> one character, <code>#</code> digits. Examples: <code>party_*_name</code>, <code>address*</code>, <code>zip????</code>, <code>phone_#</code>.</p>
            <div class="field-manager-table-wrap" id="tableWrap-case"></div>
            <div class="field-manager-empty" id="empty-case" hidden>
                No case fields yet. Click "Add Field" to create your first case field.
            </div>
        </section>

        <div class="field-manager-nav">
            <button type="button" id="wizardBackBtn" class="field-manager-btn secondary">Back</button>
            <div class="field-manager-status" id="fieldManagerStatus"></div>
            <button type="button" id="saveAllFieldsBtn" class="field-manager-btn secondary" style="display:none;">Save All Fields</button>
            <button type="button" id="wizardNextBtn" class="field-manager-btn primary">Next</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const stepConfig = {
            1: {
                title: 'Introduction',
                info: 'Use the next screen to manage the fields available for pre-assigned values. Note: This step is for field selection only. You\'ll enter the specific values for these forms later in the process.',
                nextLabel: 'Next'
            },
            2: {
                title: 'Firm Fields',
                info: 'These are fields that are specific to your firm. "Display Text" is how you will see the field described in your selectors. "Field Type" is the type of field to be matched on the form. It is also necessary for default values. "Matching Tag" is what we use to find the field on the form and attach to when adding new forms. Wildcards are supported: * (any text), ? (single character), # (digits).',
                nextLabel: 'Next'
            },
            3: {
                title: 'Attorney Information Fields',
                info: 'These fields describe the attorney of record: name, bar number, firm, address, phone, fax, and email. They map to AttyInfo/attorney caption blocks on forms. Protected system fields are seeded automatically; you can customize display names and matching tags. Manage the attorney roster on Firm Information.',
                nextLabel: 'Next'
            },
            4: {
                title: 'Client Fields',
                info: 'These are fields that are specific to your clients. "Display Text" is how you will see the field described in your selectors. "Field Type" is the type of field to be matched on the form. It is also necessary for default values. "Matching Tag" is what we use to find the field on the form and attach to when adding new forms. Wildcards are supported: * (any text), ? (single character), # (digits).',
                nextLabel: 'Next'
            },
            5: {
                title: 'Court Information Fields',
                info: 'These fields describe the court for a matter: court name, county, street, mailing, city/state/ZIP, phone, branch/department/room/floor. They map to CourtInfo/court caption blocks on forms. Protected system fields are seeded automatically; you can customize display names and matching tags.',
                nextLabel: 'Next'
            },
            6: {
                title: 'Case Fields',
                info: 'These are project-specific fields. They will appear on every case assigned to a project but are unique to that specific project\'s environment. "Display Text" is how you will see the field described in your selectors. "Field Type" is the type of field to be matched on the form. It is also necessary for default values. "Matching Tag" is what we use to find the field on the form and attach to when adding new forms. Wildcards are supported: * (any text), ? (single character), # (digits).',
                nextLabel: 'Finish'
            }
        };

        const state = {
            step: 1,
            fieldTypes: <?php echo json_encode(array_values($fieldTypes), JSON_UNESCAPED_SLASHES); ?>,
            fieldsByLocation: <?php echo json_encode($groupedFields, JSON_UNESCAPED_SLASHES); ?>
        };

        const titleEl = document.getElementById('wizardPageTitle');
        const infoEl = document.getElementById('wizardInfoText');
        const backBtn = document.getElementById('wizardBackBtn');
        const nextBtn = document.getElementById('wizardNextBtn');
        const saveAllBtn = document.getElementById('saveAllFieldsBtn');
        const statusEl = document.getElementById('fieldManagerStatus');
        const crumbs = Array.from(document.querySelectorAll('#wizardCrumbs [data-step]'));
        const sections = Array.from(document.querySelectorAll('.field-manager-section'));
        const rowAutoSaveTimers = new Map();

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function fieldTypeLabel(type) {
            const t = String(type || '');
            if (!t) {
                return '';
            }
            return t.charAt(0).toUpperCase() + t.slice(1);
        }

        function optionsHtml(selectedType) {
            const options = state.fieldTypes.map(function (type) {
                const selected = type === selectedType ? ' selected' : '';
                return '<option value="' + escapeHtml(type) + '"' + selected + '>' + escapeHtml(fieldTypeLabel(type)) + '</option>';
            });
            return options.join('');
        }

        function buildFieldTableHtml(location, rows) {
            const bodyRows = rows.map(function (row) {
                const protectedBadge = row.isSystem ? '<span style="display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:11px;font-weight:700;">Protected</span>' : '';
                const deleteButton = row.isSystem
                    ? '<button type="button" class="field-manager-btn danger" disabled title="Protected system field">Delete</button>'
                    : '<button type="button" class="field-manager-btn danger js-delete-field">Delete</button>';
                const sampleVal = String(row.sampleText || '');
                const sampleInput = '<input type="text" class="field-manager-input js-sample-text" value="' + escapeHtml(sampleVal) + '" placeholder="Text used for test export / samples">';
                return '<tr class="js-field-row" data-field-id="' + escapeHtml(row.id) + '" data-link-id="' + escapeHtml(row.linkId || '') + '" data-location="' + escapeHtml(location) + '">' +
                    '<td><input class="field-manager-input js-display-name" type="text" value="' + escapeHtml(row.displayName) + '" placeholder="Display Text"></td>' +
                    '<td><select class="field-manager-input js-field-type">' + optionsHtml(String(row.fieldType || 'text')) + '</select></td>' +
                    '<td><input class="field-manager-input js-matching-tag" type="text" value="' + escapeHtml(row.matchingTag) + '" placeholder="Matching Tag Text"></td>' +
                    '<td>' + sampleInput + '</td>' +
                    '<td style="white-space:nowrap;">' +
                    deleteButton + protectedBadge +
                    '</td>' +
                    '</tr>';
            }).join('');
            return '' +
                '<table class="field-manager-table">' +
                '<thead>' +
                '<tr><th>Display Text</th><th>Field Type</th><th>Matching Tag Text</th><th>Sample text</th><th>Actions</th></tr>' +
                '</thead>' +
                '<tbody>' + bodyRows + '</tbody>' +
                '</table>' +
                '';
        }

        function renderFieldTable(location) {
            const wrap = document.getElementById('tableWrap-' + location);
            const empty = document.getElementById('empty-' + location);
            if (!wrap || !empty) {
                return;
            }
            const rows = Array.isArray(state.fieldsByLocation[location]) ? state.fieldsByLocation[location] : [];
            if (!rows.length) {
                wrap.innerHTML = '';
                empty.hidden = false;
                return;
            }
            empty.hidden = true;
            const sortedRows = rows.slice().sort(function (a, b) {
                const aProtected = !!a.isSystem;
                const bProtected = !!b.isSystem;
                if (aProtected === bProtected) return 0;
                return aProtected ? -1 : 1;
            });
            wrap.innerHTML = buildFieldTableHtml(location, sortedRows);
        }

        function renderAllTables() {
            renderFieldTable('firm');
            renderFieldTable('attorney');
            renderFieldTable('client');
            renderFieldTable('court');
            renderFieldTable('case');
        }

        function setStatus(message, isError) {
            statusEl.textContent = message || '';
            statusEl.style.color = isError ? '#b91c1c' : '#0f766e';
        }

        function buildPayloadFromRow(row) {
            if (!row) return null;
            const location = String(row.getAttribute('data-location') || '');
            const id = String(row.getAttribute('data-field-id') || '');
            const linkId = String(row.getAttribute('data-link-id') || '');
            const displayName = row.querySelector('.js-display-name') ? row.querySelector('.js-display-name').value.trim() : '';
            const fieldType = row.querySelector('.js-field-type') ? row.querySelector('.js-field-type').value.trim() : '';
            const matchingTag = row.querySelector('.js-matching-tag') ? row.querySelector('.js-matching-tag').value.trim() : '';
            if (!displayName || !fieldType || !matchingTag || !location) {
                return null;
            }
            const payload = {
                display_name: displayName,
                field_type: fieldType,
                matching_tag: matchingTag,
                location: location
            };
            if (id) {
                payload.id = id;
            }
            if (linkId) {
                payload.link_id = linkId;
            }
            const sampleEl = row.querySelector('.js-sample-text');
            payload.sample_text = sampleEl ? String(sampleEl.value || '') : '';
            return payload;
        }

        async function saveRow(row, source) {
            const payload = buildPayloadFromRow(row);
            if (!payload) {
                if (source !== 'auto') {
                    setStatus('Display Text, Field Type, and Matching Tag Text are required.', true);
                }
                return false;
            }
            try {
                if (source === 'auto') {
                    setStatus('Auto-saving field...');
                }
                const shouldRefreshTables = source !== 'auto' || !payload.id;
                await upsertField(payload, {
                    refreshTables: shouldRefreshTables,
                    successMessage: source === 'auto' ? 'Field auto-saved.' : 'Field saved.'
                });
                if (source === 'auto') {
                    setStatus('Field auto-saved.');
                }
                return true;
            } catch (error) {
                setStatus(error.message || 'Failed to save field.', true);
                return false;
            }
        }

        function scheduleRowAutoSave(row, delayMs) {
            if (!row) return;
            const key = [
                String(row.getAttribute('data-location') || ''),
                String(row.getAttribute('data-field-id') || ''),
                String(row.getAttribute('data-link-id') || '')
            ].join('|');
            if (!key) return;
            const existing = rowAutoSaveTimers.get(key);
            if (existing) {
                window.clearTimeout(existing);
            }
            const timer = window.setTimeout(() => {
                rowAutoSaveTimers.delete(key);
                saveRow(row, 'auto');
            }, delayMs);
            rowAutoSaveTimers.set(key, timer);
        }

        function updateStep(targetStep) {
            const next = Math.min(6, Math.max(1, Number(targetStep) || 1));
            state.step = next;
            const cfg = stepConfig[next];
            titleEl.textContent = cfg.title;
            infoEl.textContent = cfg.info;
            nextBtn.textContent = cfg.nextLabel;
            backBtn.style.visibility = next === 1 ? 'hidden' : 'visible';
            if (saveAllBtn) {
                saveAllBtn.style.display = next === 1 ? 'none' : '';
            }
            setStatus('');
            crumbs.forEach(function (crumb) {
                const active = Number(crumb.getAttribute('data-step')) === next;
                crumb.classList.toggle('active', active);
            });
            sections.forEach(function (section) {
                const active = Number(section.getAttribute('data-step')) === next;
                section.classList.toggle('active', active);
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function upsertField(payload, options) {
            const config = options && typeof options === 'object' ? options : {};
            const refreshTables = config.refreshTables !== false;
            const syncState = config.syncState !== false;
            const successMessage = typeof config.successMessage === 'string' ? config.successMessage : 'Field saved.';
            const response = await fetch('?route=api/field-manager/upsert-field', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error((data && data.error) ? data.error : 'Failed to save field');
            }
            if (syncState) {
                state.fieldsByLocation = data.fieldsByLocation || state.fieldsByLocation;
            }
            if (refreshTables) {
                renderAllTables();
            }
            if (successMessage) {
                setStatus(successMessage);
            }
        }

        async function deleteField(id) {
            const response = await fetch('?route=api/field-manager/delete-field', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error((data && data.error) ? data.error : 'Failed to delete field');
            }
            state.fieldsByLocation = data.fieldsByLocation || state.fieldsByLocation;
            renderAllTables();
            setStatus('Field deleted.');
        }

        document.getElementById('wizardCrumbs').addEventListener('click', function (event) {
            const target = event.target.closest('[data-step]');
            if (!target) return;
            updateStep(Number(target.getAttribute('data-step')));
        });

        backBtn.addEventListener('click', function () {
            if (state.step > 1) {
                updateStep(state.step - 1);
            }
        });

        nextBtn.addEventListener('click', function () {
            if (state.step < 6) {
                updateStep(state.step + 1);
                return;
            }
            updateStep(1);
            setStatus('Field Manager setup complete.');
        });

        saveAllBtn?.addEventListener('click', async function () {
            const rows = Array.from(document.querySelectorAll('tr.js-field-row'));
            if (!rows.length) {
                setStatus('No fields to save.');
                return;
            }
            rowAutoSaveTimers.forEach(function (timerId) {
                window.clearTimeout(timerId);
            });
            rowAutoSaveTimers.clear();

            const prevDisabled = saveAllBtn.disabled;
            saveAllBtn.disabled = true;
            setStatus('Saving all fields...');

            let savedCount = 0;
            let failedCount = 0;
            let lastError = '';
            try {
                for (const row of rows) {
                    const payload = buildPayloadFromRow(row);
                    if (!payload) {
                        failedCount++;
                        continue;
                    }
                    try {
                        await upsertField(payload, {
                            refreshTables: false,
                            syncState: false,
                            successMessage: ''
                        });
                        savedCount++;
                    } catch (error) {
                        failedCount++;
                        lastError = String(error && error.message ? error.message : 'Failed to save one or more fields.');
                    }
                }
                if (failedCount > 0) {
                    setStatus(
                        'Saved ' + savedCount + ' field' + (savedCount === 1 ? '' : 's') +
                        '. ' + failedCount + ' failed.' + (lastError ? ' ' + lastError : ''),
                        true
                    );
                } else {
                    setStatus('Saved ' + savedCount + ' field' + (savedCount === 1 ? '' : 's') + '.');
                }
            } finally {
                saveAllBtn.disabled = prevDisabled;
            }
        });

        document.querySelectorAll('.js-add-field').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const location = String(btn.getAttribute('data-location') || 'firm');
                try {
                    await upsertField({
                        display_name: 'New Field',
                        field_type: 'text',
                        matching_tag: location + '_field_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11) + '_' + Math.random().toString(36).slice(2, 11),
                        location: location
                    });
                    setStatus('Field added. Update the row details; edits auto-save.');
                } catch (error) {
                    setStatus(error.message || 'Failed to add field.', true);
                }
            });
        });

        document.addEventListener('click', async function (event) {
            const deleteBtn = event.target.closest('.js-delete-field');
            if (!deleteBtn) return;
            const row = deleteBtn.closest('tr.js-field-row');
            if (!row) return;
            const id = String(row.getAttribute('data-field-id') || '');
            if (!id) return;
            if (!window.confirm('Delete this field?')) {
                return;
            }
            try {
                await deleteField(id);
            } catch (error) {
                setStatus(error.message || 'Failed to delete field.', true);
            }
        });

        document.addEventListener('input', function (event) {
            const field = event.target.closest('.js-display-name, .js-matching-tag, .js-sample-text');
            if (!field) return;
            const row = field.closest('tr.js-field-row');
            if (!row) return;
            scheduleRowAutoSave(row, 800);
        });

        document.addEventListener('change', function (event) {
            const field = event.target.closest('.js-field-type, .js-display-name, .js-matching-tag, .js-sample-text');
            if (!field) return;
            const row = field.closest('tr.js-field-row');
            if (!row) return;
            scheduleRowAutoSave(row, 200);
        });

        renderAllTables();
        updateStep(1);
    })();
</script>
