<?php
$projectViewConfig = is_array($projectViewConfig ?? null) ? $projectViewConfig : [];
$formSets = is_array($formSets ?? null) ? $formSets : [];
$globalForms = is_array($globalForms ?? null) ? $globalForms : [];
$clients = is_array($clients ?? null) ? $clients : [];
$caseLibrary = is_array($caseLibrary ?? null) ? $caseLibrary : [];

$client = null;
if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
    $client = $store->getClient((string)$project['clientId']);
}

$selectedFormSetId = (string)($projectViewConfig['selectedFormSetId'] ?? '');
$caseNumber = (string)($projectViewConfig['caseNumber'] ?? '');
$courtValues = is_array($projectViewConfig['courtValues'] ?? null) ? $projectViewConfig['courtValues'] : [];
$selectedCourtLocationId = (string)($projectViewConfig['selectedCourtLocationId'] ?? '');
$selectedCourtDepartmentId = (string)($projectViewConfig['selectedCourtDepartmentId'] ?? '');
$selectedCourtName = (string)($projectViewConfig['selectedCourtName'] ?? '');
$selectedCourtSystem = strtolower(trim((string)($projectViewConfig['selectedCourtSystem'] ?? 'state')));
if (!in_array($selectedCourtSystem, ['state', 'federal'], true)) {
    $selectedCourtSystem = 'state';
}
$courtSourceMeta = is_array($courtSourceMeta ?? null) ? $courtSourceMeta : [];
$courtFieldRows = is_array($courtFieldRows ?? null) ? $courtFieldRows : [];
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
    return strcasecmp((string)($a['displayName'] ?? $a['label'] ?? ''), (string)($b['displayName'] ?? $b['label'] ?? ''));
});
$courtFieldOrder = array_flip([
    'court_name',
    'court_branch',
    'court_street',
    'court_mailing_address',
    'court_city',
    'court_state',
    'court_zip',
    'court_city_zip',
    'court_county',
    'court_department',
    'court_room',
    'court_floor',
    'court_phone',
]);
usort($courtFieldRows, static function (array $a, array $b) use ($courtFieldOrder): int {
    $aLink = (string)($a['linkId'] ?? '');
    $bLink = (string)($b['linkId'] ?? '');
    $aRank = $courtFieldOrder[$aLink] ?? 999;
    $bRank = $courtFieldOrder[$bLink] ?? 999;
    if ($aRank !== $bRank) {
        return $aRank <=> $bRank;
    }
    return strcasecmp((string)($a['displayName'] ?? $a['label'] ?? ''), (string)($b['displayName'] ?? $b['label'] ?? ''));
});
$courtHelpFallback = 'Use this to search and select a court; we auto-fill branch, address, city, state, and ZIP. Example: "Stanley Mosk Courthouse."';
$stateCourtHelp = 'Search all state courts (not limited to California). Example: "Stanley Mosk Courthouse" or "Downtown Superior Court."';
$federalCourtHelp = 'Search U.S. federal courts. Example: "Central District of California" or "Southern District of New York."';
$caseValues = is_array($projectViewConfig['caseValues'] ?? null) ? $projectViewConfig['caseValues'] : [];
$attorneyValues = is_array($projectViewConfig['attorneyValues'] ?? null) ? $projectViewConfig['attorneyValues'] : [];
$selectedAttorneyId = (string)($projectViewConfig['selectedAttorneyId'] ?? '');
$selectedAttorneyName = (string)($projectViewConfig['selectedAttorneyName'] ?? '');
$projectFieldRows = [];
$projectFieldValues = [];
$additionalTemplateIds = is_array($projectViewConfig['additionalTemplateIds'] ?? null) ? $projectViewConfig['additionalTemplateIds'] : [];
$savedTemplateOrder = is_array($projectViewConfig['templateOrder'] ?? null) ? $projectViewConfig['templateOrder'] : [];

$globalById = [];
foreach ($globalForms as $row) {
    if (!is_array($row)) {
        continue;
    }
    $tid = (string)($row['templateId'] ?? '');
    if ($tid === '') {
        continue;
    }
    $globalById[$tid] = $row;
}

$selectedFormSet = null;
foreach ($formSets as $setRow) {
    if ((string)($setRow['id'] ?? '') === $selectedFormSetId) {
        $selectedFormSet = $setRow;
        break;
    }
}

$baseTemplateIds = [];
if (is_array($selectedFormSet)) {
    foreach ((array)($selectedFormSet['templateIds'] ?? []) as $tidRaw) {
        $tid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$tidRaw);
        if ($tid === '') {
            continue;
        }
        $baseTemplateIds[] = $tid;
    }
}
$baseTemplateIds = array_values(array_unique($baseTemplateIds));

$additionalTemplateIds = array_values(array_unique(array_filter(array_map(static function ($value) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$value);
}, $additionalTemplateIds))));

$finalTemplateOrder = [];
$seedOrder = array_merge($savedTemplateOrder, $baseTemplateIds, $additionalTemplateIds);
foreach ($seedOrder as $tidRaw) {
    $tid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$tidRaw);
    if ($tid === '' || in_array($tid, $finalTemplateOrder, true)) {
        continue;
    }
    $finalTemplateOrder[] = $tid;
}

$formRows = [];
foreach ($finalTemplateOrder as $tid) {
    $row = $globalById[$tid] ?? ['templateId' => $tid, 'formName' => $tid];
    $row['templateId'] = $tid;
    $formRows[] = $row;
}

$clientIsSelected = is_array($client);
$hasCourt = trim($selectedCourtName) !== '' || trim($selectedCourtLocationId) !== '' || count(array_filter($courtValues, static fn($v) => trim((string)$v) !== '')) > 0;
$hasAttorney = trim($selectedAttorneyName) !== '' || trim($selectedAttorneyId) !== '' || count(array_filter($attorneyValues, static fn($v) => trim((string)$v) !== '')) > 0;
$hasCase = trim($caseNumber) !== '';
$hasSelectedFormSet = is_array($selectedFormSet) && count($baseTemplateIds) > 0;
$docByTemplate = [];
foreach ($documents as $docRow) {
    if (!is_array($docRow)) {
        continue;
    }
    $tid = (string)($docRow['templateId'] ?? '');
    if ($tid === '' || isset($docByTemplate[$tid])) {
        continue;
    }
    $docByTemplate[$tid] = $docRow;
}
$firstPendingDoc = null;
foreach ($finalTemplateOrder as $tid) {
    if (isset($docByTemplate[$tid])) {
        $firstPendingDoc = $docByTemplate[$tid];
        break;
    }
}
$nextTemplateId = '';
if (!empty($finalTemplateOrder)) {
    $nextTemplateId = (string)$finalTemplateOrder[0];
}
$nextDisabled = !($clientIsSelected && $hasCase && $hasSelectedFormSet && $nextTemplateId !== '');
?>

