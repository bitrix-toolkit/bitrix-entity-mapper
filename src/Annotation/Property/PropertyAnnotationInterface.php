<?php // @codeCoverageIgnoreStart

namespace Sheerockoff\BitrixEntityMapper\Annotation\Property;

interface PropertyAnnotationInterface
{
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_DATETIME = 'datetime';
    const TYPE_FILE = 'file';
    const TYPE_ENTITY = 'entity';

    /**
     * @return string
     */
    public function getCode();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function isMultiple();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function isPrimaryKey();

    /**
     * @return string
     */
    public function getEntity();
}