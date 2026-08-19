<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgrochemicalProductRequest;
use App\Models\AgrochemicalProduct;
use Illuminate\Http\Request;

class AgrochemicalProductController extends Controller
{
    /**
     * Display all agrochemical products.
     * Supports search and filtering by input type.
     */
    public function index(Request $request)
    {
        $query = AgrochemicalProduct::query();

        if ($request->filled('search')) {
    $search = $request->search;

    $query->where(function ($q) use ($search) {
        $q->where('product_name', 'like', "%{$search}%")
            ->orWhere('brand_name', 'like', "%{$search}%")
            ->orWhere('active_ingredient', 'like', "%{$search}%");
    });
}

        if ($request->filled('input_type')) {
            $query->where(
                'input_type',
                $request->input_type
            );
        }

        $products = $query
            ->orderBy('product_name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Agrochemical products retrieved successfully.',
            'data' => $products,
        ]);
    }

    /**
     * Add a new agrochemical product.
     */
    public function store(StoreAgrochemicalProductRequest $request)
    {
        $product = AgrochemicalProduct::create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Agrochemical product created successfully.',
            'data' => $product,
        ], 201);
    }

    /**
     * Display one product.
     */
    public function show(AgrochemicalProduct $agrochemicalProduct)
    {
        return response()->json([
            'success' => true,
            'data' => $agrochemicalProduct,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(
        StoreAgrochemicalProductRequest $request,
        AgrochemicalProduct $agrochemicalProduct
    ) {
        $agrochemicalProduct->update(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Agrochemical product updated successfully.',
            'data' => $agrochemicalProduct->fresh(),
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(
        AgrochemicalProduct $agrochemicalProduct
    ) {
        $agrochemicalProduct->delete();

        return response()->json([
            'success' => true,
            'message' => 'Agrochemical product deleted successfully.',
        ]);
    }
}