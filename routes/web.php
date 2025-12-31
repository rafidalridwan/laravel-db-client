<?php

use Illuminate\Support\Facades\Route;
use Rafid\DbClient\Controllers\TableController;
use Rafid\DbClient\Controllers\QueryController;
use Rafid\DbClient\Controllers\ArtisanController;

Route::group([
  'prefix' => 'dbclient',
  'middleware' => config('dbclient.middleware')
], function () {

  Route::get('/', [TableController::class, 'databaseInfo'])->name('dbclient.index');
  Route::get('/tables', [TableController::class, 'index'])->name('dbclient.tables');
  Route::put('/table/{table}/rename', [TableController::class, 'rename'])->name('dbclient.table.rename');
  Route::delete('/table/{table}/drop', [TableController::class, 'drop'])->name('dbclient.table.drop');
  Route::get('/table/{table}', [TableController::class, 'show'])->name('dbclient.table.show');
  Route::get('/table/{table}/rows', [TableController::class, 'rows'])->name('dbclient.table.rows');
  Route::get('/table/{table}/row/{id}', [TableController::class, 'getRow'])->name('dbclient.table.row');

  Route::post('/table/{table}/insert', [TableController::class, 'insert'])->name('dbclient.table.insert');
  Route::put('/table/{table}/{id}/update', [TableController::class, 'update'])->name('dbclient.table.update');
  Route::delete('/table/{table}/{id}/delete', [TableController::class, 'delete'])->name('dbclient.table.delete');

  Route::post('/table/{table}/column/add', [TableController::class, 'addColumn'])->name('dbclient.table.column.add');
  Route::put('/table/{table}/column/{column}/update', [TableController::class, 'updateColumn'])->name('dbclient.table.column.update');
  Route::delete('/table/{table}/column/{column}/delete', [TableController::class, 'deleteColumn'])->name('dbclient.table.column.delete');

  Route::get('/query', [QueryController::class, 'index'])->name('dbclient.query');
  Route::post('/query/run', [QueryController::class, 'run'])->name('dbclient.query.run');

  Route::get('/artisan', [ArtisanController::class, 'index'])->name('dbclient.artisan');
  Route::post('/artisan/run', [ArtisanController::class, 'run'])->name('dbclient.artisan.run');
});
