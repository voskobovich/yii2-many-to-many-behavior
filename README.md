Yii2 ManyToMany Behavior
========================
This behavior makes it easy to maintain many-to-many and one-to-many relations in your ActiveRecord models.

*Note: Behavior is still under development and should be used with caution!*

Usage
-----
1. In your model, add the behavior and configure it
2. In your model, add validation rules for the attributes created by the behavior
3. In your view, create form fields for the attributes

Adding and configuring the behavior
----------------------------------- 

As an example, let's assume you are dealing with entities like `Product`, `Category` and `Image`. The `Product` model has the following relationships:
```php
public function getCategories()
{
    return $this->hasMany(Category::className(), ['id' => 'category_id'])
                ->viaTable('product_has_category', ['product_id' => 'id']);
}

public function getImages()
{
    return $this->hasMany(Image::className(), ['id' => 'image_id']);
}
```
In the same model, the behaviour can be configured like so:
```php
public function behaviors()
{
    return [
        [
            'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
            'relations' => [
                'category_list' => 'categories',
				'image_list' => 'images',
            ],
        ],
    ];
}
```
In this example, `category_list` and `image_list` attributes in the `Product` model are created automatically. By default, they are configured to accept data from a standard select input (see below). However, it is possible to use custom getter and setter functions, which may be useful for interaction with more complex frontend scripts:
```php
...
'category_list' => [
    'categories',
    'get' => function($value) {
        return JSON::decode($value);
    },
    'set' => function($value) {
        return JSON::encode($value);
    },
]
```
The getter function is expected to return an array of IDs.

Adding validation rules
-------------------------

The attributes are created automatically. However, you must supply a validation rule for them (usually a `safe` validator):
```php
public function rules()
{
    return [
        [['category_list', 'image_list'], 'safe']
    ];
}
```

Creating form fields
--------------------

By default, the behavior will accept data from a multiselect field:
```php
<?= $form->field($model, 'category_list')
    ->dropDownList($categories_as_array, ['multiple' => true]) ?>
...
<?= $form->field($model, 'image_list')
    ->dropDownList($images_as_array, ['multiple' => true]) ?>
```

Known issues
------------

* Composite primary keys are not supported
* In the one-to-many relationship (`hasMany` with no `via`), old links are removed by setting the corresponding foreign-key column to `NULL`. The database must support this (the column needs to be NULLable).  


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
