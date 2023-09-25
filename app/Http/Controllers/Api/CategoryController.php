<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Validator;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ], 200);
    }
    public function create(Request $request)
    {
        // Validate the request data for creating a category
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity status code indicates validation errors
        }

        // Create a new category
        $category = new Category();
        $category->name = $request->input('category_name');

        // Upload and store the category image in the public disk's category_images directory
        $categoryImage = $request->file('category_image');
        $categoryImageName = time() . '_' . $categoryImage->getClientOriginalName();
        $categoryImage->storeAs('public/category_images', $categoryImageName);
        $category->image_url = $categoryImageName ?? null;

        $category->save();
        $imagePath = 'storage/category_images/' . $categoryImageName;

        // Return a success API response
        return response()->json([
            'data' => $category,
            'message' => 'Category created successfully',
            'image_url' =>$imagePath
        ], 201);
    }
    public function update(Request $request, $id)
    {
        // Validate the request data for updating a category
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity status code indicates validation errors
        }

        // Update the category details
        try {
            $category = Category::findOrFail($id);
            $category->name = $request->input('category_name');

            if ($request->hasFile('category_image')) {
                // Check if there is an old image, and delete it
                if ($category->image_url) {
                    // Construct the full path to the old image
                    $oldImagePath = public_path('storage/category_images/' . $category->image_url);

                    // Check if the old image file exists before attempting to delete it
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Upload and store the new category image in the public disk's category_images directory
                $newCategoryImage = $request->file('category_image');
                $newCategoryImageName = time() . '_' . $newCategoryImage->getClientOriginalName();
                $newCategoryImage->storeAs('public/category_images', $newCategoryImageName);
                $category->image_url = $newCategoryImageName;
            }

            $category->save();

            // Construct the image path in the public directory
            $imagePath = 'storage/category_images/' . $category->image_url;

            // Return a success API response
            return response()->json([
                'data' => $category,
                'message' => 'Category updated successfully',
                'image_url' => $imagePath,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    }
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            $imagePath = 'storage/category_images/' . $category->image_url;
            return response()->json([
                'data' => $category,
                'message' => 'Category retrieved successfully',
                'imagePath' => $imagePath
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }
    }
    public function destroy($id)
    {
        try {
            // Find the category by ID
            $category = Category::findOrFail($id);

            // Delete the category image file if it exists
            if ($category->image_url) {
                $imagePath = storage_path('app/public/category_images/') . $category->image_url;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            // Delete the category
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    }
    public function getCategoriesWithProducts()
    {
        // Retrieve the top 5 categories with the most products using a subquery
        $categoriesWithProducts = Category::select('categories.*')
            ->selectSub(function ($query) {
                $query->selectRaw('count(*)')
                    ->from('products')
                    ->whereColumn('products.category_id', 'categories.id');
            }, 'product_count')
            ->orderByDesc('product_count')
            ->take(5)
            ->get();

        return response()->json([
            'data' => $categoriesWithProducts,
            'message' => 'Top 5 categories with the most products retrieved successfully'
        ], 200);
    }
}
