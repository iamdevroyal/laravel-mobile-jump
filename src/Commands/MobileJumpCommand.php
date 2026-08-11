<?php

namespace Iamdevroyal\MobileJump\Commands;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Illuminate\Console\Command;

class MobileJumpCommand extends Command
{
    protected $signature = 'mobile:jump
                            {--host=                 : Override the detected LAN IP address}
                            {--frontend-port=5173    : Port of the Vite / frontend dev server}
                            {--api-port=8000         : Port of the Laravel API server}
                            {--ttl=600               : Session lifetime in seconds (default 10 min)}';

    protected $description = 'Start a Mobile Jump dev session and display a QR code for the Android app';

    public function __construct(private readonly MobileSessionService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $frontendPort = (int) $this->option('frontend-port');
        $apiPort      = (int) $this->option('api-port');
        $ttl          = (int) $this->option('ttl');

        // 1 — Detect LAN IP
        $lanIp       = $this->option('host') ?: $this->service->detectLanIp();
        $frontendUrl = "http://{$lanIp}:{$frontendPort}";
        $apiUrl      = "http://{$lanIp}:{$apiPort}";

        // 2 — Probe servers
        $this->line('');
        $this->line('  Checking development servers...');

        $viteUp = $this->probe($frontendUrl);
        $apiUp  = $this->probe($apiUrl);

        if (! $viteUp) {
            $this->warn("  ⚠  Frontend not reachable at {$frontendUrl}");
            $this->warn("     Make sure it's running on all interfaces (e.g. --host 0.0.0.0)");
        }
        if (! $apiUp) {
            $this->warn("  ⚠  API not reachable at {$apiUrl}");
            $this->warn("     Run: php artisan serve --host=0.0.0.0");
        }
        if ((! $viteUp || ! $apiUp) && ! $this->confirm('Servers not fully reachable. Continue anyway?', false)) {
            return self::FAILURE;
        }

        // 3 — Create session
        $result    = $this->service->createSession($frontendUrl, $apiUrl, $frontendPort, $ttl);
        $session   = $result['session'];
        $expiresIn = number_format($ttl / 60, 0) . ' minutes';

        // 4 — Render QR — encode "JMP-XXXX-XXXX@10.x.x.x:8000" (compact token)
        $apiHost   = parse_url($apiUrl, PHP_URL_HOST) . ':' . parse_url($apiUrl, PHP_URL_PORT);
        $qrContent = $session->sessionId . '@' . $apiHost;
        $qrLines   = $this->renderTerminalQr($qrContent);

        // 5 — Display banner + QR
        $this->displayBanner($frontendUrl, $apiUrl, $session->sessionId, $expiresIn, $qrLines);
        $this->info("  Press Ctrl+C to end the session.\n");

        // 6 — Heartbeat loop
        $startTime = time();
        $wasConnected = false;

        while (true) {
            sleep(5);

            if ((time() - $startTime) >= $ttl) {
                $this->newLine();
                $this->error('  Session expired. Run php artisan mobile:jump to start a new session.');
                $this->service->destroySession($session->sessionId);
                return self::SUCCESS;
            }

            $live = $this->service->findById($session->sessionId);
            if ($live === null) {
                $this->newLine();
                $this->warn('  Session was terminated externally.');
                return self::SUCCESS;
            }

            if ($live->isConnected() && ! $wasConnected) {
                $deviceInfo   = json_decode($live->deviceInfo ?? '{}', true);
                $model        = $deviceInfo['model'] ?? 'Android';
                $wasConnected = true;
                $this->line("  🟢 Device connected: {$model}");
            }
        }

        $this->service->destroySession($session->sessionId);
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Terminal QR renderer — half-block Unicode chars (▀ ▄ █)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderTerminalQr(string $content): array
    {
        try {
            $qrCode = (new Encoder())->encode($content, ErrorCorrectionLevel::L());
            $matrix = $qrCode->getMatrix();
            $width  = $matrix->getWidth();
            $height = $matrix->getHeight();
            $pad    = str_repeat(' ', 2);
            $lines  = [''];

            for ($row = 0; $row < $height; $row += 2) {
                $line = '  ' . $pad;
                for ($col = 0; $col < $width; $col++) {
                    $top = $matrix->get($col, $row) === 1;
                    $bot = ($row + 1 < $height) ? $matrix->get($col, $row + 1) === 1 : false;
                    $line .= match (true) {
                        $top && $bot  => '█',
                        $top && !$bot => '▀',
                        !$top && $bot => '▄',
                        default       => ' ',
                    };
                }
                $lines[] = $line . $pad;
            }
            $lines[] = '';
            return $lines;
        } catch (\Throwable $e) {
            return ['  [QR unavailable: ' . $e->getMessage() . ']'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Connection info banner
    // ─────────────────────────────────────────────────────────────────────────

    private function displayBanner(
        string $frontendUrl,
        string $apiUrl,
        string $sessionId,
        string $expiresIn,
        array  $qrLines,
    ): void {
        $prefix       = config('mobile-jump.route_prefix', 'mobile-jump');
        $dashboardUrl = "{$apiUrl}/{$prefix}/dashboard?session={$sessionId}";

        $this->newLine();
        $this->line('  <fg=cyan>╔═════════════════════════════════════════════════════════════╗</>');
        $this->line('  <fg=cyan>║</> ' . str_pad('<fg=magenta;options=bold>📱 MOBILE JUMP — DEV SESSION</>', 70) . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>╠═════════════════════════════════════════════════════════════╣</>');
        $this->line('  <fg=cyan>║</>                                                             <fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Frontend  : <fg=green>' . str_pad($frontendUrl, 44) . '</>' . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  API       : <fg=green>' . str_pad($apiUrl, 44) . '</>' . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Dashboard : <fg=cyan>' . str_pad($dashboardUrl, 44) . '</>' . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>                                                             <fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Session   : <fg=yellow;options=bold>' . str_pad($sessionId, 44) . '</>' . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Expires   : ' . str_pad($expiresIn, 44) . '<fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>                                                             <fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Scan the QR below with the Mobile Jump Android app         <fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>  Or open the Dashboard URL in your browser                  <fg=cyan>║</>');
        $this->line('  <fg=cyan>║</>                                                             <fg=cyan>║</>');
        $this->line('  <fg=cyan>╚═════════════════════════════════════════════════════════════╝</>');
        $this->newLine();

        foreach ($qrLines as $line) {
            $this->line($line);
        }
        $this->newLine();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP probe helper
    // ─────────────────────────────────────────────────────────────────────────

    private function probe(string $url): bool
    {
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
            $res = @file_get_contents($url, false, $ctx);
            return $res !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
