<?php namespace Codalia\Bookend\Helpers;

use October\Rain\Support\Traits\Singleton;
use Carbon\Carbon;
use Backend;
use Flash;
use Db;
use Codalia\Bookend\Models\Category;


class BookendHelper
{
    use Singleton;


    /**
     * Checks out a given item for a given user.
     *
     * @param string  $tableName
     * @param User    $user
     * @param integer $recordId
     *
     * @return void
     */
    public function checkOut($tableName, $user, $recordId)
    {
	Db::table($tableName)->where('id', $recordId)
			     ->update(['checked_out' => $user->id, 
				       'checked_out_time' => Carbon::now()]);
    }

    /**
     * Checks in an item table. The "check-in" can be more specific according to the
     * optional parameters passed.
     *
     * @param string  $tableName
     * @param User    $user (optional)
     * @param integer $recordId (optional)
     *
     * @return void
     */
    public function checkIn($tableName, $user = null, $recordId = null)
    {
	Db::table($tableName)->where(function($query) use($user, $recordId) {
	                                 if ($user) {
					     $query->where('checked_out', $user->id);
					 }

	                                 if ($recordId) {
					     $query->where('id', $recordId);
					 }
				    })->update(['checked_out' => null,
						'checked_out_time' => null]);
    }

    /**
     * Builds and returns the check-in html code to display.
     *
     * @param objects record$
     * @param User    $user
     *
     * @return string
     */
    public function getCheckInHtml($record, $user)
    {
	$userName = $user->first_name.' '.$user->last_name;
	$itemName = (isset($record->name)) ? $record->name : $record->title; 
	$html = '<div class="checked-out">'.$itemName.'<span class="lock"></span></div>';
	$html .= '<div class="check-in"><p class="user-check-in">'.$userName.'</p>'.Backend::dateTime($record->checked_out_time).'</div>';

	return $html;
    }

    /**
     * Returns the css status mapping.
     *
     * @return array
     */
    public function getStatusIcons()
    {
        return ['published' => 'success', 'unpublished' => 'danger', 'archived' => 'muted']; 
    }

    public function getBreadcrumb($category, $item = null)
    {
        $breadcrumb = [];
	$path = Category::getCategoryPath($category, true);
	$controller = new \Cms\Classes\Controller;

	foreach ($path as $key => $attributes) {
	    $level = $key + 1;
	    $pageName = 'category-level-'.$level.'.htm';
	    $params = ['slug' => $attributes['slug']];

	    if ($level > 1) {
		// Loops through the category path again.
		foreach ($path as $k => $attr) {
		    $i = $k + 1;

		    // Don't treat the last element as it's the given category itself.
		    if ($i == $level) {
			break;
		    }

		    // Sets the parents of the given category.
		    //$params['parent-'.$i] = $category['slug']; 
		    $params['parent-'.$i] = $attr['slug']; 
		}
	    }

	    $breadcrumb[] = ['url' => $controller->pageUrl($pageName, $params), 'name' => $attributes['name']];
	}

	if ($item) {
	    $breadcrumb[] = ($item->name) ? ['name' => $item->name] : ['name' => $item->title];
	}

	return $breadcrumb;
    }
}
