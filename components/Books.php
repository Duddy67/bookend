<?php namespace Codalia\Bookend\Components;

use Lang;
use BackendAuth;
use Codalia\Bookend\Components\Categories as ComponentCategories;
use Cms\Classes\ComponentBase;
use Codalia\Bookend\Models\Book;
use Codalia\Bookend\Models\Category as BookCategory;
use Codalia\Bookend\Models\Settings;
use Cms\Classes\Page;
use Auth;
use Event;


/*
 * Inherits from the Categories component in order to share the loadCategories method.
 */
class Books extends ComponentCategories
{
    /**
     * A collection of books to display
     *
     * @var Collection
     */
    public $books;

    /**
     * If the book list should be filtered by a category, the model to use.
     *
     * @var Model
     */
    public $category;

    /**
     * The children of the category the books are filtered by. (Optional)
     *
     * @var Collection
     */
    public $categories;

    /**
     * Message to display when there are no messages
     *
     * @var string
     */
    public $noBooksMessage;

    /**
     * Reference to the page name for linking to categories
     *
     * @var string
     */
    public $categoryPage;

    /**
     * Reference to the page name for linking to books
     *
     * @var string
     */
    public $bookPage;

    /**
     * If the book list should be ordered by another attribute
     *
     * @var string
     */
    public $sortOrder;


    public function componentDetails()
    {
        return [
            'name'        => 'codalia.bookend::lang.settings.books_title',
            'description' => 'codalia.bookend::lang.settings.books_description'
        ];
    }

