<?php

namespace data;

use Yii;
use yii\helpers\JSON;

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