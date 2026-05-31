<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /** List all accounts filtered by role (supplier or customer). */
    public function listAccounts(Request $request)
    {
        $validator = Validator::make($request->only('type'), [
            'type' => 'required|in:supplier,customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing account type. Please specify "supplier" or "customer".'
            ], 400);
        }

        $type = $request->query('type');

        $users = User::where('role', $type)
            ->select('id', 'name', 'email', 'role', 'balance', 'created_at','is_banned')
            ->get();

        return response()->json([
            'status' => 'success',
            'type' => $type,
            'count' => $users->count(),
            'data' => $users
        ]);
    }
    /** Create a new user account with specified role and details. */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email|max:255',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['customer', 'supplier', 'admin'])],
            'room_number' => 'required_if:role,customer,supplier|string|nullable',
            'building' => 'required_if:role,customer,supplier|string|nullable',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'room_number' => $request->role === 'admin' ? null : $request->room_number,
            'building' => $request->role === 'admin' ? null : $request->building,
            'balance' => $request->has('balance') ? $request->balance : 0.00,
        ]);

        return response()->json([
            'message' => 'User Created Successfully By Admin',
            'user' => $user
        ], 201);
    }
    /** Ban a user and revoke their active authentication tokens. */
    public function banUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot ban your own account.'
            ], 400);
        }

        $user->update(['is_banned' => true]);

        $user->tokens()->delete();

        return response()->json([
            'message' => "The user {$user->name} has been banned successfully and removed from the system.",
            'user' => $user
        ], 200);
    }
    /** Lift the ban from a specific user account. */
    public function unbanUser($id)
    {
        $user = User::findOrFail($id);

        $user->update(['is_banned' => false]);

        return response()->json([
            'message' => "The ban on user {$user->name} has been lifted successfully, and they can now use the application.",
            'user' => $user
        ], 200);
    }
    /** Permanently delete a user account and revoke all access. */
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account.'
            ], 400);
        }
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'The account and all associated data have been successfully and permanently deleted.'
        ], 200);
    }
    /** Admin withdraws a specific amount from a supplier's balance. */
    public function withdrawFromSupplier(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized: Only admins can perform this action.'], 403);
        }

        $supplier = User::where('id', $request->supplier_id)
            ->where('role', 'supplier')
            ->first();

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        if ($supplier->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        $supplier->decrement('balance', $request->amount);

        return response()->json([
            'message' => 'Withdrawal successful.',
            'supplier' => $supplier->name,
            'new_balance' => (float) $supplier->refresh()->balance
        ]);
    }
    /** Admin deposits a specific amount to a customer's balance. */
    public function depositToCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'amount'      => 'required|numeric|min:1',
        ]);

        $customer = User::find($request->customer_id);

        if ($customer->role !== 'customer') {
            return response()->json(['message' => 'Invalid user target.'], 400);
        }

        $customer->increment('balance', $request->amount);

        return response()->json([
            'message' => 'Deposit to customer successful.',
            'customer_new_balance' => (float) $customer->refresh()->balance
        ]);
    }
}
