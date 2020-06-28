<?php namespace Codalia\Bookend\Models;

use Lang;
use Html;
use Model;
use Auth;
use Db;
use BackendAuth;
use Backend\Models\User;
use October\Rain\Support\Str;
use October\Rain\Database\Traits\Validation;
use Carbon\Carbon;
use Codalia\Bookend\Models\Category as BookCategory;
use Codalia\Bookend\Models\Settings;
use Codalia\Bookend\Components\Books;


/**
 * Book Model
 */
class Book extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'codalia_bookend_books';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = ['title' => 'required'];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'title',
        'description',
        ['slug', 'index' => true]
    ];

    /**
     * @var array Custom validation messages
     */
    public $customMessages = [
        'title.required' => 'codalia.bookend::lang.messages.required_field'
      ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = ['summary'];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'published_up',
        'published_down'
    ];

    /**
     * The attributes on which the book list can be ordered.
     * @var array
     */
    public static $allowedSortingOptions = [
        'title asc'         => 'codalia.bookend::lang.sorting.title_asc',
        'title desc'        => 'codalia.bookend::lang.sorting.title_desc',
        'created_at asc'    => 'codalia.bookend::lang.sorting.created_asc',
        'created_at desc'   => 'codalia.bookend::lang.sorting.created_desc',
        'updated_at asc'    => 'codalia.bookend::lang.sorting.updated_asc',
        'updated_at desc'   => 'codalia.bookend::lang.sorting.updated_desc',
        'published_up asc'  => 'codalia.bookend::lang.sorting.published_asc',
        'published_up desc' => 'codalia.bookend::lang.sorting.published_desc',
        'sort_order asc'  => 'codalia.bookend::lang.sorting.order_asc',
        'sort_order desc'  => 'codalia.bookend::lang.sorting.order_desc',
        'random'            => 'codalia.bookend::lang.sorting.random'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [
    ];
    public $hasMany = [
        'orderings' => [
            'Codalia\Bookend\Models\Ordering',
        ]
    ];
    public $belongsTo = [
        'user' => ['Backend\Models\User', 'key' => 'created_by'],
        'category' => ['Codalia\Bookend\Models\Category', 'key' => 'category_id'],
    ];
    public $belongsToMany = [
        'categories' => [
            'Codalia\Bookend\Models\Category',
            'table' => 'codalia_bookend_categories_books',
            'order' => 'name'
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];


    public function __construct($attributes = array())
    {
	// Ensures first that the RainLab User plugin is installed and activated.
	if (\System\Classes\PluginManager::instance()->exists('RainLab.User')) {
	    $this->belongsTo['usergroup'] = ['RainLab\User\Models\UserGroup', 'key' => 'access_id'];
	}
	else {
	    // Links to the administrator's user goup by default to prevent an error. 
	    // However, this relation will not be used.
	    $this->belongsTo['usergroup'] = ['Backend\Models\UserGroup', 'key' => 'access_id'];
	}

        parent::__construct($attributes);
    }


    public function getStatusOptions()
    {
      return array('unpublished' => 'codalia.bookend::lang.status.unpublished',
		   'published' => 'codalia.bookend::lang.status.published',
		   'archived' => 'codalia.bookend::lang.status.archived');
    }

    public function getUserRoleOptions()
    {
        $results = Db::table('backend_user_roles')->select('code', 'name')->where('code', '!=', '')->get();

        $options = array();

	foreach ($results as $option) {
	    $options[$option->code] = $option->name;
	}

	return $options;
    }

    public function getUpdatedByFieldAttribute()
    {
	$names = '';

	if($this->updated_by) {
	    $user = BackendAuth::findUserById($this->updated_by);
	    $names = $user->first_name.' '.$user->last_name;
	}

	return $names;
    }

    public function getCreatedByFieldAttribute()
    {
	$names = '';

        if ($this->created_by) {
	    $user = BackendAuth::findUserById($this->created_by);
	    $names = $user->first_name.' '.$user->last_name;
	}

	return $names;
    }

    public function getStatusFieldAttribute()
    {
	$statuses = $this->getStatusOptions();
	$status = (isset($this->status)) ? $this->status : 'unpublished';

	return Lang::get($statuses[$status]);
    }

    public function beforeCreate()
    {
	if(empty($this->slug)) {
	    $this->slug = Str::slug($this->title);
	}

	$this->published_up = self::setPublishingDate($this);

	$user = BackendAuth::getUser();
	// For whatever reason the user object is null when refreshing the plugin. 
	$this->created_by = ($user !== null) ? $user->id : 1;
    }

    public function beforeUpdate()
    {
	$this->published_up = self::setPublishingDate($this);
	$user = BackendAuth::getUser();
	$this->updated_by = $user->id;
    }

    public function afterSave()
    {
        $this->setOrderings();
	$this->reorderByCategory();
    }

    public function afterDelete()
    {
        // Deletes ordering rows linked to the deleted book.
        $this->orderings()->where('book_id', $this->id)->delete();
    }

    public function setOrderings()
    {
        // Gets the category ids.
	$newCatIds = $this->categories()->pluck('category_id')->all();
	$oldCatIds = $this->orderings()->where('book_id', $this->id)->pluck('category_id')->all();

	// Loop through the currently selected categories.
	foreach ($newCatIds as $newCatId) {
	    if (!in_array($newCatId, $oldCatIds)) {
		// Stores the new selected category in a new ordering row.
		$this->orderings()->insert(['id' => $newCatId.'_'.$this->id,
					    'category_id' => $newCatId,
					    'book_id' => $this->id,
					    'title' => $this->title]);
	    }
	    else {
		// In case the book title has been modified.
		$this->orderings()->where('id', $newCatId.'_'.$this->id)->update(['title' => $this->title]);

		// Removes the ids of the categories which are still selected.
		if (($key = array_search($newCatId, $oldCatIds)) !== false) {
		    unset($oldCatIds[$key]);
		}
	    }
	}

	// Deletes the unselected categories.
	foreach ($oldCatIds as $oldCatId) {
	    $this->orderings()->where('id', $oldCatId.'_'.$this->id)->delete();
	}
    }

    public function reorderByCategory()
    {
        // Gets the orderings for each category.
        foreach ($this->categories as $category) {
	    // N.B: The orderings with null values are placed at the end of the array: (-sort_order DESC).
	    $orderings = $category->orderings()->orderByRaw('-sort_order DESC')->pluck('sort_order', 'id')->all();
	    $order = 1;

	    foreach ($orderings as $id => $sortOrder) {
	        // A new category has been added.
	        if ($sortOrder === null) {
		    $category->orderings()->where('id', $id)->update(['sort_order' => $order]);
		}
		else {
		    $order = $sortOrder;
		}

		$order++;
	    }
	}
    }

    /**
     * Sets the "url" attribute with a URL to this object.
     * @param string $pageName
     * @param Controller $controller
     * @param Object $category          The current category the books are showed in. (optional)
     *
     * @return string
     */
    public function setUrl($pageName, $controller, $category = null)
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
            'category' => ''
        ];

	// If no (current) category is given, the main category of the book is set.
        $category = ($category === null) ? $this->category : $category;
	// Sets the category path to the book.
	$params['category'] = implode('/', BookCategory::getCategoryPath($category));

        // Expose published year, month and day as URL parameters.
        if ($this->published_up) {
            $params['year']  = $this->published_up->format('Y');
            $params['month'] = $this->published_up->format('m');
            $params['day']   = $this->published_up->format('d');
        }

        return $this->url = $controller->pageUrl($pageName, $params);
    }

    /**
     * Switch visibility of some fields according to the current user accesses.
     *
     * @param       $fields
     * @param  null $context
     * @return void
     */
    public function filterFields($fields, $context = null)
    {
	if (!\System\Classes\PluginManager::instance()->exists('RainLab.User')) {
	    // Doesn't manage the access on front-end.
	    $fields->usergroup->hidden = true;
	}

        if ($context == 'update') {
	  if (strcmp($fields->created_at->value->toDateTimeString(), $fields->updated_at->value->toDateTimeString()) === 0) {
	      $fields->updated_at->cssClass = 'hidden';
	      $fields->_updated_by_field->cssClass = 'hidden';
	  }
	}

        if (!isset($fields->_status_field)) {
            return;
	}

        $user = BackendAuth::getUser();

        if($user->hasAccess('codalia.bookend.access_publish')) {
            $fields->_status_field->cssClass = 'hidden';
        }

	if (isset($fields->_created_by_field) && $user->hasAccess('codalia.bookend.access_other_books')) {
            $fields->_created_by_field->cssClass = 'hidden';
        }
    }

    public static function setPublishingDate($book)
    {
	// Sets to the current date time in case the record has never been published before. 
	return ($book->status == 'published' && is_null($book->published_up)) ? Carbon::now() : $book->published_up;
    }

    /**
     * Used to test if a certain user has permission to edit book,
     * returns TRUE if the user is the owner or has other books access.
     * @param  User $user
     * @return bool
     */
    public function canEdit(User $user)
    {
        return ($this->created_by == $user->id) || $user->hasAnyAccess(['codalia.bookend.access_other_books']);
    }

    public function canView()
    {
	if ($this->access_id === null) {
	    return true;
	}

	if (\System\Classes\PluginManager::instance()->exists('RainLab.User') && Auth::check()) {
	    $userGroups = Auth::getUser()->getGroups();

	    foreach ($userGroups as $userGroup) {
	      if ($userGroup->id == $this->access_id) {
		  return true;
	      }
	    }
	}

	return false;
    }

    /**
     * Returns the HTML content before the <!-- more --> tag or a limited 600
     * character version.
     *
     * @return string
     */
    public function getSummaryAttribute()
    {
        $more = '<!-- more -->';

        if (strpos($this->description, $more) !== false) {
            $parts = explode($more, $this->description);

            return array_get($parts, 0);
        }

        return Html::limit($this->description, Settings::get('max_characters', 600));
    }

    //
    // Scopes
    //

    public function scopeBookCount($query)
    {
	// Ensures the book is published and access matches the groups of the current user.
	return $query->where('status', 'published')
		     ->where(function($query) { 
			  $query->whereIn('access_id', Books::getUserGroupIds()) 
				->orWhereNull('access_id');
		      });
    }

    /**
     * Allows filtering for specific categories.
     * @param  Illuminate\Query\Builder  $query      QueryBuilder
     * @param  array                     $categories List of category ids
     * @return Illuminate\Query\Builder              QueryBuilder
     */
    public function scopeFilterCategories($query, $categories)
    {
        return $query->whereHas('categories', function($q) use ($categories) {
            $q->whereIn('id', $categories);
        });
    }

    public function scopeIsPublished($query)
    {
        return $query->whereNotNull('status')
		     ->where('status', 'published')
		     ->whereNotNull('published_up')
		     ->where('published_up', '<', Carbon::now())
		     // Groups constraints within parenthesis.
		     ->where(function ($orWhere) {
			   $orWhere->whereNull('published_down')->orWhereColumn('published_down', '<', 'published_up');
		     });
    }

    /**
     * Apply a constraint to the query to find the nearest sibling
     *
     *     // Get the next book
     *     Book::applySibling()->first();
     *
     *     // Get the previous book
     *     Book::applySibling(-1)->first();
     *
     *     // Get the previous book, ordered by the ID attribute instead
     *     Book::applySibling(['direction' => -1, 'attribute' => 'id'])->first();
     *
     * @param       $query
     * @param array $options
     * @return
     */
    public function scopeApplySibling($query, $options = [])
    {
        if (!is_array($options)) {
            $options = ['direction' => $options];
        }

        extract(array_merge([
            'direction' => 'next',
            'attribute' => 'title'
        ], $options));

        $isPrevious = in_array($direction, ['previous', -1]);
        $directionOrder = $isPrevious ? 'desc' : 'asc';
        $directionOperator = $isPrevious ? '<' : '>';

        $query->where('id', '<>', $this->id);

        if (!is_null($this->$attribute)) {
            $query->where($attribute, $directionOperator, $this->$attribute);
	}

        $query->orderBy($attribute, $directionOrder);

        return $query;
    }

    /**
     * Returns the next book, if available.
     * @return self
     */
    public function nextBook()
    {
        return self::isPublished()->applySibling()->first();
    }

    /**
     * Returns the previous book, if available.
     * @return self
     */
    public function previousBook()
    {
        return self::isPublished()->applySibling(-1)->first();
    }

    /**
     * Lists books for the frontend
     *
     * @param        $query
     * @param  array $options Display options
     * @return Book
     */
    public function scopeListFrontEnd($query, $options)
    {
        /*
         * Default options
         */
        extract(array_merge([
            'page'             => 1,
            'perPage'          => 30,
            'sort'             => 'created_at',
            'categories'       => null,
            'exceptCategories' => null,
            'category'         => null,
            'search'           => '',
            'exceptBook'       => null
        ], $options));

        $searchableFields = ['title', 'slug', 'description'];

	// Shows only published books.
	$query->isPublished();

        /*
         * Except book(s)
         */
        if ($exceptBook) {
            $exceptBooks = (is_array($exceptBook)) ? $exceptBook : [$exceptBook];
            $exceptBookIds = [];
            $exceptBookSlugs = [];

            foreach ($exceptBooks as $exceptBook) {
                $exceptBook = trim($exceptBook);

                if (is_numeric($exceptBook)) {
                    $exceptBookIds[] = $exceptBook;
                } else {
                    $exceptBookSlugs[] = $exceptBook;
                }
            }

            if (count($exceptBookIds)) {
                $query->whereNotIn('codalia_bookend_books.id', $exceptBookIds);
            }
            if (count($exceptBookSlugs)) {
                $query->whereNotIn('slug', $exceptBookSlugs);
            }
        }

        /*
         * Sorting
         */
        if (in_array($sort, array_keys(static::$allowedSortingOptions))) {
            if ($sort == 'random' || (substr($sort, 0, 10) === 'sort_order' && $category === null)) {
                $query->inRandomOrder();
            } else {
                @list($sortField, $sortDirection) = explode(' ', $sort);

                if (is_null($sortDirection)) {
                    $sortDirection = "desc";
                }

		if ($sortField == 'sort_order') {
		  // Important: Exclude the ordering columns from the result or book
		  //            categories won't match.
		  $query->select('codalia_bookend_books.*')
			// Joins over the ordering model.
		        ->join('codalia_bookend_orderings AS o', function($join) use($category) {
			    $join->on('o.book_id', '=', 'codalia_bookend_books.id')
				 ->where('o.category_id', '=', $category);
			});
		}

		$query->orderBy($sortField, $sortDirection);
            }
        }

        /*
         * Search
         */
        $search = trim($search);
        if (strlen($search)) {
            $query->searchWhere($search, $searchableFields);
        }

        /*
         * Categories
         */
        if ($categories !== null) {
            $categories = is_array($categories) ? $categories : [$categories];
            $query->whereHas('categories', function($q) use ($categories) {
                $q->whereIn('id', $categories);
            });
        }

        /*
         * Except Categories
         */
        if (!empty($exceptCategories)) {
            $exceptCategories = is_array($exceptCategories) ? $exceptCategories : [$exceptCategories];
            array_walk($exceptCategories, 'trim');

            $query->whereDoesntHave('categories', function ($q) use ($exceptCategories) {
                $q->whereIn('slug', $exceptCategories);
            });
        }

        /*
         * Gets books which are in the current category.
         */
        if ($category !== null) {
            $query->whereHas('categories', function($q) use ($category) {
                $q->where('id', $category);
            });
        }

        return $query->paginate($perPage, $page);
    }
}
