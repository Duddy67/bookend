<?php 
use Codalia\Bookend\Models\Settings;

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

App::before(function($request) {
    Route::group(['prefix' => 'api/v1'], function () {
	if (Settings::get('restful_api', 0)) {
	    Route::resource('book', 'Codalia\Bookend\Controllers\Restful');
	}
	else {
	    Route::get('book', 'Codalia\Bookend\Controllers\Restful@unavailable');
	}
    });
});
