<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clio Draft MVP</title>
    <style>
        body { font-family: Segoe UI, Arial, sans-serif; margin: 0; background: #f6f7fb; }
        .topbar { background:#0b6bcb; color:#fff; padding:14px 20px; font-weight:600; }
        .container { max-width: 1100px; margin: 20px auto; background:#fff; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
        .content { padding: 20px 24px; }
        .btn { background:#0b6bcb; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; }
        .btn.secondary { background:#eef2f7; color:#1a2b3b; }
        .row { display:flex; gap:12px; align-items:center; }
        .table { width:100%; border-collapse: collapse; }
        .table th, .table td { text-align:left; padding:10px; border-bottom:1px solid #eef2f7; }
        input[type=text] { padding:10px; border:1px solid #d7dce3; border-radius:8px; width:100%; }
        select { padding:10px; border:1px solid #d7dce3; border-radius:8px; }
        .panel { border:1px solid #eef2f7; border-radius:10px; padding:14px; margin-bottom:14px; }
        .panel h3 { margin:0 0 10px; }
        .grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; }
        .muted { color:#65748b; font-size:12px; }
        a { color:#0b6bcb; text-decoration:none; }
    </style>
}</head>
<body>
    <div class="topbar">Clio Draft MVP</div>
    <div class="container">
        <div class="content">
            <div class="row" style="gap: 16px; margin-bottom: 16px;">
                <a href="?route=projects" class="btn secondary">Projects</a>
                <a href="?route=clients" class="btn secondary">Clients</a>
                <a href="?route=support" class="btn secondary">Help & support</a>
            </div>

