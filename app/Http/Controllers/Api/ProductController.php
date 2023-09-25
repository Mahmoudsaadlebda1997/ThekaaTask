<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;

class ProductController extends Controller
{
    public function getTop5ProductsByCategory($id)
    {
        try {
            // Find the category by ID
            $category = Category::findOrFail($id);

            // Retrieve the top 5 products with the highest prices for the category
            $topProducts = $category->products()
                ->orderByDesc('price')
                ->take(5)
                ->get();

            return response()->json([
                'data' => $topProducts,
                'message' => 'Top 5 products in Price retrieved successfully for the category'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    }

    public function create(Request $request)
    {
        // Create a custom validator
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'quantity' => 'required|integer', // Add validation for quantity
            'category_id' => 'required|exists:categories,id',
            'expired' => 'boolean', // Assuming 'expired' is a boolean field
            'product_images.*' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create a new product
            $product = new Product();
            $product->name = $request->input('product_name');
            $product->price = $request->input('product_price');
            $product->quantity = $request->input('quantity'); // Set the 'quantity' field based on the request
            $product->category_id = $request->input('category_id');
            $product->expired = $request->input('expired', false); // Set the 'expired' field based on the request

            $product->save();

            // Upload and store product images in the public disk's product_images directory
            $productImages = [];
            foreach ($request->file('product_images') as $image) {
                $productName = $product->name; // Get the product name
                $productId = $product->id; // Get the product ID
                $folderName = $productName . '_' . $productId;

                // Generate a unique image name
                $imageName = time() . '_' . $image->getClientOriginalName();

                // Use storePubliclyAs to store in the public folder under the product-specific folder
                $image->storePubliclyAs('product_images/' . $folderName, $imageName, 'public');

                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->image_url = $folderName . '/' . $imageName; // Save the folder name in the image URL
                $productImage->save();
                $productImages[] = $productImage;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => [
                    'product' => $product,
                    'product_images' => $productImages,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product creation failed',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Find the product by ID
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'expired' => 'boolean',
            'product_images.*' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Remove the existing folder of photos for the product
            $folderName = $product->name . '_' . $product->id;
            Storage::disk('public')->deleteDirectory('product_images/' . $folderName);

            // Update product details
            $product->name = $request->input('product_name');
            $product->price = $request->input('product_price');
            $product->quantity = $request->input('quantity');
            $product->category_id = $request->input('category_id');
            $product->expired = $request->input('expired', false);
            $product->save();

            // Upload and store new product images
            $productImages = [];
            foreach ($request->file('product_images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storePubliclyAs('product_images/' . $folderName, $imageName, 'public');
                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->image_url = $folderName . '/' . $imageName;
                $productImage->save();
                $productImages[] = $productImage;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => [
                    'product' => $product,
                    'product_images' => $productImages,
                ],
            ], 200);
        } catch (\Exception $e) {
//            dd($e);
            return response()->json([
                'status' => 'error',
                'message' => 'Product update failed',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            // Find the product by ID
            $product = Product::where('id', $id)->with('category', 'images')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Product retrieved successfully',
                'data' => $product,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }
    }

    public function deleteExpiredProducts()
    {
        try {
            // Get all expired products
            $expiredProducts = Product::where('expired', true)->get();
            $deletedCount = 0;
            foreach ($expiredProducts as $product) {
                // Remove the product images folder
                $folderName = $product->name . '_' . $product->id;
                Storage::disk('public')->deleteDirectory('product_images/' . $folderName);

                // Delete the product
                $product->delete();
                $deletedCount++;
            }
            return response()->json([
                'status' => 'success',
                'message' => "$deletedCount expired products and their images deleted successfully",
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete expired products and their images',
            ], 500);
        }
    }

}
