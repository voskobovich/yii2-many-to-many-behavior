Yii2 many-to-many behavior
===================
This behavior makes it easy to maintain relations many-to-many in your ActiveRecord model.

*Attention, behavior is under development, and not working yet!*

Usage:
------------
1. Add new validation rule for attributes  
2. Add config behavior in your model and set array relations

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
