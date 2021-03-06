<?php namespace Codalia\Bookend\Updates;

use Seeder;
use Codalia\Bookend\Models\Book;
use Codalia\Bookend\Models\Category;
use DB;

class SeedBookendTables extends Seeder
{

    public $books = [['title' => 'The Outsider', 'slug' => 'the-outsider', 'category_id' => 14],
		     ['title' => 'Breakfast at Tiffany\'s', 'slug' => 'breakfast-at-tiffany-s', 'category_id' => 9],
		     ['title' => 'The World According to Garp', 'slug' => 'the-world-according-to-garp', 'category_id' => 13],
		     ['title' => 'The Picture of Dorian Gray', 'slug' => 'the-picture-of-dorian-gray', 'category_id' => 8],
		     ['title' => 'The Shining', 'slug' => 'the-shining', 'category_id' => 10],
		     ['title' => 'It', 'slug' => 'it', 'category_id' => 10],
		     ['title' => 'Carrie', 'slug' => 'carrie', 'category_id' => 10],
		     ['title' => 'Alice in Wonderland', 'slug' => 'alice-in-wonderland', 'category_id' => 11],
		     ['title' => 'Death on the Nile', 'slug' => 'death-on-the-nile', 'category_id' => 12],
		     ['title' => 'Ten Little Niggers', 'slug' => 'ten-little-niggers', 'category_id' => 12],
		     ['title' => 'Murder on the Orient Express', 'slug' => 'murder-on-the-orient-express', 'category_id' => 12],
		     ['title' => '1984', 'slug' => '1984', 'category_id' => 16],
		     ['title' => 'Animal Farm. A Fairy Story', 'slug' => 'animal-farm-a-fairy-story', 'category_id' => 16],
		     ['title' => 'The Planet of the Apes', 'slug' => 'the-planet-of-the-apes', 'category_id' => 15],
		     ['title' => 'The Plague', 'slug' => 'the-plague', 'category_id' => 14],
		     ['title' => 'A Prayer for Owen Meany', 'slug' => 'a-prayer-for-owen-meany', 'category_id' => 13],
		     ['title' => 'The Cider House Rules', 'slug' => 'the-cider-house-rules', 'category_id' => 13],
		     ['title' => 'In Cold Blood', 'slug' => 'in-cold-blood', 'category_id' => 9],
		     ['title' => 'The Roots of Heaven', 'slug' => 'the-roots-of-heaven', 'category_id' => 17],
		     ['title' => 'Promise at Dawn', 'slug' => 'promise-at-dawn', 'category_id' => 17],
		     ['title' => 'Foam of the Days', 'slug' => 'foam-of-the-days', 'category_id' => 18],
		     ['title' => 'I Shall Spit on Your Graves', 'slug' => 'i-shall-spit-on-your-graves', 'category_id' => 18]
    ];

    public $categories = [['name' => 'Genre', 'slug' => 'genre'],
                          ['name' => 'Classics', 'slug' => 'classics'],
                          ['name' => 'Drama', 'slug' => 'drama'],
                          ['name' => 'Romance', 'slug' => 'romance'],
                          ['name' => 'Science Fiction', 'slug' => 'science-fiction'],
                          ['name' => 'Thriller', 'slug' => 'thriller'],
                          ['name' => 'Authors', 'slug' => 'authors'],
                          ['name' => 'Oscar Wilde', 'slug' => 'oscar-wilde'],
                          ['name' => 'Truman Capote', 'slug' => 'truman-capote'],
                          ['name' => 'Stephen King', 'slug' => 'stephen-king'],
                          ['name' => 'Lewis Carroll', 'slug' => 'lewis-carroll'],
                          ['name' => 'Agatha Christie', 'slug' => 'agatha-christie'],
                          ['name' => 'John Irving', 'slug' => 'john-irving'],
                          ['name' => 'Albert Camus', 'slug' => 'albert-camus'],
                          ['name' => 'Pierre Boulle', 'slug' => 'pierre-boulle'],
                          ['name' => 'George Orwell', 'slug' => 'george-orwell'],
                          ['name' => 'Romain Gary', 'slug' => 'romain-gary'],
                          ['name' => 'Boris Vian', 'slug' => 'boris-vian'],
                          ['name' => 'Countries', 'slug' => 'countries'],
                          ['name' => 'England', 'slug' => 'england'],
                          ['name' => 'France', 'slug' => 'france'],
                          ['name' => 'United States', 'slug' => 'united-states'],
                          ['name' => 'Featured', 'slug' => 'featured']
    ];

