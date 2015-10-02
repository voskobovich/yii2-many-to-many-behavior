<?php

use data\Book;
use data\BookJson;
use yii\Helpers\ArrayHelper;
use yii\Helpers\Json;

class BehaviorTest extends \yii\codeception\TestCase
{

    public $appConfig = '@tests/unit/_config.php';

    private function saveAndReload($class, $id, $post)
    {
        $class = 'data\\'.$class;

        $model = $class::findOne($id);
        $this->assertNotEmpty($model, 'Load model');

        $this->assertTrue($model->load($post), 'Load POST data');
        $this->assertTrue($model->save(), 'Save model');

        $model = $class::findOne($id);
        $this->assertNotEmpty($model, 'Reload model');
        return $model;
    }

    public function testDoNothing()
    {
        $model = $this->saveAndReload(
            'Book',
            3,
            [
                'Book' => []
            ]
        );

        $this->assertEquals(1, count($model->authors), 'Author count after save');
        $this->assertEquals(3, count($model->reviews), 'Review count after save');
    }

    public function testSaveManyToMany()
    {
        $model = $this->saveAndReload(
            'Book',
            5,
            [
                'Book' => [
                    'author_list' => [7, 9, 8]
                ]
            ]
        );

        //must have three authors
        $this->assertEquals(3, count($model->authors), 'Author count after save');

        //must have authors 7, 8, and 9
        $author_keys = array_keys($model->getAuthors()->indexBy('id')->all());
        $this->assertContains(7, $author_keys, 'Saved author exists');
        $this->assertContains(8, $author_keys, 'Saved author exists');
        $this->assertContains(9, $author_keys, 'Saved author exists');
    }

    public function testResetManyToMany()
    {
        $model = $this->saveAndReload(
            'Book',
            5,
            [
                'Book' => [
                    'author_list' => []
                ]
            ]
        );

        //must have three authors
        $this->assertEquals(0, count($model->authors), 'Author count after save');
    }

    public function testSaveOneToMany()
    {
        $model = $this->saveAndReload(
            'Book',
            3,
            [
                'Book' => [
                    'review_list' => [2, 4]
                ]
            ]
        );

        //must have two reviews
        $this->assertEquals(2, count($model->reviews), 'Review count after save');

        //must have reviews 2 and 4
        $review_keys = array_keys($model->getReviews()->indexBy('id')->all());
        $this->assertContains(2, $review_keys, 'Saved review exists');
        $this->assertContains(4, $review_keys, 'Saved review exists');
    }

    public function testResetOneToMany()
    {
        $model = $this->saveAndReload(
            'Book',
            3,
            [
                'Book' => [
                    'review_list' => []
                ]
            ]
        );

        //must have zero reviews
        $this->assertEquals(0, count($model->reviews), 'Review count after save');
    }

    public function testSaveManyToManyJson()
    {
        $model = $this->saveAndReload(
            'BookJson',
            5,
            [
                'BookJson' => [
                    'author_list' => '[7, 9, 8]'
                ]
            ]
        );

        //must have three authors
        $this->assertEquals(3, count($model->authors), 'Author count after save');

        //must have authors 7, 8, and 9
        $author_keys = array_keys($model->getAuthors()->indexBy('id')->all());
        $this->assertContains(7, $author_keys, 'Saved author exists');
        $this->assertContains(8, $author_keys, 'Saved author exists');
        $this->assertContains(9, $author_keys, 'Saved author exists');
    }

    public function testResetManyToManyJson()
    {
        $model = $this->saveAndReload(
            'BookJson',
            5,
            [
                'BookJson' => [
                    'author_list' => '[]'
                ]
            ]
        );

        //must have three authors
        $this->assertEquals(0, count($model->authors), 'Author count after save');
    }

    public function testSaveOneToManyJson()
    {
        $model = $this->saveAndReload(
            'BookJson',
            3,
            [
                'BookJson' => [
                    'review_list' => '[2, 4]'
                ]
            ]
        );

        //must have two reviews
        $this->assertEquals(2, count($model->reviews), 'Review count after save');

        //must have reviews 2 and 4
        $review_keys = array_keys($model->getReviews()->indexBy('id')->all());
        $this->assertContains(2, $review_keys, 'Saved review exists');
        $this->assertContains(4, $review_keys, 'Saved review exists');
    }

    public function testResetOneToManyJson()
    {
        $model = $this->saveAndReload(
            'BookJson',
            3,
            [
                'BookJson' => [
                    'review_list' => '[]'
                ]
            ]
        );

        //must have zero reviews
        $this->assertEquals(0, count($model->reviews), 'Review count after save');
    }

