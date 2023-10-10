<?php

namespace App\Http\Controllers;

use App\Mail\VerifyAccount;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8'
        ]);
        DB::beginTransaction();
        try{
            if(!$validator->fails()){
                //3-student, 4-guest
                $groupId = strpos($request->email, '@fpt.edu.vn') ? 3 : 4;
                $codeVerify = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                DB::table('users')->insert(
                    [
                        'username' => $request->username,
                        'password' => Hash::make($request->password),
                        'email' => $request->email,
                        'group_id' => $groupId,
                        'status' => 0, //lock
                        'verification_code' => $codeVerify,
                    ]
                );
                Mail::to($request->email)->send(new VerifyAccount($codeVerify));
                DB::commit();
                return response()->json(['message' => 'Đăng ký thành công'], 201);
            }else{
                return response()->json(['errors' => $validator->errors()], 422);
            }
        }catch(\Exception $e){
            DB::rollback();
            // Log::alert($e);
            return response()->json(['errors' => $e->getMessage()], 400);
        }
    }
    public function verify(Request $request){
        $user = User::where('email', $request->email)->first();
        if($request->verification_code == $user->verification_code){
            $user->update(['status' => 1]);
        }else{
            return response()->json(['message' => 'Mã xác nhận không chính xác'], 403);
        }
    }
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);
        if(!$validator->fails()){
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
                $user = Auth::user();
                if($user->status == 0){
                    return response()->json(['message' => 'Tài khoản chưa được kích hoạt'], 403);
                }
                $token = $user->createToken('authToken')->accessToken;
                return response()->json(['user' => $user, 'accessToken' => $token], 200);
            }else{
                return response()->json(['message' => 'Đăng nhập thất bại'], 400);
            }
        }else{
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }
    public function logout(Request $request){
        $user = Auth::user();
        $user->tokens->each(function ($token) {
            $token->delete();
        });
        return response()->json(['message' => 'Đăng xuất thành công'], 200);
    }
    public function googleAuth(){
        return Socialite::driver('google')->stateless()->redirect();
    }
    public function googleCallback(){
        $user = Socialite::driver('google')->stateless()->user();
        $checkUser = User::where('email', $user->email)->first();
        if($checkUser){
            //đăng nhập 
            if($checkUser->status == 0){
                return response()->json(['message' => 'Tài khoản chưa được kích hoạt'], 403);
            }else{
                Auth()->login($checkUser);
                $token = $checkUser->createToken('authToken')->accessToken;
                return response()->json(['user' => $user, 'accessToken' => $token], 200);
            }
        }else{
            // dd($user);
            //đăng ký
            DB::beginTransaction();
            try{
                $username = explode('@', $user->email)[0];
                // dd(config('default.password'));
                DB::table('users')->insert(
                    [
                        'username' => $username,
                        'password' => Hash::make(config('default.password')),
                        'email' => $user->email,
                        'group_id' => 3,
                        'status' => 1, //lock
                    ]
                );
                DB::commit();
                $checkUser = User::where('email', $user->email)->first();
                Auth()->login($checkUser);
                $token = $checkUser->createToken('authToken')->accessToken;
                return response()->json(['user' => $user, 'accessToken' => $token], 200);
            }catch(\Exception $e){
                DB::rollback();
                // Log::alert($e);
                return response()->json(['errors' => $e->getMessage()], 400);
            }
        }
    }
}
