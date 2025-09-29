<div class="row" style="justify-content: space-between; align-items: center; margin-bottom:10px;">
    <form class="row" method="post" action="?route=actions/update-project-name" style="gap:8px; align-items:center;">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">
        <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" style="width:360px;">
        <button class="btn" type="submit">Save name</button>
    </form>
    <div class="muted">Client: <?php echo htmlspecialchars((function($pid,$clients){ foreach(($clients??[]) as $c){ if(($c['id']??'')===($pid??'')) return $c['displayName']??''; } return 'Unassigned'; })($project['clientId']??'', $clients??[])); ?>
        <?php if (!empty($project['clientId'])): ?> — <a href="?route=client&id=<?php echo htmlspecialchars($project['clientId']); ?>">View client</a><?php endif; ?></div>
    <form method="post" action="?route=actions/duplicate-project">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">
        <button class="btn secondary" type="submit">Duplicate</button>
    </form>
    </div>

<div class="panel">
    <div class="row" style="justify-content: space-between; align-items:center;">
        <h3 style="margin:0;">Documents</h3>
        <details>
            <summary class="btn secondary" style="display:inline-block;">Add/remove documents</summary>
            <div style="margin-top:10px;">
                <form method="post" action="?route=actions/add-document" class="row" style="gap:8px; align-items:center; margin-bottom:10px;">
                    <input type="hidden" name="projectId" value="<?php echo htmlspecialchars($project['id']); ?>">
                    <select name="templateId" required>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?php echo htmlspecialchars($tpl['id']); ?>"><?php echo htmlspecialchars($tpl['code'] . ' — ' . $tpl['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">Add</button>
                </form>
                <div class="muted">Remove an item using the Remove button in the table below.</div>
            </div>
        </details>
    </div>
</div>

<table class="table">
    <thead>
        <tr><th>Template</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php foreach ($documents as $d): ?>
            <?php $tpl = $templates[$d['templateId']] ?? ['code' => $d['templateId'], 'name' => '']; ?>
            <tr>
                <td><?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?></td>
                <td>
                    <form method="post" action="?route=actions/update-doc-status" class="row" style="gap:6px;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id']); ?>">
                        <select name="status" onchange="this.form.submit()">
                            <?php $st = $d['status'] ?? 'in_progress'; ?>
                            <option value="in_progress" <?php echo $st==='in_progress'?'selected':''; ?>>In progress</option>
                            <option value="ready_to_sign" <?php echo $st==='ready_to_sign'?'selected':''; ?>>Ready to sign</option>
                            <option value="signed" <?php echo $st==='signed'?'selected':''; ?>>Signed</option>
                        </select>
                    </form>
                </td>
                <td>
                    <a class="btn secondary" href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>">Populate</a>
                    <a class="btn secondary" href="?route=actions/generate&pd=<?php echo htmlspecialchars($d['id']); ?>">Generate</a>
                    <?php if (!empty($d['outputPath'])): ?>
                        <a class="btn" href="?route=actions/download&pd=<?php echo htmlspecialchars($d['id']); ?>">Download</a>
                        <a class="btn" href="?route=actions/sign&pd=<?php echo htmlspecialchars($d['id']); ?>">Sign</a>
                    <?php endif; ?>
                    <?php if (!empty($d['signedPath'])): ?>
                        <a class="btn" href="<?php echo htmlspecialchars($d['signedPath']); ?>">Signed PDF</a>
                    <?php endif; ?>
                    <form method="post" action="?route=actions/remove-document" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id']); ?>">
                        <button class="btn secondary" type="submit" onclick="return confirm('Remove this document from project?');">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="panel">
    <h3>Signed documents</h3>
    <div>
        <?php $signed = array_values(array_filter($documents, fn($x) => ($x['status'] ?? '') === 'signed')); ?>
        <?php if (count($signed) === 0): ?>
            <div class="muted">No signed documents yet.</div>
        <?php else: ?>
            <ul>
                <?php foreach ($signed as $sd): ?>
                    <li>
                        <?php $tpl = $templates[$sd['templateId']] ?? ['code' => $sd['templateId'], 'name' => '']; ?>
                        <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?>
                        <?php if (!empty($sd['signedPath'])): ?>
                            — <a href="<?php echo htmlspecialchars($sd['signedPath']); ?>">Download signed</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<p class="muted"><a href="?route=projects">Back to projects</a></p>

