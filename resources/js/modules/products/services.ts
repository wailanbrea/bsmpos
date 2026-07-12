import { api } from '../../lib/api';
import type { ApiEnvelope } from '../auth/types';
import type { Category, Product, ProductForm } from './types';

export async function fetchProducts(search: string): Promise<Product[]> {
    const response = await api.get<ApiEnvelope<Product[]>>('/products', {
        params: search ? { search } : undefined,
    });
    return response.data.data;
}

export async function fetchCategories(): Promise<Category[]> {
    const response = await api.get<ApiEnvelope<Category[]>>('/categories', {
        params: { kind: 'product' },
    });
    return response.data.data;
}

export async function createCategory(name: string): Promise<Category> {
    const response = await api.post<ApiEnvelope<Category>>('/categories', { name, kind: 'product' });
    return response.data.data;
}

function toPayload(form: ProductForm): Record<string, unknown> {
    return {
        name: form.name,
        sku: form.sku || null,
        barcode: form.barcode || null,
        category_id: form.category_id,
        price: Number(form.price) || 0,
        cost: Number(form.cost) || 0,
        track_inventory: form.track_inventory,
        available_pos: form.available_pos,
        variants: form.variants || undefined,
        modifiers: form.modifiers || undefined,
        combos: form.combos || undefined,
    };
}

export async function createProduct(form: ProductForm): Promise<Product> {
    const response = await api.post<ApiEnvelope<Product>>('/products', toPayload(form));
    return response.data.data;
}

export async function updateProduct(id: string, form: ProductForm): Promise<Product> {
    const response = await api.patch<ApiEnvelope<Product>>(`/products/${id}`, toPayload(form));
    return response.data.data;
}

export async function uploadProductImage(id: string, file: File): Promise<Product> {
    const data = new FormData();
    data.append('image', file);
    const response = await api.post<ApiEnvelope<Product>>(`/products/${id}/image`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data.data;
}

export async function deleteProductImage(id: string): Promise<Product> {
    const response = await api.delete<ApiEnvelope<Product>>(`/products/${id}/image`);
    return response.data.data;
}
