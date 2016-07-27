<?php

namespace voskobovich\manytomany;

use voskobovich\manytomany\interfaces\ManyToManyBehaviorInterface;
use voskobovich\manytomany\interfaces\ManyToManyUpdaterInterface;
use voskobovich\manytomany\interfaces\OneToManyUpdaterInterface;
use voskobovich\manytomany\updaters\ManyToManyUpdater;
use voskobovich\manytomany\updaters\OneToManyUpdater;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\base\ErrorException;

/**
 * Class ManyToManyBehavior
 * @package voskobovich\manytomany
 *
 * See README.md for examples
 */
class ManyToManyBehavior extends Behavior implements ManyToManyBehaviorInterface
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
                    $fullFieldName = $attributeName . '_' . $fieldName;
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
                if (!empty($params['updaterClass'])) {
                    $updater = Yii::createObject($params['updaterClass']);
                    if (!$updater instanceof ManyToManyUpdaterInterface) {
                        throw new InvalidConfigException('The object passed to "updaterClass" must implement the interface "voskobovich\manytomany\interfaces\ManyToManyUpdaterInterface"');
                    }
                } else {
                    $updater = new ManyToManyUpdater();
                }
                $updater->setBehavior($this);
                $updater->saveManyToManyRelation($relation, $attributeName);
            } elseif (!empty($relation->link) && $relation->multiple) {
                // One-to-many on the many side
                if (!empty($params['updaterClass'])) {
                    $updater = Yii::createObject($params['updaterClass']);
                    if (!$updater instanceof OneToManyUpdaterInterface) {
                        throw new InvalidConfigException('The object passed to "updaterClass" must implement the interface "voskobovich\manytomany\interfaces\OneToManyUpdaterInterface"');
                    }
                } else {
                    $updater = new OneToManyUpdater();
                }
                $updater->setBehavior($this);
                $updater->saveOneToManyRelation($relation, $attributeName);
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
    public function callUserFunction($function, $value)
    {
        if (!is_array($function) && !is_callable($function)) {
            throw new ErrorException('This value is not a function');
        }

        return call_user_func($function, $value);
    }

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param string $attributeName
     * @return null
     */
    public function hasNewValue($attributeName)
    {
        return isset($this->_values[$attributeName]);
    }

    /**
     * Get value of a dirty attribute by name
     * @param string $attributeName
     * @return null
     */
    public function getNewValue($attributeName)
    {
        return $this->_values[$attributeName];
    }

    /**
     * Get default value for an attribute (used for 1-N relations)
     * @param string $attributeName
     * @return mixed
     */
    public function getDefaultValue($attributeName)
    {
        $relationParams = $this->getRelationParams($attributeName);

        if (!isset($relationParams['default'])) {
            return null;
        }

        if (is_callable($relationParams['default'])) {
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
     * @param bool $isNewRecord
     * @return mixed
     */
    public function getViaTableValue($attributeName, $viaTableAttribute, $relatedPk, $isNewRecord = true)
    {
        $viaTableParams = $this->getViaTableParams($attributeName);

        if (!isset($viaTableParams[$viaTableAttribute])) {
            return null;
        }

        if (is_callable($viaTableParams[$viaTableAttribute])) {
            $closure = $viaTableParams[$viaTableAttribute];
            $relationName = $this->getRelationName($attributeName);
            return call_user_func($closure, $this->owner, $relationName, $attributeName, $relatedPk, $isNewRecord);
        }

        return $viaTableParams[$viaTableAttribute];
    }

    /**
     * Get additional parameters of viaTable
     * @param string $attributeName
     * @return array
     */
    public function getViaTableParams($attributeName)
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
    public function getCustomDeleteCondition($attributeName)
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
    public function getFieldParams($fieldName)
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
    public function getRelationParams($attributeName)
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
    public function getRelationName($attributeName)
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
            $foreignModel = Yii::createObject($relation->modelClass);
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
