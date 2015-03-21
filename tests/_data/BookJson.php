<?php

namespace data;

use Yii;
use yii\helpers\JSON;

/**
 * This is the model class for table "book".
 *
 * @property integer $id
 * @property string $name
 * @property integer $year
 */
class BookJson extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'book';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['author_list', 'review_list'], 'safe'],
            [['name', 'year'], 'required'],
            [['year'], 'integer'],
            [['name'], 'string', 'max' => 150]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'year' => 'Year',
        ];
    }

    public function getAuthors()
    {
        return $this->hasMany(Author::className(), ['id' => 'book_id'])
                    ->viaTable('book_has_author', ['author_id' => 'id']);
    }

    public function getReviews()
    {
        return $this->hasMany(Review::className(), ['book_id' => 'id']);
    }

    public function behaviors()
    {
    return
        [
            [
                'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
                'relations' => [
                    'author_list' => [
                        'authors',
                        'get' => function($value) {
                            return JSON::encode($value);
                        },
                        'set' => function($value) {
                            return JSON::decode($value);
                        },
                    ],
                    'review_list' => [
                        'reviews',
                        'get' => function($value) {
                            return JSON::encode($value);
                        },
                        'set' => function($value) {
                            return JSON::decode($value);
                        },
                    ]
                ]
            ]
        ];
    }

}