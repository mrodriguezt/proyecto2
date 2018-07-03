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
Route::group(['middleware'=>'auth'],function(){
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
Route::get('archivo/{nombreArchivoXml}', function ($nombreArchivoXml) {
    return response()->file(public_path()."/facturasProveedores/".$nombreArchivoXml);
})->name('archivo');

Route::get('facturacionElectronica/', [
    'as'=>'facturacion.electronica',
    'uses'=>'FacturacionElectronicaController@facturacionElectronica'
]);
Route::get('gridLayoutFacturacion/', [
    'as'=>'gridLayoutFacturacion',
    'uses'=>'FacturacionElectronicaController@layoutFacturacion'
]);
Route::get('gridDataFacturacion/{fecha_inicio}/{fecha_fin}/{compania}', [
    'as'=>'gridDataFacturacion',
    'uses'=>'FacturacionElectronicaController@dataFacturacion'
]);
Route::post('enviarFactura', [
    'as'=>'enviar.factura',
    'uses'=>'FacturacionElectronicaController@enviarFacturaTandi'
]);

});
