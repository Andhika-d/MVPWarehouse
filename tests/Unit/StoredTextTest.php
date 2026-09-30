<?php

namespace Tests\Unit;

use App\Support\StoredText;
use Tests\TestCase;

class StoredTextTest extends TestCase
{
    public function test_system_text_returns_indonesian_source_under_default_locale(): void
    {
        $this->assertSame('Saldo awal dari import', StoredText::translate('Saldo awal dari import'));
        $this->assertSame('Diterima 10 pcs', StoredText::translate('Diterima 10 pcs'));
        $this->assertSame('Diterima 10 pcs — dua dicek', StoredText::translate('Diterima 10 pcs — dua dicek'));
    }

    public function test_closed_set_system_text_is_translated_for_english_readers(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('Opening balance from import', StoredText::translate('Saldo awal dari import'));
        $this->assertSame('Request delayed by HR', StoredText::translate('Permintaan ditunda oleh HR'));
        $this->assertSame('Automatically cancelled: the item location has changed.', StoredText::translate('Dibatalkan otomatis: lokasi item sudah berubah.'));
    }

    public function test_composite_system_templates_keep_their_dynamic_values(): void
    {
        $this->app->setLocale('en');

        $this->assertSame(
            'Received 10 pcs - dua dicek',
            StoredText::translate('Diterima 10 pcs - dua dicek'),
        );
        $this->assertSame(
            'Received 10 pcs - dua dicek',
            StoredText::translate('Diterima 10 pcs — dua dicek'),
        );
        $this->assertSame(
            'Remaining 4 rim closed - supplier out of stock',
            StoredText::translate('Sisa 4 rim ditutup — supplier out of stock'),
        );
        $this->assertSame(
            'Stock adjustment from 10 to 25',
            StoredText::translate('Penyesuaian stok dari 10 ke 25'),
        );
        $this->assertSame(
            'Item: Sarung Tangan - 5 pcs',
            StoredText::translate('Barang: Sarung Tangan — 5 pcs'),
        );
        $this->assertSame(
            'Item: Sarung - Tangan — Besar - 5 pcs',
            StoredText::translate('Barang: Sarung - Tangan — Besar — 5 pcs'),
        );
    }

    public function test_user_typed_text_is_returned_untouched(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('Barangozal di Gudang B', StoredText::translate('Barangozal di Gudang B'));
        $this->assertSame('Pemakaian produksi lain', StoredText::translate('Pemakaian produksi lain'));
        $this->assertSame('Catatan pengguna — berisi dash', StoredText::translate('Catatan pengguna — berisi dash'));
    }

    public function test_empty_values_are_preserved(): void
    {
        $this->assertNull(StoredText::translate(null));
        $this->assertSame('', StoredText::translate(''));
    }
}
