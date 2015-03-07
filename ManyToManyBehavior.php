<?php
/**
 * Created by PhpStorm.
 * User: Vitaly Voskobovich
 * Date: 27.11.14
 * Time: 21:00
 */

namespace voskobovich\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

/**
 * Class ManyToManyBehavior
 * @package voskobovich\mtm
 *
 * This behavior makes it easy to maintain
 * relations many-to-many in ActiveRecord model.
 *
 * Usage:
 * 1. Add new validation rule for new attributes
 * 2. Add config behavior in your model and set array relations
 *
 * These attributes are used in the ActiveForm.
 * They are created automatically.
 * $this->users_list;
 * $this->tasks_list;
 * Example:
 * <?= $form->field($model, 'users_list')
 *      ->dropDownList($users, ['multiple' => true]) ?>
 *
 * public function rules()
 * {
 *     return [
 *         [['users_list', 'tasks_list'], 'safe']
 *     ];
 * }
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => \voskobovich\behaviors\ManyToManyBehavior::className(),
 *             'relations' => [
 *                 'users_list' => 'users',
 *                 'tasks_list' => [
 *                     'tasks',
 *                     'set' => function($tasksList) {
 *                         return JSON::decode($tasksList);
 *                     },
 *                     'get' => function($value) {
 *                         return JSON::encode($value);
 *                     }
 *                 ]
 *             ],
 *         ],
 *     ];
 * }
 *
 * public function getUsers()
 * {
 *     return $this->hasMany(User::className(), ['id' => 'user_id'])
 *         ->viaTable('{{%object_has_user}}', ['object_id' => 'id']);
 * }
 *
 * public function getTasks()
 * {
 *     return $this->hasMany(Task::className(), ['id' => 'user_id'])
 *         ->viaTable('{{%object_has_task}}', ['object_id' => 'id']);
 * }
 */

class ManyToManyBehavior extends \yii\base\Behavior
{
    /**
     * Relations list
     * @var array
     */
    public $relations = array();

    /**
     * Relations value
     * @var array
     */
    private $_values = array();

    /**
     * Events list
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveRelations',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveRelations',
        ];
    }

    /**
     * Save relations value in data base
     * @param $event
     * @throws ErrorException
     * @throws \yii\db\Exception
     */
    public function saveRelations($event)
    {
        /**
         * @var $model \yii\db\ActiveRecord
         */
        $primaryModel = $event->sender;

        if (is_array($primaryModelPk = $primaryModel->getPrimaryKey())) {
            throw new ErrorException("This behavior not supported composite primary key");
        }

        foreach ($this->relations as $attributeName => $params) {

            if (!$primaryModel->isAttributeSafe($attributeName)) {
                continue;
            }

            $relationName = $this->getRelationName($attributeName);
            $relation = $primaryModel->getRelation($relationName);

            $newValue = $this->getNewValue($attributeName);

            if (!empty($params['get'])) {
                $bindingKeys = $this->callUserFunction($params['get'], $newValue);
            } else {
                $bindingKeys = $newValue;
            }

            // Save relations data
            $connection = $model::getDb();

            $transaction = $connection->beginTransaction();
            try {
                $connection = Yii::$app->db;

                // Many To Many
                if (!empty($relation->via)) {

                    list($junctionTable) = array_values($relation->via->from);
                    list($junctionColumn) = array_keys($relation->via->link);
                    list($relatedColumn) = array_values($relation->link);

                    // Remove relations
                    $connection->createCommand()
                        ->delete($junctionTable, "{$junctionColumn} = :id", [':id' => $primaryModelPk])
                        ->execute();

                    // Write new relations
                    if (!empty($bindingKeys)) {
                        $junctionRows = array();
                        foreach ($bindingKeys as $relatedPk) {
                            array_push($junctionRows, [$primaryModelPk, $relatedPk]);
                        }

                        $connection->createCommand()
                            ->batchInsert($junctionTable, [$junctionColumn, $relatedColumn], $junctionRows)
                            ->execute();
                    }
                }

                // Has Many or Has One
                elseif (!empty($relation->link)) {

                    $foreignModel = new $relation->modelClass();

                    list($bindingColumn) = array_keys($relation->link);
                    list($relatedColumn) = array_values($relation->link);

                    $p1 = $primaryModel->isPrimaryKey(array_values($relation->link));
                    $p2 = $foreignModel->isPrimaryKey(array_keys($relation->link));

                    // ???
                    if ($p1 && $p2) {
                        // https://github.com/yiisoft/yii2/blob/master/framework/db/BaseActiveRecord.php#L1196
                    }

                    // Has Many
                    elseif ($p1) {
                        $relatedTableName = $foreignModel::className();

                        // Unlink current relations
                        $connection->createCommand()
                            ->update($relatedTableName, [$bindingColumn => 0], [$bindingColumn => $primaryModelPk])
                            ->execute();

                        // Link new items
                        if (!empty($bindingKeys)) {
                            $connection->createCommand()
                                ->update($relatedTableName, [$bindingColumn => $primaryModelPk], ['in', $relatedColumn, $bindingKeys])
                                ->execute();
                        }
                    }

                    // Has One
                    elseif ($p2) {
                        $relatedTableName = $primaryModel::className();

                        // Unlink current relations
                        $connection->createCommand()
                            ->update($relatedTableName, [$relatedColumn => 0], [$bindingColumn => $primaryModelPk])
                            ->execute();

                        // Link new items
                        if (!empty($bindingKeys)) {
                            $connection->createCommand()
                                ->update($relatedTableName, [$relatedColumn => $bindingKeys[0]], [$bindingColumn => $primaryModelPk])
                                ->execute();
                        }
                    }
                }

                $transaction->commit();

            } catch (\yii\db\Exception $ex) {
                $transaction->rollback();
            }
        }
    }

