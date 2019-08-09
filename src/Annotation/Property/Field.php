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
     *     "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_TEXT", "DETAIL_PICTURE", "SECTION_ID"
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
        $map = [
            'ID' => self::TYPE_INTEGER,
            'SORT' => self::TYPE_INTEGER,
            'ACTIVE' => self::TYPE_BOOLEAN,
            'DATE_ACTIVE_FROM' => self::TYPE_DATETIME,
            'DATE_ACTIVE_TO' => self::TYPE_DATETIME,
            'PREVIEW_PICTURE' => self::TYPE_FILE,
            'DETAIL_PICTURE' => self::TYPE_FILE,
            'SECTION_ID' => self::TYPE_INTEGER,
        ];

        return array_key_exists($code, $map) ? $map[$code] : self::TYPE_STRING;
    }
}