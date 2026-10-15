<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function register(Request $request){$data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','phone'=>'required|string|max:20','password'=>'required|string|min:8']);$user=User::create($data);return response()->json(['user'=>$user,'token'=>$user->createToken('api')->plainTextToken],201);}
    public function login(Request $request){$data=$request->validate(['email'=>'required|email','password'=>'required|string']);$user=User::where('email',$data['email'])->first();if(!$user||!Hash::check($data['password'],$user->password))throw ValidationException::withMessages(['email'=>'Kredensial tidak valid.']);return response()->json(['user'=>$user,'token'=>$user->createToken('api')->plainTextToken]);}
    public function profile(Request $request){return $request->user()->load('addresses');}
}
