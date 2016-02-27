<?php

namespace voskobovich\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\base\ErrorException;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class ManyToManyBehavior
 * @package voskobovich\behaviors
 *
 * See README.md for examples
 */
class ManyToManyBehavior extends Behavior
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
     * Used to store fields that this behavior creates. Each field refers to a relation
     * and has optional getters and setters.
     * @var array
     */
    private $_fields = [];

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
     * Invokes init of parent class and assigns proper values to internal _fields variable
     */
    public function init()
    {
        parent::init();

        //configure _fields
        foreach ($this->relations as $attributeName => $params) {
            //add primary field
            $this->_fields[$attributeName] = [
                'attribute' => $attributeName,
            ];
            if (isset($params['get'])) {
                $this->_fields[$attributeName]['get'] = $params['get'];
            }
            if (isset($params['set'])) {
                $this->_fields[$attributeName]['set'] = $params['set'];
            }

            // Add secondary fields
            if (isset($params['fields'])) {
                foreach ($params['fields'] as $fieldName => $adjustments) {
                    $fullFieldName = $attributeName.'_'.$fieldName;
                    if (isset($this->_fields[$fullFieldName])) {
                        throw new ErrorException("Ambiguous field name definition: {$fullFieldName}");
                    }

                    $this->_fields[$fullFieldName] = [
                        'attribute' => $attributeName,
                    ];
                    if (isset($adjustments['get'])) {
                        $this->_fields[$fullFieldName]['get'] = $adjustments['get'];
                    }
                    if (isset($adjustments['set'])) {
                        $this->_fields[$fullFieldName]['set'] = $adjustments['set'];
                    }
                }
            }
        }
    }

    /**
     * Save all dirty (changed) relation values ($this->_values) to the database
     * @throws ErrorException
     * @throws Exception
     */
    public function saveRelations()
    {
        /** @var ActiveRecord $primaryModel */
        $primaryModel = $this->owner;

        if (is_array($primaryModelPk = $primaryModel->getPrimaryKey())) {
            throw new ErrorException('This behavior does not support composite primary keys');
        }

        foreach ($this->relations as $attributeName => $params) {
            $relationName = $this->getRelationName($attributeName);
            $relation = $primaryModel->getRelation($relationName);

            if (!$this->hasNewValue($attributeName)) {
                continue;
            }

            if (!empty($relation->via) && $relation->multiple) {
                // Many-to-many
                $this->saveManyToManyRelation($relation, $attributeName);
            } elseif (!empty($relation->link) && $relation->multiple) {
                // One-to-many on the many side
                $this->saveOneToManyRelation($relation, $attributeName);
            } else {
                throw new ErrorException('Relationship type not supported.');
            }
        }
    }

    /**
     * @param ActiveQuery $relation
     * @param string $attributeName
     * @throws Exception
     */
    private function saveManyToManyRelation($relation, $attributeName)
    {
        /** @var ActiveRecord $primaryModel */
        $primaryModel = $this->owner;
        $primaryModelPk = $primaryModel->getPrimaryKey();

        $bindingKeys = $this->getNewValue($attributeName);

        // Assuming junction column is visible from the primary model connection
        if (is_array($relation->via)) {
            // via()
            $via = $relation->via[1];
            /** @var ActiveRecord $junctionModelClass */
            $junctionModelClass = $via->modelClass;
            $junctionTable = $junctionModelClass::tableName();
            list($junctionColumn) = array_keys($via->link);
        } else {
            // viaTable()
            list($junctionTable) = array_values($relation->via->from);
            list($junctionColumn) = array_keys($relation->via->link);
        }

        list($relatedColumn) = array_values($relation->link);

        $connection = $primaryModel::getDb();
        $transaction = $connection->beginTransaction();
        try {
            // Remove old relations
            $connection->createCommand()
                ->delete($junctionTable, ArrayHelper::merge(
                    [$junctionColumn => $primaryModelPk],
                    $this->getCustomDeleteCondition($attributeName)
                ))
                ->execute();

            // Write new relations
            if (!empty($bindingKeys)) {
                $junctionRows = [];

                $viaTableParams = $this->getViaTableParams($attributeName);

                foreach ($bindingKeys as $relatedPk) {
                    $row = [$primaryModelPk, $relatedPk];

                    // Calculate additional viaTable values
                    foreach (array_keys($viaTableParams) as $viaTableColumn) {
                        $row[] = $this->getViaTableValue($attributeName, $viaTableColumn, $relatedPk);
                    }

                    array_push($junctionRows, $row);
                }

                $cols = [$junctionColumn, $relatedColumn];

                // Additional viaTable columns
                foreach (array_keys($viaTableParams) as $viaTableColumn) {
                    $cols[] = $viaTableColumn;
                }

                $connection->createCommand()
                    ->batchInsert($junctionTable, $cols, $junctionRows)
                    ->execute();
            }
            $transaction->commit();
        } catch (Exception $ex) {
            $transaction->rollback();
            throw $ex;
        }
    }

    /**
     * @param ActiveQuery $relation
     * @param string $attributeName
     * @throws Exception
     */
    private function saveOneToManyRelation($relation, $attributeName)
    {
        /** @var ActiveRecord $primaryModel */
        $primaryModel = $this->owner;
        $primaryModelPk = $primaryModel->getPrimaryKey();

        $bindingKeys = $this->getNewValue($attributeName);

        // HasMany, primary model HAS MANY foreign models, must update foreign model table
        /** @var ActiveRecord $foreignModel */
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
                ->update(
                    $manyTable,
                    [$manyTableFkColumn => $defaultValue],
                    [$manyTableFkColumn => $manyTableFkValue])
                ->execute();

            // Write new relations
            if (!empty($bindingKeys)) {
                $connection->createCommand()
                    ->update(
                        $manyTable,
                        [$manyTableFkColumn => $manyTableFkValue],
                        ['in', $manyTablePkColumn, $bindingKeys])
                    ->execute();
            }
            $transaction->commit();
        } catch (Exception $ex) {
            $transaction->rollback();
            throw $ex;
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
            throw new ErrorException('This value is not a function');
        }

        return call_user_func($function, $value);
    }

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param string $attributeName
     * @return null
     */
    private function hasNewValue($attributeName)
    {
        return isset($this->_values[$attributeName]);
    }

    /**
     * Get value of a dirty attribute by name
     * @param string $attributeName
     * @return null
     */
    private function getNewValue($attributeName)
    {
        return $this->_values[$attributeName];
    }

    /**
     * Get default value for an attribute (used for 1-N relations)
     * @param string $attributeName
     * @return mixed
     */
    private function getDefaultValue($attributeName)
    {
        $relationParams = $this->getRelationParams($attributeName);

        if (!isset($relationParams['default'])) {
            return null;
        }

        if ($relationParams['default'] instanceof \Closure) {
            $closure = $relationParams['default'];
            $relationName = $this->getRelationName($attributeName);
            return call_user_func($closure, $this->owner, $relationName, $attributeName);
        }

        return $relationParams['default'];
    }

    /**
     * Calculate additional value of viaTable
     * @param string $attributeName
     * @param string $viaTableAttribute
     * @param integer $relatedPk
     * @return mixed
     */
    private function getViaTableValue($attributeName, $viaTableAttribute, $relatedPk)
    {
        $viaTableParams = $this->getViaTableParams($attributeName);

        if (!isset($viaTableParams[$viaTableAttribute])) {
            return null;
        }

        if ($viaTableParams[$viaTableAttribute] instanceof \Closure) {
            $closure = $viaTableParams[$viaTableAttribute];
            $relationName = $this->getRelationName($attributeName);
            return call_user_func($closure, $this->owner, $relationName, $attributeName, $relatedPk);
        }

        return $viaTableParams[$viaTableAttribute];
    }

    /**
     * Get additional parameters of viaTable
     * @param string $attributeName
     * @return array
     */
    private function getViaTableParams($attributeName)
    {
        $params = $this->getRelationParams($attributeName);
        return isset($params['viaTableValues'])
            ? $params['viaTableValues']
            : [];
    }

    /**
     * Get custom condition used to delete old records.
     * @param string $attributeName
     * @return array
     */
    private function getCustomDeleteCondition($attributeName)
    {
        $params = $this->getRelationParams($attributeName);

        return isset($params['customDeleteCondition'])
            ? $params['customDeleteCondition']
            : [];
    }

    /**
     * Get parameters of a field
     * @param string $fieldName
     * @return mixed
     * @throws ErrorException
     */
    private function getFieldParams($fieldName)
    {
        if (empty($this->_fields[$fieldName])) {
            throw new ErrorException('Parameter "' . $fieldName . '" does not exist');
        }

        return $this->_fields[$fieldName];
    }

    /**
     * Get parameters of a relation
     * @param string $attributeName
     * @return mixed
     * @throws ErrorException
     */
    private function getRelationParams($attributeName)
    {
        if (empty($this->relations[$attributeName])) {
            throw new ErrorException('Parameter "' . $attributeName . '" does not exist.');
        }

        return $this->relations[$attributeName];
    }

    /**
     * Get name of a relation
     * @param string $attributeName
     * @return null
     */
    private function getRelationName($attributeName)
    {
        $params = $this->getRelationParams($attributeName);

        if (is_string($params)) {
            return $params;
        }

        if (is_array($params) && !empty($params[0])) {
            return $params[0];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->_fields) ?
            true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return array_key_exists($name, $this->_fields) ?
            true : parent::canSetProperty($name, $checkVars = true);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        $fieldParams = $this->getFieldParams($name);
        $attributeName = $fieldParams['attribute'];
        $relationName = $this->getRelationName($attributeName);

        if ($this->hasNewValue($attributeName)) {
            $value = $this->getNewValue($attributeName);
        } else {
            /** @var ActiveRecord $owner */
            $owner = $this->owner;
            $relation = $owner->getRelation($relationName);

            /** @var ActiveRecord $foreignModel */
            $foreignModel = new $relation->modelClass();
            $value = $relation->select($foreignModel->getPrimaryKey())->column();
        }

        if (empty($fieldParams['get'])) {
            return $value;
        }

        return $this->callUserFunction($fieldParams['get'], $value);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        $fieldParams = $this->getFieldParams($name);
        $attributeName = $fieldParams['attribute'];

        if (!empty($fieldParams['set'])) {
            $this->_values[$attributeName] = $this->callUserFunction($fieldParams['set'], $value);
        } else {
            $this->_values[$attributeName] = $value;
        }
    }
}
