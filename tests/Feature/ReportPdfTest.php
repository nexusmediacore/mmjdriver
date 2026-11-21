<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_reports_index_page_loads(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user, 'admin@example.com not found');

        $response = $this->actingAs($user)->get('/reports');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Reports/Index'));
    }

    public function test_reports_pdf_downloads(): void
    {
        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/reports/pdf');
        $response->assertStatus(200);
        $this->assertTrue(
            str_starts_with($response->getContent() ?: '', '%PDF'),
            'Response is not a valid PDF'
        );
    }
}
