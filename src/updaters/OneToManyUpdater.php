<?php

namespace voskobovich\manytomany\updaters;

use voskobovich\manytomany\interfaces\OneToManyUpdaterInterface;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * Class OneToManyUpdater
 * @package voskobovich\manytomany\updaters
 */
class OneToManyUpdater extends BaseUpdater implements OneToManyUpdaterInterface
{
    /**
     * @param ActiveQuery $relation
     * @param string $attributeName
     * @throws Exception
     */
    public function saveOneToManyRelation($relation, $attributeName)
    {
        /** @var ActiveRecord $primaryModel */
        $primaryModel = $this->_behavior->owner;
        $primaryModelPk = $primaryModel->getPrimaryKey();

        $bindingKeys = $this->_behavior->getNewValue($attributeName);

        // HasMany, primary model HAS MANY foreign models, must update foreign model table
        /** @var ActiveRecord $foreignModel */
        $foreignModel = Yii::createObject($relation->modelClass);
        $manyTable = $foreignModel->tableName();

        list($manyTableFkColumn) = array_keys($relation->link);
        $manyTableFkValue = $primaryModelPk;
        list($manyTablePkColumn) = ($foreignModel->primaryKey());

        $connection = $foreignModel::getDb();
        $transaction = $connection->beginTransaction();

        $defaultValue = $this->_behavior->getDefaultValue($attributeName);

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
            $transaction->rollBack();
            throw $ex;
        }
    }
}