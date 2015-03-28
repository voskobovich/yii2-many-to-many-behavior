<?php

namespace data;

use Yii;

class BookCustomDefaults extends Book
{

    public function rules()
    {
        return [
            [['review_list_none', 'review_list_null', 'review_list_constant', 'review_list_closure'], 'safe'],
            [['name', 'year'], 'required'],
            [['year'], 'integer'],
            [['name'], 'string', 'max' => 150]
        ];
    }

    public function behaviors()
    {
    return
        [
            [
                'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
                'relations' => [
                    'review_list_none' => [
                        'reviews',
                    ],
                    'review_list_null' => [
                        'reviews',
                        'default' => null,
                    ],
                    'review_list_constant' => [
                        'reviews',
                        'default' => 7,
                    ],
                    'review_list_closure' => [
                        'reviews',
                        'default' => function($connection) {
                            return $connection->createCommand('SELECT value FROM settings WHERE key="default_review"')->queryScalar();
                        },
                    ]
                ]
            ]
        ];
    }

}