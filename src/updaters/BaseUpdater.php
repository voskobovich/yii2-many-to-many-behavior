<?php

namespace voskobovich\manytomany\updaters;

use voskobovich\manytomany\interfaces\ManyToManyBehaviorInterface;
use yii\base\Behavior;
use yii\base\Object;

/**
 * Class BaseUpdater
 * @package voskobovich\manytomany\updaters
 */
abstract class BaseUpdater extends Object
{
    /**
     * @var ManyToManyBehaviorInterface|Behavior
     */
    protected $_behavior;

    /**
     * @param ManyToManyBehaviorInterface $behavior
     * @return mixed
     */
    public function setBehavior(ManyToManyBehaviorInterface $behavior)
    {
        $this->_behavior = $behavior;
    }
}