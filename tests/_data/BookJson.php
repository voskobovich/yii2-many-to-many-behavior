<?php

namespace data;

use voskobovich\manytomany\ManyToManyBehavior;
use yii\helpers\Json;

class BookJson extends Book
{

    public function behaviors()
    {
        return [
            [
                'class' => ManyToManyBehavior::className(),
                'relations' => [
                    'author_list' => [
                        'authors',
                        'get' => function ($value) {
                            return Json::encode($value);
                        },
                        'set' => function ($value) {
                            return Json::decode($value);
                        },
                    ],
                    'review_list' => [
                        'reviews',
                        'get' => function ($value) {
                            return Json::encode($value);
                        },
                        'set' => function ($value) {
                            return Json::decode($value);
                        },
                    ]
                ]
            ]
        ];
    }

}