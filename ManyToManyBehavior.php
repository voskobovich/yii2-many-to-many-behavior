<?php

namespace voskobovich\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

/**
 * Class ManyToManyBehavior
 * @package voskobovich\mtm
 *
 * See README.md for examples
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
         * @var $primaryModel \yii\db\ActiveRecord
         */
        $primaryModel = $event->sender;

        if (is_array($primaryModelPk = $primaryModel->getPrimaryKey())) {
            throw new ErrorException("This behavior does not support composite primary keys");
        }

        // Save relations data
        foreach ($this->relations as $attributeName => $params) {

            $relationName = $this->getRelationName($attributeName);
            $relation = $primaryModel->getRelation($relationName);

            if (!$this->hasNewValue($attributeName)) {
                continue;
            }

            $newValue = $this->getNewValue($attributeName);

            $bindingKeys = $newValue;


            // many-to-many
            if (!empty($relation->via) && $relation->multiple) {
                //Assuming junction column is visible from the primary model connection
                list($junctionTable) = array_values($relation->via->from);
                list($junctionColumn) = array_keys($relation->via->link);
                list($relatedColumn) = array_values($relation->link);

                $connection = $primaryModel::getDb();
                $transaction = $connection->beginTransaction();
                try {
                    // Remove old relations
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
                    $transaction->commit();
                } catch (\yii\db\Exception $ex) {
                    $transaction->rollback();
                    throw $ex;
                }

            // one-to-many on the many side
            } elseif (!empty($relation->link) && $relation->multiple) {

                //HasMany, primary model HAS MANY foreign models, must update foreign model table
                $foreignModel = new $relation->modelClass();
                $manyTable = $foreignModel->tableName();
                list($manyTableFkColumn) = array_keys($relation->link);
                $manyTableFkValue = $primaryModelPk;
                list($manyTablePkColumn) = ($foreignModel->primaryKey());


                $connection = $foreignModel::getDb();
                $transaction = $connection->beginTransaction();

                //get default value
                $default_value = $this->getDefaultValue($attributeName, $connection);

                try {
                    // Remove old relations
                    $connection->createCommand()
                        ->update($manyTable, [$manyTableFkColumn => $default_value], [$manyTableFkColumn => $manyTableFkValue])
                        ->execute();

                    // Write new relations
                    if (!empty($bindingKeys)) {
                        $connection->createCommand()
                            ->update($manyTable, [$manyTableFkColumn => $manyTableFkValue], ['in', $manyTablePkColumn, $bindingKeys])
                            ->execute();
                    }
                    $transaction->commit();
                } catch (\yii\db\Exception $ex) {
                    $transaction->rollback();
                    throw $ex;
                }

            } else {
                throw new ErrorException('Relationship type not supported.');
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

    private function getDefaultValue($name, $connection = null) {
        $relationParams = $this->getRelationParams($name);
        if (!isset($relationParams['default'])) {
            return null;
        } elseif ($relationParams['default'] instanceof \Closure) {
            return call_user_func($relationParams['default'], $connection);
        } else {
            return $relationParams['default'];
        }
    }

    /**
     * Get relation new value
     * @param $name
     * @return null
     */
    private function getNewValue($name)
    {
        return $this->_values[$name];
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
            throw new ErrorException("Parameter \"{$attributeName}\" does not exist");
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
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     * TODO: Make it honor $checkVars ??
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
     * We return true if it is one of our properties and pass the
     * params on to the parent class otherwise.
     * TODO: Make it honor $checkVars and $checkBehaviors ??
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
     * Get it from our local temporary variable if we have it,
     * get if from DB otherwise.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @see __set()
     */
    public function __get($name)
    {
        $relationName = $this->getRelationName($name);
        $relationParams = $this->getRelationParams($name);

        if ($this->hasNewValue($name)) {
            $value = $this->getNewValue($name);
        } else {
            $relation = $this->owner->getRelation($relationName);
            $foreignModel = new $relation->modelClass();
            $value = $relation->select($foreignModel->getPrimaryKey())->column();
        }

        if (!empty($relationParams['get'])) {
            return $this->callUserFunction($relationParams['get'], $value);
        } else {
            return $value;
        }
    }

    /**
     * Sets the value of a component property. The data is passed
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @see __get()
     */
    public function __set($name, $value)
    {
        $relationParams = $this->getRelationParams($name);

        if (!empty($relationParams['set'])) {
            $this->_values[$name] = $this->callUserFunction($relationParams['set'], $value);
        } else {
            $this->_values[$name] = $value;
        }
    }
}
