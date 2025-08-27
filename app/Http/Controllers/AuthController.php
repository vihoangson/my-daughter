<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('api')->plainTextToken;

        // Determine redirect path based on user type
        $redirectPath = $user->type === 'child' ? '/user-kid' : '/';

        return response()->json([
            'token' => $token,
            'user' => $user,
            'redirect_path' => $redirectPath
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function simpleLogin(Request $request)
    {
        $credentials = $request->validate([
            'password' => 'required|string|size:6',
            'type' => 'required|string|in:parent,child'
        ]);

        // Find user by numeric password and type
        $user = User::where('numeric_password', $credentials['password'])
                   ->where('type', $credentials['type'])
                   ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        // Determine redirect path based on user type
        $redirectPath = $user->type === 'child' ? '/user-kid' : '/';

        return response()->json([
            'token' => $token,
            'user' => $user,
            'redirect_path' => $redirectPath
        ]);
    }
}
