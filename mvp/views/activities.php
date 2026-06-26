<div class="pdftimesaver-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">Activities</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Track and manage your activities</p>
        </div>
    </div>
</div>

<?php
$activities = $activities ?? [];
$filter = trim((string)($_GET['filter'] ?? 'all'));
$allowedFilters = ['all', 'documents', 'clients', 'projects', 'downloads'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
$matchesFilter = static function(array $act, string $filter): bool {
    if ($filter === 'all') { return true; }
    $type = (string)($act['type'] ?? '');
    return match ($filter) {
        'documents' => str_contains($type, 'document_') || str_contains($type, 'pdf_'),
        'clients' => str_contains($type, 'client_'),
        'projects' => str_contains($type, 'project_'),
        'downloads' => str_contains($type, 'download'),
        default => true,
    };
};
$timeAgo = static function(string $ts): string {
    $t = strtotime($ts);
    if ($t === false) { return $ts; }
    $d = time() - $t;
    if ($d < 60) return $d . 's ago';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', $t);
};
$buildLinks = static function(array $meta): string {
    $parts = [];
    if (!empty($meta['clientId'])) {
        $parts[] = '<a href="?route=client&id=' . urlencode((string)$meta['clientId']) . '">Client</a>';
    }
    if (!empty($meta['projectId'])) {
        $parts[] = '<a href="?route=project&id=' . urlencode((string)$meta['projectId']) . '">Project</a>';
    }
    if (!empty($meta['projectDocumentId'])) {
        $parts[] = '<a href="?route=populate&pd=' . urlencode((string)$meta['projectDocumentId']) . '">Document</a>';
    }
    return implode(' · ', $parts);
};
$filteredActivities = array_values(array_filter($activities, fn($a) => $matchesFilter((array)$a, $filter)));
?>

<div class="pdftimesaver-card" style="margin-bottom: 12px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach ($allowedFilters as $f): ?>
            <a href="?route=activities&filter=<?php echo urlencode($f); ?>" class="<?php echo $f === $filter ? 'pdftimesaver-btn' : 'pdftimesaver-btn-secondary'; ?> pdftimesaver-btn-sm">
                <?php echo htmlspecialchars(ucfirst($f)); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($filteredActivities)): ?>
<div class="pdftimesaver-card" style="text-align: center; padding: 60px 20px;">
    <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
    <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No activities yet</h3>
    <p style="margin: 0; color: #6c757d; font-size: 16px;">As you update clients, projects, and documents, events will appear here.</p>
</div>
<?php else: ?>
<div class="pdftimesaver-card">
    <div class="table-responsive">
        <table class="pdftimesaver-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($filteredActivities as $act): ?>
                <tr>
                    <td title="<?php echo htmlspecialchars((string)($act['createdAt'] ?? '')); ?>"><?php echo htmlspecialchars($timeAgo((string)($act['createdAt'] ?? ''))); ?></td>
                    <td><span class="pdftimesaver-status pdftimesaver-status-in-progress"><?php echo htmlspecialchars((string)($act['type'] ?? 'event')); ?></span></td>
                    <td><?php echo htmlspecialchars((string)($act['message'] ?? '')); ?></td>
                    <td>
                        <?php
                            $meta = is_array($act['meta'] ?? null) ? $act['meta'] : [];
                            $links = $buildLinks($meta);
                            if ($links !== '') {
                                echo $links;
                            } else {
                                echo '<code>' . htmlspecialchars(json_encode($meta, JSON_UNESCAPED_SLASHES)) . '</code>';
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


