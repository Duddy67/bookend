<?php namespace Codalia\Bookend\Controllers;

use Flash;
use Lang;
use BackendMenu;
use Backend\Classes\Controller;
use Codalia\Bookend\Models\Category;
use BackendAuth;
use Codalia\Bookend\Helpers\BookendHelper;

/**
 * Categories Back-end Controller
 */
class Categories extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ReorderController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Codalia.Bookend', 'bookend', 'categories');
    }

    public function index()
    {
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();
	$this->addCss(url('plugins/codalia/bookend/assets/css/extra.css'));
	// Unlocks the checked out items of this user (if any).  
	BookendHelper::instance()->checkIn((new Category)->getTable(), BackendAuth::getUser());

	// Calls the parent method as an extension.
        $this->asExtension('ListController')->index();
    }

    public function update($recordId = null, $context = null)
    {
	$category = Category::find($recordId);
	$user = BackendAuth::getUser();

	// Checks for check out matching.
	if ($category->checked_out && $user->id != $category->checked_out) {
	    Flash::error(Lang::get('codalia.bookend::lang.action.check_out_do_not_match'));
	    return redirect('backend/codalia/bookend/categories');
	}

        if ($context == 'edit') {
	    // Locks the item for this user.
	    BookendHelper::instance()->checkOut((new Category)->getTable(), $user, $recordId);
	}

        return $this->asExtension('FormController')->update($recordId, $context);
    }

    public function listOverrideColumnValue($record, $columnName, $definition = null)
    {
        if ($record->checked_out && $columnName == 'name') {
	    return BookendHelper::instance()->getCheckInHtml($record, BackendAuth::findUserById($record->checked_out));
	}
    }

    public function listInjectRowClass($record, $definition = null)
    {
        if ($record->checked_out) {
	    return 'safe disabled nolink';
	}
    }

    public function index_onDelete()
    {
	// Needed for the status column partial.
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();

	if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $recordId) {
	        // Checks that book does exist and the current user has the required access levels.
                if ((!$category = Category::find($recordId))) {
                    continue;
                }

		if ($category->checked_out) {
		    Flash::warning(Lang::get('codalia.bookend::lang.action.checked_out_item', ['name' => $category->name]));
		    return;
		}

		// Checks if the category is set as main category in a book.
		if ($category->books()->where('codalia_bookend_books.category_id', $recordId)->first()) {
		    Flash::warning(Lang::get('codalia.bookend::lang.action.used_as_main_category', ['name' => $category->name]));
		    return;
		}

                $category->delete();
            }

            Flash::success(Lang::get('codalia.bookend::lang.action.delete_success'));
         }

        return $this->listRefresh();
    }

    public function index_onSetStatus()
    {
	// Needed for the status column partial.
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();

	// Ensures one or more items are selected.
	if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
	    $status = post('status');

	    foreach ($checkedIds as $catId) {
	      $category = Category::find($catId);

	      if ($category->checked_out) {
		  Flash::error(Lang::get('codalia.bookend::lang.action.checked_out_item'));
		  return $this->listRefresh();
	      }

	      if ($status == 'unpublished') {
		  // All of the children items have to be unpublished as well.
		  foreach ($category->getAllChildren() as $children) {
		      $children->status = $status;
		      $children->save();
		  }
	      }
	      // published
	      else {
		  // Gets the parent item if any.
		  $parent = Category::find($category->getParentId());
		  // Do not publish the item if the parent item is unpublished.
		  if ($parent && $parent->getAttributeValue('status') == 'unpublished') {
		      Flash::warning(Lang::get('codalia.bookend::lang.action.parent_item_unpublished'));
		      return false;
		  }
	      }

	      // Assigns the new status value to the selected item.
	      $category->status = $status;
	      $category->save();
	  }

	  Flash::success(Lang::get('codalia.bookend::lang.action.'.rtrim($status, 'ed').'_success'));
      }

      return $this->listRefresh();
    }

    public function index_onCheckIn()
    {
	// Needed for the status column partial.
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();

	// Ensures one or more items are selected.
	if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
	  foreach ($checkedIds as $recordId) {
	      BookendHelper::instance()->checkIn((new Category)->getTable(), null, $recordId);
	  }

	  Flash::success(Lang::get('codalia.bookend::lang.action.check_in_success'));
	}

	return $this->listRefresh();
    }

    public function reorder()
    {
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();
	$this->addCss(url('plugins/codalia/bookend/assets/css/extra.css'));

        $this->asExtension('ReorderController')->reorder();
    }
}
