<?php namespace Codalia\Bookend;

use Backend;
use System\Classes\PluginBase;
use Backend\Models\User as BackendUserModel;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Models\UserGroup;
use Codalia\Bookend\Models\Book;
use Backend\FormWidgets\Relation;
use Event;
use Db;

/**
 * bookend Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Bookend',
            'description' => 'A simple note book used for books.',
            'author'      => 'codalia',
            'icon'        => 'icon-book'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
	BackendUserModel::extend(function ($model) {
	    $model->hasMany['books'] = ['Codalia\Bookend\Models\Book', 'key' => 'created_by'];
	});

	// Ensures first that the RainLab User plugin is installed and activated.
	if (\System\Classes\PluginManager::instance()->exists('RainLab.User')) {
	    UserGroup::extend(function ($model) {
		$model->hasMany['books'] = ['Codalia\Bookend\Models\Book', 'key' => 'access_id'];
	    });
	}

	// Extends the partial files used for the relation type fields.
	Relation::extend(function ($widget) {
	    $widget->addViewPath(['$/codalia/bookend/models/book']);
	});

	\Cms\Controllers\Index::extend(function ($controller) {
	    $controller->bindEvent('template.processSettingsBeforeSave', function ($dataHolder) {
	        $data = post();  
		// Ensures the page file names for categories fit the correct pattern.
		if ($data['templateType'] == 'page' &&
		    isset($data['component_names']) && in_array('bookList', $data['component_names']) &&
		    !preg_match('#^category-level-[0-9]+\.htm$#', $data['fileName'])) {
		    throw new \ApplicationException(\Lang::get('codalia.bookend::lang.settings.invalid_file_name'));
		}
	    });
	});
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Codalia\Bookend\Components\Book' => 'book',
            'Codalia\Bookend\Components\Books' => 'bookList',
            'Codalia\Bookend\Components\Categories' => 'bookCategories',
            'Codalia\Bookend\Components\Featured' => 'featuredBooks',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'codalia.bookend.manage_settings' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.manage_settings',
		'order' => 200
	      ],
            'codalia.bookend.access_books' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_books',
		'order' => 201
            ],
            'codalia.bookend.access_categories' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_categories',
		'order' => 202
            ],
            'codalia.bookend.access_publish' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_publish'
            ],
            'codalia.bookend.access_delete' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_delete'
            ],
            'codalia.bookend.access_other_books' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_other_books'
            ],
            'codalia.bookend.access_check_in' => [
                'tab' => 'codalia.bookend::lang.bookend.tab',
                'label' => 'codalia.bookend::lang.bookend.access_check_in'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'bookend' => [
                'label'       => 'Bookend',
                'url'         => Backend::url('codalia/bookend/books'),
                'icon'        => 'icon-book',
                'permissions' => ['codalia.bookend.*'],
                'order'       => 500,
	    'sideMenu' => [
		'new_book' => [
		    'label'       => 'codalia.bookend::lang.books.new_book',
		    'icon'        => 'icon-plus',
		    'url'         => Backend::url('codalia/bookend/books/create'),
		    'permissions' => ['codalia.bookend.access_books']
		],
		'books' => [
		    'label'       => 'codalia.bookend::lang.bookend.books',
		    'icon'        => 'icon-book',
		    'url'         => Backend::url('codalia/bookend/books'),
		    'permissions' => ['codalia.bookend.access_books']
		],
		'categories' => [
		    'label'       => 'codalia.bookend::lang.bookend.categories',
		    'icon'        => 'icon-sitemap',
		    'url'         => Backend::url('codalia/bookend/categories'),
		    'permissions' => ['codalia.bookend.access_categories']
		]
	      ]
            ],
        ];
    }


    public function registerSettings()
    {
	return [
	    'bookend' => [
		'label'       => 'Bookend',
		'description' => 'Manage available user countries and states.',
		'category'    => 'Bookend',
		'icon'        => 'icon-book',
		'class' => 'Codalia\Bookend\Models\Settings',
		'order'       => 500,
		'keywords'    => 'geography place placement',
		'permissions' => ['codalia.bookend.manage_settings']
	    ]
	];
    }
}
