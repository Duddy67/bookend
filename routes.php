<?php 

// Redirects all the orderings views except for the reorder one.

Route::get('backend/codalia/bookend/orderings', function() {
    return redirect('backend/codalia/bookend/books');
});

Route::get('backend/codalia/bookend/orderings/create', function() {
    return redirect('backend/codalia/bookend/books');
});

Route::get('backend/codalia/bookend/orderings/update/{id}', function() {
    return redirect('backend/codalia/bookend/books');
});

Route::get('backend/codalia/bookend/orderings/preview/{id}', function() {
    return redirect('backend/codalia/bookend/books');
});

// RESTful API.

Route::group(['prefix' => 'api/v1', 'middleware' => 'Codalia\Bookend\Classes\RestfulApiMiddleware'], function () {
    Route::resource('book', 'Codalia\Bookend\Controllers\Restful');
});

