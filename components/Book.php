<?php namespace Codalia\Bookend\Components;

use Event;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Codalia\Bookend\Models\Book as BookItem;
use Codalia\Bookend\Models\Category;
use Codalia\Bookend\Models\Settings;
use Codalia\Bookend\Components\Books;


class Book extends ComponentBase
{
    /**
     * @var Codalia\Bookend\Models\Book The book model used for display.
     */
    public $book;

    /**
     * Reference to the page name for linking to categories
     *
     * @var string
     */
    public $categoryPage;


    public function componentDetails()
    {
        return [
            'name'        => 'codalia.bookend::lang.settings.book_title',
            'description' => 'codalia.bookend::lang.settings.book_description'
        ];
    }

    public function defineProperties()
    {
	  return [
            'slug' => [
                'title'       => 'codalia.bookend::lang.settings.book_slug',
                'description' => 'codalia.bookend::lang.settings.book_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
	    'categoryPage' => [
                'title'       => 'codalia.bookend::lang.settings.books_category',
                'description' => 'codalia.bookend::lang.settings.books_category_description',
                'type'        => 'dropdown',
                'group'       => 'codalia.bookend::lang.settings.group_links',
                'showExternalParam' => false
            ],
        ];
    }


    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function init()
    {
        Event::listen('translate.localePicker.translateParams', function ($page, $params, $oldLocale, $newLocale) {
            $newParams = $params;

            foreach ($params as $paramName => $paramValue) {
	        if ($paramName == 'category-path') {
		    // Breaks down the category path string into slug segments.
		    $slugs = explode('/', $paramValue);
		    $newPath = '';

		    foreach ($slugs as $slug) {
			$records = Category::transWhere('slug', $slug, $oldLocale)->first();

			if ($records) {
			    $records->translateContext($newLocale);
			    $newPath .= $records['slug'].'/';
			}
		    }
                    // Removes the slash from the end of the string.
		    $newPath = substr($newPath, 0, -1);
		    $newParams[$paramName] = $newPath;
		}
		else {
		    $records = BookItem::transWhere($paramName, $paramValue, $oldLocale)->first();

		    if ($records) {
			$records->translateContext($newLocale);
			$newParams[$paramName] = $records[$paramName];
		    }
		}
            }

            return $newParams;
        });
    }

    public function onRun()
    {
	$this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
        $this->book = $this->page['book'] = $this->loadBook();

        if ($this->book === null || $this->book->category->status != 'published') {
            return \Redirect::to(404);
        }

	if (!$this->book->canView()) {
	    return \Redirect::to(403);
	}

	$this->addCss(url('plugins/codalia/bookend/assets/css/breadcrumb.css'));
    }

    public function onRender()
    {
        if (empty($this->book)) {
            $this->book = $this->page['book'] = $this->loadBook();
        }
    }

    protected function loadBook()
    {
        $slug = $this->property('slug');
        $book = new BookItem;

        $book = $book->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel')
		      ? $book->transWhere('slug', $slug)
		      : $book->where('slug', $slug);

        $book->with(['categories' => function ($query) {
	    // Gets only published categories.
	    $query->where('status', 'published');
        }]);

	if (($book = $book->first()) === null) {
	    return null;
        }

        // Add a "url" helper attribute for linking to the main category.
	$book->category->setUrl($this->categoryPage, $this->controller);
	$book->canonical = null;
	$urls = [];

        /*
         * Add a "url" helper attribute for linking to each extra category
         */
        if ($book && $book->categories->count()) {
            $book->categories->each(function($category, $key) use(&$urls) {
		$url = $category->setUrl($this->categoryPage, $this->controller);

		if (Settings::get('hierarchical_url', 0)) {
		    $segments = explode('/', $url);
		    // Computes the index of the first category segment.
		    $index = count($segments) - ($category->nest_depth + 1);
		    $path = '';

		    // Builds the path for this category.
		    for ($i = $index; $i < count($segments); $i++) {
			$path .= $segments[$i].'/';
		    }

		    // Removes slash from the end of the string.
		    $path = substr($path, 0, -1);
		    $urls[] = $path;
		}
            });
	}

	if (Settings::get('hierarchical_url', 0)) {
	    // Checks the given category path.
	    if ($this->param('category-path') && !in_array($this->param('category-path'), $urls)) {
		return null;
	    }

	    // Builds the canonical link to the book based on the main category of the book.
	    $path = implode('/', Category::getCategoryPath($book->category));
	    $bookPage = $this->getPage()->getBaseFileName();
	    $params = ['id' => $book->id, 'slug' => $book->slug, 'category' => $path];
	    $book->canonical = $this->controller->pageUrl($bookPage, $params);
	}

	if (Settings::get('show_breadcrumb') && request()->has('cat') && request()->cat) {
	    $book->breadcrumb = $this->getBreadcrumb($book);
	}

        return $book;
    }

    /**
     * Returns the breadcrumb path to a given book.
     *
     * @param object $book
     *
     * @return array
     */
    public function getBreadcrumb($book)
    {
        $category = new Category;

        try {
            $category = $category->find(request()->cat);
        } catch (ModelNotFoundException $ex) {
            $this->setStatusCode(404);
            return $this->controller->run('404');
        }

	$category->categoryPage = $this->categoryPage;

	return \Codalia\Bookend\Helpers\BookendHelper::instance()->getBreadcrumb($category, $book);
    }

    public function previousBook()
    {
        return $this->getBookSibling(-1);
    }

    public function nextBook()
    {
        return $this->getBookSibling(1);
    }

    protected function getBookSibling($direction = 1)
    {
        if (!$this->book) {
            return;
        }

        $method = $direction === -1 ? 'previousBook' : 'nextBook';

        if (!$book = $this->book->$method()) {
            return;
        }

        $bookPage = $this->getPage()->getBaseFileName();

        $book->setUrl($bookPage, $this->controller);

	if (Settings::get('show_breadcrumb', 0)) {
	    // Uses the main category.
	    $book->url = $book->url.'?cat='.$book->category->id;
	}

        $book->categories->each(function($category) {
            $category->setUrl($this->categoryPage, $this->controller);
        });

        return $book;
    }
}
