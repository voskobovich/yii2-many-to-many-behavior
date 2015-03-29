Yii2 ManyToMany Behavior
========================
This behavior makes it easy to maintain many-to-many and one-to-many relations in your ActiveRecord models.

Usage
-----
1. In your model, add the behavior and configure it
2. In your model, add validation rules for the attributes created by the behavior
3. In your view, create form fields for the attributes

Adding and configuring the behavior
-----------------------------------

As an example, let's assume you are dealing with entities like `Book`, `Author` and `Review`. The `Book` model has the following relationships:
```php
public function getAuthors()
{
    return $this->hasMany(Author::className(), ['id' => 'author_id'])
                ->viaTable('book_has_author', ['book_id' => 'id']);
}

public function getReviews()
{
    return $this->hasMany(Review::className(), ['id' => 'review_id']);
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
                'author_list' => 'authors',
				'review_list' => 'reviews',
            ],
        ],
    ];
}
```

### Custom getters and setters ###

Attributes lik `author_list` and `review_list` in the `Book` model are created automatically. By default, they are configured to accept data from a standard select input (see below). However, it is possible to use custom getter and setter functions, which may be useful for interaction with more complex frontend scripts:
```php
...
'author_list' => [
    'authors',
    'get' => function($value) {
        return JSON::encode($value);
    },
    'set' => function($value) {
        return JSON::decode($value);
    },
]
...
```
The setter function receives whatever data comes through the `$_REQUEST` and is expected to return the array of the related model IDs. The getter function receives the array of the related model IDs. 

### Setting default values for orphaned models ###

When one-to-many relations are saved, old links are removed and new links are created. To remove an old link, the corresponding foreign-key column is set to a certain value. It is `NULL` by default, but can be configured differently. Note that your database must support your chosen default value, so if you are using `NULL` as a default value, the field must be nullable.

You can supply a constant value like so: 

```php
...
'review_list' => [
    'reviews',
    'default' => 17,
],
...
```

It is also possible to assign the default value to `NULL` explicitly, like so: `'default' => null`. Another option is to provide a function to calculate the default value:

```php
...
'review_list' => [
    'reviews',
    'default' => function($model, $relationName, $attributeName) {
        //default value calculation
        //...
        return $defaultValue;
    },
],
...
```

The function accepts 3 parameters. In our example `$model` is the instance of the `Book` class (owner of the behavior), `$relationName` is `'reviews'` and `$attributeName` is `'review_list'`.

If you need the db connection inside this function, it is recommended to obtain it from either the primary model (`Book`) or the secondary model (`Review`).
```php
function($model, $relationName, $attributeName) {
    //get db connection from primary model (Book)
    $connection = $model::getDb();
    ...
    //OR get db connection from secondary model (Review)
    $secondaryModelClass = $model->getRelation($relationName)->modelClass;
    $connection = $secondaryModelClass::getDb();
    ...
    //further value calculation logic (db query)
```  

Adding validation rules
-------------------------

The attributes are created automatically. However, you must supply a validation rule for them (usually a `safe` validator):
```php
public function rules()
{
    return [
        [['author_list', 'review_list'], 'safe']
    ];
}
```

Creating form fields
--------------------

By default, the behavior will accept data from a multiselect field:
```php
<?= $form->field($model, 'author_list')
    ->dropDownList($authors_as_array, ['multiple' => true]) ?>
...
<?= $form->field($model, 'review_list')
    ->dropDownList($reviews_as_array, ['multiple' => true]) ?>
```

Known issues and limitations
----------------------------

* Composite primary keys are not supported.
* Junction table for many-to-many links is updated using the connection from the primary model.
* When using a function to calculate the default value, keep in mind that this function is called once, right before the relations are saved, and then its result is used to update all relevant rows using one query.
* Relations are saved using DAO (i. e. by manipulating the tables directly). 


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
