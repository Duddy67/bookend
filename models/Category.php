<?php namespace Codalia\Bookend\Models;

use Model;
use Lang;

/**
 * Category Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'codalia_bookend_categories';

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
    public $rules = [];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'name',
        'description',
        ['slug', 'index' => true]
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
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'orderings' => [
            'Codalia\Bookend\Models\Ordering',
        ]
    ];
    public $belongsTo = [];
    public $belongsToMany = [
      'books' => ['Codalia\Bookend\Models\Book',
	  'table' => 'codalia_bookend_categories_books',
	  'order' => 'created_at desc',
	  //'scope' => 'isPublished'
      ],
      'books_count' => ['Codalia\Bookend\Models\Book',
	  'table' => 'codalia_bookend_categories_books',
	  'count' => true,
	  'scope' => 'bookCount',
      ],
 
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];


    public function beforeCreate()
    {
        // Ensure that the left and right columns are set from the start.
	$this->setDefaultLeftAndRight();
    }

    public function beforeSave()
    {
	// Gets the parent category if any.
	$parent = Category::find($this->getParentId());
	// Do not publish this category if its parent is unpublished.
	if ($parent && $parent->getAttributeValue('status') == 'unpublished' && $this->status == 'published') {
	    throw new \ApplicationException(Lang::get('codalia.bookend::lang.action.parent_item_unpublished'));
	}
    }

    public function afterSave()
    {
	foreach ($this->getAllChildren() as $children) {
	    if ($this->status == 'unpublished') {
		// All of the children items have to be unpublished as well.
		\Db::table('codalia_bookend_categories')->where('id', $children->id)->update(['status' => 'unpublished']);
	    }
	}
    }

    /**
     * Count books in this and nested categories
     * @return int
     */
    public function getNestedBookCount()
    {
        return $this->books_count()->count() + $this->children->sum(function ($category) {
            return $category->getNestedBookCount();
        });
    }


    public function getStatusOptions()
    {
	return array('unpublished' => 'codalia.bookend::lang.status.unpublished',
		     'published' => 'codalia.bookend::lang.status.published');
    }

    public function getStatusFieldAttribute()
    {
	$statuses = $this->getStatusOptions();
	$status = (isset($this->status)) ? $this->status : 'unpublished';

	return Lang::get($statuses[$status]);
    }

    public function getParentFieldAttribute()
    {
        if ($this->parent) {
	    return $this->parent->attributes['name'];
	}

	return Lang::get('codalia.bookend::lang.attribute.none');
    }

    /**
     * Switch visibility of some fields according to the parent and status values.
     *
     * @param       $fields
     * @param  null $context
     * @return void
     */
    public function filterFields($fields, $context = null)
    {
        if ($context == 'create') {
	    $fields->created_at->hidden = true;
	    $fields->updated_at->hidden = true;
	}

        if ($context == 'update') {
	  // The item has just been created. Don't display the update field. 
	  if (strcmp($fields->created_at->value->toDateTimeString(), $fields->updated_at->value->toDateTimeString()) === 0) {
	      $fields->updated_at->cssClass = 'hidden';
	  }
	}

        if ($this->parent && $this->parent->attributes['status'] == 'unpublished') {
	    $fields->status->cssClass = 'hidden';
            $fields->parent->cssClass = 'hidden';
            $fields->_status_field->cssClass = 'visible';
            $fields->_parent_field->cssClass = 'visible';
	}
	elseif ($this->parent && $this->parent->attributes['status'] == 'published' && $this->status == 'unpublished') {
            $fields->parent->cssClass = 'hidden';
            $fields->_parent_field->cssClass = 'visible';
            $fields->_status_field->cssClass = 'hidden';
	}
	else {
            $fields->_parent_field->cssClass = 'hidden';
            $fields->_status_field->cssClass = 'hidden';
	}
    }

    /**
     * Sets the "url" attribute with a URL to this object
     *
     * @param Cms\Classes\Controller $controller
     *
     * @return string
     */
    public function setUrl($controller)
    {
        $params = [
            'id'   => $this->id,
	    'slug' => $this->slug,
        ];

	$this->path = self::getCategoryPath($this);
	$level = count($this->path);
	// Sets the category page with the appropriate url pattern.
	$pageName = 'category-level-'.$level;

	// The given category has parents.
	if ($level > 1) {
	    // Loops through the category path.
	    foreach ($this->path as $key => $slug) {
	        $i = $key + 1;

		// Don't treat the last element as it's the given category itself.
		if ($i == $level) {
		    break;
		}

		// Sets the parents of the given category.
	        $params['parent-'.$i] = $slug; 
	    }
	}

        return $this->url = $controller->pageUrl($pageName, $params, false);
    }

    /**
     * Returns the category path to a given category.
     *
     * @param object  $category
     * @param boolean $attributes (optional)
     *
     * @return array
     */
    public static function getCategoryPath($category, $attributes = false)
    {
        $path = ($attributes) ? [['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name]] : [$category->slug];
	$parent = $category->getParent()->first();

	// Goes up to the root parent.
	while ($parent) {
	    $path[] = ($attributes) ? ['id' => $parent->id, 'slug' => $parent->slug, 'name' => $parent->name] : $parent->slug;
	    $parent = $parent->getParent()->first();
	}

        return array_reverse($path);
    }

    protected static function listSubCategoryOptions()
    {
        $category = self::getNested();

        $iterator = function($categories) use (&$iterator) {
            $result = [];

            foreach ($categories as $category) {
                if (!$category->children) {
                    $result[$category->id] = $category->name;
                }
                else {
                    $result[$category->id] = [
                        'title' => $category->name,
                        'items' => $iterator($category->children)
                    ];
                }
            }

            return $result;
        };

        return $iterator($category);
    }
}
