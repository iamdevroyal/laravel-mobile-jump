<?php

namespace Iamdevroyal\MobileJump\Tests;

use Iamdevroyal\MobileJump\MobileJumpServiceProvider;
use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Iamdevroyal\MobileJump\Storage\FileSessionStore;
use Orchestra\Testbench\TestCase;

class MobileJumpApiTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [MobileJumpServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mobile-jump.storage', 'file');
        $app['config']->set('mobile-jump.session_ttl', 600);
        $app['config']->set('mobile-jump.register_routes', true);
    }

    // ─── POST /mobile-jump/api/connect ────────────────────────────────────────

    public function test_connect_with_valid_session_id_returns_200(): void
    {
        $result    = $this->app->make(MobileSessionService::class)
            ->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $sessionId = $result['session']->sessionId;

        $response = $this->postJson('/mobile-jump/api/connect', [
            'session_id'  => $sessionId,
            'device_info' => ['model' => 'Test Phone', 'os_version' => 'Android 14'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'connected')
            ->assertJsonPath('session_id', $sessionId);
    }

    public function test_connect_with_invalid_session_returns_401(): void
    {
        $response = $this->postJson('/mobile-jump/api/connect', [
            'session_id' => 'JMP-FAKE-0000',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_connect_missing_session_id_returns_422(): void
    {
        $response = $this->postJson('/mobile-jump/api/connect', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    // ─── GET /mobile-jump/api/status/{id} ────────────────────────────────────

    public function test_status_returns_waiting_before_device_connects(): void
    {
        $result    = $this->app->make(MobileSessionService::class)
            ->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $sessionId = $result['session']->sessionId;

        $response = $this->getJson("/mobile-jump/api/status/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'waiting');
    }

    public function test_status_of_connected_session_returns_connected(): void
    {
        $service = $this->app->make(MobileSessionService::class);
        $result  = $service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $session = $result['session'];
        $service->recordConnection($session, ['model' => 'Pixel 8']);

        $response = $this->getJson("/mobile-jump/api/status/{$session->sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'connected');
    }

    public function test_status_of_unknown_session_returns_404(): void
    {
        $response = $this->getJson('/mobile-jump/api/status/JMP-FAKE-DEAD');
        $response->assertStatus(404);
    }

    // ─── DELETE /mobile-jump/api/disconnect/{id} ──────────────────────────────

    public function test_disconnect_removes_session(): void
    {
        $service = $this->app->make(MobileSessionService::class);
        $result  = $service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $id      = $result['session']->sessionId;

        $this->deleteJson("/mobile-jump/api/disconnect/{$id}")->assertStatus(200);

        // Session should now be gone
        $this->getJson("/mobile-jump/api/status/{$id}")->assertStatus(404);
    }

    // ─── GET /mobile-jump/api/qr/{id} ────────────────────────────────────────

    public function test_qr_returns_svg_image(): void
    {
        $result = $this->app->make(MobileSessionService::class)
            ->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $id     = $result['session']->sessionId;

        $response = $this->get("/mobile-jump/api/qr/{$id}");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_qr_of_unknown_session_returns_404(): void
    {
        $this->get('/mobile-jump/api/qr/JMP-FAKE-DEAD')->assertStatus(404);
    }
}
