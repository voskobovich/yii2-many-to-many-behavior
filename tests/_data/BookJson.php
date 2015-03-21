<?php

namespace data;

use Yii;

/**
 * This is the model class for table "book".
 *
 * @property integer $id
 * @property string $name
 * @property integer $year
 */
class BookJson extends Book
{

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
                            return JSON::decode($value);
                        },
                        'set' => function($value) {
                            return JSON::encode($value);
                        },
                    ],
                    'review_list' => [
                        'reviews',
                        'get' => function($value) {
                            return JSON::decode($value);
                        },
                        'set' => function($value) {
                            return JSON::encode($value);
                        },
                    ]
                ]
            ]
        ];
    }

}
