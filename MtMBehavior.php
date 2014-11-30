<?php
/**
 * Created by PhpStorm.
 * User: Vitaly Voskobovich
 * Date: 27.11.14
 * Time: 21:00
 */

namespace voskobovich\behaviors;

use Yii;
use yii\base\ErrorException;
use yii\db\ActiveRecord;

/**
 * Class MtMBehavior
 * @package voskobovich\mtm
 *
 * This behavior makes it easy to maintain
 * relations many-to-many in ActiveRecord model.
 *
 * Usage:
 * 1. Add new attributes for usage in ActiveForm
 * 2. Add new validation rule for new attributes
 * 3. Add config behavior in your model and set array relations
 *
 * // This attributes usage in form
 * public $users_list = array();
 * public $tasks_list = array();
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
 *             'class' => \voskobovich\behaviors\MtMBehavior::className(),
 *             'relations' => [
 *                 'users' => 'users_list',
 *                 'tasks' => [
 *                     'tasks_list',
 *                     function($tasksList) {
 *                         return array_rand($tasksList, 2);
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

class MtMBehavior extends \yii\base\Behavior
{
    /**
     * Relations list
     * @var array
     */
    public $relations = array();

    /**
     * Events list
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveRelations',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveRelations',
            ActiveRecord::EVENT_AFTER_FIND   => 'loadRelations'
        ];
    }

    /**
     * Load relation data from model attributes
     * @param $event
     */
    public function loadRelations($event)
    {
        $component = $event->sender;
        list($primaryKey) = $component::primaryKey();

        foreach($this->relations as $relationName => $source)
        {
            if(is_array($source))
                list($attributeName) = $source;
            else
                $attributeName = $source;

            $relation = $component->getRelation($relationName);

            if(!is_null($relation))
            {
                $relatedModels = $relation->indexBy($primaryKey)->all();
                $component->{$attributeName} = array_keys($relatedModels);
            }
        }
    }

    /**
     * Save relations value in data base
     * @param $event
     * @throws ErrorException
     * @throws \yii\db\Exception
     */
    public function saveRelations($event)
    {
        $component = $event->sender;
        $safeAttributes = $component->safeAttributes();

        foreach($this->relations as $relationName => $source)
        {
            if(array_search($relationName, $safeAttributes) === NULL)
                throw new ErrorException("Relation \"{$relationName}\" must be safe attributes");

            if(is_array($component->getPrimaryKey()))
                throw new ErrorException("This behavior not supported composite primary key");

            $relation = $component->getRelation($relationName);

            if(empty($relation->via))
                throw new ErrorException("Attribute \"{$relationName}\" is not relation");

            list($junctionTable) = array_values($relation->via->from);
            list($relatedColumn) = array_values($relation->link);
            list($junctionColumn) = array_keys($relation->via->link);

            // Get relation keys of attribute name
            if(is_string($source) && isset($component->{$source}))
                $relatedPkCollection = $component->{$source};
            elseif(is_array($source))
            {
                list($attributeName, $callback) = $source;

                if(isset($component->{$attributeName})) {
                    $relatedPkCollection = (array)call_user_func($callback, $component->{$attributeName});
                    $component->{$attributeName} = $relatedPkCollection;
                }
            }

            // Save relations data
            if(!empty($relatedPkCollection))
            {
                $transaction = Yii::$app->db->beginTransaction();
                try
                {
                    $connection = Yii::$app->db;
                    $componentPk = $component->getPrimaryKey();

                    // Remove relations
                    $connection->createCommand()
                        ->delete($junctionTable, "{$junctionColumn} = :id", [':id' => $componentPk])
                        ->execute();

                    // Write new relations
                    $junctionRows = array();
                    foreach($relatedPkCollection as $relatedPk)
                        array_push($junctionRows, [$componentPk, $relatedPk]);

                    $connection->createCommand()
                        ->batchInsert($junctionTable, [$junctionColumn, $relatedColumn], $junctionRows)
                        ->execute();

                    $transaction->commit();
                }
                catch(\yii\db\Exception $ex)
                {
                    $transaction->rollback();
                }
            }
        }
    }
}