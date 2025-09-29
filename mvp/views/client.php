<h2><?php echo htmlspecialchars($client['displayName'] ?? 'Client'); ?></h2>

<div class="row" style="gap:12px; margin-bottom:12px;">
    <a class="btn secondary" href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>">Projects</a>
    <a class="btn secondary" href="#">Client vault</a>
    <a class="btn secondary" href="#">Profile</a>
    <a class="btn secondary" href="#">Notes</a>
    <a class="btn secondary" href="?route=clients">Back to Clients</a>
  </div>

<div class="panel">
    <form method="post" action="?route=actions/create-project" class="row" style="gap:8px; align-items:center;">
        <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
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
                <td><?php echo htmlspecialchars($p['status'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['updatedAt'] ?? $p['createdAt'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


