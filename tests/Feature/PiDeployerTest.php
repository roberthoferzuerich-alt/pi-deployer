<?php

namespace Tests\Feature;

use Tests\TestCase;

class PiDeployerTest extends TestCase
{
    /**
     * Test that pi-deploy dashboard returns status 200.
     */
    public function test_pi_deployer_dashboard_is_accessible(): void
    {
        $response = $this->get('/pi-deploy');

        $response->assertStatus(200);
        $response->assertSee('Pi Deployer');
    }

    /**
     * Test system audit API returns valid structure.
     */
    public function test_audit_api_returns_json(): void
    {
        $response = $this->get('/pi-deploy/api/audit');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'php_version',
            'is_raspberry_pi',
            'extensions',
            'permissions',
            'disk_free_space',
        ]);
    }

    /**
     * Test artisan pi:check command execution.
     */
    public function test_pi_check_artisan_command(): void
    {
        $this->artisan('pi:check', ['--fix' => true])
            ->assertExitCode(0);
    }

    /**
     * Test saving environment variables via API.
     */
    public function test_save_env_api_endpoint(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/save-env', [
                'APP_NAME' => 'PiDeployerTest',
                'APP_ENV' => 'testing',
                'APP_URL' => 'http://localhost',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'testing',
                'DB_USERNAME' => 'testuser',
                'DB_PASSWORD' => 'secret123',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test database connection API endpoint.
     */
    public function test_test_db_api_endpoint(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/test-db', [
                'db_connection' => 'sqlite',
                'db_database' => ':memory:',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test create DB API endpoint.
     */
    public function test_create_db_api_endpoint(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/create-db', [
                'db_connection' => 'sqlite',
                'db_database' => ':memory:',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test create DB user API endpoint for unsupported driver or missing parameters.
     */
    public function test_create_db_user_api_endpoint_returns_json(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/create-db-user', [
                'db_connection' => 'sqlite',
                'db_username' => 'test_user',
                'db_password' => 'secret',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test generic Nginx generator API endpoint.
     */
    public function test_generate_nginx_api_endpoint(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/generate-nginx', [
                'app_name' => 'chatconnect',
                'port' => 8443,
                'server_name' => 'rhz.internet-box.ch',
                'root_path' => '/var/www/chatconnect',
                'php_version' => '8.4',
                'ssl_enabled' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'filename' => 'chatconnect_8443',
            'sites_available_path' => '/etc/nginx/sites-available/chatconnect_8443',
        ]);
    }

    /**
     * Test check port API endpoint.
     */
    public function test_check_port_api_endpoint(): void
    {
        $response = $this->withoutMiddleware()
            ->getJson('/pi-deploy/api/check-port?port=8445');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'port',
            'is_free',
            'suggested_port',
            'used_nginx_ports',
        ]);
    }

    /**
     * Test composer install returns safety error when targeting self or non-existent path.
     */
    public function test_composer_install_prevents_self_deployment(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/composer-install', [
                'target_path' => base_path(),
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('Sicherheitssperre', $response->json('output'));
    }

    /**
     * Test composer install returns error when targeting non-existent directory.
     */
    public function test_composer_install_fails_on_non_existent_path(): void
    {
        $response = $this->withoutMiddleware()
            ->postJson('/pi-deploy/api/composer-install', [
                'target_path' => '/var/www/non-existent-project-12345',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('existiert nicht', $response->json('output'));
    }

    /**
     * Test pi:deploy command fails when target path is invalid.
     */
    public function test_pi_deploy_command_fails_on_invalid_target_path(): void
    {
        config(['pi-deployer.target_path' => '/var/www/non-existent-project-12345']);

        $this->artisan('pi:deploy')
            ->assertExitCode(1);
    }
}
