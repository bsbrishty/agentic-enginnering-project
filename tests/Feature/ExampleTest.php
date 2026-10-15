<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example. The unauthenticated root should redirect
     * to the login page; an authenticated root should redirect to the
     * dashboard.
     */
    public function test_the_application_redirects_unauthenticated_root_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
