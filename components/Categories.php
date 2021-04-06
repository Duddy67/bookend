<?php namespace Codalia\Bookend\Components;

use Cms\Classes\ComponentBase;
use Codalia\Bookend\Models\Category as BookCategory;
use Cms\Classes\Page;


class Categories extends ComponentBase
{

    /**
     * @var Collection A collection of categories to display
     */
    public $categories;

    /**
     * Reference to the page name for linking to categories
     *
     * @var string
     */
    public $categoryPage;


    public function componentDetails()
    {
        return [
            'name'        => 'codalia.bookend::lang.settings.category_title',
            'description' => 'codalia.bookend::lang.settings.category_description'
        ];
    }

    public function defineProperties()
    {
      return [
            'displayEmpty' => [
                'title'       => 'codalia.bookend::lang.settings.category_display_empty',
                'description' => 'codalia.bookend::lang.settings.category_display_empty_description',
                'type'        => 'checkbox',
                'default'     => 0,
                'showExternalParam' => false
            ],
            'displayAsMenu' => [
                'title'       => 'codalia.bookend::lang.settings.category_display_as_menu',
                'description' => 'codalia.bookend::lang.settings.category_display_as_menu_description',
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
      ];
    }


    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
	$this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
	$this->categories = $this->page['categories'] = $this->loadCategories();
	$this->page['displayAsMenu'] = $this->property('displayAsMenu');
    }

    /**
     * Load all published categories or, depending on the <displayEmpty> option, only those that have books
     * @return mixed
     */
    protected function loadCategories($category = null)
    {
        $categories = ($category) ? $category->getChildren()->where('status', 'published') : BookCategory::where('status', 'published')->getNested();

        if (!$this->property('displayEmpty')) {
            $iterator = function ($categories) use (&$iterator) {
                return $categories->reject(function ($category) use (&$iterator) {
                    if ($category->getNestedBookCount() == 0) {
                        return true;
                    }

                    if ($category->children) {
                        $category->children = $iterator($category->children);
                    }

                    return false;
                });
            };

            $categories = $iterator($categories);
        }

        /*
         * Add a "url" helper attribute for linking to each category
         */
        return $this->linkCategories($categories);
    }

    /**
     * Sets the URL on each category according to the defined category page
     * @return void
     */
    protected function linkCategories($categories)
    {
        return $categories->each(function ($category) {
            $category->setUrl($this->categoryPage, $this->controller);

            if ($category->children) {
                $this->linkCategories($category->children);
            }
        });
    }
}
