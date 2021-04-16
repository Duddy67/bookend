<?php namespace Codalia\Bookend\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Codalia\Bookend\Models\Book;
use Input;

/**
 * Restful Back-end Controller
 */
class Restful extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Codalia.Bookend', 'bookend', 'restful');
    }

    public function index()
    {
        $category = Input::get('category', null);
	$query = Book::where('status', 'published');

	if ($category) {
	    $query = Book::whereHas('categories', function($query) use($category) {
	        $query->where('id', $category);
	    });
	}

	return response()->json($query->get(), 200);
    }

    public function show($id)
    {
        if ($book = Book::where('id', $id)->first()) {
	    return response()->json($book, 200);
	}

	return response()->json(['error' => 'Resource not found'], 404);
    }

    public function create()
    {
        // Replace with logic to return the model data
        return 'bar (create)';
    }

    public function store()
    {
        // Replace with logic to return the model data
        return 'bar (store)';
    }

    public function update($id)
    {
        // Replace with logic to return the model data
        return 'bar (update)';
    }

    public function destroy($id = null)
    {
        // Replace with logic to return the model data
        return 'bar (destroy)';
    }

    public function unavailable()
    {
	return response()->json(['error' => 'Service unavailable'], 503);
    }
}
