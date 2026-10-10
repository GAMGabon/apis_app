<?php

use Illuminate\Support\Facades\Route;

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

Route::view('/flyer', 'challenge.flyer');
Route::view('/wifizone', 'challenge.flyer');

Route::get('/',[\App\Http\Controllers\ObjectifController::class, 'getObjectifs'])->name('home');
Route::get('/points',[\App\Http\Controllers\CustomerController::class, 'pointsCustomer'])->name('points');
Route::get('/point',[\App\Http\Controllers\CustomerController::class, 'pointsClients']);
Route::get('/pointMbaya',[\App\Http\Controllers\CustomerController::class, 'pointsClientsMbaya']);// challenge Mbaya
Route::get('/edan',[\App\Http\Controllers\GamElectriciteHistController::class, 'getHist'])->name('edan');
Route::post('/edan',[\App\Http\Controllers\GamElectriciteHistController::class, 'updateHist'])->name('sendData');
Route::get('/lots',[\App\Http\Controllers\LotController::class, 'lotCustomer'])->name('lots');

Route::get('/client',[\App\Http\Controllers\CustomerController::class, 'regiterCustomerWebform'])->name('client');
Route::post('/client',[\App\Http\Controllers\CustomerController::class, 'regiterCustomerWeb'])->name('clientSave');
Route::get('/gamTv',[\App\Http\Controllers\CustomerController::class, 'gamTv'])->name('gamTv');

Route::get('/removecompte',[\App\Http\Controllers\CustomerController::class, 'removecompte'])->name('removecompte');

Route::get('/cgu',[\App\Http\Controllers\CustomerController::class, 'cguPiege'])->name('cgu');

// challenge
Route::get('/challenge',[\App\Http\Controllers\CustomerController::class, 'challenge'])->name('challenge');
Route::get('filleul',[\App\Http\Controllers\ChallengeContoller::class, 'filleul'])->name('filleul');
Route::get('students',[\App\Http\Controllers\ChallengeContoller::class, 'students'])->name('students');
Route::get('boris',[\App\Http\Controllers\ChallengeContoller::class, 'boris'])->name('boris');
Route::get('filleulBack',[\App\Http\Controllers\ChallengeContoller::class, 'filleulBack'])->name('filleulBack');
Route::get('caissier',[\App\Http\Controllers\ChallengeContoller::class, 'caissier'])->name('caissier');
Route::get('validelogin',[\App\Http\Controllers\CustomerController::class, 'valideConnexion']);
Route::get('/rm',[\App\Http\Controllers\ServicesController::class, 'rm'])->name('rm');
Route::get('/storeApp',[\App\Http\Controllers\ServicesController::class, 'storeApp'])->name('storeApp');
Route::get('ville',[\App\Http\Controllers\ChallengeContoller::class, 'challengeVille']);
Route::get('rm_what/{trans}',[\App\Http\Controllers\ServicesController::class, 'rmWhat'])->name('rm_what');
Route::post('/updatedZoneCustomer',[\App\Http\Controllers\ChallengeContoller::class, 'updatedZoneCustomer'])->name('updatedZoneCustomer');



Route::get('caissiere',[\App\Http\Controllers\ChallengeContoller::class, 'caissiere'])->name('caissiere');
Route::get('hotesse',[\App\Http\Controllers\ChallengeContoller::class, 'hotesse'])->name('hotesse');
Route::get('/help',[\App\Http\Controllers\ChallengeContoller::class, 'help'])->name('help');

Route::get('/clientCom',[\App\Http\Controllers\ObjectifController::class, 'clientCom'])->name('clientCom');

Route::get('/classement',[\App\Http\Controllers\ChallengeContoller::class, 'classementFête'])->name('classement');
Route::get('/classementFin',[\App\Http\Controllers\ChallengeContoller::class, 'classementFin'])->name('classementFin');
Route::get('/classementPromo',[\App\Http\Controllers\ChallengeContoller::class, 'classementPromo'])->name('classementPromo');
Route::get('/classementFollow',[\App\Http\Controllers\ChallengeContoller::class, 'classementFollow'])->name('classementFollow');
Route::get('/classementQuiz',[\App\Http\Controllers\ChallengeContoller::class, 'classementQuiz'])->name('classementQuiz');
Route::get('/cadeaux',[\App\Http\Controllers\ChallengeContoller::class, 'cadeaux'])->name('cadeaux');
Route::get('/followers',[\App\Http\Controllers\ChallengeContoller::class, 'followers'])->name('followers');

Route::get('/buyforme',[\App\Http\Controllers\ServicesController::class, 'buyforme'])->name('buyforme');
Route::get('/gamview',[\App\Http\Controllers\ServicesController::class, 'gamview'])->name('gamview');

Route::get('/recapTrans',[\App\Http\Controllers\HistoriquetransController::class, 'recapTrans'])->name('recapTrans');


Route::get('visiteCarte',[\App\Http\Controllers\VisiteController::class, 'visiteCarte']);


