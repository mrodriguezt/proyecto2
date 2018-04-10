<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FacturasController extends Controller
{
    public function subir()
    {
        return view('facturas.index');
    }
    public function subirXML(Request $request)
    {
        if($request->file('image')){
            $file = $request->file('image');
            $content = utf8_encode(file_get_contents($file));
            $xml = simplexml_load_string($content);
            dd($xml);
        }
    }
}
