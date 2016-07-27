<?php

namespace voskobovich\manytomany\updaters;

use voskobovich\manytomany\interfaces\ManyToManyUpdaterInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class ManyToManyUpdater
 * @package voskobovich\manytomany\updaters
 */
class ManyToManyUpdater extends BaseUpdater implements ManyToManyUpdaterInterface
{
    /**
     * @param ActiveQuery $relation
     * @param string $attributeName
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function saveManyToManyRelation($relation, $attributeName)
    {
        /** @var ActiveRecord $primaryModel */
        $primaryModel = $this->_behavior->owner;
        $primaryModelPk = $primaryModel->getPrimaryKey();

        $bindingKeys = $this->_behavior->getNewValue($attributeName);

        // Assuming junction column is visible from the primary model connection
        if (is_array($relation->via)) {
            // via()
            $via = $relation->via[1];
            /** @var ActiveRecord $junctionModelClass */
            $junctionModelClass = $via->modelClass;
            $viaTableName = $junctionModelClass::tableName();
            list($junctionColumn) = array_keys($via->link);
        } else {
            // viaTable()
            list($viaTableName) = array_values($relation->via->from);
            list($junctionColumn) = array_keys($relation->via->link);
        }

        list($relatedColumn) = array_values($relation->link);

        $connection = $primaryModel::getDb();
        $transaction = $connection->beginTransaction();
        try {
            // Load current rows
            $currentRows = $primaryModel::find()
                ->from($viaTableName)
                ->where(ArrayHelper::merge(
                    [$junctionColumn => $primaryModelPk],
                    $this->_behavior->getCustomDeleteCondition($attributeName)
                ))
                ->indexBy($relatedColumn)
                ->asArray()
                ->all();

            $currentKeys = array_map(function ($item) use ($relatedColumn) {
                return $item[$relatedColumn];
            }, $currentRows);

            if (!empty($bindingKeys)) {
                // Find removed relations
                $removedKeys = array_diff($currentKeys, $bindingKeys);
                // Find new relations
                $addedKeys = array_diff($bindingKeys, $currentKeys);
                // Find untouched relations
                $untouchedKeys = array_diff($currentKeys, $removedKeys, $addedKeys);

                $viaTableParams = $this->_behavior->getViaTableParams($attributeName);
                $viaTableColumns = array_keys($viaTableParams);

                $junctionColumns = [$junctionColumn, $relatedColumn];
                foreach ($viaTableColumns as $viaTableColumn) {
                    $junctionColumns[] = $viaTableColumn;
                }

                // Write new relations
                if (!empty($addedKeys)) {
                    $junctionRows = [];
                    foreach ($addedKeys as $addedKey) {
                        $row = [$primaryModelPk, $addedKey];

                        // Calculate additional viaTable values
                        foreach ($viaTableColumns as $viaTableColumn) {
                            $row[] = $this->_behavior->getViaTableValue($attributeName, $viaTableColumn, $addedKey);
                        }

                        array_push($junctionRows, $row);
                    }

                    $connection->createCommand()
                        ->batchInsert($viaTableName, $junctionColumns, $junctionRows)
                        ->execute();
                }

                // Processing untouched relations
                if (!empty($untouchedKeys) && !empty($viaTableColumns)) {
                    foreach ($untouchedKeys as $untouchedKey) {
                        // Calculate additional viaTable values
                        $row = [];
                        foreach ($viaTableColumns as $viaTableColumn) {
                            $row[$viaTableColumn] = $this->_behavior->getViaTableValue($attributeName, $viaTableColumn,
                                $untouchedKey, false);
                        }

                        $currentRow = (array)$currentRows[$untouchedKey];
                        unset($currentRow[$junctionColumn]);
                        unset($currentRow[$relatedColumn]);

                        if (array_diff_assoc($currentRow, $row)) {
                            $connection->createCommand()
                                ->update($viaTableName, $row, [
                                    $junctionColumn => $primaryModelPk,
                                    $relatedColumn => $untouchedKey
                                ])
                                ->execute();
                        }
                    }
                }
            } else {
                $removedKeys = $currentKeys;
            }

            if (!empty($removedKeys)) {
                $connection->createCommand()
                    ->delete($viaTableName, ArrayHelper::merge(
                        [$junctionColumn => $primaryModelPk],
                        [$relatedColumn => $removedKeys],
                        $this->_behavior->getCustomDeleteCondition($attributeName)
                    ))
                    ->execute();
            }

            $transaction->commit();
        } catch (Exception $ex) {
            $transaction->rollBack();
            throw $ex;
        }
    }
}