    public function testResetWithDefaultNone()
    {
        $model = data\BookCustomDefaults::findOne(3);
        $this->assertNotEmpty($model, 'Load model');

        //this model is attached to reviews 1, 2 and 3

        $this->assertTrue($model->load(['BookCustomDefaults' => [ 'review_list_none' => [] ]]), 'Load POST data');
        $this->assertTrue($model->save(), 'Save model');

        //get data from DB directly
        $new_values = ArrayHelper::map(Yii::$app->db->createCommand('SELECT id, book_id FROM review WHERE id IN (1, 2, 3)')->queryAll(), 'id', 'book_id');
        $this->assertEquals(null, $new_values[1], 'Default value saved');
        $this->assertEquals(null, $new_values[2], 'Default value saved');
        $this->assertEquals(null, $new_values[3], 'Default value saved');
    }

    public function testResetWithDefaultNull()
    {
        $model = data\BookCustomDefaults::findOne(3);
        $this->assertNotEmpty($model, 'Load model');

        //this model is attached to reviews 1, 2 and 3

        $this->assertTrue($model->load(['BookCustomDefaults' => [ 'review_list_null' => [] ]]), 'Load POST data');
        $this->assertTrue($model->save(), 'Save model');

        //get data from DB directly
        $new_values = ArrayHelper::map(Yii::$app->db->createCommand('SELECT id, book_id FROM review WHERE id IN (1, 2, 3)')->queryAll(), 'id', 'book_id');
        $this->assertEquals(null, $new_values[1], 'Default value saved');
        $this->assertEquals(null, $new_values[2], 'Default value saved');
        $this->assertEquals(null, $new_values[3], 'Default value saved');
    }

    public function testResetWithDefaultConstant()
    {
        $model = data\BookCustomDefaults::findOne(3);
        $this->assertNotEmpty($model, 'Load model');

        //this model is attached to reviews 1, 2 and 3

        $this->assertTrue($model->load(['BookCustomDefaults' => [ 'review_list_constant' => [] ]]), 'Load POST data');
        $this->assertTrue($model->save(), 'Save model');

        //get data from DB directly
        $new_values = ArrayHelper::map(Yii::$app->db->createCommand('SELECT id, book_id FROM review WHERE id IN (1, 2, 3)')->queryAll(), 'id', 'book_id');
        $this->assertEquals(7, $new_values[1], 'Default value saved');
        $this->assertEquals(7, $new_values[2], 'Default value saved');
        $this->assertEquals(7, $new_values[3], 'Default value saved');
    }

    public function testResetWithDefaultClosure()
    {
        $model = data\BookCustomDefaults::findOne(3);
        $this->assertNotEmpty($model, 'Load model');

        //this model is attached to reviews 1, 2 and 3

        $this->assertTrue($model->load(['BookCustomDefaults' => [ 'review_list_closure' => [] ]]), 'Load POST data');
        $this->assertTrue($model->save(), 'Save model');

        //get data from DB directly
        $new_values = ArrayHelper::map(Yii::$app->db->createCommand('SELECT id, book_id FROM review WHERE id IN (1, 2, 3)')->queryAll(), 'id', 'book_id');
        $this->assertEquals(17, $new_values[1], 'Default value saved');
        $this->assertEquals(17, $new_values[2], 'Default value saved');
        $this->assertEquals(17, $new_values[3], 'Default value saved');
    }

    public function testCustomGettersSetters()
    {
        $review_list = [1, 2, 4];
        $review_list_json = Json::encode($review_list);
        $review_list_implode = implode(',', $review_list);

        $author_list = [5, 6];
        $author_list_json = Json::encode($author_list);
        $author_list_implode = implode(',', $author_list);

        //assign and getters
        $model = new data\BookJsonFields;
        $model->review_list = $review_list;
        $model->author_list = $author_list;

        $this->assertEquals($model->review_list, $review_list, 'Direct getter');
        $this->assertEquals($model->author_list, $author_list, 'Direct getter');

        $this->assertEquals($model->author_list_json, $author_list_json, 'JSON getter');
        $this->assertEquals($model->review_list_json, $review_list_json, 'JSON getter');

        $this->assertEquals($model->review_list_implode, $review_list_implode, 'Implode getter');

        //test json setters
        $model = new data\BookJsonFields;
        $model->review_list_json = $review_list_json;
        $this->assertEquals($model->review_list, $review_list, 'JSON setter');
        $model->author_list_json = $author_list_json;
        $this->assertEquals($model->author_list, $author_list, 'JSON setter');

        //test implode setter for non-existence where appropriate
        $model = new data\BookJsonFields;
        $this->assertFalse(isset($model->author_list_implode), 'Non-existence of setter where not declared');

        //test implode setter
        $model = new data\BookJsonFields;
        $model->review_list_implode = $review_list_implode;
        $this->assertEquals($model->review_list, $review_list, 'Implode setter');
    }

    public function testBadFields()
    {
        $caught = false;
        try {
            $model = new data\BookBadFields;
        } catch (\yii\base\ErrorException $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Caught exception');
    }

}