<style>
    .project-view-grid {
        display: grid;
        gap: 14px;
    }
    .project-view-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .project-view-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .project-section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }
    .project-pill {
        padding: 4px 8px;
        border-radius: 6px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 12px;
        font-weight: 600;
    }
    .project-form-actions {
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .project-next-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 8px;
    }
    .project-additional-wrap {
        margin-top: 12px;
        border-top: 1px solid #e5e7eb;
        padding-top: 12px;
    }
    .project-formset-actions {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }
    .project-footer-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .wpts-selectable-row { cursor: pointer; }
    .wpts-selectable-row:hover td { background: #eef2f7; }
    .wpts-info-tip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        margin-left: 6px;
        border-radius: 50%;
        border: 1px solid #94a3b8;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        cursor: help;
        vertical-align: middle;
        flex-shrink: 0;
        position: relative;
    }
    .wpts-info-tip::after {
        content: attr(data-tip);
        position: absolute;
        left: 50%;
        top: auto;
        bottom: calc(100% + 8px);
        transform: translateX(-50%);
        min-width: 0;
        width: min(320px, calc(100vw - 28px));
        max-width: min(360px, calc(100vw - 28px));
        padding: 8px 10px;
        border-radius: 8px;
        background: #0f172a;
        color: #fff;
        font-size: 12px;
        line-height: 1.35;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
        opacity: 0;
        pointer-events: none;
        z-index: 2000;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .wpts-info-tip:hover::after,
    .wpts-info-tip:focus-visible::after {
        opacity: 1;
    }
    .wpts-court-system-row {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .wpts-court-system-row label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        font-weight: 500;
    }
    .project-view-grid .pdftimesaver-btn-secondary,
    .project-view-grid .pdftimesaver-btn,
    .project-footer-actions .pdftimesaver-btn-secondary,
    .project-footer-actions .pdftimesaver-btn {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .project-view-grid .pdftimesaver-btn-secondary:hover,
    .project-view-grid .pdftimesaver-btn:hover,
    .project-footer-actions .pdftimesaver-btn-secondary:hover,
    .project-footer-actions .pdftimesaver-btn:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }
    .project-view-grid .pdftimesaver-icon-btn {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .project-view-grid .pdftimesaver-icon-btn:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }
</style>

<div class="pdftimesaver-card wpts-form-shell">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
        <div>
            <h2 class="wpts-form-title">Project View</h2>
            <p class="wpts-form-help">Select client, reuse or enter case details, choose a form set, then add/reorder forms and continue to Fill Out Forms.</p>
        </div>
        <button type="button" id="deleteProjectBtn" class="pdftimesaver-delete-btn" aria-label="Delete project" title="Delete project">&#128465;</button>
    </div>
</div>

<form method="post" action="?route=actions/save-project-view-config" class="project-view-grid">
    <input type="hidden" name="projectId" value="<?php echo htmlspecialchars((string)$project['id']); ?>">
    <input type="hidden" name="caseValuesJson" id="caseValuesJsonInput">
    <input type="hidden" name="courtValuesJson" id="courtValuesJsonInput">
    <input type="hidden" name="attorneyValuesJson" id="attorneyValuesJsonInput">
    <input type="hidden" name="selectedAttorneyId" id="selectedAttorneyIdInput" value="<?php echo htmlspecialchars($selectedAttorneyId); ?>">
    <input type="hidden" name="selectedAttorneyName" id="selectedAttorneyNameInput" value="<?php echo htmlspecialchars($selectedAttorneyName); ?>">
    <input type="hidden" name="selectedCourtLocationId" id="selectedCourtLocationIdInput" value="<?php echo htmlspecialchars($selectedCourtLocationId); ?>">
    <input type="hidden" name="selectedCourtDepartmentId" id="selectedCourtDepartmentIdInput" value="<?php echo htmlspecialchars($selectedCourtDepartmentId); ?>">
    <input type="hidden" name="selectedCourtName" id="selectedCourtNameInput" value="<?php echo htmlspecialchars($selectedCourtName); ?>">
    <input type="hidden" name="selectedCourtSystem" id="selectedCourtSystemInput" value="<?php echo htmlspecialchars($selectedCourtSystem); ?>">
    <input type="hidden" name="projectFieldRowsJson" id="projectFieldRowsJsonInput">
    <input type="hidden" name="projectFieldValuesJson" id="projectFieldValuesJsonInput">
    <input type="hidden" name="additionalTemplateIdsJson" id="additionalTemplateIdsJsonInput">
    <input type="hidden" name="templateOrderJson" id="templateOrderJsonInput">

    <div class="pdftimesaver-card">
        <div class="project-view-row">
            <label class="pdftimesaver-form-label" for="projectNameInput" style="margin:0;">Project Name</label>
        </div>
        <input id="projectNameInput" name="projectName" type="text" class="pdftimesaver-input" value="<?php echo htmlspecialchars((string)$project['name']); ?>" placeholder="Project name">
    </div>

    <div class="pdftimesaver-card">
        <div class="project-view-row">
            <div>
                <span class="project-section-title">Client</span>
                <?php if ($clientIsSelected): ?>
                    <div class="wpts-form-help" style="margin:4px 0 0 0;"><?php echo htmlspecialchars((string)($client['displayName'] ?? '')); ?></div>
                <?php else: ?>
                    <div class="wpts-form-help" style="margin:4px 0 0 0;">No client selected.</div>
                <?php endif; ?>
            </div>
            <div class="project-view-actions">
                <?php if ($clientIsSelected): ?>
                    <a href="?route=client&id=<?php echo urlencode((string)$client['id']); ?>&returnTo=<?php echo urlencode('?route=project&id=' . (string)$project['id']); ?>" class="pdftimesaver-btn-secondary">Edit</a>
                <?php endif; ?>
                <button type="button" class="pdftimesaver-btn-secondary" id="toggleClientPickerBtn"><?php echo $clientIsSelected ? 'Change' : 'Select'; ?></button>
            </div>
        </div>
        <div id="clientPickerWrap" style="display:none; margin-top:12px;">
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center;">
                <input type="text" id="clientSearchInput" class="pdftimesaver-input" placeholder="Search clients by name or company">
                <button type="button" class="pdftimesaver-btn-secondary" id="clientSearchBtn">Search</button>
                <a href="?route=clients" class="pdftimesaver-btn">Add Client</a>
            </div>
            <div id="clientSearchList" style="margin-top:10px;" class="table-responsive"></div>
        </div>
    </div>

    <div class="pdftimesaver-card">
        <div class="project-view-row">
            <div>
                <span class="project-section-title">Court</span><?php
                    $courtTip = $selectedCourtSystem === 'federal' ? $federalCourtHelp : $stateCourtHelp;
                    echo ' <span class="wpts-info-tip" data-tip="' . htmlspecialchars($courtTip) . '" tabindex="0" aria-label="Court data sources">?</span>';
                ?>
                <div id="courtSummaryText" class="wpts-form-help" style="margin:4px 0 0 0;"><?php
                    if ($hasCourt) {
                        $courtLabel = trim($selectedCourtName);
                        if ($courtLabel === '') {
                            $courtLabel = trim((string)($courtValues[array_key_first($courtValues)] ?? ''));
                        }
                        echo htmlspecialchars($courtLabel !== '' ? $courtLabel : 'Court details entered.');
                    } else {
                        echo 'No court selected.';
                    }
                ?></div>
            </div>
            <div class="project-view-actions">
                <button type="button" class="pdftimesaver-btn-secondary" id="toggleCourtEditorBtn"><?php echo $hasCourt ? 'Change' : 'Select'; ?></button>
            </div>
        </div>
        <div id="courtEditorWrap" style="display:none; margin-top:12px;">
            <div class="wpts-court-system-row">
                <span class="wpts-form-help" style="margin:0;">Court type:</span>
                <label>
                    <input type="radio" name="courtSystemPicker" value="state"<?php echo $selectedCourtSystem === 'state' ? ' checked' : ''; ?>>
                    State
                    <span class="wpts-info-tip" data-tip="<?php echo htmlspecialchars($stateCourtHelp); ?>" tabindex="0" aria-label="State court data sources">?</span>
                </label>
                <label>
                    <input type="radio" name="courtSystemPicker" value="federal"<?php echo $selectedCourtSystem === 'federal' ? ' checked' : ''; ?>>
                    Federal
                    <span class="wpts-info-tip" data-tip="<?php echo htmlspecialchars($federalCourtHelp); ?>" tabindex="0" aria-label="Federal court data sources">?</span>
                </label>
            </div>
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <input type="text" id="courtSearchInput" class="pdftimesaver-input" placeholder="Search courts by name, city, county, zip, or department">
                <button type="button" class="pdftimesaver-btn-secondary" id="courtSearchBtn">Search</button>
            </div>
            <div id="courtSearchList" class="table-responsive" style="margin-bottom:10px;"></div>
            <div id="courtDepartmentWrap" class="wpts-form-group" style="display:none; margin-bottom:10px;">
                <label class="pdftimesaver-form-label" for="courtDepartmentSelect">
                    Department / Room
                    <span class="wpts-info-tip" id="courtDeptSourceTip" data-tip="<?php echo htmlspecialchars($stateCourtHelp); ?>" tabindex="0" aria-label="Department data source">?</span>
                </label>
                <select id="courtDepartmentSelect" class="pdftimesaver-input"></select>
            </div>
            <?php if (!empty($courtFieldRows)): ?>
                <div class="wpts-form-grid wpts-form-grid-2">
                    <?php foreach ($courtFieldRows as $row): ?>
                        <?php
                        $fid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($row['id'] ?? ''));
                        if ($fid === '') { continue; }
                        $label = (string)($row['displayName'] ?? $row['label'] ?? $fid);
                        $value = (string)($courtValues[$fid] ?? '');
                        ?>
                        <div class="wpts-form-group">
                            <label class="pdftimesaver-form-label" for="courtField_<?php echo htmlspecialchars($fid); ?>"><?php echo htmlspecialchars($label); ?></label>
                            <input
                                id="courtField_<?php echo htmlspecialchars($fid); ?>"
                                type="text"
                                class="pdftimesaver-input js-court-field"
                                data-court-id="<?php echo htmlspecialchars($fid); ?>"
                                data-court-link-id="<?php echo htmlspecialchars((string)($row['linkId'] ?? '')); ?>"
                                value="<?php echo htmlspecialchars($value); ?>"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wpts-form-help">No court fields configured in Field Manager yet.</div>
            <?php endif; ?>
        </div>
    </div>

