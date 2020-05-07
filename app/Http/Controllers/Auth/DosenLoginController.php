<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Controller;
use Auth;

class DosenLoginController extends Controller
{
    public function __construct(){
        $this->middleware('guest:dosen',['except'=>['logout']]);
    }

    public function showLoginForm(){
        if(session('gagal')){
            Alert::error('Login Gagal','Terdapat Kesalahan Pada Email atau Password Anda');
        }
        return view('auth.dosen-login');
    }

    public function login(Request $request){
        //Validate the form data
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        //Attempt to log the user in
        if(Auth::guard('dosen')->attempt(['email'=>$request->email,'password'=>$request->password],$request->remember)){
            //If succesfull, then redirect to their intended location
            return redirect()->intended(route('dosen.dashboard'));
        }

        //If unsuccesfull, then redirect back to the login with the form data
        return redirect()->back()->withInput($request->only('email','remember'))->with('gagal','Gagal Ditambahkan!');

    }

    public function logout()
    {
        Auth::guard('dosen')->logout();

        return redirect('/');
    }
}
