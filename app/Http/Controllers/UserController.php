<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function changeUser(Request $request)
    {

        $user = User::where('username','=',$request->input('cambiarUsuario'))->get()->first();
        auth()->login($user);
        //return redirect()->route('/');
        return view('index');

    }
}
