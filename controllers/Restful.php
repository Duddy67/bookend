<?php namespace Codalia\Bookend\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Codalia\Bookend\Models\Book;
use BackendAuth;
use Input;
use DB;

/**
 * Restful Back-end Controller
 */
class Restful extends Controller
{
    /**
     * @var array 
     */
    public $error = ['status' => '', 'message' => ''];


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

    public function store(Request $request)
    {
	if (!$user = $this->getUser($request->header('Authorization'))) {
	    return response()->json(['error' => $this->error['message']], $this->error['status']);
	}

        if ($request->accepts(['text/html', 'application/json'])) {
	    $request->request->add(['created_by' => $user->id]);

	    // Getting the dispatcher instance (needed to enable again the event observer later on)
	    $dispatcher = Book::getEventDispatcher();
	    // Disabling the events
	    Book::unsetEventDispatcher();

	    try {
		Book::create($request->all());
	    }
	    catch (\Exception $e) {
		Book::setEventDispatcher($dispatcher);
		return response()->json(['error' => $e->getMessage()], 404);
	    }

	    // Enabling the event dispatcher
	    Book::setEventDispatcher($dispatcher);

	    return $request->all();
	}

	return response()->json(['error' => 'Bad request. JSON is required.'], 400);
    }

    public function update(Request $request, $id)
    {
	if (!$user = $this->getUser($request->header('Authorization'))) {
	    return response()->json(['error' => $this->error['message']], $this->error['status']);
	}

        if ($request->accepts(['text/html', 'application/json'])) {
	    // Check first for the item to update.
	    if (!$book = Book::select('id', 'title', 'slug', 'description')->find($id)) {
		return response()->json(['error' => 'Resource not found'], 404);
	    }

	    $request->request->add(['updated_by' => $user->id]);

	    // Getting the dispatcher instance (needed to enable again the event observer later on)
	    $dispatcher = Book::getEventDispatcher();
	    // Disabling the events
	    Book::unsetEventDispatcher();

	    try {
		$book->update($request->all());
	    }
	    catch (\Exception $e) {
		Book::setEventDispatcher($dispatcher);
		return response()->json(['error' => $e->getMessage()], 404);
	    }

	    // Enabling the event dispatcher
	    Book::setEventDispatcher($dispatcher);

	    return response()->json($book->setAppends([]), 200);
	}

	return response()->json(['error' => 'Bad request. JSON is required.'], 400);
    }

    public function destroy(Request $request, $id = null)
    {
	if (!$user = $this->getUser($request->header('Authorization'))) {
	    return response()->json(['error' => $this->error['message']], $this->error['status']);
	}

	// Check first for the item to delete.
	if (!$book = Book::select('id', 'title', 'slug', 'description')->find($id)) {
	    return response()->json(['error' => 'Resource not found'], 404);
	}

	$book->delete($id);

	return response()->json(['message' => 'Item deleted.'], 200);
    }

    private function getQuery()
    {
	return Book::select(['id', 'title', DB::raw("ExtractValue(description, '//text()') as description")])
		     ->with(['categories' => function($query) { $query->select('id', 'name')->where('status', 'published'); }])
		     ->where('status', 'published');
    }

    private function getUser($authorization)
    {
        $auth = explode(':', $authorization);

	if (empty($auth) || count($auth) !== 2) {
	    $this->error['status'] = 401;
	    $this->error['message'] = 'Invalid authorization.';

	    return false;
	}

	$credentials = ['login' => $auth[0], 'password' => $auth[1]];

	try {
	    BackendAuth::once($credentials);
	}
	catch (\Exception $e) {
	    $this->error['status'] = 401;
	    $this->error['message'] = $e->getMessage();

	    return false;
	}

	return BackendAuth::user();
    }
}
