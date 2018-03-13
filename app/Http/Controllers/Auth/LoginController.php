<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Adldap\Laravel\Facades\Adldap;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function username()
    {
        return 'samaccountname'; //username
    }
     public function samaccountname()
    {
        return 'samaccountname'; //username
    }
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function login(){
        $input = Input::all();

        $username=$input['username'];
        $password=$input['password'];
        $credentials = [
            'username' => $username,
            'password' => $password
        ];
        if(Auth::attempt($credentials)) {
            $user = Auth::user();
            return view('index');
        }
        return 'fail';
    }
    /*public function username()
    {
        return 'username';
        $input = Input::all();

        $username=$input['username'];
        $password=$input['password'];

        Auth::attempt(['username' => $username, 'password' => $password]);


dd(Auth::check());

        if (Auth::check()) {

            if (Auth::attempt($username, $password)) {
                // Aqui necesito crear las sesiones
                dd(Adldap::auth()->user());
            }else{
               // return redirect()->route('login');
                dd(Adldap::auth()->user());
            }
        }else{
            dd("NO VALE");
        }
    }*/

}
