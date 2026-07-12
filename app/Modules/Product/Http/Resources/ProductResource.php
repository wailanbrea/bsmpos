<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Resources;

use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Product */
final class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'brand' => $this->brand,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'unit_id' => $this->unit_id,
            'tax_id' => $this->tax?->public_id,
            'price' => $this->price,
            'cost' => $this->cost,
            'image_url' => $this->image_path !== null ? Storage::disk('public')->url($this->image_path) : null,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'available_pos' => $this->available_pos,
            'available_delivery' => $this->available_delivery,
            'available_digital_menu' => $this->available_digital_menu,
            'inventory' => $this->whenLoaded('inventorySetting', fn () => $this->inventorySetting),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($v) => [
                'id' => $v->public_id,
                'name' => $v->name,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'price' => $v->price,
                'cost' => $v->cost,
                'is_active' => $v->is_active,
            ])),
            'modifiers' => $this->whenLoaded('modifiers', fn () => $this->modifiers->map(fn ($m) => [
                'id' => $m->public_id,
                'name' => $m->name,
                'required' => $m->required,
                'multiselect' => $m->multiselect,
                'min_options' => $m->min_options,
                'max_options' => $m->max_options,
                'options' => array_map(static fn ($option): array => [
                    'id' => (string) $option->public_id,
                    'name' => (string) $option->name,
                    'price' => $option->price,
                    'cost' => $option->cost,
                    'is_active' => $option->is_active,
                ], $m->options->all()),
            ])),
            'combos' => $this->whenLoaded('combos', fn () => $this->combos->map(fn ($c) => [
                'child_product_id' => $c->child?->public_id,
                'child_product_name' => $c->child?->name,
                'quantity' => $c->quantity,
                'extra_price' => $c->extra_price,
            ])),
        ];
    }
}
