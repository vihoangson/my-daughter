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
            'password' => 'required|string|size:4',
            'type' => 'required|string|in:parent,child'
        ]);

        // Find user by numeric password and type
        $user = User::where('numeric_password', $credentials['password'])
                   ->where('type', $credentials['type'])
                   ->first();

        // If no user is found, we need to track the failed attempt for this type
        if (!$user) {
            // Get all users of this type to check for lockouts
            $typeUsers = User::where('type', $credentials['type'])->get();

            foreach ($typeUsers as $typeUser) {
                // Check if the user is currently locked out
                if ($typeUser->locked_until && now()->lt($typeUser->locked_until)) {
                    $lockRemainingSeconds = now()->diffInSeconds($typeUser->locked_until);
                    return response()->json([
                        'message' => 'Too many failed attempts. Please try again after ' . $lockRemainingSeconds . ' seconds.',
                        'locked_until' => $typeUser->locked_until,
                        'remaining_seconds' => $lockRemainingSeconds
                    ], 429); // Too Many Requests status code
                }

                // If the lock period has passed, reset the counter
                if ($typeUser->locked_until && now()->gt($typeUser->locked_until)) {
                    $typeUser->login_attempts = 0;
                    $typeUser->locked_until = null;
                    $typeUser->save();
                }

                // Increment the failed attempt counter
                $typeUser->login_attempts++;

                // If failed attempts reach the threshold, lock the account
                if ($typeUser->login_attempts >= 5) {
                    $typeUser->locked_until = now()->addMinute(); // Lock for 1 minute
                }

                $typeUser->save();
            }

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if the user is currently locked out
        if ($user->locked_until && now()->lt($user->locked_until)) {
            $lockRemainingSeconds = now()->diffInSeconds($user->locked_until);
            return response()->json([
                'message' => 'Too many failed attempts. Please try again after ' . $lockRemainingSeconds . ' seconds.',
                'locked_until' => $user->locked_until,
                'remaining_seconds' => $lockRemainingSeconds
            ], 429); // Too Many Requests status code
        }

        // Reset the login attempts counter on successful login
        $user->login_attempts = 0;
        $user->locked_until = null;
        $user->save();

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
