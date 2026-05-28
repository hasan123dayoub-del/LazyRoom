<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    //Show All Products In DataBase
    public function index(){
        $products=Product::with('supplier:id,name')->get();
        return response()->json([
        'message'=>'Data received successfully',
        'data'=>$products
        ], 200);
    }
    //Show Products By Category
    public function getProductByCategory($id){
        $category=Category::findOrFail($id);
        $products=$category->products()->with('supplier:id,name')->get();
        return response()->json([
            'message'=>'Data received successfully',
            'data'=>$products,
            'category_name'=>$category->name
        ], 200);
    }

    //Add Product TO DataBase
    public function addProduct(StoreProductRequest $request){
        $validatedData=$request->validated();
        $validatedData['supplier_id']=Auth::user()->id;
        $product= Product::create($validatedData);
        return response()->json([
            'message'=>'Product stored successfully',
            'product'=>$product
        ], 201);
    }

    //Update Product
    public function updateProduct(UpdateProductRequest $request,$id){
     $product= Product::findOrFail($id);
     if (Auth::user()->id!=$product->supplier_id){
        return response()->json([
            'message'=>'Unauthorized'
        ], 403);
     }
      $validatedData=$request->validated();
      $product->update($validatedData);
      return response()->json([
        'message'=>'Product updated successfully',
        'product'=>$product
      ], 200);

    }

    //Delete Product
    public function deleteProduct($id){
        $product=Product::findOrFail($id);
        if (Auth::user()->id!=$product->supplier_id){
            return response()->json([
                'message'=>'Unauthorized'
            ],403);
        }
        $product->delete();
        return response()->json([
            'message'=>'Product deleted successfully'
        ], 200);
    }

    //Show Supplier Products
    public function showSupplierProduct(){
        $supplier_id=Auth::user()->id;
        $products=Product::where('supplier_id', $supplier_id)->get();
        return response()->json([
            'message'=>'Products received sudccseefully',
            'products'=>$products
        ], 200);
    }
}
