<?php
namespace data;

class Category extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'category';
    }

    public function rules()
    {
        return [
            [['products_list'], 'safe']
        ];
    }

    public function behaviors()
    {
    return
        [
            [
                'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
                'relations' => [
                    'products_list' => 'products',
                ],
            ]
        ];
    }

    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['id' => 'product_id'])
                    ->viaTable('product_has_category', ['category_id' => 'id']);
    }

}
