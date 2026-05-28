<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     * Use stateless() since this is a REST API with a mobile frontend.
     */
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google')->stateless();
        // Bypass SSL verification for local development
        $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

        return response()->json([
            'status' => 'success',
            'url' => $driver->redirect()->getTargetUrl(),
        ]);
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google')->stateless();
            // Bypass SSL verification for local development
            $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            
            $googleUser = $driver->user();

            // Find existing user or create a new one
            $user = User::firstOrCreate(
                [
                    'email' => $googleUser->getEmail(),
                ],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(24)), // Random secure password
                    'role' => 'student', // Default role for OAuth users
                ]
            );

            // Generate JWT Token
            $token = JWTAuth::fromUser($user);

            // Redirect back to frontend with the token
            // Assuming the frontend app will capture this from the URL query params
            return redirect()->to(env('FRONTEND_URL', 'http://localhost:8100') . '/login?token=' . $token . '&user=' . urlencode(json_encode($user)));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Auth Error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->to(env('FRONTEND_URL', 'http://localhost:8100') . '/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Verify Google idToken from native mobile app.
     */
    public function verifyGoogleToken(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            // Verify token using Google's tokeninfo endpoint (disable SSL verify for local dev environments)
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $request->id_token
            ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Google token'
                ], 401);
            }

            $googleUser = $response->json();

            // Ensure the token has an email and is verified (if Google provides email_verified)
            if (!isset($googleUser['email'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not retrieve email from Google'
                ], 400);
            }

            // Find existing user or create a new one
            $user = User::firstOrCreate(
                [
                    'email' => $googleUser['email'],
                ],
                [
                    'name' => $googleUser['name'] ?? explode('@', $googleUser['email'])[0],
                    'google_id' => $googleUser['sub'] ?? null,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'student',
                ]
            );

            // Update google_id if not set previously
            if (!$user->google_id && isset($googleUser['sub'])) {
                $user->update(['google_id' => $googleUser['sub']]);
            }

            // Generate JWT Token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Token Verify Error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify Google token: ' . $e->getMessage()
            ], 500);
        }
    }
}
