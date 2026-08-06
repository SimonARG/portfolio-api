<?php

declare(strict_types=1);

it('reports ok when its dependencies are reachable', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk()
        ->assertJsonStructure(['status', 'version', 'environment', 'checks' => ['database', 'cache']])
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.cache', true);
});

it('reports the configured application version', function (): void {
    config()->set('app.version', '9.9.9-test');

    $this->getJson('/api/v1/health')->assertJsonPath('version', '9.9.9-test');
});

it('is reachable without authentication', function (): void {
    $this->getJson('/api/v1/health')->assertOk();
});
