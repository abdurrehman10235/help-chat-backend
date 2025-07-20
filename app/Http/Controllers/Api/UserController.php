<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
   public function verifyId(Request $request)
{
    $request->validate([
        'id' => 'required|integer',
    ]);

    $user = DB::table('users')
        ->select('name')
        ->where('id', $request->id)
        ->first();

    if ($user) {
        return response()->json([
            'valid' => true,
            'name' => $user->name,
        ]);
    }

    return response()->json([
        'valid' => false,
    ]);
}
public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
    ]);

    $userId = DB::table('users')->insertGetId([
        'name'       => $request->name,
        'email'      => $request->email,
        'password'   => Hash::make($request->password), // password is required
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = DB::table('users')->where('id', $userId)->first();

    return response()->json([
        'message' => 'User created successfully',
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ],
    ], 201);
}
}

