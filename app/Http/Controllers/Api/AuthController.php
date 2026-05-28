<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                Password::min(10)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $input = $request->all();
            $input['name'] = strip_tags($input['name']); // Sanitize stored XSS
            
            $data = $this->authService->register($input);
            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $data
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Authenticate a user
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            $data = $this->authService->login($credentials);

            // Intercept unverified accounts and block access
            if (isset($data['status']) && $data['status'] === 'unverified') {
                return response()->json([
                    'status' => 'unverified',
                    'message' => $data['message'],
                    'email' => $data['email']
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }
    }

    /**
     * Verify registered email OTP and activate account
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $this->authService->verifyEmailOtp($request->only('email', 'otp'));

            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully! Your account is now active.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'data' => auth()->user()
        ]);
    }

    /**
     * Send password reset OTP
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $this->authService->sendResetOtp($request->email);
            
            return response()->json([
                'status' => 'success',
                'message' => 'A 6-digit OTP verification code has been sent to your email.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Verify Password Reset OTP and generate a signed reset token
     */
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $this->authService->verifyResetOtp($request->only('email', 'otp'));

            return response()->json([
                'status' => 'success',
                'message' => 'Identity verified successfully! You can now reset your password.',
                'reset_token' => $data['reset_token']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reset the user's password using the verified reset token
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reset_token' => 'required|string',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(10)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->authService->resetPasswordWithToken($request->only(
                'email', 'password', 'password_confirmation', 'reset_token'
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|nullable|string|max:255',
            'department' => 'sometimes|nullable|string|max:255',
            'student_id' => 'sometimes|nullable|string|max:255',
            'avatar_url' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'department', 'student_id', 'avatar_url']));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Upload and store avatar image
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            
            try {
                // Initialize Cloudinary with the URL from .env
                $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
                
                // Upload the image to Cloudinary
                $uploadResult = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'kaintau_avatars',
                    'transformation' => [
                        'width' => 400,
                        'height' => 400,
                        'crop' => 'fill',
                        'gravity' => 'face'
                    ]
                ]);
                
                // Get the secure HTTPS URL from Cloudinary
                $url = $uploadResult['secure_url'];

                // Update user's database record instantly
                $user = auth()->user();
                $user->update(['avatar_url' => $url]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Avatar uploaded to Cloudinary successfully',
                    'url' => $url,
                    'data' => $user
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload image to Cloudinary: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No file uploaded'
        ], 400);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        \Tymon\JWTAuth\Facades\JWTAuth::invalidate(\Tymon\JWTAuth\Facades\JWTAuth::getToken());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ]);
    }
}
