<?php

namespace Modules\JerryUpdates\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiAuthController extends Controller
{
    /**
     * VERCEL NEXT.JS API: Login and issue personal access token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                return response()->json(['success' => false, 'message' => 'User account is inactive.'], 403);
            }
            
            // Create Passport Token
            $tokenResult = $user->createToken('TradexHeadlessPOS');
            $token = $tokenResult->token;
            // You can set expiration here if needed
            // $token->expires_at = Carbon::now()->addWeeks(1);
            $token->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'username' => $user->username,
                        'email' => $user->email,
                    ]
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    /**
     * VERCEL NEXT.JS API: Fetch authenticated user's profile and permissions.
     */
    public function profile(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Fetch user's permitted locations
        $permitted_locations = [];
        if ($user->hasPermissionTo('access_all_locations')) {
            $permitted_locations = DB::table('business_locations')
                ->where('business_id', $user->business_id)
                ->where('is_active', 1)
                ->select('id', 'name', 'location_id')
                ->get();
        } else {
            // Find permitted locations from permissions
            $permissions = $user->permissions->pluck('name')->toArray();
            $location_ids = [];
            foreach ($permissions as $permission) {
                if (preg_match('/location\.(\d+)/', $permission, $matches)) {
                    $location_ids[] = $matches[1];
                }
            }
            if (!empty($location_ids)) {
                $permitted_locations = DB::table('business_locations')
                    ->where('business_id', $user->business_id)
                    ->where('is_active', 1)
                    ->whereIn('id', $location_ids)
                    ->select('id', 'name', 'location_id')
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'business_id' => $user->business_id,
                ],
                'locations' => $permitted_locations,
                'roles' => $user->roles->pluck('name'),
                // Could include permissions if needed by frontend
            ]
        ]);
    }

    /**
     * VERCEL NEXT.JS API: Logout and revoke token.
     */
    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $request->user()->token()->revoke();
            return response()->json(['success' => true, 'message' => 'Successfully logged out.']);
        }
        return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
    }
}
