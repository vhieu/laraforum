<?php
Route::group(['namespace' => 'Exp\Laraforum\Controllers',
    'prefix' => 'laraforum',
    'middleware' => ['web']
], function () {
    Route::get('/discuss', 'DiscussController@index')->name('discuss.index');
    Route::get('/discuss/channels/{channel}/{thread}', 'DiscussController@show')->name('discuss.show');//thread is id or slug
    Route::get('/discuss/create', 'DiscussController@create')->name('discuss.create');
    Route::post('/discuss/store', 'DiscussController@store')->name('discuss.store');
    Route::get('/discuss/{thread_id}/edit', 'DiscussController@edit')->name('discuss.edit');
    Route::post('/discuss/{thread_id}/update', 'DiscussController@update')->name('discuss.update');
    Route::post('discuss/{thread_id}/best/{post_id}', 'DiscussController@setBestAnswer')->name('discuss.set_best');

    Route::get('/@{user_name}', 'ProfileController@show')->name('profile.show');
    Route::get('/@{user_name}/edit', 'ProfileController@edit')->name('profile.edit');
    Route::post('/@{user_name}/update', 'ProfileController@update')->name('profile.update');

    Route::post('/discuss/{thread_id}/replies', 'PostController@store')->name('post.store');
    Route::put('/discuss/replies/{post_id}/update', 'PostController@update')->name('post.update');
    Route::post('/discuss/replies/{post_id}/edit', 'PostController@edit')->name('post.edit');
    Route::delete('/discuss/replies/{post_id}/delete', 'PostController@destroy')->name('post.destroy');

    Route::post('/discuss/{target}/{id}/{reach}/add', 'ReachController@store')->name('reach.store');
    Route::delete('/discuss/{target}/{id}/{reach}/delete', 'ReachController@destroy')->name('reach.destroy');


    Auth::routes();
    Route::get('/logout', 'Auth\LoginController@logout')->name('forum.logout');
    Route::get('/register', 'Auth\RegisterController@show')->name('forum.register');
    Route::post('/register/store','Auth\RegisterController@register')->name('forum.register.store');

});