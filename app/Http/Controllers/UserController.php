<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::with(['company:id,name'])->get()
        );
    }

 public function store(Request $request)
{
    $authUser = Auth::user();
    $validated = $request->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'company_id' => 'nullable|exists:companies,id',
        'role' => 'required|string|in:admin,manager,driver',
        'zone_id' => 'nullable|exists:company_zones,id',
    ]);
    if ($authUser->role === 'manager') {
        if ($validated['role'] !== 'driver') {
            return response()->json(['error' => 'Managers can only create drivers'], 403);
        }
        @dd($authUser);
        $validated['company_id'] = $authUser->company_id;
    }

    

    $validated['password'] = Hash::make($validated['password']);
    $validated['created_by'] = $authUser->id;

    $user = User::create($validated);

    return response()->json($user, 201);
}


    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if ($authUser->role === 'manager' && $user->company_id !== $authUser->company_id) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|string|in:super_admin,manager,driver',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        // Manager can only delete drivers in his company
        if ($authUser->role === 'manager' && ($user->company_id !== $authUser->company_id || $user->role !== 'driver')) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getUsersNames()
    {
        $users = User::with(['company:id,name'])
            ->get(['id', 'name', 'company_id']);
        return response()->json($users);
    }
    public function getUsersProfile(Request $request)
{
    // Get the authenticated user from the token
    $user = $request->user(); // This comes from sanctum's auth middleware

    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'company_id' => $user->company_id,
        // add any other fields you need
    ]);
}
}
