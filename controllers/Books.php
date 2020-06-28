<?php namespace Codalia\Bookend\Controllers;

use Flash;
use Lang;
use Carbon\Carbon;
use BackendMenu;
use Backend\Classes\Controller;
use Codalia\Bookend\Models\Book;
use Backend\Behaviors\FormController;
use BackendAuth;
use Codalia\Bookend\Helpers\BookendHelper;


/**
 * Books Back-end Controller
 */
class Books extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['codalia.bookend.access_books'];


    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Codalia.Bookend', 'bookend', 'books');
    }


    public function index()
    {
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();
	$this->addCss(url('plugins/codalia/bookend/assets/css/extra.css'));
	// Unlocks the checked out items of this user (if any).  
	BookendHelper::instance()->checkIn((new Book)->getTable(), BackendAuth::getUser());
	// Calls the parent method as an extension.
        $this->asExtension('ListController')->index();
    }

    public function create()
    {
	BackendMenu::setContextSideMenu('new_book');

	return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null, $context = null)
    {
	$book = Book::find($recordId);
	$user = BackendAuth::getUser();

	// Checks for permissions.
	if (!$book->canEdit($user)) {
	    Flash::error(Lang::get('codalia.bookend::lang.action.editing_not_allowed'));
	    return redirect('backend/codalia/bookend/books');
	}

	// Checks for check out matching.
	if ($book->checked_out && $user->id != $book->checked_out) {
	    Flash::error(Lang::get('codalia.bookend::lang.action.check_out_do_not_match'));
	    return redirect('backend/codalia/bookend/books');
	}

        if ($context == 'edit') {
	    // Locks the item for this user.
	    BookendHelper::instance()->checkOut((new Book)->getTable(), $user, $recordId);
	}

        return $this->asExtension('FormController')->update($recordId, $context);
    }

    public function listOverrideColumnValue($record, $columnName, $definition = null)
    {
        if ($record->checked_out && $columnName == 'title') {
	    return BookendHelper::instance()->getCheckInHtml($record, BackendAuth::findUserById($record->checked_out));
	}
    }

    public function listExtendQuery($query)
    {
	if (!$this->user->hasAnyAccess(['codalia.bookend.access_other_books'])) {
	    // Shows only the user's books if they don't have access to other books.
	    $query->where('created_by', $this->user->id);
	}
    }

    public function listInjectRowClass($record, $definition = null)
    {
        $class = '';

        if ($record->status == 'archived') {
            $class = 'safe disabled';
        }

        if ($record->checked_out) {
	    $class = 'safe disabled nolink';
	}

	return $class;
    }

    public function index_onDelete()
    {
	// Needed for the status column partial.
	$this->vars['statusIcons'] = BookendHelper::instance()->getStatusIcons();

	if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $recordId) {
	        // Checks that book does exist and the current user has the required access levels.
                if ((!$book = Book::find($recordId)) || !$book->canEdit($this->user) || $book->checked_out) {
                    continue;
                }

                $book->delete();
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
	  foreach ($checkedIds as $recordId) {
	      $book = Book::find($recordId);

	      if ($book->checked_out) {
		  Flash::error(Lang::get('codalia.bookend::lang.action.checked_out_item'));
		  return $this->listRefresh();
	      }

	      $book->status = $status;
	      $book->published_up = Book::setPublishingDate($book);
	      $book->save();
	  }

	  $toRemove = ($status == 'archived') ? 'd' : 'ed';

	  Flash::success(Lang::get('codalia.bookend::lang.action.'.rtrim($status, $toRemove).'_success'));
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
	      BookendHelper::instance()->checkIn((new Book)->getTable(), null, $recordId);
	  }

	  Flash::success(Lang::get('codalia.bookend::lang.action.check_in_success'));
	}

	return $this->listRefresh();
    }

    public function update_onSave($recordId = null, $context = null)
    {
	// Calls the original update_onSave method
	return $this->asExtension('FormController')->update_onSave($recordId, $context);
    }
}
