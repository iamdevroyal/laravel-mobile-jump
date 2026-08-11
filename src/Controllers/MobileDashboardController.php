<?php

namespace Iamdevroyal\MobileJump\Controllers;

use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MobileDashboardController extends Controller
{
    public function __construct(private readonly MobileSessionService $service) {}

    public function index(Request $request): \Illuminate\Http\Response
    {
        $prefix  = config('mobile-jump.route_prefix', 'mobile-jump');
        $apiBase = url("/{$prefix}/api");

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mobile Jump — Dev Dashboard</title>
  <style>
    :root {
      --bg:#0d0d1a;--card:#14142a;--card-border:rgba(255,255,255,.08);
      --brand:#7c3aed;--brand-light:#a78bfa;--cyan:#67e8f9;
      --green:#4ade80;--amber:#fbbf24;--red:#f87171;
      --text:#e2e8f0;--text-muted:#64748b;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:var(--bg);color:var(--text);font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem}
    header{text-align:center;margin-bottom:2rem}
    header h1{font-size:1.6rem;font-weight:700}
    header p{color:var(--text-muted);font-size:.9rem;margin-top:.25rem}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;width:100%;max-width:800px}
    @media(max-width:600px){.grid{grid-template-columns:1fr}}
    .card{background:var(--card);border:1px solid var(--card-border);border-radius:16px;padding:1.5rem}
    .card h2{font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:1rem}
    .status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:8px;animation:pulse 2s infinite}
    .status-dot.green{background:var(--green);box-shadow:0 0 8px var(--green)}
    .status-dot.amber{background:var(--amber);box-shadow:0 0 8px var(--amber)}
    .status-dot.red{background:var(--red);box-shadow:0 0 8px var(--red);animation:none}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
    .url-chip{display:inline-block;background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.3);border-radius:8px;padding:.35rem .75rem;font-family:monospace;font-size:.85rem;color:var(--brand-light);word-break:break-all;margin-top:.5rem}
    .meta-row{display:flex;gap:1rem;flex-wrap:wrap;margin-top:.75rem}
    .meta-badge{font-size:.78rem;background:rgba(103,232,249,.08);border:1px solid rgba(103,232,249,.2);border-radius:6px;padding:.2rem .6rem;color:var(--cyan)}
    #qr-container{grid-column:1 / -1;display:flex;flex-direction:column;align-items:center;gap:1rem}
    #qr-img{border:4px solid white;border-radius:12px;width:200px;height:200px;background:white}
    .no-session{text-align:center;color:var(--text-muted);margin-top:3rem;font-size:.95rem}
    .no-session code{display:block;background:var(--card);border:1px solid var(--card-border);border-radius:8px;padding:.75rem 1.25rem;margin-top:1rem;font-size:.9rem;color:var(--cyan)}
    footer{margin-top:3rem;color:var(--text-muted);font-size:.78rem;text-align:center}
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <header>
    <h1>📱 Mobile Jump</h1>
    <p>Live development session dashboard</p>
  </header>
  <div id="app"></div>
  <footer>Auto-refreshes every 3 seconds &mdash; Mobile Jump v1</footer>
  <script>
    const API_BASE   = '{$apiBase}';
    const SESSION_ID = new URLSearchParams(location.search).get('session');

    function statusIcon(s) {
      if (s === 'connected') return '<span class="status-dot green"></span>Connected';
      if (s === 'waiting')   return '<span class="status-dot amber"></span>Waiting for device';
      return '<span class="status-dot red"></span>' + (s === 'expired' ? 'Expired' : 'Not Found');
    }

    async function fetchStatus() {
      if (!SESSION_ID) { renderNoSession(); return; }
      try {
        const res  = await fetch(\`\${API_BASE}/status/\${SESSION_ID}\`);
        const data = await res.json();
        renderDashboard(data);
      } catch (e) {
        document.getElementById('app').innerHTML = '<div class="no-session"><p style="color:var(--red)">⚠ Could not reach the API: ' + e.message + '</p></div>';
      }
    }

    function renderNoSession() {
      document.getElementById('app').innerHTML = \`
        <div class="no-session">
          <p>No session parameter. Run the artisan command:</p>
          <code>php artisan mobile:jump</code>
        </div>\`;
    }

    function renderDashboard(data) {
      if (data.status === 'not_found' || data.status === 'expired') {
        document.getElementById('app').innerHTML = \`
          <div class="no-session">
            <p style="color:var(--red);font-weight:700">Session Expired or Ended</p>
            <p style="margin-top:.5rem">Run a new session:</p>
            <code>php artisan mobile:jump</code>
          </div>\`;
        return;
      }
      const device      = data.device_info || {};
      const connectedAt = data.connected_at ? new Date(data.connected_at).toLocaleTimeString() : null;
      const expiresAt   = data.expires_at   ? new Date(data.expires_at).toLocaleTimeString()   : '—';

      document.getElementById('app').innerHTML = \`
        <div class="grid">
          <div class="card">
            <h2>Status</h2>
            <div>\${statusIcon(data.status)}</div>
            <div class="meta-row">
              <span class="meta-badge">Session: \${data.session_id ?? SESSION_ID}</span>
              <span class="meta-badge">Expires: \${expiresAt}</span>
            </div>
          </div>
          <div class="card">
            <h2>Endpoints</h2>
            <div class="url-chip">Frontend<br>\${data.frontend_url ?? '—'}</div>
            <div class="url-chip" style="margin-top:.5rem">API<br>\${data.api_url ?? '—'}</div>
          </div>
          \${data.device_info ? \`
          <div class="card" style="grid-column:1/-1">
            <h2>Connected Device</h2>
            <div class="meta-row">
              <span class="meta-badge">Model: \${device.model ?? 'Android'}</span>
              <span class="meta-badge">OS: \${device.os_version ?? '—'}</span>
              <span class="meta-badge">Runner: \${device.runner_version ?? 'v1'}</span>
              \${connectedAt ? '<span class="meta-badge">Connected: ' + connectedAt + '</span>' : ''}
            </div>
          </div>\` : ''}
          <div id="qr-container" class="card">
            <h2>QR Code</h2>
            \${data.status === 'connected'
              ? '<p style="color:var(--green)">✓ Device connected</p>'
              : \`<img src="\${API_BASE}/qr/\${data.session_id ?? SESSION_ID}" id="qr-img" alt="Scan QR"><p style="color:var(--text-muted);font-size:.85rem">Scan with Mobile Jump Android app</p>\`}
          </div>
        </div>\`;
    }

    fetchStatus();
    setInterval(fetchStatus, 3000);
  </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
