<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::pattern('lang', '(ja|en)');
Route::get('/{lang}/{area}', array('uses' => 'IndexController@index'));
Route::get('/station/{lang}/{area}', array('uses' => 'StationController@index'));
Route::get('/station/lists/{lang}/{area}', array('uses' => 'StationController@lists'));
Route::get('/station/{station}/{lang}/{area}', array('uses' => 'StationController@result'));
Route::get('/area/{lang}/{area}', array('uses' => 'AreaController@index'));
Route::get('/area/lists/{lang}/{area}', array('uses' => 'AreaController@lists'));
Route::get('/area/{areas}/{lang}/{area}', array('uses' => 'AreaController@result'));
Route::get('/category/{lang}/{area}', array('uses' => 'CategoryController@index'));
Route::get('/category/{categories}/{lang}/{area}', array('uses' => 'CategoryController@result'));
Route::get('/freeword/{lang}/{area}', array('uses' => 'FreewordController@index'));
Route::get('/freeword/{freewords}/{lang}/{area}', array('uses' => 'FreewordController@result'));
Route::get('/detail/{lang}/{area}', array('uses' => 'DetailController@index'));
Route::get('/search/{lang}/{area}', array('uses' => 'SearchController@index'));
Route::get('/privacy/{lang}/{area}', array('uses' => 'PrivacyController@index'));
Route::get('/terms/{lang}/{area}', array('uses' => 'TermsController@index'));
Route::get('/act/{lang}/{area}', array('uses' => 'ActController@index'));
Route::get('/other/{lang}/{area}', array('uses' => 'IndexController@other'));
