<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class SSOController extends Controller
{
    public function getLogin(Request $request)
    {
        // dd($request);
        $state = Str::random(40);
        $request->session()->put("state", $state);
        $query = http_build_query([
            "client_id" => "9818535f-ccb5-4376-a372-0aec1b2f8c37",
            "redirect_uri" => "http://127.0.0.1:8080/callback",
            "response_type" => "code",
            "scope" => "view-user",
            "state" => $state,
        ]);

        return redirect("http://127.0.0.1:8000/oauth/authorize?" . $query);
    }

    public function getCallback(Request $request)
    {
        $state = $request->session()->pull("state");

        throw_unless(strlen($state) > 0 && $state == $request->state, InvalidArgumentException::class);

        $response = Http::asForm()->post("http://127.0.0.1:8000/oauth/token", [
            "grant_type" => "authorization_code",
            "client_id" => "9818535f-ccb5-4376-a372-0aec1b2f8c37",
            "client_secret" => "jln22mzE8wjyXhJ0CZSxs5oyzLNDhyeMLGzN4Va5",
            "redirect_uri" => "http://127.0.0.1:8080/callback",
            "code" => $request->code,
        ]);

        $request->session()->put($response->json());
        return redirect()->route('sso.user.auth');
    }

    public function getUserAuth(Request $request)
    {
        $access_token = $request->session()->get('access_token');
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
        ])->get("http://127.0.0.1:8000/api/user");

        $userArray = $response->json();

        try {
            $email = $userArray['email'];
        } catch (\Throwable $th) {
            return redirect('login')->withErrors("Gagal mengambil informasi login!, Coba beberapa saat lagi");
        }
        $user = User::where('email', '=', $email)->first();
        if (!$user) {
            $user = new User;
            $user->name = $userArray['name'];
            $user->email = $userArray['email'];
            $user->email_verified_at = $userArray['email_verified_at'];
            $user->save();
        }
        Auth::login($user);
        // dd(Session::all());
        return redirect('home');
    }
}
