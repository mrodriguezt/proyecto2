<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});
Auth::routes();

Route::get('ats/', [
    'as'=>'ats',
    'uses'=>'AtsController@ats'
]);
Route::get('subirFacturas/', [
    'as'=>'subir.facturas',
    'uses'=>'FacturasController@subir'
]);
Route::post('getAts/', [
    'as'=>'getAts',
    'uses'=>'AtsController@getAts'
]);
Route::post('subirXML/', [
    'as'=>'subirXML',
    'uses'=>'FacturasController@subirXML'
]);
