<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|unique:users,email|max:255',
            'password' => 'required|string|min:8',
            'room_number' => 'required|string',
            'building' => 'required|string'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'room_number' => $request->room_number,
            'building' => $request->building,
            'role' => 'customer',
            'balance' => 0.00,
        ]);

        $token = $user->createToken('auth_Token')->plainTextToken;

        return response()->json([
            'message' => 'User Registered Successfully',
            'user' => $user->fresh(),
            'token' => $token
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Error In Your Email OR Password'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->is_banned) {
            Auth::logout();
            return response()->json(['message' => 'This account is banned by the administration, please contact customer support.'], 403);
        }

        $token = $user->createToken('auth_Token')->plainTextToken;

        return response()->json([
            'message' => 'Login Successful',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully, token deleted.'
        ], 200);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Your account has been permanently deleted.'
        ], 200);
    }
}
