<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\ElfProfileResource;
use App\Models\Elf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // 帳號不存在與密碼錯誤回同一則訊息，避免被拿來探測哪些編號存在
    public function login(LoginRequest $request)
    {
        $elf = Elf::with('department')->where('number', $request->validated('number'))->first();

        if (! $elf || ! Hash::check($request->validated('password'), $elf->password)) {
            return response()->json(['message' => '帳號或密碼錯誤'], 401);
        }

        // 明碼只回傳一次，資料庫只存雜湊；重新登入會讓舊權杖失效
        $token = Str::random(60);
        $elf->forceFill(['api_token' => hash('sha256', $token)])->save();

        return response()->json([
            'token' => $token,
            'elf' => new ElfProfileResource($elf),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->forceFill(['api_token' => null])->save();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new ElfProfileResource($request->user()->load('department'));
    }
}
