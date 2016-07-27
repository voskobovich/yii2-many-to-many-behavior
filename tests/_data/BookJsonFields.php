<?php

namespace data;

use voskobovich\manytomany\ManyToManyBehavior;
use yii\helpers\Json;

/**
 * Class BookJsonFields
 * @package data
 */
class BookJsonFields extends Book
{
    public function behaviors()
    {
        return [
            [
                'class' => ManyToManyBehavior::className(),
                'relations' => [
                    'author_list' => [
                        'authors',
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
                    'review_list' => [
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
                            'implode' => [
                                'get' => function ($value) {
                                    return implode(',', $value);
                                },
                                'set' => function ($value) {
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