<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse {

    $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required','string','min:8','regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-=+]).+$/'],
            'c_password' => 'required|same:password',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $input = $request->only(['name', 'email', 'password']);
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
    
    $success['token'] =  $user->createToken('MyApp')->plainTextToken;
    $success['name'] =  $user->name;
    
    return response()->json([
        'data' => $success,
        'links' => [
            'self' => [
                'href' => url('/api/register'),
                'method' => 'POST'
            ],
            'all_books' => [
                'href' => url('/api/books'),
                'method' => 'GET'
                ]
            ]
        ]);
    }

    public function login(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) { 
            return response()->json(['error' => ['Unauthorised']], 403);
        }

        $user = Auth::user(); 

    // $user->tokens()->delete(); // decidere se eliminare i vecchi token
    // $success['token'] = $user->createToken('MyApp', ['*'], now()->addDays(7))->plainTextToken; // Scade tra 7 giorni (esempio)
    $success['token'] =  $user->createToken('MyApp')->plainTextToken; 
    $success['name'] =  $user->name;
    
    return response()->json([
        'data' => $success,
        'links' => [
            'self' => [
                'href' => url('/api/login'),
                'method' => 'POST'
            ],
            'all_books' => [
                'href' => url('/api/books'),
                'method' => 'GET'
                ]
            ]
        ]);
    }
                            
    // UNSECURE
    // ONLY A DEMO, NOT WORKING
    // API4:2023 Unrestricted Resource Consumption
    public function passwordRecovery(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($user = User::where('email', $request->email)->first()) {
            Log::info('Password recovery requested for email', ['email' => $request->email]);
            // Qui andrebbe inviata la vera richiesta di reset o SMS in modo sicuro.
        }

        return response()->json([
            'message' => 'If the email exists, recovery instructions have been sent.',
            'links' => [
                'self' => [
                    'href' => url('/api/login'),
                    'method' => 'POST'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }
                                    
    public function getUserInfo(Request $request) {
        if (!$user = Auth::user()) {
            return response()->json(['error' => ['Unauthorised']], 403);
        }

        return response()->json([
            'data' => $user,
            'links' => [
                'self' => [
                    'href' => url('/api/user'),
                    'method' => 'GET'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                ]
            ]
        ]);
    }
                                            
    public function updateEmail(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,'.Auth::id(),
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 422);
        }
        // SECURE
        $user = Auth::user();
        
        // UNSECURE
        // $user = User::findOrFail($request->user_id); // sent user_id in request body 
        
        $user->email = $request->email;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully.',
            'links' => [
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                    ]
                    ]
                ]);
    }
}
                                                