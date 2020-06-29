<?php namespace Codalia\Bookend\Components;

use Lang;
use BackendAuth;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Codalia\Bookend\Models\Book;
use Codalia\Bookend\Models\Category as BookCategory;
use Codalia\Bookend\Models\Settings;
use Cms\Classes\Theme;
use Auth;
use Event;


class Books extends ComponentBase
{
    /**
     * A collection of books to display
     *
     * @var Collection
     */
    public $books;

    /**
     * Parameter to use for the page number
     *
     * @var string
     */
    public $pageParam;

    /**
     * If the book list should be filtered by a category, the model to use
     *
     * @var Model
     */
    public $category;

    /**
     * Message to display when there are no messages
     *
     * @var string
     */
    public $noBooksMessage;

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
            'pageNumber' => [
                'title'       => 'codalia.bookend::lang.settings.books_pagination',
                'description' => 'codalia.bookend::lang.settings.books_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}'
            ],
            'categoryFilter' => [
                'title'       => 'codalia.bookend::lang.settings.books_filter',
                'description' => 'codalia.bookend::lang.settings.books_filter_description',
                'type'        => 'string',
                'default'     => '{{ :slug }}',
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
                'default'     => 'published_at desc'
            ],
            'bookPage' => [
                'title'       => 'codalia.bookend::lang.settings.books_book',
                'description' => 'codalia.bookend::lang.settings.books_book_description',
                'type'        => 'dropdown',
                'group'       => 'codalia.bookend::lang.settings.group_links'
            ],
            'exceptBook' => [
                'title'             => 'codalia.bookend::lang.settings.books_except_book',
                'description'       => 'codalia.bookend::lang.settings.books_except_book_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_except_book_validation',
                'group'             => 'codalia.bookend::lang.settings.group_exceptions'
            ],
            'exceptCategories' => [
                'title'             => 'codalia.bookend::lang.settings.books_except_categories',
                'description'       => 'codalia.bookend::lang.settings.books_except_categories_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'codalia.bookend::lang.settings.books_except_categories_validation',
                'group'             => 'codalia.bookend::lang.settings.group_exceptions'
            ]
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
	$this->addCss(url('plugins/codalia/bookend/assets/css/breadcrumb.css'));

        /*
         * If the page number is not valid, redirect
         */
        if ($pageNumberParam = $this->paramName('pageNumber')) {
            $currentPage = $this->property('pageNumber');

            if ($currentPage > ($lastPage = $this->books->lastPage()) && $currentPage > 1) {
                return \Redirect::to($this->currentPageUrl([$pageNumberParam => $lastPage]));
            }
        }
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noBooksMessage = $this->page['noBooksMessage'] = $this->property('noBooksMessage');

        /*
         * Page link
         */
        $this->bookPage = $this->page['bookPage'] = $this->property('bookPage');
    }

    protected function listBooks()
    {
        $category = $this->category ? $this->category->id : null;

	// Removes the colon before the page number.
	if ($this->property('pageNumber') && preg_match('#^:([0-9]+)$#', $this->property('pageNumber'), $matches) === 1) {
	    $this->setProperty('pageNumber', $matches[1]);
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
	}])->listFrontEnd([
            'page'             => $this->property('pageNumber'),
            'sort'             => $this->property('sortOrder'),
            'perPage'          => $this->property('booksPerPage'),
            'search'           => trim(input('search')),
            'category'         => $category,
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

	    $book->categories->each(function($category, $key) {
		$category->setUrl($this->controller);
	    });
        });

	if (isset($this->category) && Settings::get('show_breadcrumb')) {
	    $this->category->breadcrumb = \Codalia\Bookend\Helpers\BookendHelper::instance()->getBreadcrumb($this->category);
	}

        return $books;
    }

    protected function loadCategory()
    {
        if (!$slug = $this->property('categoryFilter')) {
            return null;
        }

        $category = new BookCategory;

        $category = $category->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
            ? $category->transWhere('slug', $slug)
	    : $category->where('slug', $slug);

        $category = $category->first();

        return $category ?: null;
    }
}
