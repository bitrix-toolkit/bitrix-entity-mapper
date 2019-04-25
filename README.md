# Bitrix Entity Mapper

[![coverage report](https://gitlab.com/sheerockoff/bitrix-entity-mapper/badges/master/coverage.svg)](https://gitlab.com/sheerockoff/bitrix-entity-mapper/-/jobs)
[![pipeline status](https://gitlab.com/sheerockoff/bitrix-entity-mapper/badges/master/pipeline.svg)](https://gitlab.com/sheerockoff/bitrix-entity-mapper/pipelines)
[![php version](https://img.shields.io/packagist/php-v/sheerockoff/bitrix-entity-mapper.svg)](https://packagist.org/packages/sheerockoff/bitrix-entity-mapper)
[![bitrix version](https://img.shields.io/badge/bitrix-v18.1.5-red.svg)](https://www.1c-bitrix.ru/download/cms.php)

Альтернативный ORM для Bitrix.

## Установка

```
composer require sheerockoff/bitrix-entity-mapper:dev-master
```

## Быстрый старт

Описываем с помощью PHPDoc аннотаций способ хранения объектов в Bitrix:

```php
<?php

use Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Field;
use Sheerockoff\BitrixEntityMapper\Annotation\Property\Property;

/**
 * @InfoBlock(type="library", code="books", name="Книги")
 */
class Book
{
    /**
     * @Field(code="ID", primaryKey=true) 
     */
    public $id;
    
    /**
     * @Field(code="ACTIVE") 
     */
    public $active = true;
    
    /**
     * @Field(code="NAME") 
     */
    public $title;
    
    /**
     * @Property(code="author", type="string", name="Автор") 
     */
    public $author;
    
    /**
     * @var DateTime|string|null
     * @Property(code="published_at", type="datetime", name="Дата публикации") 
     */
    public $publishedAt;
}
```

Применяем автоматические миграции:

```php
<?php

use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Entity\Book;

require 'vendor/autoload.php';

$entityMap = EntityMap::fromClass(Book::class);
SchemaBuilder::build($entityMap);
```

Сохраняем новый объект:

```php
<?php

use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

require 'vendor/autoload.php';

$book = new Book();
$book->active = true;
$book->title = 'Остров сокровищ';
$book->author = 'Р. Л. Стивенсон';
$book->publishedAt = new DateTime('1883-06-14 00:00:00');

$bitrixId = EntityMapper::save($book);
```

Получаем объект по фильтру сущности:

```php
<?php

use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

require 'vendor/autoload.php';

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->where('title', 'Остров сокровищ')->fetch();

if (!$book) {
    throw new InvalidArgumentException('Книга "Остров сокровищ" не найдена.');
}

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->where('author', '%', 'Стивенсон')->fetchAll();

if (!$books) {
    throw new InvalidArgumentException('Книги Стивенсона не найдены.');
}

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->where('publishedAt', '<', '01.01.1900')->fetchAll();

if (!$books) {
    throw new InvalidArgumentException('Книги опубликованные до 1900 года не найдены.');
}
```

Получаем объект по фильтру Bitrix:

```php
<?php

use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

require 'vendor/autoload.php';

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->whereRaw('ID', 1)->fetch();
if (!$book) {
    throw new InvalidArgumentException('Не найдена книга с ID элемента инфоблока = 1.');
}
```

Сортируем выборку:

```php
<?php

use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

require 'vendor/autoload.php';

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->orderBy('publishedAt', 'desc')->fetch();

if (!$book) {
    throw new InvalidArgumentException('Последняя опубликованная книга не найдена.');
}
```

Обновляем существующий объект:

```php
<?php

use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $existBook */
$existBook = EntityMapper::select(Book::class)->fetch();

if (!$existBook) {
    throw new InvalidArgumentException('Случайная книга не найдена.');
}

$existBook->title = 'Забытая книга';
$existBook->author = 'Неизвестный автор';
$existBook->publishedAt = null;
$existBook->active = false;

$updatedBitrixId = EntityMapper::save($existBook);
```