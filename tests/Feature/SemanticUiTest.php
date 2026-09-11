<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SemanticUiTest extends TestCase
{
    public function test_status_badges_use_the_canonical_semantic_palette(): void
    {
        $cases = [
            ['request', 'Menunggu Review', 'info'],
            ['request', 'Pending', 'warning'],
            ['request', 'Sebagian Diterima', 'progress'],
            ['request', 'Diterima Penuh', 'success'],
            ['request', 'Ditolak', 'danger'],
            ['request', 'Ditutup Sebagian', 'neutral'],
            ['request', 'Dibatalkan', 'muted'],
            ['procurement', 'Draft', 'warning'],
            ['procurement', 'Diterbitkan', 'info'],
            ['location', 'Terisi', 'success'],
            ['location', 'Kosong', 'neutral'],
            ['account', 'Aktif', 'success'],
            ['account', 'Nonaktif', 'danger'],
        ];

        foreach ($cases as [$domain, $status, $semantic]) {
            $html = Blade::render(
                '<x-status-badge :domain="$domain" :status="$status" />',
                compact('domain', 'status'),
            );

            $this->assertStringContainsString("status-badge--{$semantic}", $html);
            $this->assertStringContainsString($status, $html);
        }
    }

    public function test_status_badge_can_use_a_display_label_without_changing_its_semantics(): void
    {
        $html = Blade::render(
            '<x-status-badge domain="location-change" status="Menunggu Konfirmasi" label="Menunggu" dot />',
        );

        $this->assertStringContainsString('status-badge--warning', $html);
        $this->assertStringContainsString('status-badge__dot', $html);
        $this->assertStringContainsString('Menunggu', $html);
        $this->assertStringNotContainsString('Menunggu Konfirmasi', $html);
    }
}
