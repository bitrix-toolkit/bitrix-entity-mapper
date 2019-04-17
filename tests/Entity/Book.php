<?php

namespace Entity;

use DateTime;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;

/**
 * Class Book
 * @package Entity
 * @InfoBlock(type="entity", code="books", name="Книги")
 */
class Book
{
    /**
     * @var string
     * @Field(code="NAME")
     */
    public $title;
    /**
     * @var string
     * @Property(code="author", type="string", name="Автор")
     */
    public $author;
    /**
     * @var DateTime
     * @Property(code="published_at", type="datetime", name="Опубликована")
     */
    public $publishedAt;
    /**
     * @var bool
     * @Property(code="is_bestseller", type="boolean", name="Бестселлер")
     */
    public $isBestseller;
    /**
     * @var int
     * @Property(code="pages_num", type="integer", name="Кол-во страниц")
     */
    public $pagesNum;
    /**
     * @var int
     * @Field(code="ID", primaryKey=true)
     */
    protected $id;
}