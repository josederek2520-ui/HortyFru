<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pagina.inicio')->name('inicio');

Route::middleware(['auth', 'active'])->prefix('panel')->name('panel.')->group(function (): void {
    Route::view('/', 'panel.dashboard.ecommerce')->name('inicio');
    Route::view('/users', 'panel.users.index')->name('users.index');
    Route::view('/employees', 'panel.employees.index')->name('employees.index');
    Route::view('/clients', 'panel.clients.index')->name('clients.index');
    Route::view('/branches', 'panel.branches.index')->name('branches.index');
    Route::view('/proveedores', 'panel.proveedores.index')->name('proveedores.index');
    Route::view('/vehiculos', 'panel.vehiculos.index')->name('vehiculos.index');
    Route::view('/almacenes', 'panel.almacenes.index')->name('almacenes.index');
    Route::view('/categorias-articulos', 'panel.categorias-articulos.index')->name('categorias-articulos.index');
    Route::view('/unidades-medida', 'panel.unidades-medida.index')->name('unidades-medida.index');
    Route::view('/articulos', 'panel.articulos.index')->name('articulos.index');
    Route::view('/presentaciones-articulos', 'panel.presentaciones-articulos.index')->name('presentaciones-articulos.index');
    Route::view('/roles', 'panel.roles.index')->name('roles.index');
    Route::view('/activity', 'panel.activity-logs.index')->name('activity.index');
    Route::view('/calendar', 'panel.calender')->name('calendar');
    Route::view('/profile', 'panel.profile')->name('profile');
    Route::view('/form-elements', 'panel.form.form-elements')->name('form-elements');
    Route::view('/basic-tables', 'panel.tables.basic-tables')->name('basic-tables');
    Route::view('/blank', 'panel.blank')->name('blank');
    Route::view('/error-404', 'panel.errors.error-404')->name('error-404');
    Route::view('/line-chart', 'panel.chart.line-chart')->name('line-chart');
    Route::view('/bar-chart', 'panel.chart.bar-chart')->name('bar-chart');
    Route::view('/alerts', 'panel.ui-elements.alerts')->name('alerts');
    Route::view('/avatars', 'panel.ui-elements.avatars')->name('avatars');
    Route::view('/badges', 'panel.ui-elements.badges')->name('badges');
    Route::view('/buttons', 'panel.ui-elements.buttons')->name('buttons');
    Route::view('/images', 'panel.ui-elements.images')->name('images');
    Route::view('/videos', 'panel.ui-elements.videos')->name('videos');
});
