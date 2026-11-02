<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create storage location
        $location = StorageLocation::create([
            'code' => 'TEST-01',
            'rack' => 'A',
            'number' => 1,
            'status' => 'Terisi',
        ]);
        
        // Create test item
        $this->item = Item::create([
            'name' => 'Test Item',
            'storage_location_id' => $location->id,
            'stock' => 450,
            'unit' => 'Pcs',
            'primary_unit' => 'Pcs',
        ]);
        
        // Create primary unit
        ItemUnit::create([
            'item_id' => $this->item->id,
            'unit_name' => 'Pcs',
            'conversion_to_primary' => 1,
            'is_primary' => true,
            'context' => 'both',
        ]);
        
        // Create Pack unit
        ItemUnit::create([
            'item_id' => $this->item->id,
            'unit_name' => 'Pack',
            'conversion_to_primary' => 200,
            'is_primary' => false,
            'context' => 'purchase',
        ]);
    }

    public function test_item_has_units_relationship()
    {
        $this->assertCount(2, $this->item->itemUnits);
    }

    public function test_conversion_to_primary_unit()
    {
        $result = $this->item->convertToPrimary(2, 'Pack');
        $this->assertEquals(400, $result);
        
        $result = $this->item->convertToPrimary(50, 'Pcs');
        $this->assertEquals(50, $result);
    }

    public function test_stock_display_with_breakdown()
    {
        $display = $this->item->stock_display;
        
        $this->assertStringContainsString('450 Pcs', $display);
        $this->assertStringContainsString('2 Pack', $display);
        $this->assertStringContainsString('50 Pcs', $display);
    }

    public function test_get_conversion_factor()
    {
        $factor = $this->item->getConversionFactor('Pack');
        $this->assertEquals(200, $factor);
        
        $factor = $this->item->getConversionFactor('Pcs');
        $this->assertEquals(1, $factor);
    }

    public function test_item_unit_convert_methods()
    {
        $packUnit = $this->item->itemUnits()->where('unit_name', 'Pack')->first();
        
        $converted = $packUnit->convertToPrimary(3);
        $this->assertEquals(600, $converted);
        
        $converted = $packUnit->convertFromPrimary(600);
        $this->assertEquals(3, $converted);
    }
}
