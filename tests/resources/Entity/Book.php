<?php

namespace Entity;

use CFile;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;

/**
 * Class Book
 * @package Entity
 * @InfoBlock(type="test_entity", code="books", name="Книги")
 */
class Book
{
    /**
     * @var bool
     * @Field(code="ACTIVE")
     */
    public $isShow;

    /**
     * @var string
     * @Field(code="NAME")
     */
    public $title;

    /**
     * @var Author
     * @Property(code="author", type="entity", entity="Entity\Author", name="Автор")
     */
    public $author;

    /**
     * @var Author[]
     * @Property(code="co_authors", type="entity", entity="\Entity\Author", multiple=true, name="Соавторы")
     */
    public $coAuthors = [];

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
     * @var string[]
     * @Property(code="tags", type="string", multiple=true, name="Теги")
     */
    public $tags = [];

    /**
     * @var DateTime[]
     * @Property(code="republications_at", type="datetime", multiple=true, name="Переиздания")
     */
    public $republicationsAt = [];

    /**
     * @var mixed
     */
    public $notMappedProperty = 'not_mapped_property';

    /**
     * @var int|null
     * @Property(code="cover", type="file", name="Обложка")
     */
    protected $cover;

    /**
     * @var int
     * @Field(code="ID", primaryKey=true)
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $imgPath
     */
    public function setCover($imgPath)
    {
        if (empty($imgPath)) {
            $this->cover = null;
            return;
        }

        if (!is_file($imgPath)) {
            throw new InvalidArgumentException("Файл $imgPath не найден.");
        }

        $content = file_get_contents($imgPath);
        $imgInfo = getimagesizefromstring($content);

        if (empty($imgInfo['mime']) || !preg_match('/^image\//ui', $imgInfo['mime'])) {
            throw new InvalidArgumentException("Файл $imgPath не является файлом изображения.");
        }

        $arFile = [
            'name' => pathinfo($imgPath, PATHINFO_BASENAME),
            'type' => $imgInfo['mime'],
            'content' => $content
        ];

        $fileId = CFile::SaveFile($arFile, 'books');
        if (!$fileId) {
            throw new RuntimeException("Ошибка сохранения файла $imgPath.");
        }

        $this->cover = $fileId;
    }

    /**
     * @return string|null
     */
    public function getCover()
    {
        return $this->cover ? CFile::GetPath($this->cover) : null;
    }
}