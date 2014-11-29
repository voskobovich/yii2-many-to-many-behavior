Yii2 many-to-many behavior
===================
This behavior makes it easy to maintain relations many-to-many in your ActiveRecord model.

Usage:
------------
1. Add new attributes for usage in ActiveForm  
2. Add new validation rule for new attributes  
3. Add config behavior in your model and set array relations

Example code:
```php
<?php

// These attributes are used in the ActiveForm
public $users_list = array();
public $tasks_list = array();

public function rules()
{
    return [
        [['users_list', 'users_list'], 'safe']
    ];
}

public function behaviors()
{
    return [
        [
            'class' => \voskobovich\mtm\MtMBehavior::className(),
            'relations' => [
                'users' => 'users_list',
                'tasks' => [
                    'tasks_list',
                    function($tasksList) {
                        return array_rand($tasksList, 2);
                    }
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
php composer.phar require --prefer-dist https://bitbucket.org/voskobovich/yii2-many-many-behavior "*"
```

or add

```
"https://bitbucket.org/voskobovich/yii2-many-many-behavior": "*"
```

to the require section of your `composer.json` file.