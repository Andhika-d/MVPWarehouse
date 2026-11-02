# Multi-Unit Conversion System

## Overview
Sistem konversi multi-unit memungkinkan item memiliki berbagai satuan dengan konversi otomatis ke unit dasar (primary unit) untuk konsistensi tracking stock.

## Implementasi

### Database Schema

#### Tabel Baru: `item_units`
```sql
- id (bigint, PK)
- item_id (FK ke items)
- unit_name (varchar 50) - nama unit (Pack, Box, Kaleng, dll)
- conversion_to_primary (decimal 10,4) - faktor konversi ke primary unit
- is_primary (boolean) - apakah ini unit dasar
- context (enum: purchase, usage, both) - konteks penggunaan unit
- timestamps
```

#### Kolom Baru pada `items`
```sql
- primary_unit (varchar 50) - unit dasar untuk tracking (Pcs, Liter, Meter)
```

#### Kolom Baru pada `stock_requests`
```sql
- quantity_in_primary (decimal 12,4) - quantity dalam primary unit (auto-calculated)
```

#### Kolom Baru pada `stock_movements`
```sql
- quantity_in_primary (decimal 12,4) - quantity dalam primary unit (auto-calculated)
```

### Models

#### ItemUnit Model
- Relationship: belongsTo Item
- Methods:
  - `convertToPrimary(float $quantity)` - convert dari unit ini ke primary
  - `convertFromPrimary(float $quantityInPrimary)` - convert dari primary ke unit ini

#### Item Model (Updated)
- Relationship: hasMany ItemUnits
- Methods:
  - `getConversionFactor(string $unit)` - dapatkan faktor konversi untuk unit tertentu
  - `convertToPrimary(float $quantity, string $unit)` - convert quantity ke primary unit
  - `getStockDisplayAttribute()` - tampilkan stock dengan breakdown (e.g., "450 Pcs (2 Pack + 50 Pcs)")

### Controllers

#### Updated: RequestController
- Saat Gudang buat request, sistem auto-calculate `quantity_in_primary`
- Support multi-unit selection

#### Updated: GudangController
- **Barang Masuk**: Support terima dalam berbagai unit, auto-convert ke primary untuk stock
- **Barang Keluar**: Support keluarkan dalam berbagai unit, auto-convert ke primary

#### New: Api/ItemUnitController
- Endpoint: `GET /api/items/{item}/units?context={purchase|usage|both}`
- Return: Available units untuk item dengan konversi info

### Audit Log Enhancement
- Audit log sekarang mencatat unit asli dan converted value
- Format: "Barang masuk: 2 Pack (400 Pcs) Item X"

## Flow Penggunaan

### 1. Setup Unit (Admin/Gudang)
```
Item: Skrup M8
Primary Unit: Pcs

Units:
- Pcs (primary, 1:1, context: both)
- Pack (200:1, context: purchase)
- Box (2000:1, context: purchase)
```

### 2. Request Barang (Gudang)
```
Form Request:
- Item: Skrup M8
- Qty: 2
- Unit: Pack (dropdown: Pack, Box)
- System calculates: quantity_in_primary = 400 Pcs
```

### 3. Terima Barang (Gudang)
```
Form Terima:
- Request: 2 Pack Skrup M8
- Qty Terima: 2
- Unit: Pack (bisa pilih Pack atau Pcs)
- Stock += 400 Pcs
```

### 4. Barang Keluar (Gudang)
```
Form Keluar:
- Item: Skrup M8
- Stock: 450 Pcs (2 Pack + 50 Pcs)
- Qty: 50
- Unit: Pcs (dropdown: hanya usage units)
- Stock -= 50 Pcs
```

## Testing

### Test Suite: `ItemUnitTest`
✅ 5 tests, 10 assertions, 0 failures

Tests:
1. Item has units relationship
2. Conversion to primary unit calculation
3. Stock display with breakdown
4. Get conversion factor
5. ItemUnit convert methods

### Manual Testing Checklist
- [ ] Buat item dengan multi-unit
- [ ] Request barang dalam unit Pack
- [ ] Terima barang dalam unit Pack
- [ ] Keluarkan barang dalam unit Pcs
- [ ] Verify stock calculation correct
- [ ] Check audit log shows both units

## Migration & Seeding

### Run Migrations
```bash
php artisan migrate
```

### Initialize Existing Items
```bash
php artisan db:seed --class=InitializeItemUnitsSeeder
```

Seeder ini akan:
- Create primary unit untuk setiap item existing
- Set conversion 1:1 untuk primary unit
- Context: both

## API Endpoint

### Get Item Units
```
GET /api/items/{item}/units?context=purchase

Response:
{
  "units": [
    {
      "name": "Pack",
      "conversion": 200,
      "is_primary": false,
      "conversion_text": "1 Pack = 200 Pcs"
    }
  ],
  "primary_unit": "Pcs",
  "current_stock": 450,
  "stock_display": "450 Pcs (2 Pack + 50 Pcs)"
}
```

## Backward Compatibility

✅ **Fully backward compatible**
- Existing items auto-migrated dengan primary unit = current unit
- Existing stock_requests dan stock_movements backfilled dengan quantity_in_primary
- Tidak ada breaking changes pada functionality existing

## Next Steps (UI Implementation)

1. **Admin: Manage Item Units**
   - Form untuk add/edit/delete units per item
   - UI untuk set conversion factors
   - Context selection (purchase/usage/both)

2. **Gudang: Dynamic Unit Dropdown**
   - Request form: dropdown units filtered by context=purchase
   - Keluar form: dropdown units filtered by context=usage
   - Display real-time conversion info

3. **Stock Display Enhancement**
   - Show breakdown di semua list items
   - Hover tooltip untuk detail konversi

## Notes

- Stock selalu disimpan dalam **primary unit** untuk konsistensi
- Conversion factor bisa diubah kapan saja tanpa corrupt historical data
- Setiap transaksi menyimpan unit asli + converted value untuk audit trail
- Context system mencegah user salah pilih unit (e.g., tidak bisa request dalam unit "Pcs" kalau seharusnya "Pack")
