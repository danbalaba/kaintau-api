<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get all categories with items
     */
    public function categories()
    {
        return response()->json([
            'status' => 'success',
            'data' => Category::all()
        ]);
    }

    /**
     * Get menu items (with filtering)
     */
    public function index(Request $request)
    {
        $query = MenuItem::with('category')->where('is_available', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_popular')) {
            $query->where('is_popular', true);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }
}
