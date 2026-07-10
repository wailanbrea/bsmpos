<?php

declare(strict_types=1);

namespace App\Modules\Product\Actions;

use App\Modules\Company\Models\Company;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

final class SaveProductAction
{
    /**
     * Crea o actualiza un producto y sincroniza sus ajustes de inventario.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Company $company, array $attributes, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($company, $attributes, $product): Product {
            $inventory = $attributes['inventory'] ?? [];
            $variants = $attributes['variants'] ?? null;
            $modifiers = $attributes['modifiers'] ?? null;
            $combos = $attributes['combos'] ?? null;

            unset($attributes['inventory'], $attributes['variants'], $attributes['modifiers'], $attributes['combos']);

            if ($product === null) {
                $product = Product::query()->create(['company_id' => $company->getKey(), ...$attributes]);
                $product->audit('product.created', [], $product->only(['name', 'sku', 'price']));
            } else {
                $before = $product->only(['name', 'price', 'cost', 'is_active']);
                $product->update($attributes);
                $product->audit('product.updated', $before, $product->only(['name', 'price', 'cost', 'is_active']));
            }

            if ($product->track_inventory) {
                $product->inventorySetting()->updateOrCreate(
                    ['product_id' => $product->getKey()],
                    is_array($inventory) ? $inventory : [],
                );
            }

            // Guardar variantes
            if (is_array($variants)) {
                $variantNames = array_filter(array_column($variants, 'name'));
                $product->variants()->whereNotIn('name', $variantNames)->delete();

                foreach ($variants as $vData) {
                    $product->variants()->updateOrCreate(
                        ['name' => $vData['name']],
                        [
                            'sku' => $vData['sku'] ?? null,
                            'barcode' => $vData['barcode'] ?? null,
                            'price' => isset($vData['price']) ? (string) $vData['price'] : null,
                            'cost' => isset($vData['cost']) ? (string) $vData['cost'] : null,
                            'is_active' => $vData['is_active'] ?? true,
                        ]
                    );
                }
            }

            // Guardar modificadores y opciones
            if (is_array($modifiers)) {
                $modifierNames = array_filter(array_column($modifiers, 'name'));
                $product->modifiers()->whereNotIn('name', $modifierNames)->delete();

                foreach ($modifiers as $modData) {
                    $modifier = $product->modifiers()->updateOrCreate(
                        ['name' => $modData['name']],
                        [
                            'required' => $modData['required'] ?? false,
                            'multiselect' => $modData['multiselect'] ?? false,
                            'min_options' => $modData['min_options'] ?? 0,
                            'max_options' => $modData['max_options'] ?? 0,
                        ]
                    );

                    $options = is_array($modData['options'] ?? null) ? $modData['options'] : [];
                    $optionNames = array_filter(array_column($options, 'name'));
                    $modifier->options()->whereNotIn('name', $optionNames)->delete();

                    foreach ($modData['options'] ?? [] as $optData) {
                        $modifier->options()->updateOrCreate(
                            ['name' => $optData['name']],
                            [
                                'price' => isset($optData['price']) ? (string) $optData['price'] : '0.00',
                                'cost' => isset($optData['cost']) ? (string) $optData['cost'] : '0.00',
                                'is_active' => $optData['is_active'] ?? true,
                            ]
                        );
                    }
                }
            }

            // Guardar combos
            if (is_array($combos)) {
                $product->combos()->delete();

                foreach ($combos as $comboData) {
                    $childProduct = Product::query()
                        ->where('company_id', $company->getKey())
                        ->where('public_id', $comboData['child_product_id'])
                        ->first();

                    if ($childProduct && $childProduct->getKey() !== $product->getKey()) {
                        $product->combos()->create([
                            'child_product_id' => $childProduct->getKey(),
                            'quantity' => $comboData['quantity'],
                            'extra_price' => isset($comboData['extra_price']) ? (string) $comboData['extra_price'] : '0.00',
                        ]);
                    }
                }
            }

            return $product->load(['inventorySetting', 'variants', 'modifiers.options', 'combos.child']);
        });
    }
}
