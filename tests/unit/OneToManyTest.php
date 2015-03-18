<?php
use yii\codeception\TestCase;
use yii\db\Connection;
use yii\db\ActiveRecord;

use voskobovich\behaviors\ManyManyBehavior;

use data\Product;
use data\Image;

class OneToManyTest extends TestCase
{
    public $appConfig = '@tests/unit/_config.php';

    public function testSave()
    {
        //id=4, 'M4 bolt generic'
        $image = Image::find()->andWhere(['id_img' => 4])->one();

        //update categories
        $post = [
            'Image' => [
                'products_list' => [4, 6, 5]
            ]
        ];

        $this->assertTrue($image->load($post), 'Load POST data');
        $this->assertTrue($image->save(), 'Save model');

        //reload
        $image = Image::find()->andWhere(['id_img' => 4])->one();

        //must have three products
        $this->assertEquals(3, count($image->products), 'Product count after save');

        //products must be 4, 5, 6
        $ids = [];
        foreach ($image->products as $product) {
            $ids[$product->id_prod] = 1;
        }

        $this->assertTrue(isset($ids[4]), 'Saved product exists');
        $this->assertTrue(isset($ids[5]), 'Saved product exists');
        $this->assertTrue(isset($ids[6]), 'Saved product exists');
    }

    public function testSaveDoNothing()
    {
        //id=6, 'M8 bolt generic'
        $image = Image::find()->andWhere(['id_img' => 6])->one();

        //update categories
        $post = [
            'Image' => []
        ];

        $this->assertTrue($image->load($post), 'Load POST data');
        $this->assertTrue($image->save(), 'Save model');

        //reload
        $image = Image::find()->andWhere(['id_img' => 6])->one();

        //must have two products
        $this->assertEquals(2, count($image->products), 'Product count after save');

        //products must be 9 and 10
        $ids = [];
        foreach ($image->products as $product) {
            $ids[$product->id_prod] = 1;
        }

        $this->assertTrue(isset($ids[9]), 'Saved product exists');
        $this->assertTrue(isset($ids[10]), 'Saved product exists');
    }

    public function testSaveClear()
    {
        //id=6, 'M8 bolt generic'
        $image = Image::find()->andWhere(['id_img' => 6])->one();

        //update categories
        $post = [
            'Image' => [
            	'products_list' => ''
            ]
        ];

        $this->assertTrue($image->load($post), 'Load POST data');
        $this->assertTrue($image->save(), 'Save model');

        //reload
        $image = Image::find()->andWhere(['id_img' => 6])->one();

        //must have no products
        $this->assertEquals(0, count($image->products), 'Product count after save');
    }

}
