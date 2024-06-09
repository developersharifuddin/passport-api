<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RefreshTokenRequest;

class AuthController extends Controller
{
    /**
     * User registration
     */

    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = $request->validated();
        $userData['password'] = bcrypt($userData['password']);
        $userData['email_verified_at'] = now();
        $user = User::create($userData);

        try {

            $response = Http::post('http://localhost/passport-authentication/public/oauth/token', [
                'grant_type' => 'password',
                'client_id' => '9c1ff1b3-c43a-4db3-8e28-c6f36e491b47',  // Your OAuth client ID
                'client_secret' => 'n6K5DW9X5jkGai1rnxl3XCN9ih57VRsVy3pF4TAC',  // Your OAuth client secret
                'username' => $userData['email'],
                'password' => $request->password,
                'scope' => '',
            ]);

            if ($response->failed()) { // use the raw password from the request
                Log::error('OAuth token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'success' => false,
                    'statusCode' => 500,
                    'message' => 'Failed to get access token.',
                ], 500);
            }

            $user['token'] = $response->json();

            return response()->json([
                'success' => true,
                'statusCode' => 201,
                'message' => 'User has been registered successfully.',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('An error occurred during user registration', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getmessage(),
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Attempt to authenticate the user using the provided email and password
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();

                log::info('http://localhost/passport-authentication/public/oauth/token');

                // Make the OAuth token request
                $response = Http::post('http://localhost/passport-authentication/public/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => '9c1ff1b3-c43a-4db3-8e28-c6f36e491b47',  // Your OAuth client ID
                    'client_secret' => 'n6K5DW9X5jkGai1rnxl3XCN9ih57VRsVy3pF4TAC',  // Your OAuth client secret
                    'username' => $request->email,  // Use the email from the request
                    'password' => $request->password,  // Use the password from the request
                ]);

                // $response = Http::post(env('OAUTH_TOKEN_URL'), [
                //     'grant_type' => 'password',
                //     'client_id' => env('OAUTH_CLIENT_ID'),
                //     'client_secret' => env('OAUTH_CLIENT_SECRET'),
                //     'username' => $request->email,
                //     'password' => $request->password,
                // ]);


                // Check if the response from the OAuth server is successful
                if ($response->successful()) {
                    $user['token'] = $response->json();

                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'User has been logged successfully.',
                        'data' => $user,
                    ], 200);
                } else {
                    // Log the OAuth server response for debugging
                    Log::error('OAuth token request failed', ['response' => $response->body()]);

                    return response()->json([
                        'success' => false,
                        'statusCode' => 500,
                        'message' => 'OAuth token request failed.',
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized.',
                    'errors' => 'Unauthorized',
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('An error occurred during user login', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Login user
     *
     * @param  LoginRequest  $request
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Authenticated use info.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error('An error occurred during user login', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * refresh token
     *
     * @return void
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        $response = Http::asForm()->post(env('APP_URL') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_PASSWORD_SECRET'),
            'scope' => '',
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Refreshed token.',
            'data' => $response->json(),
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->token()->revoke();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User logged out successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while logging out.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