    public function defineProperties()
    {
	return [
            'categoryFilter' => [
                'title'       => 'codalia.bookend::lang.settings.books_filter',
                'description' => 'codalia.bookend::lang.settings.books_filter_description',
                'type'        => 'string',
                'default'     => '',
                'validationPattern' => '^[0-9,\s]+$',
            ],
            'booksPerPage' => [
                'title'             => 'codalia.bookend::lang.settings.books_per_page',
                'default'           => 5,
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_per_page_validation',
                'showExternalParam' => false
            ],
            'noBooksMessage' => [
                'title'             => 'codalia.bookend::lang.settings.books_no_books',
                'description'       => 'codalia.bookend::lang.settings.books_no_books_description',
                'type'              => 'string',
                'default'           => Lang::get('codalia.bookend::lang.settings.books_no_books_default'),
                'showExternalParam' => false
            ],
            'sortOrder' => [
                'title'       => 'codalia.bookend::lang.settings.books_order',
                'description' => 'codalia.bookend::lang.settings.books_order_description',
                'type'        => 'dropdown',
                'default'     => 'created_at desc',
                'showExternalParam' => false
            ],
            'hideCategories' => [
                'title'             => 'codalia.bookend::lang.settings.books_hide_categories',
                'description'       => 'codalia.bookend::lang.settings.books_hide_categories_description',
                'type'              => 'string',
                'validationPattern' => '^[0-9,\s]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_category_ids_validation',
                'showExternalParam' => false
            ],
            'showCategories' => [
                'title'       => 'codalia.bookend::lang.settings.books_show_categories',
                'description' => 'codalia.bookend::lang.settings.books_show_categories_description',
                'type'        => 'checkbox',
                'default'     => 0,
                'showExternalParam' => false
            ],
            'displayEmpty' => [
                'title'       => 'codalia.bookend::lang.settings.category_display_empty',
                'description' => 'codalia.bookend::lang.settings.category_display_empty_description',
                'type'        => 'checkbox',
                'default'     => 0,
                'showExternalParam' => false
            ],
	    'categoryPage' => [
                'title'       => 'codalia.bookend::lang.settings.books_category',
                'description' => 'codalia.bookend::lang.settings.books_category_description',
                'type'        => 'dropdown',
                'group'       => 'codalia.bookend::lang.settings.group_links',
                'showExternalParam' => false
            ],
	    'bookPage' => [
                'title'       => 'codalia.bookend::lang.settings.books_book',
                'description' => 'codalia.bookend::lang.settings.books_book_description',
                'type'        => 'dropdown',
                'group'       => 'codalia.bookend::lang.settings.group_links',
                'showExternalParam' => false
            ],
            'exceptBook' => [
                'title'             => 'codalia.bookend::lang.settings.books_except_book',
                'description'       => 'codalia.bookend::lang.settings.books_except_book_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_except_book_validation',
                'group'             => 'codalia.bookend::lang.settings.group_exceptions',
                'showExternalParam' => false
            ],
            'exceptCategories' => [
                'title'             => 'codalia.bookend::lang.settings.books_except_categories',
                'description'       => 'codalia.bookend::lang.settings.books_except_categories_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_except_categories_validation',
                'group'             => 'codalia.bookend::lang.settings.group_exceptions',
                'showExternalParam' => false
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getBookPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getSortOrderOptions()
    {
        $options = Book::$allowedSortingOptions;

        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return $options;
    }

    public static function getUserGroupIds()
    {
        $ids = [];

	if (\System\Classes\PluginManager::instance()->exists('RainLab.User') && Auth::check()) {
	    $userGroups = Auth::getUser()->getGroups();

	    foreach ($userGroups as $userGroup) {
	        $ids[] = $userGroup->id;
	    }
	}

	return $ids;
    }

    public function init()
    {
        Event::listen('translate.localePicker.translateParams', function ($page, $params, $oldLocale, $newLocale) {
            $newParams = $params;

            foreach ($params as $paramName => $paramValue) {
		if ($paramName == 'page') {
		    continue;
		}

		$realParamName = $paramName;

	        if (preg_match('#^parent-[0-9]+#', $paramName)) {
		    $realParamName = 'slug';
		}

		$records = BookCategory::transWhere($realParamName, $paramValue, $oldLocale)->first();

		if ($records) {
		    $records->translateContext($newLocale);
		    $newParams[$paramName] = $records[$realParamName];
		}
            }

            return $newParams;
        });
    }

    public function onRun()
    {
        $this->prepareVars();
	$this->category = $this->page['category'] = $this->loadCategory();
        $this->books = $this->page['books'] = $this->listBooks();

	// N.B: Do not use the category class attribut here as it's going to be used with filtering.
	$category = $this->category;
	// Books are filtered by a single category id.
	if ($category === null && preg_match('#^[0-9]+$#', $this->property('categoryFilter'))) {
	    $category = BookCategory::find($this->property('categoryFilter'));
	}

        if ($category && $this->property('showCategories')) {
	    $this->categories = $this->page['categories'] = $this->loadCategories($category);
	}

	$this->addCss(url('plugins/codalia/bookend/assets/css/breadcrumb.css'));

        /*
         * If the page number is not valid, redirect
         */
        if ($currentPage = \Request::get('page')) {
            if ($currentPage > $this->books->lastPage()) {
                return \Redirect::to($this->currentPageUrl());
            }
	}
    }

    protected function prepareVars()
    {
        $this->noBooksMessage = $this->page['noBooksMessage'] = $this->property('noBooksMessage');
        $this->page['showCategories'] = $this->property('showCategories');

        /*
         * Page link
         */
        $this->bookPage = $this->page['bookPage'] = $this->property('bookPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function listBooks()
    {
        // Sets the ids the books are filtered by.

        // Books are filtered by a category slug.
        $categoryIds = ($this->category) ? [$this->category->id] : null;
        // Books are filtered by one or more category ids.
	if ($categoryIds === null && preg_match('#^[0-9,\s]+$#', $this->property('categoryFilter'))) {
	    $categoryIds = explode(',', $this->property('categoryFilter'));
	}

        /*
         * List all the books, eager load their categories
         */
	$books = Book::whereHas('category', function ($query) {
	        // Books must have their main category published.
		$query->where('status', 'published');
	})->where(function($query) { 
	        // Gets books which match the groups of the current user.
		$query->whereIn('access_id', self::getUserGroupIds()) 
		      ->orWhereNull('access_id');
        })->with(['categories' => function ($query) {
	        // Gets published categories only.
		$query->where('status', 'published');

		if ($this->property('hideCategories')) {
		    $query->whereNotIn('id', explode(',', $this->property('hideCategories')));
		}
	}])->listFrontEnd([
            'sort'             => $this->property('sortOrder'),
            'perPage'          => $this->property('booksPerPage'),
            'search'           => trim(input('search')),
            'categoryIds'      => $categoryIds,
            'exceptBook'       => is_array($this->property('exceptBook'))
                ? $this->property('exceptBook')
                : preg_split('/,\s*/', $this->property('exceptBook'), -1, PREG_SPLIT_NO_EMPTY),
            'exceptCategories' => is_array($this->property('exceptCategories'))
                ? $this->property('exceptCategories')
                : preg_split('/,\s*/', $this->property('exceptCategories'), -1, PREG_SPLIT_NO_EMPTY),
        ]);

        /*
         * Add a "url" helper attribute for linking to each book and category
         */
        $books->each(function($book, $key) {
	    $book->setUrl($this->bookPage, $this->controller, $this->category);

	    if ($this->category && Settings::get('show_breadcrumb')) {
		$book->url = $book->url.'?cat='.$this->category->id;
	    }

	    $book->categories->each(function($category, $key) {
		$category->setUrl($this->categoryPage, $this->controller);
	    });
        });

	// Books are filtered by a single category id.
	if ($this->category === null && preg_match('#^[0-9]+$#', $this->property('categoryFilter'))) {
	    $this->category = BookCategory::find($this->property('categoryFilter'));
	}

	if ($this->category && Settings::get('show_breadcrumb')) {
	    $this->category->categoryPage = $this->categoryPage;
	    $this->category->breadcrumb = \Codalia\Bookend\Helpers\BookendHelper::instance()->getBreadcrumb($this->category);
	}

        return $books;
    }

    /*
     * Gets a category filtered by slug.
     */
    protected function loadCategory()
    {
        // Ensures the category is filtered by slug.
        if (!($slug = $this->property('categoryFilter')) || !preg_match('#^[a-z-]+?#', $this->property('categoryFilter'))) {
            return null;
	}

        $category = new BookCategory;

        $category = $category->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
            ? $category->transWhere('slug', $slug)
	    : $category->where('slug', $slug);

	if (($category = $category->first()) === null) {
	    return null;
        }

	$path = [];
	$i = 1;

	// Builds the category path (if any).
	while ($this->param('level-'.$i)) {
	    $path[] = $this->param('level-'.$i);
	    $i++;
	}

	if (!empty($path)) {
	    $path = array_reverse($path);
	    $parent = $category;

	    // Goes up to the root parent.
	    foreach ($path as $segment) {
	        $parent = $parent->getParent()->first();
                // Checks against the given path segment.
		if ($parent->slug != $segment) {
		    return null;
		}
	    }
	}

        return $category;
    }
}
