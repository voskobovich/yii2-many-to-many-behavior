Yii2 ManyToMany Behavior
===================
This behavior makes it easy to maintain many-to-many and one-to-many relations in your ActiveRecord models.

*Attention, behavior is under development, and not working yet!*

Usage:
------------
1. Add the behavior to your model and configure it
2. Add validation rules for the attributes created by the behavior   

These attributes are used in the ActiveForm.
They are created automatically.
```php
$this->users_list;
$this->tasks_list;
```
Example:
```php
<?= $form->field($model, 'users_list')
    ->dropDownList($users, ['multiple' => true]) ?>
```

Example code:

```php
<?php

public function rules()
{
    return [
        [['users_list', 'tasks_list'], 'safe']
    ];
}

public function behaviors()
{
    return [
        [
            'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
            'relations' => [
                'users_list' => 'users',
                'tasks_list' => [
                    'tasks',
                    'get' => function($value) {
                        return JSON::decode($value);
                    },
                    'set' => function($value) {
                        return JSON::encode($value);
                    },
                ]
            ],
        ],
    ];
}

public function getUsers()
{
    return $this->hasMany(User::className(), ['id' => 'user_id'])
        ->viaTable('{{%object_has_user}}', ['object_id' => 'id']);
}

public function getTasks()
{
    return $this->hasMany(Task::className(), ['id' => 'user_id'])
        ->viaTable('{{%object_has_task}}', ['object_id' => 'id']);
}
```

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist voskobovich/yii2-many-many-behavior "*"
```

or add

```
"voskobovich/yii2-many-many-behavior": "*"
```

to the require section of your `composer.json` file.
