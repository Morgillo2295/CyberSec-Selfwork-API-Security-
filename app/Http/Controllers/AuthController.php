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
        'name' => 'required',
        'email' => 'required|email',
        // SECURE
        'password' => 'required|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-=+])[A-Za-z\d!@#$%^&*()_\-=+]{8,20}$/',  
        'password' => 'required',
        
        'c_password' => 'required|same:password',
    ]);
    
    if($validator->fails()){  
        return response()->json(['error' =>  $validator->errors()], 403);    
    }
    
    $input = $request->all();
    
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
        
    if(!Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
        return response()->json(['error' =>  ['Unauthorised']], 403);    
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
        if(!$user = User::where('email',$request->email)->first()){
            return response()->json(['error' =>  ['Unauthorised']]);
        }
        // Use sms api to send confirmation code to user number
        // $newCode = SMS::generateCode();
        // $user->smsCode = $newCode;
        // $user->save();
        // SMS::send($user->phone, ['Please don't share this code: $user->smsCode']);
        
        return response()->json([
            'data' => 'SMS sent to $user->phone',
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
                                    
    public function getUserInfo(Request $request, $id) {
        // SECURE (manual)
        // $token = $request->bearerToken();
        // 5|mUEqxncaO2zLLKtCSlLQoGeoxkS46FkygBItGRdAd7a0ab93
        $parts = explode('|', $token);
        // array:2 [ // app/Http/Controllers/AuthController.php:115
        //   0 => "5"
        //   1 => "mUEqxncaO2zLLKtCSlLQoGeoxkS46FkygBItGRdAd7a0ab93"
        // ]
        $hashedToken = hash('sha256', $parts[1]);
        // Cerca il token nel database
        $tokenRecord = PersonalAccessToken::where('token', $hashedToken)->first();
        // Recuper l'user
        $user = User::find($tokenRecord->tokenable_id);
        
        if(!$user){
            return response()->json(['error' =>  ['Unauthorised']]);
        }
        
        // SECURE (con Auth)
        if(!$user = Auth::user()){
            return response()->json(['error' =>  ['Unauthorised']]);
        }

        // UNSECURE
        // if(!$user = User::find($id)){
        //     return response()->json(['error' =>  ['User not found']]);
        // };

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
            'email' => 'required|email|unique:users,email',
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
                                                