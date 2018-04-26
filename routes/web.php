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
Route::post('validarArchivo/', [
    'as'=>'validarArchivo',
    'uses'=>'FacturasController@validarArchivo'
]);

Route::get('validarFacturas/', [
    'as'=>'validar.facturas',
    'uses'=>'FacturasController@validarFacturas'
]);

Route::get('gridLayoutXML/', [
    'as'=>'grid.layoutXML',
    'uses'=>'FacturasController@layoutXML'
]);
Route::get('gridDataXML/{compania}', [
    'as'=>'grid.dataXML',
    'uses'=>'FacturasController@dataXML'
]);


