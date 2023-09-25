<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    protected $signature = 'check:low-stock';
    protected $description = 'Check for products with 5 or fewer items in stock';

    public function handle()
    {
        // Retrieve products with a quantity of 5 or less
        $lowStockProducts = Product::where('quantity', '<=', 5)->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No products with 5 or fewer items in stock.');
        } else {
            $this->info('Products with 5 or fewer items in stock:');
            foreach ($lowStockProducts as $product) {
                $this->info("Product Name: {$product->name}, Quantity: {$product->quantity}");
            }
        }
    }
}