<div class="pdftimesaver-card">
        <div class="project-view-row">
            <div>
                <span class="project-section-title">Case</span>
                <div id="caseSummaryText" class="wpts-form-help" style="margin:4px 0 0 0;"><?php echo $hasCase ? htmlspecialchars($caseNumber) : 'No case selected.'; ?></div>
            </div>
            <div class="project-view-actions">
                <button type="button" class="pdftimesaver-btn-secondary" id="toggleCaseEditorBtn"><?php echo $hasCase ? 'Change' : 'Select'; ?></button>
            </div>
        </div>
        <div id="caseEditorWrap" style="display:none; margin-top:12px;">
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <input type="text" id="caseSearchInput" class="pdftimesaver-input" placeholder="Search existing cases by case number or project">
                <button type="button" class="pdftimesaver-btn-secondary" id="caseSearchBtn">Search</button>
                <button type="button" class="pdftimesaver-btn-secondary" id="caseBrowseBtn">Browse</button>
            </div>
            <div id="caseSearchList" class="table-responsive" style="margin-bottom:10px;"></div>
            <div class="wpts-form-group">
                <label class="pdftimesaver-form-label" for="caseNumberInput">Case Number</label>
                <input id="caseNumberInput" type="text" name="caseNumber" class="pdftimesaver-input" value="<?php echo htmlspecialchars($caseNumber); ?>" placeholder="Enter case number">
            </div>
            <?php if (!empty($caseFieldRows)): ?>
                <div class="wpts-form-grid wpts-form-grid-2">
                    <?php foreach ($caseFieldRows as $row): ?>
                        <?php
                        $fid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($row['id'] ?? ''));
                        if ($fid === '') { continue; }
                        $label = (string)($row['displayName'] ?? $row['label'] ?? $fid);
                        $value = (string)($caseValues[$fid] ?? '');
                        ?>
                        <div class="wpts-form-group">
                            <label class="pdftimesaver-form-label" for="caseField_<?php echo htmlspecialchars($fid); ?>"><?php echo htmlspecialchars($label); ?></label>
                            <input
                                id="caseField_<?php echo htmlspecialchars($fid); ?>"
                                type="text"
                                class="pdftimesaver-input js-case-field"
                                data-case-id="<?php echo htmlspecialchars($fid); ?>"
                                value="<?php echo htmlspecialchars($value); ?>"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wpts-form-help">No case dynamic fields configured in Field Manager yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pdftimesaver-card">
        <div class="project-view-row">
            <div>
                <span class="project-section-title">Attorney</span>
                <div id="attorneySummaryText" class="wpts-form-help" style="margin:4px 0 0 0;"><?php
                    if ($hasAttorney) {
                        $attorneyLabel = trim($selectedAttorneyName);
                        if ($attorneyLabel === '') {
                            foreach ($attorneyFieldRows as $row) {
                                if (!is_array($row)) { continue; }
                                if ((string)($row['linkId'] ?? '') !== 'attorney_name') { continue; }
                                $fid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($row['id'] ?? ''));
                                $attorneyLabel = trim((string)($attorneyValues[$fid] ?? ''));
                                if ($attorneyLabel !== '') { break; }
                            }
                        }
                        echo htmlspecialchars($attorneyLabel !== '' ? $attorneyLabel : 'Attorney details entered.');
                    } else {
                        echo 'No attorney selected.';
                    }
                ?></div>
            </div>
            <div class="project-view-actions">
                <button type="button" class="pdftimesaver-btn-secondary" id="toggleAttorneyEditorBtn"><?php echo $hasAttorney ? 'Change' : 'Select'; ?></button>
            </div>
        </div>
        <div id="attorneyEditorWrap" style="display:none; margin-top:12px;">
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <input type="text" id="attorneySearchInput" class="pdftimesaver-input" placeholder="Search attorneys by name, firm, or bar number">
                <button type="button" class="pdftimesaver-btn-secondary" id="attorneySearchBtn">Search</button>
                <button type="button" class="pdftimesaver-btn-secondary" id="attorneyBrowseBtn">Browse</button>
            </div>
            <div id="attorneySearchList" class="table-responsive" style="margin-bottom:10px;"></div>
            <?php if (!empty($attorneyFieldRows)): ?>
                <div class="wpts-form-grid wpts-form-grid-2">
                    <?php foreach ($attorneyFieldRows as $row): ?>
                        <?php
                        $fid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($row['id'] ?? ''));
                        if ($fid === '') { continue; }
                        $label = (string)($row['displayName'] ?? $row['label'] ?? $fid);
                        $value = (string)($attorneyValues[$fid] ?? '');
                        ?>
                        <div class="wpts-form-group">
                            <label class="pdftimesaver-form-label" for="attorneyField_<?php echo htmlspecialchars($fid); ?>"><?php echo htmlspecialchars($label); ?></label>
                            <input
                                id="attorneyField_<?php echo htmlspecialchars($fid); ?>"
                                type="text"
                                class="pdftimesaver-input js-attorney-field"
                                data-attorney-id="<?php echo htmlspecialchars($fid); ?>"
                                data-attorney-link-id="<?php echo htmlspecialchars((string)($row['linkId'] ?? '')); ?>"
                                value="<?php echo htmlspecialchars($value); ?>"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wpts-form-help">No attorney fields configured in Field Manager yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pdftimesaver-card">
        <div class="project-view-row">
            <div>
                <span class="project-section-title">Form Set</span>
                <?php if (is_array($selectedFormSet)): ?>
                    <div id="selectedFormSetLabel" class="wpts-form-help" style="margin:4px 0 0 0;"><?php echo htmlspecialchars((string)($selectedFormSet['name'] ?? $selectedFormSet['id'] ?? 'Selected')); ?></div>
                <?php else: ?>
                    <div id="selectedFormSetLabel" class="wpts-form-help" style="margin:4px 0 0 0;">No form set selected.</div>
                <?php endif; ?>
            </div>
            <div class="project-view-actions">
                <button type="button" class="pdftimesaver-btn-secondary" id="toggleFormSetPickerBtn"><?php echo is_array($selectedFormSet) ? 'Change' : 'Select'; ?></button>
    </div>
</div>
        <div id="formSetPickerWrap" style="display:none; margin-top:12px;">
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center;">
                <input type="text" id="formSetSearchInput" class="pdftimesaver-input" placeholder="Search form sets">
                <button type="button" class="pdftimesaver-btn-secondary" id="formSetSearchBtn">Search</button>
                <button type="button" class="pdftimesaver-btn-secondary" id="formSetBrowseBtn">Browse</button>
            </div>
            <div id="formSetSearchList" style="margin-top:10px;" class="table-responsive"></div>
        </div>
</div>

<div class="pdftimesaver-card">
        <div class="project-view-row">
            <span class="project-section-title">Selected Forms</span>
            <span class="project-pill"><?php echo count($formRows); ?> total</span>
        </div>
        <div id="projectFormsWrap" class="table-responsive" style="margin-top:10px;"></div>
        <div class="project-formset-actions">
            <a
                href="?route=form-sets-manager<?php echo $selectedFormSetId !== '' ? '&set_id=' . urlencode($selectedFormSetId) : ''; ?>"
                class="pdftimesaver-btn-secondary"
            >Update</a>
            <a href="?route=form-sets-manager" class="pdftimesaver-btn-secondary">Add New</a>
        </div>
        <div class="project-additional-wrap">
            <div class="project-view-row">
            <span class="project-section-title">Add Additional Forms</span>
            </div>
            <div class="wpts-form-row" style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                <input type="text" id="addFormSearchInput" class="pdftimesaver-input" placeholder="Search forms to append...">
                <button type="button" class="pdftimesaver-btn-secondary" id="addFormSearchBtn">Search</button>
                <button type="button" class="pdftimesaver-btn-secondary" id="addFormBrowseBtn">Browse</button>
            </div>
            <div id="addFormResultsWrap" class="table-responsive" style="margin-top:10px;"></div>
        </div>
    </div>

</form>

<div class="pdftimesaver-card">
    <div class="project-footer-actions">
        <button type="button" id="backToProjectsBtn" class="pdftimesaver-btn-secondary pdftimesaver-btn-action">Back</button>
        <form method="post" action="?route=actions/open-project-form" style="margin:0;">
            <input type="hidden" name="projectId" value="<?php echo htmlspecialchars((string)$project['id']); ?>">
            <input type="hidden" name="templateId" id="nextTemplateIdInput" value="<?php echo htmlspecialchars($nextTemplateId); ?>">
            <input type="hidden" name="templateOrderJson" id="nextTemplateOrderInput" value="">
            <input type="hidden" name="selectedFormSetId" id="nextSelectedFormSetInput" value="<?php echo htmlspecialchars($selectedFormSetId); ?>">
            <button type="submit" id="nextFillOutBtn" class="pdftimesaver-btn pdftimesaver-btn-action<?php echo $nextDisabled ? ' pdftimesaver-btn-secondary' : ''; ?>" <?php echo $nextDisabled ? 'aria-disabled="true" onclick="return false;"' : ''; ?>>
                Next
                            </button>
                                </form>
    </div>
    <div id="autosaveStatus" class="wpts-form-help" style="margin-top:8px;">All changes save automatically.</div>
    <div id="nextRequirementsHint" class="wpts-form-help" style="margin-top:8px;<?php echo $nextDisabled ? '' : 'display:none;'; ?>">Next requires: selected client, case number, and selected form set.</div>
