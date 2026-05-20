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
}
