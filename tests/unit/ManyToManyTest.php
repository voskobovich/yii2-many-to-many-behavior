<?php
use yii\codeception\TestCase;
use yii\db\Connection;
use yii\db\ActiveRecord;

use voskobovich\behaviors\ManyManyBehavior;

use data\Product;
use data\Category;

class ManyToManyTest extends TestCase
{
    public $appConfig = '@tests/unit/_config.php';

    public function testSave()
    {
        //id=2, 'M6x20, allen'
        $product = Product::find()->andWhere(['id' => 2])->one();

        //update categories
        $post = [
            'Product' => [
                'categories_list' => [5, 6, 2]
            ]
        ];

        $this->assertTrue($product->load($post), 'Load POST data');
        $this->assertTrue($product->save(), 'Save model');

        //reload
        $product = Product::find()->andWhere(['id' => 2])->one();

        //must have three categories
        $this->assertEquals(3, count($product->categories), 'Category count after save');

        //categories must be 2, 5, 6
        $ids = [];
        foreach ($product->categories as $category) {
            $ids[$category->id] = 1;
        }

        $this->assertTrue(isset($ids[2]), 'Saved category exists');
        $this->assertTrue(isset($ids[5]), 'Saved category exists');
        $this->assertTrue(isset($ids[6]), 'Saved category exists');
    }

    public function testSaveDoNothing()
    {
        //id=4, 'M4x60, allen'
        $product = Product::find()->andWhere(['id' => 4])->one();

        print_r($product->categories_list);
        die;

        //update categories
        $post = [
            'Product' => []
        ];

        $this->assertTrue($product->load($post), 'Load POST data');
        $this->assertTrue($product->save(), 'Save model');

        //reload
        $product = Product::find()->andWhere(['id' => 4])->one();

        //must have three categories
        $this->assertEquals(3, count($product->categories), 'Category count after save');

        //categories must be 1, 5, 6
        $ids = [];
        foreach ($product->categories as $category) {
            $ids[$category->id] = 1;
        }

        $this->assertTrue(isset($ids[1]), 'Saved category exists');
        $this->assertTrue(isset($ids[5]), 'Saved category exists');
        $this->assertTrue(isset($ids[6]), 'Saved category exists');
    }

    public function testSaveClear()
    {
        //id=4, 'M4x60, allen'
        $product = Product::find()->andWhere(['id' => 4])->one();

        //update categories
        $post = [
            'Product' => [
                'categories_list' => '',
            ]
        ];

        $this->assertTrue($product->load($post), 'Load POST data');
        $this->assertTrue($product->save(), 'Save model');

        //reload
        $product = Product::find()->andWhere(['id' => 4])->one();

        //must have three categories
        $this->assertEquals(0, count($product->categories), 'Category count after save');
    }
}
