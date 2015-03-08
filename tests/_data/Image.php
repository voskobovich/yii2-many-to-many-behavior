<?php
namespace data;

class Image extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'image';
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
        return $this->hasMany(Product::className(), ['image_id' => 'id']);
    }

}
