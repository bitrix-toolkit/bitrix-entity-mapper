<?php

namespace Sheerockoff\BitrixEntityMapper\Annotation\Property;

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Field extends AbstractPropertyAnnotation implements PropertyAnnotationInterface
{
    /**
     * @var string
     * @Required
     * @Enum({
     *     "ID", "ACTIVE", "NAME", "CODE", "XML_ID", "SORT", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO",
     *     "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_TEXT", "DETAIL_PICTURE"
     * })
     */
    protected $code;

    /**
     * @var bool
     */
    protected $primaryKey;

    public function __construct(array $values)
    {
        $this->code = isset($values['code']) ? $values['code'] : null;
        $this->primaryKey = isset($values['primaryKey']) ? (bool)$values['primaryKey'] : false;
        $this->type = self::getTypeByCode($this->code);
    }

    /**
     * @param string $code
     * @return string
     */
    private static function getTypeByCode($code)
    {
        if (in_array($code, ['ID', 'SORT'])) {
            return self::TYPE_INTEGER;
        } elseif (in_array($code, ['ACTIVE'])) {
            return self::TYPE_BOOLEAN;
        } elseif (in_array($code, ['DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO'])) {
            return self::TYPE_DATETIME;
        } elseif (in_array($code, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
            return self::TYPE_FILE;
        } else {
            return self::TYPE_STRING;
        }
    }
}