</div>

<script>
(function () {
    const projectId = <?php echo json_encode((string)$project['id']); ?>;
    const clients = <?php echo json_encode(array_values($clients), JSON_UNESCAPED_SLASHES); ?>;
    const formSets = <?php echo json_encode(array_values($formSets), JSON_UNESCAPED_SLASHES); ?>;
    const caseLibrary = <?php echo json_encode(array_values($caseLibrary), JSON_UNESCAPED_SLASHES); ?>;
    const courtFieldRows = <?php echo json_encode(array_values($courtFieldRows ?? []), JSON_UNESCAPED_SLASHES); ?>;
    const attorneyFieldRows = <?php echo json_encode(array_values($attorneyFieldRows ?? []), JSON_UNESCAPED_SLASHES); ?>;
    const globalById = <?php echo json_encode($globalById, JSON_UNESCAPED_SLASHES); ?>;
    const globalForms = <?php echo json_encode(array_values($globalForms), JSON_UNESCAPED_SLASHES); ?>;
    const existingDocs = <?php echo json_encode(array_values($documents), JSON_UNESCAPED_SLASHES); ?>;
    const hasClientSelected = <?php echo $clientIsSelected ? 'true' : 'false'; ?>;
    let additionalTemplateIds = <?php echo json_encode(array_values($additionalTemplateIds), JSON_UNESCAPED_SLASHES); ?>;
    let templateOrder = <?php echo json_encode(array_values($finalTemplateOrder), JSON_UNESCAPED_SLASHES); ?>;
    let projectFieldRows = <?php echo json_encode(array_values($projectFieldRows), JSON_UNESCAPED_SLASHES); ?>;
    let projectFieldValues = <?php echo json_encode($projectFieldValues, JSON_UNESCAPED_SLASHES); ?>;
    let selectedFormSetId = <?php echo json_encode($selectedFormSetId); ?>;
    let selectedCourtLocationId = <?php echo json_encode($selectedCourtLocationId); ?>;
    let selectedCourtDepartmentId = <?php echo json_encode($selectedCourtDepartmentId); ?>;
    let selectedCourtName = <?php echo json_encode($selectedCourtName); ?>;
    let selectedCourtSystem = <?php echo json_encode($selectedCourtSystem); ?>;
    let selectedAttorneyId = <?php echo json_encode($selectedAttorneyId); ?>;
    let selectedAttorneyName = <?php echo json_encode($selectedAttorneyName); ?>;
    const courtSourceMeta = <?php echo json_encode($courtSourceMeta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    let attorneyRoster = [];
    let pendingCourtLocation = null;

    const form = document.querySelector('form[action*="save-project-view-config"]');
    const hiddenCaseValues = document.getElementById('caseValuesJsonInput');
    const hiddenCourtValues = document.getElementById('courtValuesJsonInput');
    const hiddenAttorneyValues = document.getElementById('attorneyValuesJsonInput');
    const hiddenAttorneyId = document.getElementById('selectedAttorneyIdInput');
    const hiddenAttorneyName = document.getElementById('selectedAttorneyNameInput');
    const hiddenCourtLocationId = document.getElementById('selectedCourtLocationIdInput');
    const hiddenCourtDepartmentId = document.getElementById('selectedCourtDepartmentIdInput');
    const hiddenCourtName = document.getElementById('selectedCourtNameInput');
    const hiddenCourtSystem = document.getElementById('selectedCourtSystemInput');
    const hiddenProjectFieldRows = document.getElementById('projectFieldRowsJsonInput');
    const hiddenProjectFieldValues = document.getElementById('projectFieldValuesJsonInput');
    const hiddenAdditionalTemplateIds = document.getElementById('additionalTemplateIdsJsonInput');
    const hiddenTemplateOrder = document.getElementById('templateOrderJsonInput');
    const selectedFormSetLabel = document.getElementById('selectedFormSetLabel');
    const nextFillOutBtn = document.getElementById('nextFillOutBtn');
    const nextTemplateIdInput = document.getElementById('nextTemplateIdInput');
    const nextRequirementsHint = document.getElementById('nextRequirementsHint');

    function escapeHtml(v) {
        return String(v || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeTemplateOrder() {
        const seen = {};
        const out = [];
        const selectedSet = (formSets || []).find((row) => String(row.id || '') === String(selectedFormSetId || ''));
        const selectedSetTemplateIds = Array.isArray(selectedSet && selectedSet.templateIds)
            ? selectedSet.templateIds.map((v) => String(v || '').trim()).filter(Boolean)
            : [];
        const merged = []
            .concat(Array.isArray(templateOrder) ? templateOrder : [])
            .concat(selectedSetTemplateIds)
            .concat(Array.isArray(additionalTemplateIds) ? additionalTemplateIds : []);
        merged.forEach((value) => {
            const tid = String(value || '').trim();
            if (!tid || seen[tid]) return;
            seen[tid] = true;
            out.push(tid);
        });
        templateOrder = out;
    }

    function formLabelByTid(tid) {
        const row = globalById[String(tid)] || {};
        const name = String(row.formName || '').trim();
        return name || String(tid);
    }

    function docIdByTemplateId(tid) {
        const found = (existingDocs || []).find((d) => String(d.templateId || '') === String(tid));
        return found ? String(found.id || '') : '';
    }

    function hasSavedDocumentForCurrentOrder() {
        return (templateOrder || []).some((tid) => !!docIdByTemplateId(tid));
    }

    function renderProjectForms() {
        normalizeTemplateOrder();
        if (typeof syncHiddenInputs === 'function') syncHiddenInputs();
        if (typeof updateNextButtonState === 'function') updateNextButtonState();
        const host = document.getElementById('projectFormsWrap');
        if (!host) return;
        if (!templateOrder.length) {
            host.innerHTML = '<div class="wpts-form-help">No forms selected yet.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Actions</th><th>Order</th><th>Form</th><th>Template ID</th></tr></thead><tbody>' +
            templateOrder.map((tid, idx) => {
                const canUp = idx > 0;
                const canDown = idx < templateOrder.length - 1;
                const docId = docIdByTemplateId(tid);
                const viewHref = docId ? ('?route=populate&pd=' + encodeURIComponent(docId)) : ('?route=actions/form-template-pdf&template_id=' + encodeURIComponent(tid));
                return '<tr>' +
                    '<td class="project-form-actions">' +
                        '<a class="pdftimesaver-icon-btn" href="' + viewHref + '" aria-label="View" title="View">&#128065;</a>' +
                        '<button type="button" class="pdftimesaver-icon-btn js-form-move" data-tid="' + escapeHtml(tid) + '" data-dir="up" aria-label="Up" title="Up"' + (canUp ? '' : ' disabled') + '>&#9650;</button>' +
                        '<button type="button" class="pdftimesaver-icon-btn js-form-move" data-tid="' + escapeHtml(tid) + '" data-dir="down" aria-label="Down" title="Down"' + (canDown ? '' : ' disabled') + '>&#9660;</button>' +
                        '<button type="button" class="pdftimesaver-icon-btn js-form-remove" data-tid="' + escapeHtml(tid) + '" aria-label="Remove" title="Remove">&#10005;</button>' +
                    '</td>' +
                    '<td>' + String(idx + 1) + '</td>' +
                    '<td>' + escapeHtml(formLabelByTid(tid)) + '</td>' +
                    '<td><code>' + escapeHtml(tid) + '</code></td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function renderAdditionalFormRows(forceBrowse) {
        const host = document.getElementById('addFormResultsWrap');
        if (!host) return;
        const q = String((document.getElementById('addFormSearchInput') || {}).value || '').trim().toLowerCase();
        if (!forceBrowse && !q) {
            host.innerHTML = '<div class="wpts-form-help">Search forms to append, or click Browse.</div>';
            return;
        }
        const rows = (globalForms || []).filter((row) => {
            const tid = String(row.templateId || '').trim();
            if (!tid) return false;
            if (forceBrowse) return true;
            return [row.formName, row.templateId, row.sourceFileName, row.formLocation]
                .join(' ')
                .toLowerCase()
                .indexOf(q) >= 0;
        });
        if (!rows.length) {
            host.innerHTML = '<div class="wpts-form-help">No forms found.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Actions</th><th>Form</th><th>Template ID</th></tr></thead><tbody>' +
            rows.map((row) => {
                const tid = String(row.templateId || '').trim();
                const alreadyIncluded = (templateOrder || []).includes(tid);
                const label = String(row.formName || tid || '').trim() || tid;
                return '<tr>' +
                    '<td class="project-form-actions">' +
                        '<button type="button" class="pdftimesaver-icon-btn js-add-additional-form" data-tid="' + escapeHtml(tid) + '" aria-label="Add Form" title="Add Form"' + (alreadyIncluded ? ' disabled' : '') + '>&#43;</button>' +
                    '</td>' +
                    '<td>' + escapeHtml(label) + '</td>' +
                    '<td><code>' + escapeHtml(tid) + '</code></td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function setClientPickerVisible(show) {
        const wrap = document.getElementById('clientPickerWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';
    }

    function setCaseEditorVisible(show) {
        const wrap = document.getElementById('caseEditorWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';
    }

    function setCourtEditorVisible(show) {
        const wrap = document.getElementById('courtEditorWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';
    }

    function courtSystemTooltip(system) {
        const key = String(system || 'state').toLowerCase();
        if (key === 'federal') {
            return 'Search U.S. federal courts. Example: "Central District of California" or "Southern District of New York."';
        }
        return 'Search all state courts (not limited to California). Example: "Stanley Mosk Courthouse" or "Downtown Superior Court."';
    }

    function normalizeTipText(raw) {
        const text = String(raw || '').replace(/\s+/g, ' ').trim();
        if (!text) return '';
        const parts = text.split(/[.;]\s+/).map((s) => s.trim()).filter(Boolean);
        const seen = new Set();
        const unique = [];
        parts.forEach((p) => {
            const key = p.toLowerCase();
            if (!seen.has(key)) {
                seen.add(key);
                unique.push(p);
            }
        });
        const joined = unique.slice(0, 2).join('. ');
        return joined.length > 180 ? (joined.slice(0, 177).trim() + '...') : joined;
    }

    function applyTipText(el, raw) {
        if (!el) return;
        const tip = normalizeTipText(raw) || 'Search and select a court to auto-fill location details.';
        el.removeAttribute('title');
        el.setAttribute('data-tip', tip);
    }

    function updateCourtSearchPlaceholder() {
        const input = document.getElementById('courtSearchInput');
        if (!input) return;
        if (String(selectedCourtSystem || 'state') === 'federal') {
            input.placeholder = 'Search federal courts by name, city, state, or zip';
        } else {
            input.placeholder = 'Search courts by name, city, county, zip, or department';
        }
    }

    function updateCourtHeaderTip() {
        const headerTip = document.querySelector('.pdftimesaver-card .wpts-info-tip[aria-label="Court data sources"]');
        const tip = courtSystemTooltip(selectedCourtSystem);
        applyTipText(headerTip, tip);
    }

    function updateCourtDeptSourceTip() {
        const tipEl = document.getElementById('courtDeptSourceTip');
        if (!tipEl) return;
        if (String(selectedCourtSystem || 'state') === 'federal') {
            const msg = 'Federal division office addresses from PACER — individual courtroom numbers are not included.';
            applyTipText(tipEl, msg);
            tipEl.style.display = '';
        } else {
            const msg = courtSystemTooltip('state');
            applyTipText(tipEl, msg);
            tipEl.style.display = '';
        }
    }

    function setSelectedCourtSystem(system) {
        const next = String(system || 'state').toLowerCase() === 'federal' ? 'federal' : 'state';
        if (next === selectedCourtSystem) return;
        selectedCourtSystem = next;
        if (hiddenCourtSystem) hiddenCourtSystem.value = selectedCourtSystem;
        updateCourtSearchPlaceholder();
        updateCourtHeaderTip();
        updateCourtDeptSourceTip();
        const host = document.getElementById('courtSearchList');
        if (host) host.innerHTML = '';
        pendingCourtLocation = null;
        document.querySelectorAll('input[name="courtSystemPicker"]').forEach((el) => {
            el.checked = String(el.value || '') === selectedCourtSystem;
        });
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    }

    function formatCityZip(loc) {
        const city = String(loc && loc.city || '').trim();
        const st = String(loc && loc.stateCode || 'CA').trim();
        const zip = String(loc && loc.zip || '').trim();
        if (!city && !zip) return '';
        if (city && st && zip) return city + ', ' + st + ' ' + zip;
        return [city, st, zip].filter(Boolean).join(' ');
    }

    function formatBranch(dept) {
        if (!dept) return '';
        const parts = [];
        const d = String(dept.department || '').trim();
        if (d) parts.push('Dept. ' + d);
        const room = String(dept.room || '').trim();
        if (room) parts.push('Room ' + room);
        else {
            const floor = String(dept.floor || '').trim();
            if (floor) parts.push('Floor ' + floor);
        }
        return parts.join(', ');
    }

    function courtLinkIdMap() {
        const out = {};
        (courtFieldRows || []).forEach((row) => {
            const linkId = String(row.linkId || '').trim();
            const fid = String(row.id || '').trim();
            if (linkId && fid) out[linkId] = fid;
        });
        return out;
    }

    function setCourtFieldByLinkId(linkId, value) {
        const map = courtLinkIdMap();
        const fid = map[String(linkId || '').trim()];
        if (!fid) return;
        const el = document.querySelector('.js-court-field[data-court-id="' + fid + '"]');
        if (el) el.value = String(value || '');
    }

    function renderCourtDepartmentPicker(loc, selectedDeptId) {
        const wrap = document.getElementById('courtDepartmentWrap');
        const sel = document.getElementById('courtDepartmentSelect');
        if (!wrap || !sel || !loc) {
            if (wrap) wrap.style.display = 'none';
            return;
        }
        if (String(selectedCourtSystem || 'state') === 'federal' || String(loc.courtSystem || '').toLowerCase() === 'federal') {
            wrap.style.display = 'none';
            sel.innerHTML = '';
            return;
        }
        const depts = Array.isArray(loc.departments) ? loc.departments : [];
        if (!depts.length) {
            wrap.style.display = 'none';
            sel.innerHTML = '';
            return;
        }
        wrap.style.display = '';
        sel.innerHTML = '<option value="">— Select department —</option>' +
            depts.map((dept) => {
                const id = String(dept.id || '').trim();
                const label = formatBranch(dept) || ('Dept. ' + String(dept.department || id));
                const selected = id !== '' && id === String(selectedDeptId || '') ? ' selected' : '';
                return '<option value="' + escapeHtml(id) + '"' + selected + '>' + escapeHtml(label) + '</option>';
            }).join('');
    }

    function applyCourtFromLocation(loc, dept) {
        if (!loc) return;
        pendingCourtLocation = loc;
        selectedCourtLocationId = String(loc.id || '');
        selectedCourtDepartmentId = dept ? String(dept.id || '') : '';
        selectedCourtName = String(loc.courtName || '');
        if (loc.courtSystem) {
            selectedCourtSystem = String(loc.courtSystem).toLowerCase() === 'federal' ? 'federal' : 'state';
            if (hiddenCourtSystem) hiddenCourtSystem.value = selectedCourtSystem;
            document.querySelectorAll('input[name="courtSystemPicker"]').forEach((el) => {
                el.checked = String(el.value || '') === selectedCourtSystem;
            });
            updateCourtSearchPlaceholder();
            updateCourtHeaderTip();
            updateCourtDeptSourceTip();
        }
        const branchVal = formatBranch(dept) || selectedCourtName;
        const isFederal = String(selectedCourtSystem || '') === 'federal';
        const stateCode = String(loc.stateCode || loc.state || '').trim();
        const cityVal = String(loc.city || '').trim();
        const zipVal = String(loc.zip || '').trim();
        setCourtFieldByLinkId('court_county', isFederal ? '' : (loc.county || ''));
        setCourtFieldByLinkId('court_street', loc.street || '');
        setCourtFieldByLinkId('court_mailing_address', loc.mailingAddress || loc.street || '');
        setCourtFieldByLinkId('court_city_zip', formatCityZip(loc));
        setCourtFieldByLinkId('court_branch', branchVal);
        setCourtFieldByLinkId('court_name', selectedCourtName);
        setCourtFieldByLinkId('court_city', cityVal);
        setCourtFieldByLinkId('court_state', stateCode);
        setCourtFieldByLinkId('court_zip', zipVal);
        setCourtFieldByLinkId('court_phone', loc.phone || '');
        setCourtFieldByLinkId('court_department', dept ? (dept.department || '') : '');
        setCourtFieldByLinkId('court_room', dept ? (dept.room || '') : '');
        setCourtFieldByLinkId('court_floor', dept ? (dept.floor || '') : '');
        renderCourtDepartmentPicker(loc, selectedCourtDepartmentId);
        updateCourtSummaryText();
        updateNextButtonState();
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    }

    function updateCourtSummaryText() {
        const summary = document.getElementById('courtSummaryText');
        if (!summary) return;
        const name = String(selectedCourtName || '').trim();
        if (name) {
            summary.textContent = name;
            return;
        }
        const countyEl = document.querySelector('.js-court-field[data-court-link-id="court_county"]');
        const streetEl = document.querySelector('.js-court-field[data-court-link-id="court_street"]');
        const county = countyEl ? String(countyEl.value || '').trim() : '';
        const street = streetEl ? String(streetEl.value || '').trim() : '';
        const parts = [street, county].filter(Boolean);
        summary.textContent = parts.length ? parts.join(', ') : 'No court selected.';
    }

    function renderCourtSearchRows(results) {
        const host = document.getElementById('courtSearchList');
        if (!host) return;
        const rows = Array.isArray(results) ? results : [];
        if (!rows.length) {
            host.innerHTML = '<div class="wpts-form-help">No matching courts found.</div>';
            return;
        }
        const isFederal = String(selectedCourtSystem || 'state') === 'federal';
        const colLabel = isFederal ? 'State' : 'County';
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Court</th><th>' + escapeHtml(colLabel) + '</th><th>Address</th></tr></thead><tbody>' +
            rows.map((loc, idx) => {
                const name = String(loc.courtName || 'Court');
                const county = isFederal
                    ? String(loc.stateCode || loc.state || '').trim()
                    : String(loc.county || '');
                const addr = [loc.street, loc.city, loc.zip].filter(Boolean).join(', ');
                return '<tr class="js-select-court wpts-selectable-row" data-court-idx="' + String(idx) + '" title="Use this court">' +
                    '<td>' + escapeHtml(name) + '</td>' +
                    '<td>' + escapeHtml(county) + '</td>' +
                    '<td>' + escapeHtml(addr) + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
        host._courtResults = rows;
    }

    function searchCourtsRemote() {
        const q = String((document.getElementById('courtSearchInput') || {}).value || '').trim();
        const host = document.getElementById('courtSearchList');
        if (!q) {
            if (host) host.innerHTML = '<div class="wpts-form-help">Enter a search term (city, county, zip, court name, or department).</div>';
            return;
        }
        if (host) host.innerHTML = '<div class="wpts-form-help">Searching…</div>';
        const system = String(selectedCourtSystem || 'state');
        fetch('?route=api/courts/search&q=' + encodeURIComponent(q) + '&limit=25&system=' + encodeURIComponent(system), {
            headers: { 'Accept': 'application/json' },
        })
            .then((res) => res.json())
            .then((data) => {
                renderCourtSearchRows(data && data.results ? data.results : []);
            })
            .catch(() => {
                if (host) host.innerHTML = '<div class="wpts-form-help">Court search failed. Try again.</div>';
            });
    }

    function setFormSetPickerVisible(show) {
        const wrap = document.getElementById('formSetPickerWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';
    }

    function renderClientSearchRows() {
        const q = String((document.getElementById('clientSearchInput') || {}).value || '').trim().toLowerCase();
        const host = document.getElementById('clientSearchList');
        if (!host) return;
        const rows = clients.filter((row) => {
            if (!q) return true;
            return [row.displayName, row.email, row.company].join(' ').toLowerCase().indexOf(q) >= 0;
        });
        if (!rows.length) {
            host.innerHTML = '<div class="wpts-form-help">No clients found.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Client</th><th>Email</th></tr></thead><tbody>' +
            rows.map((row) => {
                return '<tr class="js-select-client wpts-selectable-row" data-client-id="' + escapeHtml(String(row.id || '')) + '" title="Select this client">' +
                    '<td>' + escapeHtml(String(row.displayName || row.company || row.id || 'Client')) + '</td>' +
                    '<td>' + escapeHtml(String(row.email || '')) + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function renderFormSetRows(forceBrowse) {
        const q = String((document.getElementById('formSetSearchInput') || {}).value || '').trim().toLowerCase();
        const host = document.getElementById('formSetSearchList');
        if (!host) return;
        if (!forceBrowse && !q) {
            host.innerHTML = '<div class="wpts-form-help">Type to search form sets, or click Browse.</div>';
            return;
        }
        const rows = formSets.filter((row) => {
            if (forceBrowse) return true;
            return [row.name, row.id].join(' ').toLowerCase().indexOf(q) >= 0;
        });
        if (!rows.length) {
            host.innerHTML = '<div class="wpts-form-help">No form sets found.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Form Set</th><th>Forms</th></tr></thead><tbody>' +
            rows.map((row) => {
                const count = Array.isArray(row.templateIds) ? row.templateIds.length : 0;
                return '<tr class="js-select-form-set wpts-selectable-row" data-id="' + escapeHtml(String(row.id || '')) + '" title="Select this form set">' +
                    '<td>' + escapeHtml(String(row.name || row.id || 'Form Set')) + '</td>' +
                    '<td>' + String(count) + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function setAttorneyEditorVisible(show) {
        const wrap = document.getElementById('attorneyEditorWrap');
        if (wrap) wrap.style.display = show ? '' : 'none';
    }

    function updateAttorneySummaryText() {
        const summary = document.getElementById('attorneySummaryText');
        if (!summary) return;
        const name = String(selectedAttorneyName || '').trim();
        if (name) {
            summary.textContent = name;
            return;
        }
        const nameEl = document.querySelector('.js-attorney-field[data-attorney-link-id="attorney_name"]');
        const derived = nameEl ? String(nameEl.value || '').trim() : '';
        summary.textContent = derived !== '' ? derived : 'No attorney selected.';
    }

    function applyAttorneySelection(row) {
        if (!row) return;
        selectedAttorneyId = String(row.id || '');
        selectedAttorneyName = String(row.displayName || '');
        const fieldValues = (row && typeof row.fieldValues === 'object' && row.fieldValues) ? row.fieldValues : {};
        document.querySelectorAll('.js-attorney-field').forEach((el) => {
            const fid = String(el.getAttribute('data-attorney-id') || '').trim();
            if (!fid) return;
            el.value = String(fieldValues[fid] || '');
        });
        if (!selectedAttorneyName) {
            const nameEl = document.querySelector('.js-attorney-field[data-attorney-link-id="attorney_name"]');
            selectedAttorneyName = nameEl ? String(nameEl.value || '').trim() : '';
        }
        updateAttorneySummaryText();
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    }

    function renderAttorneyRows(forceBrowse) {
        const host = document.getElementById('attorneySearchList');
        if (!host) return;
        const q = String((document.getElementById('attorneySearchInput') || {}).value || '').trim().toLowerCase();
        if (!forceBrowse && !q) {
            host.innerHTML = '<div class="wpts-form-help">Type to search firm attorneys, or click Browse.</div>';
            return;
        }
        const rows = (attorneyRoster || []).filter((row) => {
            if (forceBrowse) return true;
            const hay = [row.displayName, row.id]
                .concat(Array.isArray(row.fields) ? row.fields.map((f) => f.value) : [])
                .join(' ')
                .toLowerCase();
            return hay.indexOf(q) >= 0;
        });
        if (!rows.length) {
            host.innerHTML = '<div class="wpts-form-help">No matching attorneys found. Add attorneys on Firm Information.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Attorney</th><th>Bar #</th><th>Firm</th></tr></thead><tbody>' +
            rows.map((row, idx) => {
                const fields = Array.isArray(row.fields) ? row.fields : [];
                const bar = fields.find((f) => String(f.linkId || '') === 'attorney_bar_number');
                const firm = fields.find((f) => String(f.linkId || '') === 'attorney_firm');
                return '<tr class="js-select-attorney wpts-selectable-row" data-attorney-idx="' + String(idx) + '" title="Use this attorney">' +
                    '<td>' + escapeHtml(String(row.displayName || 'Attorney')) + '</td>' +
                    '<td>' + escapeHtml(String(bar && bar.value ? bar.value : '')) + '</td>' +
                    '<td>' + escapeHtml(String(firm && firm.value ? firm.value : '')) + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
        host._attorneyResults = rows;
    }

    function loadAttorneyRoster() {
        return fetch('?route=api/attorneys/list&_ts=' + Date.now(), { cache: 'no-store', headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((data) => {
                attorneyRoster = (data && data.success && Array.isArray(data.attorneys)) ? data.attorneys : [];
            })
            .catch(() => {
                attorneyRoster = [];
            });
    }

    function applyCaseSelection(row) {
        const caseNumberInput = document.getElementById('caseNumberInput');
        if (caseNumberInput) {
            caseNumberInput.value = String(row.caseNumber || '');
        }
        const selectedValues = (row && typeof row.caseValues === 'object' && row.caseValues) ? row.caseValues : {};
        document.querySelectorAll('.js-case-field').forEach((el) => {
            const fid = String(el.getAttribute('data-case-id') || '').trim();
            if (!fid) return;
            el.value = String(selectedValues[fid] || '');
        });
        if (typeof updateCaseSummaryText === 'function') updateCaseSummaryText();
        updateNextButtonState();
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    }

    function renderCaseRows(forceBrowse) {
        const host = document.getElementById('caseSearchList');
        if (!host) return;
        const q = String((document.getElementById('caseSearchInput') || {}).value || '').trim().toLowerCase();
        if (!forceBrowse && !q) {
            host.innerHTML = '<div class="wpts-form-help">Type to search reusable cases, or click Browse.</div>';
            return;
        }
        const matches = [];
        (caseLibrary || []).forEach((row, i) => {
            if (forceBrowse || [row.caseNumber, row.projectName].join(' ').toLowerCase().indexOf(q) >= 0) {
                matches.push({ row: row, idx: i });
            }
        });
        if (!matches.length) {
            host.innerHTML = '<div class="wpts-form-help">No matching saved cases found.</div>';
            return;
        }
        host.innerHTML = '<table class="pdftimesaver-table"><thead><tr><th>Case Number</th><th>Project</th></tr></thead><tbody>' +
            matches.map((m) => {
                return '<tr class="js-select-case wpts-selectable-row" data-case-idx="' + String(m.idx) + '" title="Use this case">' +
                    '<td>' + escapeHtml(String(m.row.caseNumber || '')) + '</td>' +
                    '<td>' + escapeHtml(String(m.row.projectName || m.row.projectId || 'Project')) + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function syncHiddenInputs() {
        const caseValues = {};
        document.querySelectorAll('.js-case-field').forEach((el) => {
            const fid = String(el.getAttribute('data-case-id') || '').trim();
            if (!fid) return;
            caseValues[fid] = String(el.value || '');
        });
        hiddenCaseValues.value = JSON.stringify(caseValues);

        const courtValues = {};
        document.querySelectorAll('.js-court-field').forEach((el) => {
            const fid = String(el.getAttribute('data-court-id') || '').trim();
            if (!fid) return;
            courtValues[fid] = String(el.value || '');
        });
        if (hiddenCourtValues) hiddenCourtValues.value = JSON.stringify(courtValues);
        if (hiddenCourtLocationId) hiddenCourtLocationId.value = selectedCourtLocationId || '';
        if (hiddenCourtDepartmentId) hiddenCourtDepartmentId.value = selectedCourtDepartmentId || '';
        if (hiddenCourtName) hiddenCourtName.value = selectedCourtName || '';
        if (hiddenCourtSystem) hiddenCourtSystem.value = selectedCourtSystem || 'state';

        const attorneyValues = {};
        document.querySelectorAll('.js-attorney-field').forEach((el) => {
            const fid = String(el.getAttribute('data-attorney-id') || '').trim();
            if (!fid) return;
            attorneyValues[fid] = String(el.value || '');
        });
        if (hiddenAttorneyValues) hiddenAttorneyValues.value = JSON.stringify(attorneyValues);
        if (hiddenAttorneyId) hiddenAttorneyId.value = selectedAttorneyId || '';
        if (hiddenAttorneyName) hiddenAttorneyName.value = selectedAttorneyName || '';

        const normalizedRows = (projectFieldRows || []).map((row) => ({
            id: String(row.id || '').trim(),
            label: String(row.label || '').trim(),
        })).filter((row) => row.id && row.label);
        hiddenProjectFieldRows.value = JSON.stringify(normalizedRows);
        hiddenProjectFieldValues.value = JSON.stringify(projectFieldValues || {});
        hiddenAdditionalTemplateIds.value = JSON.stringify(additionalTemplateIds || []);
        hiddenTemplateOrder.value = JSON.stringify(templateOrder || []);

        const selectedInput = document.querySelector('input[name="selectedFormSetId"]');
        if (selectedInput) {
            selectedInput.value = selectedFormSetId || '';
        }
        if (nextTemplateIdInput) {
            nextTemplateIdInput.value = (templateOrder && templateOrder.length) ? String(templateOrder[0] || '') : '';
        }
        const nextOrderInput = document.getElementById('nextTemplateOrderInput');
        if (nextOrderInput) {
            nextOrderInput.value = JSON.stringify(templateOrder || []);
        }
        const nextSetInput = document.getElementById('nextSelectedFormSetInput');
        if (nextSetInput) {
            nextSetInput.value = selectedFormSetId || '';
        }
    }

    function updateNextButtonState() {
        if (!nextFillOutBtn) return;
        const caseNumberInput = document.getElementById('caseNumberInput');
        const selectedSet = (formSets || []).find((row) => String(row.id || '') === String(selectedFormSetId || ''));
        const selectedSetTemplateIds = Array.isArray(selectedSet && selectedSet.templateIds)
            ? selectedSet.templateIds.map((v) => String(v || '').trim()).filter(Boolean)
            : [];
        const hasCaseNumber = !!String((caseNumberInput && caseNumberInput.value) || '').trim();
        const hasFormSet = !!String(selectedFormSetId || '').trim() && selectedSetTemplateIds.length > 0;
        const hasTemplate = !!(templateOrder && templateOrder.length);
        const ready = !!(hasClientSelected && hasCaseNumber && hasFormSet && hasTemplate);
        nextFillOutBtn.classList.toggle('pdftimesaver-btn-secondary', !ready);
        if (ready) {
            nextFillOutBtn.removeAttribute('aria-disabled');
            nextFillOutBtn.removeAttribute('onclick');
            nextFillOutBtn.disabled = false;
            if (nextRequirementsHint) nextRequirementsHint.style.display = 'none';
        } else {
            nextFillOutBtn.setAttribute('aria-disabled', 'true');
            nextFillOutBtn.setAttribute('onclick', 'return false;');
            nextFillOutBtn.disabled = false;
            if (nextRequirementsHint) nextRequirementsHint.style.display = '';
        }
    }

    function validateBeforeSave() {
        // "Save Project Setup" always saves whatever progress exists (name, client,
        // case, form set). The server persists partial progress and returns a notice
        // listing anything still required before "Next". Navigation is gated separately
        // by updateNextButtonState(), so we never block saving here.
        return true;
    }

    const selectedFormSetInput = document.createElement('input');
    selectedFormSetInput.type = 'hidden';
    selectedFormSetInput.name = 'selectedFormSetId';
    selectedFormSetInput.value = selectedFormSetId || '';
    form.appendChild(selectedFormSetInput);

    document.getElementById('toggleClientPickerBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('clientPickerWrap');
        const isOpen = wrap && wrap.style.display !== 'none';
        setClientPickerVisible(!isOpen);
        if (!isOpen) renderClientSearchRows();
    });
    document.getElementById('clientSearchBtn')?.addEventListener('click', renderClientSearchRows);
    document.getElementById('clientSearchInput')?.addEventListener('input', renderClientSearchRows);
    document.getElementById('clientSearchList')?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.js-select-client');
        if (!btn) return;
        const clientId = String(btn.getAttribute('data-client-id') || '').trim();
        if (!clientId) return;
        const post = document.createElement('form');
        post.method = 'POST';
        post.action = '?route=actions/assign-client';
        post.innerHTML =
            '<input type="hidden" name="projectId" value="' + escapeHtml(projectId) + '">' +
            '<input type="hidden" name="clientId" value="' + escapeHtml(clientId) + '">';
        document.body.appendChild(post);
        post.submit();
    });

    document.getElementById('toggleCourtEditorBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('courtEditorWrap');
        const isOpen = wrap && wrap.style.display !== 'none';
        setCourtEditorVisible(!isOpen);
        if (!isOpen) {
            updateCourtSearchPlaceholder();
        }
    });
    document.querySelectorAll('input[name="courtSystemPicker"]').forEach((el) => {
        el.addEventListener('change', function () {
            if (!this.checked) return;
            setSelectedCourtSystem(String(this.value || 'state'));
        });
    });
    document.getElementById('courtSearchBtn')?.addEventListener('click', searchCourtsRemote);
    document.getElementById('courtSearchInput')?.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            searchCourtsRemote();
        }
    });
    document.getElementById('courtSearchList')?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.js-select-court');
        if (!btn) return;
        const host = document.getElementById('courtSearchList');
        const idx = parseInt(String(btn.getAttribute('data-court-idx') || '-1'), 10);
        const rows = host && Array.isArray(host._courtResults) ? host._courtResults : [];
        if (idx < 0 || idx >= rows.length) return;
        applyCourtFromLocation(rows[idx], null);
    });
    document.getElementById('courtDepartmentSelect')?.addEventListener('change', function () {
        if (!pendingCourtLocation) return;
        const deptId = String(this.value || '').trim();
        const depts = Array.isArray(pendingCourtLocation.departments) ? pendingCourtLocation.departments : [];
        const dept = depts.find((d) => String(d.id || '') === deptId) || null;
        applyCourtFromLocation(pendingCourtLocation, dept);
    });
    document.querySelectorAll('.js-court-field').forEach((el) => {
        el.addEventListener('input', function () {
            updateCourtSummaryText();
            if (typeof scheduleAutosave === 'function') scheduleAutosave();
        });
    });

    document.getElementById('toggleAttorneyEditorBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('attorneyEditorWrap');
        const isOpen = wrap && wrap.style.display !== 'none';
        setAttorneyEditorVisible(!isOpen);
        if (!isOpen) {
            void loadAttorneyRoster().then(function () { renderAttorneyRows(false); });
        }
    });
    document.getElementById('attorneySearchBtn')?.addEventListener('click', function () { renderAttorneyRows(false); });
    document.getElementById('attorneyBrowseBtn')?.addEventListener('click', function () {
        const input = document.getElementById('attorneySearchInput');
        if (input) input.value = '';
        void loadAttorneyRoster().then(function () { renderAttorneyRows(true); });
    });
    document.getElementById('attorneySearchInput')?.addEventListener('input', function () { renderAttorneyRows(false); });
    document.getElementById('attorneySearchList')?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.js-select-attorney');
        if (!btn) return;
        const host = document.getElementById('attorneySearchList');
        const idx = parseInt(String(btn.getAttribute('data-attorney-idx') || '-1'), 10);
        const rows = host && Array.isArray(host._attorneyResults) ? host._attorneyResults : [];
        if (idx < 0 || idx >= rows.length) return;
        applyAttorneySelection(rows[idx]);
    });
    document.querySelectorAll('.js-attorney-field').forEach((el) => {
        el.addEventListener('input', function () {
            updateAttorneySummaryText();
            if (typeof scheduleAutosave === 'function') scheduleAutosave();
        });
    });

    document.getElementById('toggleCaseEditorBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('caseEditorWrap');
        const isOpen = wrap && wrap.style.display !== 'none';
        setCaseEditorVisible(!isOpen);
        if (!isOpen) {
            renderCaseRows(false);
        }
    });
    document.getElementById('caseSearchBtn')?.addEventListener('click', function () { renderCaseRows(false); });
    document.getElementById('caseBrowseBtn')?.addEventListener('click', function () {
        const input = document.getElementById('caseSearchInput');
        if (input) input.value = '';
        renderCaseRows(true);
    });
    document.getElementById('caseSearchInput')?.addEventListener('input', function () { renderCaseRows(false); });
    document.getElementById('caseSearchList')?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.js-select-case');
        if (!btn) return;
        const idx = parseInt(String(btn.getAttribute('data-case-idx') || '-1'), 10);
        if (idx < 0 || idx >= caseLibrary.length) return;
        applyCaseSelection(caseLibrary[idx]);
    });

    document.getElementById('toggleFormSetPickerBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('formSetPickerWrap');
        const isOpen = wrap && wrap.style.display !== 'none';
        setFormSetPickerVisible(!isOpen);
        if (!isOpen) renderFormSetRows(false);
    });
    document.getElementById('formSetSearchBtn')?.addEventListener('click', function () {
        renderFormSetRows(false);
    });
    document.getElementById('formSetBrowseBtn')?.addEventListener('click', function () {
        const input = document.getElementById('formSetSearchInput');
        if (input) input.value = '';
        renderFormSetRows(true);
    });
    document.getElementById('formSetSearchInput')?.addEventListener('input', function () {
        renderFormSetRows(false);
    });
    document.getElementById('formSetSearchList')?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.js-select-form-set');
        if (!btn) return;
        const id = String(btn.getAttribute('data-id') || '').trim();
        if (!id) return;
        selectedFormSetId = id;
        const setRow = formSets.find((row) => String(row.id || '') === id);
        const nextBase = Array.isArray(setRow && setRow.templateIds) ? setRow.templateIds.slice() : [];
        // Strict behavior: selecting a form set rebases the project list to that set.
        additionalTemplateIds = [];
        templateOrder = nextBase;
        normalizeTemplateOrder();
        selectedFormSetInput.value = selectedFormSetId;
        if (selectedFormSetLabel) {
            const setName = String((setRow && (setRow.name || setRow.id)) || id);
            selectedFormSetLabel.textContent = setName || 'Selected';
        }
        renderProjectForms();
        updateNextButtonState();
        setFormSetPickerVisible(false);
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    });

    document.getElementById('projectFormsWrap')?.addEventListener('click', function (ev) {
        const moveBtn = ev.target.closest('.js-form-move');
        if (moveBtn) {
            const tid = String(moveBtn.getAttribute('data-tid') || '').trim();
            const dir = String(moveBtn.getAttribute('data-dir') || '').trim();
            const idx = templateOrder.findIndex((v) => String(v) === tid);
            if (idx < 0) return;
            const swap = dir === 'up' ? idx - 1 : idx + 1;
            if (swap < 0 || swap >= templateOrder.length) return;
            const tmp = templateOrder[idx];
            templateOrder[idx] = templateOrder[swap];
            templateOrder[swap] = tmp;
            renderProjectForms();
            if (typeof scheduleAutosave === 'function') scheduleAutosave();
            return;
        }
        const removeBtn = ev.target.closest('.js-form-remove');
        if (!removeBtn) return;
        const tid = String(removeBtn.getAttribute('data-tid') || '').trim();
        additionalTemplateIds = (additionalTemplateIds || []).filter((v) => String(v) !== tid);
        templateOrder = (templateOrder || []).filter((v) => String(v) !== tid);
        renderProjectForms();
        renderAdditionalFormRows(false);
        updateNextButtonState();
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    });

    document.getElementById('addFormSearchBtn')?.addEventListener('click', function () {
        renderAdditionalFormRows(false);
    });
    document.getElementById('addFormBrowseBtn')?.addEventListener('click', function () {
        const input = document.getElementById('addFormSearchInput');
        if (input) input.value = '';
        renderAdditionalFormRows(true);
    });
    document.getElementById('addFormSearchInput')?.addEventListener('input', function () {
        renderAdditionalFormRows(false);
    });
    document.getElementById('addFormResultsWrap')?.addEventListener('click', function (ev) {
        const addBtn = ev.target.closest('.js-add-additional-form');
        if (!addBtn) return;
        const tid = String(addBtn.getAttribute('data-tid') || '').trim();
        if (!tid || (templateOrder || []).includes(tid)) return;
        if (!(additionalTemplateIds || []).includes(tid)) {
            additionalTemplateIds.push(tid);
        }
        templateOrder.push(tid);
        normalizeTemplateOrder();
        renderProjectForms();
        renderAdditionalFormRows(false);
        updateNextButtonState();
        if (typeof scheduleAutosave === 'function') scheduleAutosave();
    });

    // --- Autosave-as-you-go: persist setup changes without a Save button ---
    let autosaveTimer = null;
    function setAutosaveStatus(text) {
        const el = document.getElementById('autosaveStatus');
        if (el) el.textContent = text;
    }
    function doAutosave() {
        if (typeof syncHiddenInputs === 'function') syncHiddenInputs();
        const fd = new FormData(form);
        setAutosaveStatus('Saving\u2026');
        return fetch(form.getAttribute('action'), {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function () {
            setAutosaveStatus('All changes saved.');
        }).catch(function () {
            setAutosaveStatus('Save failed \u2014 will retry on next change.');
        });
    }
    function scheduleAutosave() {
        setAutosaveStatus('Saving\u2026');
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(doAutosave, 700);
    }
    // Typing in name / case number / case fields (ignore the in-form search boxes).
    form.addEventListener('input', function (ev) {
        const t = ev.target;
        if (!t) return;
        if (t.id === 'projectNameInput' || t.id === 'caseNumberInput' || (t.classList && (t.classList.contains('js-case-field') || t.classList.contains('js-attorney-field')))) {
            scheduleAutosave();
        }
    });

    // Delete project (trash can) — uses the JSON delete endpoint then follows redirect.
    document.getElementById('deleteProjectBtn')?.addEventListener('click', function () {
        if (!window.confirm('Delete this project? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('projectId', projectId);
        fetch('?route=actions/delete-project', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
            window.location.href = (data && data.redirect) ? data.redirect : '?route=projects';
        }).catch(function () {
            window.location.href = '?route=projects';
        });
    });

    // Back to Projects: flush any pending save, then navigate.
    document.getElementById('backToProjectsBtn')?.addEventListener('click', function () {
        clearTimeout(autosaveTimer);
        Promise.resolve(doAutosave()).then(function () {
            window.location.href = '?route=projects';
        });
    });

    form.addEventListener('submit', function (ev) {
        // No explicit Save button anymore — Enter just flushes an autosave.
        ev.preventDefault();
        clearTimeout(autosaveTimer);
        doAutosave();
    });

    function updateCaseSummaryText() {
        const summary = document.getElementById('caseSummaryText');
        const caseInput = document.getElementById('caseNumberInput');
        if (!summary || !caseInput) return;
        const val = String(caseInput.value || '').trim();
        summary.textContent = val !== '' ? val : 'No case selected.';
    }
    document.getElementById('caseNumberInput')?.addEventListener('input', function () {
        updateNextButtonState();
        updateCaseSummaryText();
    });

    renderProjectForms();
    renderAdditionalFormRows(false);
    syncHiddenInputs();
    updateNextButtonState();
    updateCourtSummaryText();
    updateCourtSearchPlaceholder();
    document.querySelectorAll('.wpts-info-tip').forEach(function (tipEl) {
        applyTipText(tipEl, tipEl.getAttribute('data-tip') || '');
    });
    updateCourtHeaderTip();
    updateCourtDeptSourceTip();
})();
</script>

