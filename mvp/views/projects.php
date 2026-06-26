<style>
    .matter-cell-identity { position: relative; z-index: 2; }
    .projects-toolbar {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
        margin-top: 12px;
    }
    .projects-search-form {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: nowrap;
        width: auto;
        min-width: 0;
        flex: 1 1 auto;
    }
    .projects-search-input {
        width: 280px;
        max-width: 100%;
        flex: 1 1 320px;
        min-width: 220px;
    }
    .projects-search-cluster {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1 1 320px;
        min-width: 220px;
    }
    .projects-search-cluster .projects-search-input {
        flex: 1 1 auto;
        min-width: 0;
    }
    .projects-status-filter {
        width: 170px;
        flex: 0 0 170px;
        white-space: nowrap;
    }
    .projects-clear-btn {
        flex: 0 0 auto;
        margin-left: 0;
    }
    .projects-toolbar .pdftimesaver-btn-secondary,
    .pdftimesaver-table .pdftimesaver-btn-secondary {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .projects-toolbar .pdftimesaver-btn-secondary,
    .projects-toolbar .pdftimesaver-btn-secondary:hover,
    .pdftimesaver-table .pdftimesaver-btn-secondary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }
    .projects-pagination {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid #e5e7eb;
    }
    .projects-pagination-meta {
        color: #64748b;
        font-size: 13px;
    }
    .projects-pagination-bar {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .projects-pagination-arrow,
    .projects-pagination-page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        line-height: 1;
    }
    .projects-pagination-arrow:hover,
    .projects-pagination-page:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }
    .projects-pagination-page.is-current {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        cursor: default;
        pointer-events: none;
    }
    .projects-pagination-arrow.is-disabled,
    .projects-pagination-ellipsis {
        color: #94a3b8;
        border-color: #e2e8f0;
        background: #f8fafc;
        cursor: default;
        pointer-events: none;
    }
    .projects-toolbar .pdftimesaver-btn-secondary.is-disabled {
        background: #94a3b8;
        border-color: #94a3b8;
        color: #e2e8f0;
        cursor: default;
        pointer-events: none;
        opacity: 0.75;
    }
</style>
<?php
$filters = is_array($filters ?? null) ? $filters : [];
$filterQ = (string)($filters['q'] ?? '');
$rawStatus = (string)($filters['status'] ?? 'active');
$statusFilter = strtolower(trim($rawStatus));
if (!in_array($statusFilter, ['active', 'completed', 'all'], true)) {
    $statusFilter = 'active';
}
$projects = is_array($projects ?? null) ? $projects : [];
$totalProjectsFiltered = max(0, (int)($totalProjectsFiltered ?? count($projects)));
$pagination = is_array($pagination ?? null) ? $pagination : [];
$page = max(1, (int)($pagination['page'] ?? 1));
$perPage = max(1, (int)($pagination['perPage'] ?? 20));
$totalPages = max(1, (int)($pagination['totalPages'] ?? 1));
$sort = (string)($filters['sort'] ?? 'updated_desc');

$projectsPageUrl = static function (int $targetPage) use ($statusFilter, $filterQ, $sort): string {
    $params = [
        'route' => 'projects',
        'page' => max(1, $targetPage),
    ];
    if ($statusFilter !== 'active') {
        $params['status'] = $statusFilter;
    }
    if ($filterQ !== '') {
        $params['q'] = $filterQ;
    }
    if ($sort !== 'updated_desc') {
        $params['sort'] = $sort;
    }
    return '?' . http_build_query($params);
};

$projectsPaginationItems = static function (int $currentPage, int $pageCount): array {
    if ($pageCount <= 1) {
        return [1];
    }
    if ($pageCount <= 9) {
        return range(1, $pageCount);
    }

    $items = [1];
    $start = max(2, $currentPage - 1);
    $end = min($pageCount - 1, $currentPage + 1);

    if ($start > 2) {
        $items[] = 'ellipsis-left';
    }
    for ($i = $start; $i <= $end; $i++) {
        $items[] = $i;
    }
    if ($end < $pageCount - 1) {
        $items[] = 'ellipsis-right';
    }
    $items[] = $pageCount;

    return $items;
};

