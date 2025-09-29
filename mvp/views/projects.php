<div class="row" style="justify-content: space-between; margin-bottom:14px;">
    <form method="get" class="row" style="gap:8px; align-items:center;">
        <input type="hidden" name="route" value="projects">
        <input type="text" name="q" placeholder="Search projects" value="<?php echo htmlspecialchars($filters['q'] ?? ''); ?>" style="width:260px;">
        <select name="status">
            <?php $cur = $filters['status'] ?? ''; ?>
            <option value="" <?php echo $cur===''?'selected':''; ?>>All status</option>
            <option value="in_progress" <?php echo $cur==='in_progress'?'selected':''; ?>>In progress</option>
            <option value="completed" <?php echo $cur==='completed'?'selected':''; ?>>Completed</option>
            <option value="archived" <?php echo $cur==='archived'?'selected':''; ?>>Archived</option>
        </select>
        <select name="sort">
            <?php $s = $filters['sort'] ?? 'updated_desc'; ?>
            <option value="updated_desc" <?php echo $s==='updated_desc'?'selected':''; ?>>Last updated</option>
            <option value="updated_asc" <?php echo $s==='updated_asc'?'selected':''; ?>>Oldest</option>
            <option value="name_asc" <?php echo $s==='name_asc'?'selected':''; ?>>Name A→Z</option>
            <option value="name_desc" <?php echo $s==='name_desc'?'selected':''; ?>>Name Z→A</option>
        </select>
        <button class="btn secondary" type="submit">Apply</button>
    </form>
    <form method="post" action="?route=actions/create-project" class="row" style="gap:8px;">
        <input type="text" name="name" placeholder="New project name" required>
        <button class="btn" type="submit">Add new project</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr><th>Name</th><th>Status</th><th>Updated</th></tr>
    </thead>
    <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><a href="?route=project&id=<?php echo htmlspecialchars($p['id']); ?>"><?php echo htmlspecialchars($p['name']); ?></a></td>
                <td>
                    <form method="post" action="?route=actions/update-project-status" class="row" style="gap:6px;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                        <select name="status" onchange="this.form.submit()">
                            <?php $st = $p['status'] ?? 'in_progress'; ?>
                            <option value="in_progress" <?php echo $st==='in_progress'?'selected':''; ?>>In progress</option>
                            <option value="completed" <?php echo $st==='completed'?'selected':''; ?>>Completed</option>
                            <option value="archived" <?php echo $st==='archived'?'selected':''; ?>>Archived</option>
                        </select>
                    </form>
                </td>
                <td><?php echo htmlspecialchars($p['createdAt'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    </table>

