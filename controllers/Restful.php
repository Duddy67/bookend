<?php namespace Codalia\Bookend\Controllers;

//use BackendMenu;
//use Backend\Classes\Controller;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Codalia\Bookend\Models\Book;
use Input;
use DB;

/**
 * Restful Back-end Controller
 */
class Restful extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    /*public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];*/

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    //public $formConfig = 'config_form.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    //public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        //parent::__construct();

        //BackendMenu::setContext('Codalia.Bookend', 'bookend', 'restful');
    }

    public function index()
    {
	$query = $this->getQuery();

	if ($category = Input::get('category', null)) {
	    $query->whereHas('categories', function($query) use($category) {
	        $query->where('id', $category);
	    });
	}

	// N.B: Removes the $appends attributes from the data set.
	return response()->json($query->get()->each->setAppends([]), 200);
    }

    public function show($id)
    {
	$query = $this->getQuery();

	if ($book = $query->where('id', $id)->first()) {
	    // N.B: Removes the $appends attributes from the result.
	    return response()->json($book->setAppends([]), 200);
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

    public function update(Request $request, $id)
    {
        return $request;
        // Replace with logic to return the model data
        return 'bar (update)';
    }

    public function destroy($id = null)
    {
        // Replace with logic to return the model data
        return 'bar (destroy)';
    }

    private function getQuery()
    {
	return Book::select(['id', 'title', DB::raw("ExtractValue(description, '//text()') as description")])
		     ->with(['categories' => function($query) { $query->select('id', 'name')->where('status', 'published'); }])
		     ->where('status', 'published');
    }
}