$rangeStart = $totalProjectsFiltered > 0 ? (($page - 1) * $perPage) + 1 : 0;
$rangeEnd = min($page * $perPage, $totalProjectsFiltered);
$paginationItems = $projectsPaginationItems($page, $totalPages);
$isClearApplicable = ($filterQ !== '' || $statusFilter !== 'active');
?>
<div class="pdftimesaver-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">All Projects</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;"><?php echo $totalProjectsFiltered; ?> projects</p>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px;">Use the Active/Completed filter to switch project state views.</p>
        </div>
        <div class="button-group" style="display: flex; gap: 12px;">
            <form method="post" action="?route=actions/create-project" style="margin:0;">
                <input type="hidden" name="name" value="">
                <button type="submit" class="pdftimesaver-btn">
                    <span>New Project</span>
                </button>
            </form>
        </div>
    </div>

    <div class="projects-toolbar">
        <form method="get" class="projects-search-form">
            <input type="hidden" name="route" value="projects">
            <div class="projects-search-cluster">
                <input
                    type="text"
                    name="q"
                    value="<?php echo htmlspecialchars($filterQ); ?>"
                    class="pdftimesaver-input projects-search-input"
                    placeholder="Search for project"
                >
                <button type="submit" class="pdftimesaver-btn-secondary pdftimesaver-search-btn" aria-label="Search" title="Search">&#128269;</button>
            </div>
            <select id="projects-status-filter" name="status" class="pdftimesaver-input projects-status-filter" aria-label="Project status filter">
                <option value="active"<?php echo $statusFilter === 'active' ? ' selected' : ''; ?>>Active</option>
                <option value="completed"<?php echo $statusFilter === 'completed' ? ' selected' : ''; ?>>Completed</option>
                <option value="all"<?php echo $statusFilter === 'all' ? ' selected' : ''; ?>>All</option>
            </select>
            <a href="?route=projects"
               class="pdftimesaver-btn-secondary projects-clear-btn<?php echo $isClearApplicable ? '' : ' is-disabled'; ?>"
               <?php echo $isClearApplicable ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>
                Clear
            </a>
        </form>
    </div>
    <script>
    (function () {
        var statusSel = document.getElementById('projects-status-filter');
        if (!statusSel || !statusSel.form) { return; }
        statusSel.addEventListener('change', function () {
            statusSel.form.requestSubmit ? statusSel.form.requestSubmit() : statusSel.form.submit();
        });
    })();
    </script>
</div>

<?php if (!empty($_GET['new'])): ?>
    <div class="pdftimesaver-card wpts-form-shell">
        <h3 class="wpts-form-title">New Project</h3>
        <p class="wpts-form-help">Create a blank project and continue in Project View.</p>
        <form method="post" action="?route=actions/create-project" class="wpts-form-grid">
            <div class="wpts-form-group">
                <label class="pdftimesaver-form-label" for="newProjectName">Project Name</label>
                <input id="newProjectName" type="text" name="name" class="pdftimesaver-input" required placeholder="Enter project name">
            </div>
            <div class="wpts-form-actions">
                <button type="submit" class="pdftimesaver-btn">Create Project</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (empty($projects)): ?>
    <div class="pdftimesaver-card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📁</div>
        <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No projects found</h3>
        <p style="margin: 0; color: #6c757d; font-size: 16px;">Projects are created within client accounts. <a href="?route=clients" style="color: #1976d2; text-decoration: none;">Go to clients</a> to create your first project.</p>
    </div>
<?php else: ?>
    <div class="pdftimesaver-card">
        <div class="table-responsive">
            <table class="pdftimesaver-table">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <?php
                    // Get client info for this project
                    $client = null;
                    if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
                        $client = $store->getClient($project['clientId']);
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="matter-cell-identity" style="font-weight: 600; color: #2c3e50;">
                                <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>" style="color: #1976d2; text-decoration: none;">
                                    <?php echo htmlspecialchars(trim((string)($project['name'] ?? '')) !== '' ? (string)$project['name'] : 'Untitled project'); ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?php if ($client): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                        <?php echo strtoupper(substr($client['displayName'] ?? 'C', 0, 1)); ?>
                                    </div>
                                    <span style="color: #495057;"><?php echo htmlspecialchars($client['displayName']); ?></span>
                                </div>
                            <?php else: ?>
                                <span style="color: #6c757d;">No client assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="pdftimesaver-status pdftimesaver-status-<?php echo str_replace('_', '-', $project['status'] ?? 'in-progress'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $project['status'] ?? 'in_progress')); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $date = new DateTime($project['updatedAt'] ?? $project['createdAt'] ?? 'now');
                            echo $date->format('M j, Y');
                            ?>
                        </td>
                        <td>
                            <div class="button-group" style="display: flex; gap: 8px;">
                                <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>" class="pdftimesaver-btn-secondary pdftimesaver-btn-sm">View</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="projects-pagination" aria-label="Projects pagination">
                <div class="projects-pagination-meta">
                    Showing <?php echo (int)$rangeStart; ?>–<?php echo (int)$rangeEnd; ?> of <?php echo (int)$totalProjectsFiltered; ?>
                    · Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?>
                </div>
                <div class="projects-pagination-bar">
                    <?php if ($page > 1): ?>
                        <a class="projects-pagination-arrow" href="<?php echo htmlspecialchars($projectsPageUrl($page - 1)); ?>" aria-label="Previous page">&#8592;</a>
                    <?php else: ?>
                        <span class="projects-pagination-arrow is-disabled" aria-hidden="true">&#8592;</span>
                    <?php endif; ?>

                    <?php foreach ($paginationItems as $item): ?>
                        <?php if ($item === 'ellipsis-left' || $item === 'ellipsis-right'): ?>
                            <span class="projects-pagination-ellipsis" aria-hidden="true">…</span>
                        <?php elseif ((int)$item === $page): ?>
                            <span class="projects-pagination-page is-current" aria-current="page"><?php echo (int)$item; ?></span>
                        <?php else: ?>
                            <a class="projects-pagination-page" href="<?php echo htmlspecialchars($projectsPageUrl((int)$item)); ?>"><?php echo (int)$item; ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="projects-pagination-arrow" href="<?php echo htmlspecialchars($projectsPageUrl($page + 1)); ?>" aria-label="Next page">&#8594;</a>
                    <?php else: ?>
                        <span class="projects-pagination-arrow is-disabled" aria-hidden="true">&#8594;</span>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>


