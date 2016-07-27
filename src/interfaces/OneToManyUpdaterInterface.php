<?php

namespace voskobovich\manytomany\interfaces;

/**
 * Interface OneToManyUpdaterInterface
 * @package voskobovich\manytomany\interfaces
 */
interface OneToManyUpdaterInterface
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
    public function saveOneToManyRelation($relation, $attributeName);
}