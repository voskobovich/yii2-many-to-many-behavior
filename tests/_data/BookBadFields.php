<?php

namespace data;

use voskobovich\manytomany\ManyToManyBehavior;
use yii\helpers\Json;

/**
 * Class BookBadFields
 * @package data
 */
class BookBadFields extends Book
{
    public function behaviors()
    {
        return [
            [
                'class' => ManyToManyBehavior::className(),
                'relations' => [
                    'author' => [
                        'authors',
                        'fields' => [
                            'list_json' => [
                                'get' => function ($value) {
                                    return Json::encode($value);
                                },
                                'set' => function ($value) {
                                    return Json::decode($value);
                                },
                            ],
                        ],
                    ],
                    'author_list' => [
                        'reviews',
                        'fields' => [
                            'json' => [
                                'get' => function ($value) {
                                    return Json::encode($value);
                                },
                                'set' => function ($value) {
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