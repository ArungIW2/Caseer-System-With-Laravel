<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class MasterDataController extends Controller
{
    private function config(string $resource): array
    {
        return match ($resource) {
            'stores' => [
                'model' => Store::class, 'title' => 'Stores', 'singular' => 'Store', 'route' => 'master-data.stores',
                'search' => ['code', 'name', 'phone'],
                'fields' => [
                    'code' => ['label' => 'Code', 'type' => 'text', 'required' => true],
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'phone' => ['label' => 'Phone', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'type' => 'textarea'],
                    'timezone' => ['label' => 'Timezone', 'type' => 'text', 'required' => true],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            'categories' => [
                'model' => Category::class, 'title' => 'Categories', 'singular' => 'Category', 'route' => 'master-data.categories',
                'search' => ['name', 'slug'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'slug' => ['label' => 'Slug', 'type' => 'text', 'required' => true],
                    'parent_id' => ['label' => 'Parent Category', 'type' => 'category'],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            'brands' => [
                'model' => Brand::class, 'title' => 'Brands', 'singular' => 'Brand', 'route' => 'master-data.brands',
                'search' => ['name', 'slug'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'slug' => ['label' => 'Slug', 'type' => 'text', 'required' => true],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            'units' => [
                'model' => Unit::class, 'title' => 'Units', 'singular' => 'Unit', 'route' => 'master-data.units',
                'search' => ['name', 'symbol'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'symbol' => ['label' => 'Symbol', 'type' => 'text', 'required' => true],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            'suppliers' => [
                'model' => Supplier::class, 'title' => 'Suppliers', 'singular' => 'Supplier', 'route' => 'master-data.suppliers',
                'search' => ['code', 'name', 'contact_person', 'phone', 'email'],
                'fields' => [
                    'code' => ['label' => 'Code', 'type' => 'text', 'required' => true],
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'contact_person' => ['label' => 'Contact Person', 'type' => 'text'],
                    'phone' => ['label' => 'Phone', 'type' => 'text'],
                    'email' => ['label' => 'Email', 'type' => 'email'],
                    'address' => ['label' => 'Address', 'type' => 'textarea'],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            'products' => [
                'model' => Product::class, 'title' => 'Products', 'singular' => 'Product', 'route' => 'master-data.products',
                'search' => ['sku', 'barcode', 'name'],
                'fields' => [
                    'sku' => ['label' => 'SKU', 'type' => 'text', 'required' => true],
                    'barcode' => ['label' => 'Barcode', 'type' => 'text'],
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'category_id' => ['label' => 'Category', 'type' => 'category'],
                    'brand_id' => ['label' => 'Brand', 'type' => 'brand'],
                    'unit_id' => ['label' => 'Unit', 'type' => 'unit', 'required' => true],
                    'cost_price' => ['label' => 'Cost Price', 'type' => 'number', 'step' => '0.01', 'required' => true],
                    'selling_price' => ['label' => 'Selling Price', 'type' => 'number', 'step' => '0.01', 'required' => true],
                    'minimum_stock' => ['label' => 'Minimum Stock', 'type' => 'number', 'step' => '0.001', 'required' => true],
                    'track_stock' => ['label' => 'Track Stock', 'type' => 'boolean'],
                    'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                ],
            ],
            default => abort(404),
        };
    }

    public function index(Request $request, string $resource): View
    {
        $config = $this->config($resource);
        $query = ($config['model'])::query();
        $search = trim((string) $request->string('search'));

        if ($search !== '') {
            $query->where(function ($q) use ($config, $search): void {
                foreach ($config['search'] as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        return view('master-data.index', compact('config', 'resource', 'items', 'search'));
    }

    public function create(string $resource): View
    {
        $config = $this->config($resource);
        return view('master-data.form', array_merge(compact('config', 'resource'), ['item' => null, ...$this->options($resource)]));
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->config($resource);
        $validated = $request->validate($this->rules($resource));
        ($config['model'])::create($this->normalise($resource, $validated));
        return redirect()->route($config['route'].'.index')->with('success', $config['singular'].' created successfully.');
    }

    public function edit(string $resource, int $id): View
    {
        $config = $this->config($resource);
        $item = ($config['model'])::findOrFail($id);
        return view('master-data.form', array_merge(compact('config', 'resource', 'item'), $this->options($resource)));
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $item = ($config['model'])::findOrFail($id);
        $validated = $request->validate($this->rules($resource, $item));
        $item->update($this->normalise($resource, $validated));
        return redirect()->route($config['route'].'.index')->with('success', $config['singular'].' updated successfully.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource);
        $item = ($config['model'])::findOrFail($id);

        if ($this->hasDependencies($resource, $item)) {
            return back()->with('error', 'This record cannot be deleted because it is already referenced by operational data.');
        }

        $item->delete();
        return back()->with('success', $config['singular'].' deleted successfully.');
    }

    private function options(string $resource): array
    {
        return match ($resource) {
            'categories' => ['categories' => Category::query()->orderBy('name')->get(['id', 'name'])],
            'products' => [
                'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            ],
            default => [],
        };
    }

    private function rules(string $resource, ?Model $item = null): array
    {
        $id = $item?->getKey();
        return match ($resource) {
            'stores' => [
                'code' => ['required', 'string', 'max:30', Rule::unique('stores', 'code')->ignore($id)],
                'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string'], 'timezone' => ['required', 'string', 'max:64'], 'is_active' => ['boolean'],
            ],
            'categories' => [
                'name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($id)],
                'parent_id' => ['nullable', 'integer', 'exists:categories,id'], 'is_active' => ['boolean'],
            ],
            'brands' => [
                'name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($id)], 'is_active' => ['boolean'],
            ],
            'units' => [
                'name' => ['required', 'string', 'max:255'], 'symbol' => ['required', 'string', 'max:20', Rule::unique('units', 'symbol')->ignore($id)], 'is_active' => ['boolean'],
            ],
            'suppliers' => [
                'code' => ['required', 'string', 'max:30', Rule::unique('suppliers', 'code')->ignore($id)], 'name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean'],
            ],
            'products' => [
                'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($id)],
                'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($id)], 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'brand_id' => ['nullable', 'integer', 'exists:brands,id'], 'unit_id' => ['required', 'integer', 'exists:units,id'],
                'cost_price' => ['required', 'numeric', 'min:0'], 'selling_price' => ['required', 'numeric', 'min:0'], 'minimum_stock' => ['required', 'numeric', 'min:0'],
                'track_stock' => ['boolean'], 'is_active' => ['boolean'],
            ],
            default => [],
        };
    }

    private function normalise(string $resource, array $data): array
    {
        foreach (['is_active', 'track_stock'] as $boolean) {
            if (array_key_exists($boolean, $this->config($resource)['fields'])) {
                $data[$boolean] = (bool) ($data[$boolean] ?? false);
            }
        }
        return $data;
    }

    private function hasDependencies(string $resource, Model $item): bool
    {
        return match ($resource) {
            'categories' => $item->products()->exists(),
            'brands' => $item->products()->exists(),
            'units' => $item->products()->exists(),
            'suppliers' => $item->purchases()->exists(),
            'stores' => $item->users()->exists() || $item->inventories()->exists() || $item->sales()->exists() || $item->purchases()->exists(),
            default => false,
        };
    }
}
