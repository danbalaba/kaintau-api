<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function history(Request $request)
    {
        $orders = Order::with(['items.menuItem'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.menuItem'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'total_price' => $request->total_price,
                    'status' => 'pending',
                    'payment_method' => $request->payment_method ?? 'cash',
                    'payment_status' => 'pending'
                ]);

                \App\Models\Notification::create([
                    'user_id' => $request->user()->id,
                    'title' => 'Order Placed Successfully!',
                    'message' => 'Your order #' . $order->id . ' for PHP ' . number_format((float) $order->total_price, 2) . ' has been successfully submitted and is pending review.',
                    'type' => 'order',
                    'is_read' => false
                ]);

                foreach ($request->items as $item) {
                    $menuItem = \App\Models\MenuItem::findOrFail($item['menu_item_id']);
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'price' => $menuItem->price
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order placed successfully!',
                    'order_id' => $order->id
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to place order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all customer orders (Admin access)
     */
    public function index(Request $request)
    {
        $orders = Order::with(['items.menuItem', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Update order status and trigger notifications (Admin access)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,preparing,ready,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::findOrFail($id);
            $order->status = $request->status;

            if ($request->status === 'completed') {
                $order->payment_status = 'paid';
            }

            $order->save();

            // Prepare notification details
            $messages = [
                'pending' => 'Your order #' . $order->id . ' is now pending review.',
                'preparing' => 'Your order #' . $order->id . ' is now being prepared in the kitchen.',
                'ready' => 'Your order #' . $order->id . ' is ready for pickup! Please proceed to the counter.',
                'completed' => 'Your order #' . $order->id . ' has been picked up. Thank you for dining with KainTAU!',
                'cancelled' => 'Your order #' . $order->id . ' has been cancelled.'
            ];
            $messageText = $messages[$request->status] ?? 'Your order #' . $order->id . ' status has changed to ' . $request->status . '.';

            \App\Models\Notification::create([
                'user_id' => $order->user_id,
                'title' => 'Order Status Updated!',
                'message' => $messageText,
                'type' => 'order',
                'is_read' => false
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully!',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get archived (soft-deleted) orders for the current user or all if admin.
     */
    public function getArchivedOrders(Request $request)
    {
        $query = Order::onlyTrashed()->with(['items.menuItem', 'user']);
        
        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }
        
        $orders = $query->orderBy('deleted_at', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Soft-delete (archive) an order.
     */
    public function archive(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        if ($request->user()->role !== 'admin' && $order->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }
        
        $order->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order archived successfully.'
        ]);
    }

    /**
     * Restore an archived order.
     */
    public function restore(Request $request, $id)
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        
        if ($request->user()->role !== 'admin' && $order->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }
        
        $order->restore();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order restored successfully.'
        ]);
    }

    /**
     * Permanently delete (force delete) an order from the database.
     * Enforces the rule: order must be archived (soft-deleted) first!
     */
    public function forceDelete(Request $request, $id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        
        if ($request->user()->role !== 'admin' && $order->user_id !== $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }
        
        if (!$order->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete active order. You must archive it first.'
            ], 400);
        }
        
        $order->forceDelete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order permanently deleted from archive.'
        ]);
    }
}
