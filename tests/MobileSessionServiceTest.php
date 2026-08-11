<?php

namespace Iamdevroyal\MobileJump\Tests;

use Carbon\Carbon;
use Iamdevroyal\MobileJump\Models\MobileSession;
use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Iamdevroyal\MobileJump\Storage\FileSessionStore;
use PHPUnit\Framework\TestCase;

class MobileSessionServiceTest extends TestCase
{
    private MobileSessionService $service;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a temp directory for the file store so tests are isolated
        $this->tmpDir = sys_get_temp_dir() . '/mobile-jump-tests-' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        // Swap the FileSessionStore's directory
        $store = new class($this->tmpDir) extends FileSessionStore {
            public function __construct(private string $overrideDir)
            {
                // don't call parent — avoids storage_path() which isn't available
            }
            protected function getDir(): string { return $this->overrideDir; }
        };

        $this->service = new MobileSessionService($store);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*.json'));
        rmdir($this->tmpDir);
        parent::tearDown();
    }

    // ─── Happy path ──────────────────────────────────────────────────────────

    public function test_create_and_retrieve_session(): void
    {
        $result  = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $session = $result['session'];

        $this->assertInstanceOf(MobileSession::class, $session);
        $this->assertNotEmpty($session->sessionId);
        $this->assertStringStartsWith('JMP-', $session->sessionId);

        $found = $this->service->findById($session->sessionId);
        $this->assertNotNull($found);
        $this->assertEquals($session->sessionId, $found->sessionId);
    }

    public function test_validate_session_without_token(): void
    {
        $result    = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $sessionId = $result['session']->sessionId;

        $validated = $this->service->validateSession($sessionId);
        $this->assertNotNull($validated);
    }

    public function test_validate_session_with_correct_token(): void
    {
        $result   = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $rawToken = $result['rawToken'];
        $id       = $result['session']->sessionId;

        $validated = $this->service->validateSession($id, $rawToken);
        $this->assertNotNull($validated);
    }

    public function test_validate_session_with_wrong_token_returns_null(): void
    {
        $result = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $id     = $result['session']->sessionId;

        $validated = $this->service->validateSession($id, 'wrong-token');
        $this->assertNull($validated);
    }

    // ─── Record connection ────────────────────────────────────────────────────

    public function test_record_connection_marks_session_connected(): void
    {
        $result  = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $session = $result['session'];

        $this->assertFalse($session->isConnected());

        $connected = $this->service->recordConnection($session, ['model' => 'Pixel 8']);
        $this->assertTrue($connected->isConnected());
        $this->assertStringContainsString('Pixel 8', $connected->deviceInfo);
    }

    // ─── Expiry edge case ─────────────────────────────────────────────────────

    public function test_expired_session_returns_null(): void
    {
        $result    = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000', 5173, 1);
        $sessionId = $result['session']->sessionId;

        sleep(2); // let it expire

        $found = $this->service->findById($sessionId);
        $this->assertNull($found);
    }

    // ─── Destroy ─────────────────────────────────────────────────────────────

    public function test_destroy_session_removes_it(): void
    {
        $result    = $this->service->createSession('http://10.0.0.1:5173', 'http://10.0.0.1:8000');
        $sessionId = $result['session']->sessionId;

        $this->service->destroySession($sessionId);

        $this->assertNull($this->service->findById($sessionId));
    }

    // ─── LAN IP detection ─────────────────────────────────────────────────────

    public function test_detect_lan_ip_returns_a_valid_ip(): void
    {
        $ip = $this->service->detectLanIp();
        $this->assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $ip);
    }
}
