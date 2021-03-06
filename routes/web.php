<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Anam\PhantomMagick\Converter;
use App\Events\TestEvent;
// use Illuminate\Routing\Route;
use Illuminate\View\View;
use App\Helpers\Helper;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/cmd/exec/{key?}', 'CmdController@artisanCmd')->name('cmd.exec');

Auth::routes();

// auth:web middleware
Route::group(['middleware' => 'auth'], function(){
  Route::get('/dashboard', 'HomeController@index')->name('home');   // default /home
  Route::get('/users/logout', 'Auth\LoginController@userLogout')->name('user.logout');
  
  //route group prefix operator/ do
  Route::group(['prefix' => 'operator'], function() {
    Route::get('/', 'HomeController@akun')->name('akun'); 
    Route::put('/password', 'HomeController@ubahPassword')->name('ubah.password'); 
    Route::get('/edit', 'HomeController@editAkun')->name('edit.akun'); 
    Route::put('/', 'HomeController@updateAkun')->name('update.akun'); 
  });

  //route group pasien
  Route::prefix('pasien')->group(function() {
    //tambah
    Route::get('/registrasi', 'PasienController@create')->name('pasien.create');
    Route::post('/simpan', 'PasienController@store')->name('pasien.store');

    //tampil pasien & peliharaan
    Route::get('/', 'PasienController@index')->name('pasien.index');
    Route::get('/{id}', 'PasienController@show')->name('pasien.show');
    
    //id card
    Route::post('/card/{id}', 'CardController@idCardShow')->name('pasien.card.show');
    Route::post('/card/{id}/regenerate', 'CardController@reGenerateCard')->name('pasien.card.regenerate');

    //edit
    Route::get('/{id}/edit', 'PasienController@edit')->name('pasien.edit');
    Route::put('/{id}', 'PasienController@update')->name('pasien.update');

    //soft delete
    Route::delete('/{id}', 'PasienController@delete')->name('pasien.delete');
  });

  //route group peliharaan
  Route::prefix('peliharaan')->group(function() {
    //tambah
    Route::post('/{id}', 'PeliharaanController@store')->name('peliharaan.store');

    //edit
    Route::get('{pasien}/{hewan}/edit', 'PeliharaanController@edit')->name('peliharaan.edit');
    Route::put('/{id}', 'PeliharaanController@update')->name('peliharaan.update');

    //soft delete
    Route::delete('{pasien}/{hewan}', 'PeliharaanController@delete')->name('peliharaan.delete');
  });

  //route group medis
  Route::prefix('medis')->group(function() {
    //Create
    Route::post('/{id}', 'MedisController@store')->name('medis.store');

    //Show
    Route::get('/{pasien}/{hewan}', 'MedisController@index')->name('medis.index');
    Route::get('/{pasien}/{hewan}/{medis}', 'MedisController@show')->name('medis.show');

    //update
    Route::put('/{id}', 'MedisController@update')->name('medis.update');

    //soft delete
    Route::delete('/{pasien}/{hewan}/{medis}', 'MedisController@delete')->name('medis.delete');
  });

  // route periksa
  Route::resource('/periksa', 'PeriksaController');

  //route group panduan
  Route::prefix('panduan')->group(function() {
    Route::get('/', 'PanduanController@index')->name('panduan.index');
    Route::get('/{panduan}', 'PanduanController@view')->name('view.panduan');
  });

  //Export PDF
  Route::get('/pdf-all/{pasien}/{hewan}', 'ExportController@pdfAll')->name('rekam.pdf.all');
  Route::get('/pdf/{pasien}/{hewan}/{medis}', 'ExportController@pdfSingle')->name('rekam.pdf.single');
  //Id Card Template
  Route::get('/template/card/{pasien}', 'CardController@idCardTemplate')->name('template.card');

  //Ulang Tahun
  Route::prefix('ulang-tahun')->group(function() {
    Route::get('/', 'UltahController@showUltah')->name('ultah.show');
  });

  // capture id card, export ke file .png
  Route::get('/shot', function () {
    // $view = View::make('admin.export.idCard')->render();
    $options = [
      'width' => 1011,
      'height' => 638,
      'quality' => 100
    ];
    $conv = new Converter();
    $conv->source(route('id.card'))
    ->toPng()
    ->imageOptions($options)
    // ->download('card.png');
    ->save(public_path('storage/card/'.rand().'.png'));
    // ->serve();
  });
  
  // id card html view
  Route::get('/card', function() {
    return view('admin.export.idCard');
  })->name('id.card');

  // api prefix group
  Route::group(['prefix' => 'api', 'as' => 'api.'], function($app) {
    $app->apiResource('peliharaan', 'Api\HewanController')->only('index');
  });

  Route::get('/test', function() {
    broadcast(new TestEvent('hay'));
    // dd(now()->format('Y-m-d H:i:s'));
  });

});


