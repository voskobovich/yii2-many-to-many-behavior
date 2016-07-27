<?php

namespace voskobovich\manytomany\interfaces;

/**
 * Interface ManyToManyUpdaterInterface
 * @package voskobovich\manytomany\interfaces
 */
interface ManyToManyUpdaterInterface
{
    /**
     * @param ManyToManyBehaviorInterface $behavior
     * @return mixed
     */
    public function setBehavior(ManyToManyBehaviorInterface $behavior);

    /**
     * @param $relation
     * @param $attributeName
     */
    public function saveManyToManyRelation($relation, $attributeName);
}