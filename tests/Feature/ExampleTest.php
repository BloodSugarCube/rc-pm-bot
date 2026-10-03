<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Корень всегда редиректит на страницу входа в админку.
     */
    public function test_the_application_redirects_root_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.login'));
    }
}