//route group dengan prefix admin (/admin/...)
Route::group(['prefix' => 'admin'], function($app) {
  Route::get('/login', 'Auth\AdminLoginController@showLoginForm')->name('admin.login');
  Route::post('/login', 'Auth\AdminLoginController@login')->name('admin.login.submit');
  Route::post('/update', 'Auth\AdminResetPasswordController@adminUpdate')->name('admin.update');
  Route::get('/kode/reset', 'Auth\AdminForgotPasswordController@showRequestKode')->name('admin.kode.request');
  Route::get('/kode', 'Auth\AdminResetPasswordController@kodeConfirm')->name('admin.kode.confirm');
  
  // auth:admin middleware group
  $app->middleware('auth:admin')->group(function(){
    Route::get('/', 'AdminController@index')->name('admin.dashboard');
    Route::get('/logout', 'Auth\AdminLoginController@logout')->name('admin.logout');

    Route::get('/operator', 'AdminController@operator')->name('operator');
    Route::get('/operator/{id}', 'AdminController@kelolaOperator')->name('kelola.operator');
    Route::delete('/operator/{id}', 'AdminController@updateOperator')->name('update.operator');
    Route::put('/operator/{id}', 'AdminController@resetOperator')->name('reset.operator');
  
    Route::get('/pasien-trash', 'AdminController@pasienTrash')->name('pasien.trash');
    Route::get('/pasien/{id}', 'AdminController@kelolaPasien')->name('kelola.pasien');
    Route::post('/pasien/{id}/action', 'AdminController@actionPasien')->name('action.pasien');
  
    Route::get('/hewan-trash', 'AdminController@hewanTrash')->name('hewan.trash');
    Route::get('/hewan/{id}', 'AdminController@kelolaHewan')->name('kelola.hewan');
    Route::post('/hewan/{id}/action', 'AdminController@actionHewan')->name('action.hewan');
  
    Route::get('/medis-trash', 'AdminController@medisTrash')->name('medis.trash');
    Route::get('/medis/{id}', 'AdminController@kelolaMedis')->name('kelola.medis');
    Route::post('/medis/{id}/action', 'AdminController@actionMedis')->name('action.medis');
  
    Route::get('/panduan', 'AdminController@panduan')->name('panduan');
    Route::get('/panduan/create', 'AdminController@panduanCreate')->name('create.panduan');
    Route::post('/panduan', 'AdminController@panduanStore')->name('simpan.panduan');
    Route::get('/panduan/{panduan}', 'AdminController@panduanShow')->name('show.panduan');
    Route::get('/panduan/{panduan}/edit', 'AdminController@panduanEdit')->name('edit.panduan');
    Route::put('/panduan/{panduan}', 'AdminController@panduanUpdate')->name('update.panduan');
    Route::delete('/panduan/{panduan}', 'AdminController@panduanDestroy')->name('delete.panduan');

    Route::post('/socket/serve', 'SocketController@serve')->name('socket.serve');
  });
});

Route::middleware(['auth','auth:admin'])->group(function() {
  Route::post('ajax/periksa', 'AjaxController@dataPeriksa')->name('ajax.periksa');
});

