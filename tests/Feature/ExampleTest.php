<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_api_root_returns_documentation(): void
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'API Financiera');
        $response->assertJsonStructure(['message', 'endpoints', 'docs']);
    }
}
