<?php

namespace data;

use Yii;
use yii\helpers\Json;

class BookBadFields extends Book
{

    public function behaviors()
    {
    return
        [
            [
                'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
                'relations' => [
                    'author' => [
                        'authors',
                        'fields' => [
                            'list_json' => [
                                'get' => function($value) {
                                    return Json::encode($value);
                                },
                                'set' => function($value) {
                                    return Json::decode($value);
                                },
                            ],
                        ],
                    ],
                    'author_list' => [
                        'reviews',
                        'fields' => [
                            'json' => [
                                'get' => function($value) {
                                    return Json::encode($value);
                                },
                                'set' => function($value) {
                                    return Json::decode($value);
                                },
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

}