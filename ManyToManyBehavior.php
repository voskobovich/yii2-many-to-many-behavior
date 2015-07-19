<?php

namespace voskobovich\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

/**
 * Class ManyToManyBehavior
 * @package voskobovich\behaviors
 *
 * See README.md for examples
 */

class ManyToManyBehavior extends \yii\base\Behavior
{
    /**
     * Stores a list of relations, affected by the behavior. Configurable property.
     * @var array
     */
    public $relations = [];

    /**
     * Stores values of relation attributes. All entries in this array are considered
     * dirty (changed) attributes and will be saved in saveRelations().
     * @var array
     */
    private $_values = [];

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
     * Save all dirty (changed) relation values ($this->_values) to the database
     * @param $event
     * @throws ErrorException
     * @throws \yii\db\Exception
     */
    public function saveRelations($event)
    {
        /**
         * @var $primaryModel \yii\db\ActiveRecord
         */
        $primaryModel = $this->owner;

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
                        ->delete($junctionTable, [$junctionColumn => $primaryModelPk])
                        ->execute();

                    // Write new relations
                    if (!empty($bindingKeys)) {
                        $junctionRows = [];
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

                $defaultValue = $this->getDefaultValue($attributeName);

                try {
                    // Remove old relations
                    $connection->createCommand()
                        ->update($manyTable, [$manyTableFkColumn => $defaultValue], [$manyTableFkColumn => $manyTableFkValue])
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

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param $attributeName
     * @return null
     */
    private function hasNewValue($attributeName)
    {
        return isset($this->_values[$attributeName]);
    }

    /**
     * Get value of a dirty attribute by name
     * @param $attributeName
     * @return null
     */
    private function getNewValue($attributeName)
    {
        return $this->_values[$attributeName];
    }

    /**
     * Get default value for an attribute (used for 1-N relations)
     * @param $attributeName
     * @return mixed
     */
    private function getDefaultValue($attributeName) {
        $relationParams = $this->getRelationParams($attributeName);
        if (!isset($relationParams['default'])) {
            return null;
        } elseif ($relationParams['default'] instanceof \Closure) {
            return call_user_func($relationParams['default'], $this->owner, $this->getRelationName($attributeName), $attributeName);
        } else {
            return $relationParams['default'];
        }
    }

    /**
     * Get parameters of a relation
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
     * Get name of a relation
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