    public $query = 'INSERT INTO `codalia_bookend_categories_books`(`book_id`, `category_id`) VALUES 
		    (1,3), (1,14), (1,21), (1,23),
		    (2,4), (2,9), (2,22), (2,23),
		    (3,3), (3,13), (3,22), (3,23),
		    (4,2), (4,8), (4,20),
		    (5,6), (5,10), (5,22), (5,23),
		    (6,6), (6,10), (6,22),
		    (7,6), (7,10), (7,22),
		    (8,2), (8,11), (8,20), (8,23),
		    (9,6), (9,12), (9,20), (9,23),
		    (10,6), (10,12), (10,20),
		    (11,6), (11,12), (11,20),
		    (12,5), (12,16), (12,20), (12,23),
		    (13,5), (13,16), (13,20),
		    (14,5), (14,15), (14,21), (14,23),
		    (15,3), (15,14), (15,21),
		    (16,3), (16,13), (16,22),
		    (17,3), (17,13), (17,22),
		    (18,3), (18,9), (18,22),
		    (19,3), (19,17), (19,21),
		    (20,3), (20,17), (20,21),
		    (21,3), (21,18), (21,21),
		    (22,3), (22,18), (22,21);';

    public $ordering = [['parent_id' => '', 'nest_left' => 1, 'nest_right' => 12, 'nest_depth' => 0],
			['parent_id' => 1, 'nest_left' => 2, 'nest_right' => 3, 'nest_depth' => 1],
			['parent_id' => 1, 'nest_left' => 4, 'nest_right' => 5, 'nest_depth' => 1],
			['parent_id' => 1, 'nest_left' => 6, 'nest_right' => 7, 'nest_depth' => 1],
			['parent_id' => 1, 'nest_left' => 8, 'nest_right' => 9, 'nest_depth' => 1],
			['parent_id' => 1, 'nest_left' => 10, 'nest_right' => 11, 'nest_depth' => 1],
			['parent_id' => '', 'nest_left' => 13, 'nest_right' => 36, 'nest_depth' => 0],
			['parent_id' => 7, 'nest_left' => 14, 'nest_right' => 15, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 16, 'nest_right' => 17, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 18, 'nest_right' => 19, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 20, 'nest_right' => 21, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 22, 'nest_right' => 23, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 24, 'nest_right' => 25, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 26, 'nest_right' => 27, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 28, 'nest_right' => 29, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 30, 'nest_right' => 31, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 32, 'nest_right' => 33, 'nest_depth' => 1],
			['parent_id' => 7, 'nest_left' => 34, 'nest_right' => 35, 'nest_depth' => 1],
			['parent_id' => '', 'nest_left' => 37, 'nest_right' => 44, 'nest_depth' => 0],
			['parent_id' => 19, 'nest_left' => 38, 'nest_right' => 39, 'nest_depth' => 1],
			['parent_id' => 19, 'nest_left' => 40, 'nest_right' => 41, 'nest_depth' => 1],
			['parent_id' => 19, 'nest_left' => 42, 'nest_right' => 43, 'nest_depth' => 1] 
    ];


    public function run()
    {
      foreach ($this->books as $key => $book) {
	$order = $key + 1;
	$day = (string)$order;
	if ($order < 10) {
	  $day = '0'.$order;
	}

	Book::create(['title' => $book['title'], 'slug' => $book['slug'], 'category_id' => $book['category_id'],
		     'description' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>', 
		     'status' => 'published', 'created_by' => 1, 'updated_by' => 1, 
		     'created_at' => '2020-03-'.$day.' 04:35:00', 'published_up' => '2020-04-'.$day.' 17:08:54']);
      }

      foreach ($this->categories as $category) {
	Category::create(['name' => $category['name'], 'slug' => $category['slug'], 
		     'description' => '<p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>', 
		     'status' => 'published']);
      }

      DB::statement($this->query);

      foreach ($this->ordering as $key => $order) {
	  $id = $key + 1;
	  $parentId = (!empty($order['parent_id'])) ? 'parent_id = '.$order['parent_id'].',' : '';
	  $query = 'UPDATE codalia_bookend_categories SET '.$parentId.' nest_left = '.$order['nest_left'].', nest_right = '.$order['nest_right'].', nest_depth = '.$order['nest_depth'].' WHERE id = '.$id.';';

	  DB::statement($query);
      }
    }
}

