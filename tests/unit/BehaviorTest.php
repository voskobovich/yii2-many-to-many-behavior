<?php

use data\Book;
use data\BookJson;

class BehaviorTest extends \yii\codeception\TestCase
{

    public $appConfig = '@tests/unit/_config.php';

    public function testDoNothing()
    {
        //load
        $book = Book::findOne(3);

        //simulate form input
        $post = [
            'Book' => [
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = Book::findOne(3);

        $this->assertEquals(1, count($book->authors), 'Author count after save');
        $this->assertEquals(3, count($book->reviews), 'Review count after save');
    }

    public function testSaveManyToMany()
    {
        //load
        $book = Book::findOne(5);

        //simulate form input
        $post = [
            'Book' => [
                'author_list' => [7, 9, 8]
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = Book::findOne(5);

        //must have three authors
        $this->assertEquals(3, count($book->authors), 'Author count after save');

        //must have authors 7, 8, and 9
        $author_keys = array_keys($book->getAuthors()->indexBy('id')->all());
        $this->assertContains(7, $author_keys, 'Saved author exists');
        $this->assertContains(8, $author_keys, 'Saved author exists');
        $this->assertContains(9, $author_keys, 'Saved author exists');
    }

    public function testResetManyToMany()
    {
        //load
        $book = Book::findOne(5);

        //simulate form input
        $post = [
            'Book' => [
                'author_list' => []
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = Book::findOne(5);

        //must have three authors
        $this->assertEquals(0, count($book->authors), 'Author count after save');
    }

    public function testSaveOneToMany()
    {
        //load
        $book = Book::findOne(3);

        //simulate form input
        $post = [
            'Book' => [
                'review_list' => [2, 4]
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = Book::findOne(3);

        //must have two reviews
        $this->assertEquals(2, count($book->reviews), 'Author count after save');

        //must have reviews 2 and 4
        $review_keys = array_keys($book->getReviews()->indexBy('id')->all());
        $this->assertContains(2, $review_keys, 'Saved review exists');
        $this->assertContains(4, $review_keys, 'Saved review exists');
    }

    public function testResetOneToMany()
    {
        //load
        $book = Book::findOne(3);

        //simulate form input
        $post = [
            'Book' => [
                'review_list' => []
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = Book::findOne(3);

        //must have zero reviews
        $this->assertEquals(0, count($book->reviews), 'Review count after save');
    }

    public function testSaveManyToManyJson()
    {
        //load
        $book = BookJson::findOne(5);

        //simulate form input
        $post = [
            'BookJson' => [
                'author_list' => '[7, 9, 8]'
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = BookJson::findOne(5);

        //must have three authors
        $this->assertEquals(3, count($book->authors), 'Author count after save');

        //must have authors 7, 8, and 9
        $author_keys = array_keys($book->getAuthors()->indexBy('id')->all());
        $this->assertContains(7, $author_keys, 'Saved author exists');
        $this->assertContains(8, $author_keys, 'Saved author exists');
        $this->assertContains(9, $author_keys, 'Saved author exists');
    }

    public function testResetManyToManyJson()
    {
        //load
        $book = BookJson::findOne(5);

        //simulate form input
        $post = [
            'BookJson' => [
                'author_list' => '[]'
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = BookJson::findOne(5);

        //must have three authors
        $this->assertEquals(0, count($book->authors), 'Author count after save');
    }

    public function testSaveOneToManyJson()
    {
        //load
        $book = BookJson::findOne(3);

        //simulate form input
        $post = [
            'BookJson' => [
                'review_list' => '[2, 4]'
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = BookJson::findOne(3);

        //must have two reviews
        $this->assertEquals(2, count($book->reviews), 'Author count after save');

        //must have reviews 2 and 4
        $review_keys = array_keys($book->getReviews()->indexBy('id')->all());
        $this->assertContains(2, $review_keys, 'Saved review exists');
        $this->assertContains(4, $review_keys, 'Saved review exists');
    }

    public function testResetOneToManyJson()
    {
        //load
        $book = BookJson::findOne(3);

        //simulate form input
        $post = [
            'BookJson' => [
                'review_list' => '[]'
            ]
        ];

        $this->assertTrue($book->load($post), 'Load POST data');
        $this->assertTrue($book->save(), 'Save model');

        //reload
        $book = BookJson::findOne(3);

        //must have zero reviews
        $this->assertEquals(0, count($book->reviews), 'Review count after save');
    }

}