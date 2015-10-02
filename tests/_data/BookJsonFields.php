<?php

namespace data;

use Yii;
use yii\helpers\JSON;

class BookJsonFields extends Book
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
                        'fields' => [
                            'json' => [
                                'get' => function($value) {
                                    return JSON::encode($value);
                                },
                                'set' => function($value) {
                                    return JSON::decode($value);
                                },
                            ],
                        ],
                    ],
                    'review_list' => [
                        'reviews',
                        'fields' => [
                            'json' => [
                                'get' => function($value) {
                                    return JSON::encode($value);
                                },
                                'set' => function($value) {
                                    return JSON::decode($value);
                                },
                            ],
                            'implode' => [
                                'get' => function($value) {
                                    return implode(',', $value);
                                },
                                'set' => function($value) {
                                    return explode(',', $value);
                                },
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

}