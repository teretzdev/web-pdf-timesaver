<style>
.diag-page {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    padding: 24px;
}

.diag-header {
    display: flex;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.diag-title {
    margin: 0;
}

.diag-subtitle {
    margin: 6px 0 0 0;
    color: #64748b;
    font-size: 14px;
}

.diag-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #0f172a;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: 600;
}

.diag-btn:hover {
    background: #eef2ff;
    border-color: #a5b4fc;
}

.diag-meta {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 12px;
}

.diag-output {
    margin: 0;
    padding: 14px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #0f172a;
    color: #e2e8f0;
    font-size: 12px;
    line-height: 1.45;
    white-space: pre-wrap;
    word-break: break-word;
    max-height: 70vh;
    overflow: auto;
}
</style>

<div class="diag-page">
    <div class="diag-header">
        <div>
            <h1 class="diag-title">Diagnostics</h1>
            <p class="diag-subtitle">Hidden developer route for Form Manager diagnostics.</p>
        </div>
        <button id="diagRefreshBtn" type="button" class="diag-btn">Refresh</button>
    </div>
    <div id="diagMeta" class="diag-meta">Loading...</div>
    <pre id="diagOutput" class="diag-output">Loading diagnostics...</pre>
</div>

<script>
(function () {
    const out = document.getElementById('diagOutput');
    const meta = document.getElementById('diagMeta');
    const btn = document.getElementById('diagRefreshBtn');

    async function loadDiagnostics() {
        if (!out || !meta) return;
        const startedAt = new Date();
        meta.textContent = 'Loading...';
        out.textContent = 'Loading diagnostics...';
        try {
            const res = await fetch('?route=actions/universal-diagnostics', { cache: 'no-store' });
            const raw = await res.text();
            let parsed;
            try {
                parsed = raw ? JSON.parse(raw) : {};
            } catch (e) {
                throw new Error(`Invalid JSON from diagnostics endpoint (HTTP ${res.status})`);
            }
            const elapsed = Date.now() - startedAt.getTime();
            meta.textContent = `Updated ${new Date().toLocaleString()} (${elapsed} ms)`;
            out.textContent = JSON.stringify(parsed, null, 2);
        } catch (err) {
            meta.textContent = `Failed ${new Date().toLocaleString()}`;
            out.textContent = String(err && err.message ? err.message : err);
        }
    }

    btn && btn.addEventListener('click', loadDiagnostics);
    loadDiagnostics();
})();
</script>
