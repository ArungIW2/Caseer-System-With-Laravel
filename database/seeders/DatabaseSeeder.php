<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::updateOrCreate(['code' => 'MAIN'], [
            'name' => 'Caseer Main Store', 'timezone' => 'Asia/Jakarta', 'is_active' => true,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@caseer.test'],
            [
                'name' => 'Caseer Super Admin', 'password' => Hash::make('ChangeMe123!'), 'is_active' => true,
                'role' => 'super_admin', 'store_id' => null,
            ],
        );

        $units = [
            ['name' => 'Piece', 'symbol' => 'pcs'], ['name' => 'Box', 'symbol' => 'box'], ['name' => 'Kilogram', 'symbol' => 'kg'],
        ];
        foreach ($units as $unit) Unit::updateOrCreate(['symbol' => $unit['symbol']], [...$unit, 'is_active' => true]);

        $categories = ['General', 'Electronics', 'Office Supplies'];
        foreach ($categories as $name) Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);

        foreach (['Generic', 'Caseer Brand'] as $name) Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]);
        Supplier::updateOrCreate(['code' => 'SUP-001'], ['name' => 'Default Supplier', 'is_active' => true]);

        $general = Category::where('slug', 'general')->first();
        $brand = Brand::where('slug', 'generic')->first();
        $piece = Unit::where('symbol', 'pcs')->first();
        Product::updateOrCreate(['sku' => 'DEMO-001'], [
            'barcode' => '899000000001', 'name' => 'Demo Product', 'category_id' => $general?->id, 'brand_id' => $brand?->id,
            'unit_id' => $piece->id, 'cost_price' => 5000, 'selling_price' => 7500, 'minimum_stock' => 5,
            'track_stock' => true, 'is_active' => true,
        ]);
    }
}
