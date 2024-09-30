# Bitrix Entity Mapper

[![PHPUnit](https://github.com/bitrix-toolkit/bitrix-entity-mapper/actions/workflows/php-unit.yml/badge.svg)](https://github.com/bitrix-toolkit/bitrix-entity-mapper/actions/workflows/php-unit.yml)
[![Coverage](https://scrutinizer-ci.com/g/bitrix-toolkit/bitrix-entity-mapper/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bitrix-toolkit/bitrix-entity-mapper/?branch=master)
[![Scrutinizer](https://scrutinizer-ci.com/g/bitrix-toolkit/bitrix-entity-mapper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bitrix-toolkit/bitrix-entity-mapper/?branch=master)

Альтернативный ORM для Bitrix.

## Установка

```bash
composer require bitrix-toolkit/bitrix-entity-mapper
```

## Быстрый старт

Описываем с помощью PHPDoc аннотаций способ хранения объектов в Bitrix:

```php
<?php

use BitrixToolkit\BitrixEntityMapper\Annotation\Entity\InfoBlock;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Field;
use BitrixToolkit\BitrixEntityMapper\Annotation\Property\Property;

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
     * @var DateTime|null
     * @Property(code="published_at", type="datetime", name="Дата публикации") 
     */
    public $publishedAt;
}
```

Подключаем Bitrix, модуль `iblock` и автолоадинг Composer:

```php
require 'bitrix/modules/main/include/prolog_before.php';
require 'vendor/autoload.php';
CModule::IncludeModule('iblock');
```

Вручную нужно создать только тип инфоблока. Остальные миграции может выполнить `SchemaBuilder`. 
Метод `SchemaBuilder::build($entityMap)` запускает автоматические миграции, которые
создадут или изменят необходимый инфоблок и свойства инфоблока для сущности:

```php
use BitrixToolkit\BitrixEntityMapper\SchemaBuilder;
use BitrixToolkit\BitrixEntityMapper\Map\EntityMap;
use Entity\Book;

$entityMap = EntityMap::fromClass(Book::class);
SchemaBuilder::build($entityMap);
```

Сохраняем новый объект:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

$book = new Book();
$book->active = true;
$book->title = 'Остров сокровищ';
$book->author = 'Р. Л. Стивенсон';
$book->publishedAt = new DateTime('1883-06-14 00:00:00');

$bitrixId = EntityMapper::save($book);
```

Есть несколько способов перебрать результат:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

$query = EntityMapper::select(Book::class)->where('author', 'Р. Л. Стивенсон');

// Получить один результат.
$query->fetch(); 

// Перебрать по одному результату.
while ($book = $query->fetch()) { /* ... */ }

// Использовать реализованную имплементацию интерфейса Iterator.
foreach ($query as $book) { /* ... */ }

// Использовать метод возвращающий генератор.
foreach ($query->iterator() as $book) { /* ... */ }

// Получить массив со всеми результатами. 
// Не рекомендуется! Небезопасное потребление памяти.
$query->fetchAll();
```

Получаем результат по фильтру сущности:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->where('title', 'Остров сокровищ')->fetch();

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->where('author', '%', 'Стивенсон')->fetchAll();

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->where('publishedAt', '<', '01.01.1900')->fetchAll();
```

Получаем результат по фильтру Bitrix:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->whereRaw('ID', 1)->fetch();

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->whereRaw('ACTIVE', 'Y')->fetchAll();
```

Сортируем выборку:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->orderBy('publishedAt', 'desc')->fetch();
```

Обновляем существующий объект:

```php
use BitrixToolkit\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $existBook */
$existBook = EntityMapper::select(Book::class)->fetch();

if ($existBook) {
    $existBook->title = 'Забытая книга';
    $existBook->author = 'Неизвестный автор';
    $existBook->publishedAt = null;
    $existBook->active = false;
    $updatedBitrixId = EntityMapper::save($existBook);
}
```
