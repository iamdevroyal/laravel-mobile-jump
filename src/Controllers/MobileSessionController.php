<?php

namespace Iamdevroyal\MobileJump\Controllers;

use Iamdevroyal\MobileJump\Requests\ConnectRequest;
use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class MobileSessionController extends Controller
{
    public function __construct(private readonly MobileSessionService $service) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /connect
    // ─────────────────────────────────────────────────────────────────────────

    public function connect(ConnectRequest $request): JsonResponse
    {
        $session = $this->service->validateSession(
            $request->validated('session_id'),
            $request->validated('token'),
        );

        if ($session === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired session. Please generate a new QR code.',
            ], 401);
        }

        $session = $this->service->recordConnection($session, $request->validated('device_info', []));

        return response()->json([
            'status'       => 'connected',
            'session_id'   => $session->sessionId,
            'frontend_url' => $session->frontendUrl,
            'api_url'      => $session->apiUrl,
            'hmr_port'     => $session->hmrPort,
            'expires_at'   => $session->expiresAt->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /status/{sessionId}
    // ─────────────────────────────────────────────────────────────────────────

    public function status(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->service->findById($sessionId);

        if ($session === null) {
            return response()->json(['status' => 'not_found', 'session_id' => $sessionId], 404);
        }

        if ($session->isExpired()) {
            $this->service->destroySession($sessionId);
            return response()->json(['status' => 'expired', 'session_id' => $sessionId], 410);
        }

        return response()->json([
            'status'       => $session->isConnected() ? 'connected' : 'waiting',
            'session_id'   => $session->sessionId,
            'frontend_url' => $session->frontendUrl,
            'api_url'      => $session->apiUrl,
            'connected_at' => $session->connectedAt?->toIso8601String(),
            'expires_at'   => $session->expiresAt->toIso8601String(),
            'device_info'  => $session->deviceInfo ? json_decode($session->deviceInfo, true) : null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /qr/{sessionId}   — SVG QR image for the browser dashboard
    // ─────────────────────────────────────────────────────────────────────────

    public function qr(string $sessionId): \Illuminate\Http\Response
    {
        $session = $this->service->findById($sessionId);

        if ($session === null || $session->isExpired()) {
            return response('Session not found or expired', 404);
        }

        $apiHost   = parse_url($session->apiUrl, PHP_URL_HOST) . ':' . parse_url($session->apiUrl, PHP_URL_PORT);
        $qrContent = $session->sessionId . '@' . $apiHost;

        $renderer = new ImageRenderer(
            new RendererStyle(260, 2),
            new SvgImageBackEnd()
        );
        $svg = (new Writer($renderer))->writeString($qrContent);

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'no-store',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /disconnect/{sessionId}
    // ─────────────────────────────────────────────────────────────────────────

    public function disconnect(Request $request, string $sessionId): JsonResponse
    {
        $this->service->destroySession($sessionId);

        return response()->json(['status' => 'disconnected']);
    }
}
