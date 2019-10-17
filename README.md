# Bitrix Entity Mapper

[![Build Status](https://travis-ci.com/sheerockoff/bitrix-entity-mapper.svg?branch=master)](https://travis-ci.com/sheerockoff/bitrix-entity-mapper)
[![Test Coverage](https://api.codeclimate.com/v1/badges/0d09026c52b8f7a32343/test_coverage)](https://codeclimate.com/github/sheerockoff/bitrix-entity-mapper/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/0d09026c52b8f7a32343/maintainability)](https://codeclimate.com/github/sheerockoff/bitrix-entity-mapper/maintainability)

Альтернативный ORM для Bitrix.

## Установка

```bash
composer require sheerockoff/bitrix-entity-mapper
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
use Sheerockoff\BitrixEntityMapper\SchemaBuilder;
use Sheerockoff\BitrixEntityMapper\Map\EntityMap;
use Entity\Book;

$entityMap = EntityMap::fromClass(Book::class);
SchemaBuilder::build($entityMap);
```

Сохраняем новый объект:

```php
use Sheerockoff\BitrixEntityMapper\EntityMapper;
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
use Sheerockoff\BitrixEntityMapper\EntityMapper;
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
use Sheerockoff\BitrixEntityMapper\EntityMapper;
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
use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->whereRaw('ID', 1)->fetch();

/** @var Book[] $books */
$books = EntityMapper::select(Book::class)->whereRaw('ACTIVE', 'Y')->fetchAll();
```

Сортируем выборку:

```php
use Sheerockoff\BitrixEntityMapper\EntityMapper;
use Entity\Book;

/** @var Book|null $book */
$book = EntityMapper::select(Book::class)->orderBy('publishedAt', 'desc')->fetch();
```

Обновляем существующий объект:

```php
use Sheerockoff\BitrixEntityMapper\EntityMapper;
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