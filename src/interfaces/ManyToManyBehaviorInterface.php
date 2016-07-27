<?php

namespace voskobovich\manytomany\interfaces;

/**
 * Interface ManyToManyBehaviorInterface
 * @package voskobovich\manytomany\interfaces
 */
interface ManyToManyBehaviorInterface
{
    /**
     * Call user function
     * @param $function
     * @param $value
     * @return mixed
     */
    public function callUserFunction($function, $value);

    /**
     * Check if an attribute is dirty and must be saved (its new value exists)
     * @param string $attributeName
     * @return null
     */
    public function hasNewValue($attributeName);

    /**
     * Get value of a dirty attribute by name
     * @param string $attributeName
     * @return null
     */
    public function getNewValue($attributeName);

    /**
     * Get default value for an attribute (used for 1-N relations)
     * @param string $attributeName
     * @return mixed
     */
    public function getDefaultValue($attributeName);

    /**
     * Calculate additional value of viaTable
     * @param string $attributeName
     * @param string $viaTableAttribute
     * @param integer $relatedPk
     * @param bool $isNewRecord
     * @return mixed
     */
    public function getViaTableValue($attributeName, $viaTableAttribute, $relatedPk, $isNewRecord = true);

    /**
     * Get additional parameters of viaTable
     * @param string $attributeName
     * @return array
     */
    public function getViaTableParams($attributeName);

    /**
     * Get custom condition used to delete old records.
     * @param string $attributeName
     * @return array
     */
    public function getCustomDeleteCondition($attributeName);

    /**
     * Get parameters of a field
     * @param string $fieldName
     * @return mixed
     */
    public function getFieldParams($fieldName);

    /**
     * Get parameters of a relation
     * @param string $attributeName
     * @return mixed
     */
    public function getRelationParams($attributeName);

    /**
     * Get name of a relation
     * @param string $attributeName
     * @return null
     */
    public function getRelationName($attributeName);
}