    /**
     * Call user function
     * @param $function
     * @param $value
     * @return mixed
     * @throws ErrorException
     */
    private function callUserFunction($function, $value)
    {
        if (!is_array($function) && !$function instanceof \Closure) {
            throw new ErrorException("This value is not a function");
        }

        return call_user_func($function, $value);
    }

    /**
     * Get relation new value
     * @param $name
     * @return null
     */
    private function getNewValue($name)
    {
        if ($this->hasNewValue($name)) {
            return $this->_values[$name];
        }

        return array();
    }

    /**
     * Check has new value
     * @param $name
     * @return null
     */
    private function hasNewValue($name)
    {
        return isset($this->_values[$name]);
    }

    /**
     * Get params relation
     * @param $attributeName
     * @return mixed
     * @throws ErrorException
     */
    private function getRelationParams($attributeName)
    {
        if (empty($this->relations[$attributeName])) {
            throw new ErrorException("Item \"{$attributeName}\" must be configured");
        }

        return $this->relations[$attributeName];
    }

    /**
     * Get source attribute name
     * @param $attributeName
     * @return null
     */
    private function getRelationName($attributeName)
    {
        $params = $this->getRelationParams($attributeName);

        if (is_string($params)) {
            return $params;
        } elseif (is_array($params) && !empty($params[0])) {
            return $params[0];
        }

        return NULL;
    }

    /**
     * Returns a value indicating whether a property can be read.
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->relations) ?
            true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * Returns a value indicating whether a property can be set.
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return array_key_exists($name, $this->relations) ?
            true : parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * Returns the value of an object property.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @see __set()
     */
    public function __get($name)
    {
        $relationName = $this->getRelationName($name);
        $relationParams = $this->getRelationParams($name);

        $value = $this->hasNewValue($name) ?
            $this->getNewValue($name) : $this->owner->getRelation($relationName)->all();

        if (!empty($relationParams['set'])) {
            return $this->callUserFunction($relationParams['set'], $value);
        }

        return $value;
    }

    /**
     * Sets the value of a component property.
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @see __get()
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }
}