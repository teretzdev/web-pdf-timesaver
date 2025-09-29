<h2>Clients</h2>

<div class="panel">
    <form method="post" action="?route=actions/create-client" class="row" style="gap:8px; align-items:center;">
        <input type="text" name="displayName" placeholder="Client name" required>
        <input type="text" name="email" placeholder="Email">
        <input type="text" name="phone" placeholder="Phone">
        <button class="btn" type="submit">Add client</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Created</th></tr>
    </thead>
    <tbody>
        <?php foreach ($clients as $c): ?>
            <tr>
                <td><a href="?route=client&id=<?php echo htmlspecialchars($c['id']); ?>"><?php echo htmlspecialchars($c['displayName'] ?? ''); ?></a></td>
                <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($c['createdAt'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>




