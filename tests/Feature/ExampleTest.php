<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_is_redirected_to_login_page_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_signin_page_loads_successfully(): void
    {
        $response = $this->get('/authentication/sign-in');

        $response->assertStatus(200);
    }
}
