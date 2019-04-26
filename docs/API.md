# Bitrix Entity Mapper

* [AnnotationReader](#annotationreader)
    * [__construct](#__construct)
* [EntityMap](#entitymap)
    * [__construct](#__construct-1)
    * [fromClass](#fromclass)
    * [getClass](#getclass)
    * [getAnnotation](#getannotation)
    * [getReflection](#getreflection)
    * [getProperties](#getproperties)
    * [getProperty](#getproperty)
* [EntityMapper](#entitymapper)
    * [save](#save)
    * [select](#select)
* [Field](#field)
    * [getCode](#getcode)
    * [getType](#gettype)
    * [isMultiple](#ismultiple)
    * [getName](#getname)
    * [isPrimaryKey](#isprimarykey)
    * [getEntity](#getentity)
    * [__construct](#__construct-2)
* [InfoBlock](#infoblock)
    * [__construct](#__construct-3)
    * [getType](#gettype-1)
    * [getCode](#getcode-1)
    * [getName](#getname-1)
* [Property](#property)
    * [getCode](#getcode-2)
    * [getType](#gettype-2)
    * [isMultiple](#ismultiple-1)
    * [getName](#getname-2)
    * [isPrimaryKey](#isprimarykey-1)
    * [getEntity](#getentity-1)
    * [__construct](#__construct-4)
* [PropertyMap](#propertymap)
    * [__construct](#__construct-5)
    * [getCode](#getcode-3)
    * [getAnnotation](#getannotation-1)
    * [getReflection](#getreflection-1)
* [RawResult](#rawresult)
    * [__construct](#__construct-6)
    * [getId](#getid)
    * [getInfoBlockId](#getinfoblockid)
    * [getData](#getdata)
    * [getField](#getfield)
* [SchemaBuilder](#schemabuilder)
    * [build](#build)
* [Select](#select-1)
    * [__construct](#__construct-7)
    * [from](#from)
    * [where](#where)
    * [whereRaw](#whereraw)
    * [orderBy](#orderby)
    * [rawIterator](#rawiterator)
    * [iterator](#iterator)
    * [fetch](#fetch)
    * [fetchAll](#fetchall)

## AnnotationReader





* Full name: \Sheerockoff\BitrixEntityMapper\Annotation\AnnotationReader
* Parent class: \Doctrine\Common\Annotations\AnnotationReader


### __construct

AnnotationReader constructor.

```php
AnnotationReader::__construct( \Doctrine\Common\Annotations\DocParser|null $parser = null )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$parser` | **\Doctrine\Common\Annotations\DocParser&#124;null** |  |




---

## EntityMap





* Full name: \Sheerockoff\BitrixEntityMapper\Map\EntityMap


### __construct

EntityMap constructor.

```php
EntityMap::__construct( string $class, \Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock $annotation, \ReflectionClass $reflection, array<mixed,\Sheerockoff\BitrixEntityMapper\Map\PropertyMap> $properties )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** |  |
| `$annotation` | **\Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock** |  |
| `$reflection` | **\ReflectionClass** |  |
| `$properties` | **array<mixed,\Sheerockoff\BitrixEntityMapper\Map\PropertyMap>** |  |




---

### fromClass



```php
EntityMap::fromClass( string|object $class ): \Sheerockoff\BitrixEntityMapper\Map\EntityMap
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string&#124;object** |  |




---

### getClass



```php
EntityMap::getClass(  ): string
```







---

### getAnnotation



```php
EntityMap::getAnnotation(  ): \Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock
```







---

### getReflection



```php
EntityMap::getReflection(  ): \ReflectionClass
```







---

### getProperties



```php
EntityMap::getProperties(  ): array<mixed,\Sheerockoff\BitrixEntityMapper\Map\PropertyMap>
```







---

### getProperty



```php
EntityMap::getProperty( string $code ): \Sheerockoff\BitrixEntityMapper\Map\PropertyMap
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **string** |  |




---

## EntityMapper





* Full name: \Sheerockoff\BitrixEntityMapper\EntityMapper


### save



```php
EntityMapper::save( object $object ): boolean|integer
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **object** |  |




---

### select



```php
EntityMapper::select( string $class ): \Sheerockoff\BitrixEntityMapper\Query\Select
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** |  |




---

## Field





* Full name: \Sheerockoff\BitrixEntityMapper\Annotation\Property\Field
* Parent class: \Sheerockoff\BitrixEntityMapper\Annotation\Property\AbstractPropertyAnnotation
* This class implements: \Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface


### getCode



```php
Field::getCode(  ): string
```







---

### getType



```php
Field::getType(  ): string
```







---

### isMultiple



```php
Field::isMultiple(  ): boolean
```







---

### getName



```php
Field::getName(  ): string
```







---

### isPrimaryKey



```php
Field::isPrimaryKey(  ): boolean
```







---

### getEntity



```php
Field::getEntity(  ): string
```







---

### __construct



```php
Field::__construct( array $values )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$values` | **array** |  |




---

## InfoBlock





* Full name: \Sheerockoff\BitrixEntityMapper\Annotation\Entity\InfoBlock


### __construct

InfoBlock constructor.

```php
InfoBlock::__construct( array $values )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$values` | **array** |  |




---

### getType



```php
InfoBlock::getType(  ): string
```







---

### getCode



```php
InfoBlock::getCode(  ): string
```







---

### getName



```php
InfoBlock::getName(  ): string
```







---

## Property





* Full name: \Sheerockoff\BitrixEntityMapper\Annotation\Property\Property
* Parent class: \Sheerockoff\BitrixEntityMapper\Annotation\Property\AbstractPropertyAnnotation
* This class implements: \Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface


### getCode



```php
Property::getCode(  ): string
```







---

### getType



```php
Property::getType(  ): string
```







---

### isMultiple



```php
Property::isMultiple(  ): boolean|null
```







---

### getName



```php
Property::getName(  ): string
```







---

### isPrimaryKey



```php
Property::isPrimaryKey(  ): boolean
```







---

### getEntity



```php
Property::getEntity(  ): string
```







---

### __construct



```php
Property::__construct( array $values )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$values` | **array** |  |




---

## PropertyMap





* Full name: \Sheerockoff\BitrixEntityMapper\Map\PropertyMap


### __construct

PropertyMap constructor.

```php
PropertyMap::__construct( string $code, \Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface $annotation, \ReflectionProperty $reflection )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **string** |  |
| `$annotation` | **\Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface** |  |
| `$reflection` | **\ReflectionProperty** |  |




---

### getCode



```php
PropertyMap::getCode(  ): string
```







---

### getAnnotation



```php
PropertyMap::getAnnotation(  ): \Sheerockoff\BitrixEntityMapper\Annotation\Property\PropertyAnnotationInterface
```







---

### getReflection



```php
PropertyMap::getReflection(  ): \ReflectionProperty
```







---

## RawResult





* Full name: \Sheerockoff\BitrixEntityMapper\Query\RawResult


### __construct

RawResult constructor.

```php
RawResult::__construct( integer $id, integer $infoBlockId, array $data )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **integer** |  |
| `$infoBlockId` | **integer** |  |
| `$data` | **array** |  |




---

### getId



```php
RawResult::getId(  ): integer
```







---

### getInfoBlockId



```php
RawResult::getInfoBlockId(  ): integer
```







---

### getData



```php
RawResult::getData(  ): array
```







---

### getField



```php
RawResult::getField( string $code ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$code` | **string** |  |




---

## SchemaBuilder





* Full name: \Sheerockoff\BitrixEntityMapper\SchemaBuilder


### build



```php
SchemaBuilder::build( \Sheerockoff\BitrixEntityMapper\Map\EntityMap $entityMap ): boolean
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$entityMap` | **\Sheerockoff\BitrixEntityMapper\Map\EntityMap** |  |




---

## Select





* Full name: \Sheerockoff\BitrixEntityMapper\Query\Select


### __construct

Select constructor.

```php
Select::__construct( string $class )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** |  |




---

### from



```php
Select::from( string $class ): \Sheerockoff\BitrixEntityMapper\Query\Select
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$class` | **string** |  |




---

### where

Может быть вызвано с двумя или тремя аргументами.

```php
Select::where( string $p, mixed $_ ): $this
```

Если 2 аргумента, то название свойства и значение для фильтрации.
Например: $this->where('name', 'bender');
По-умолчанию будет использован оператор сравнения "=".

Если 3 аргумента, то название свойства, оператор сравнения и значение для фильтрации.
Например: $this->where('age', '>', 18);


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$p` | **string** | Название свойства класса для фильтрации. |
| `$_` | **mixed** | Если 3 аргумента то оператор сравнения, иначе значение для фильтрации. |




---

### whereRaw



```php
Select::whereRaw( string $f, mixed $_ ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$f` | **string** |  |
| `$_` | **mixed** |  |




---

### orderBy



```php
Select::orderBy( string $p, string $d = &#039;asc&#039; ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$p` | **string** |  |
| `$d` | **string** |  |




---

### rawIterator



```php
Select::rawIterator(  ): \Generator|array<mixed,\Sheerockoff\BitrixEntityMapper\Query\RawResult>
```







---

### iterator



```php
Select::iterator(  ): \Generator
```







---

### fetch



```php
Select::fetch(  ): object
```







---

### fetchAll



```php
Select::fetchAll(  ): array<mixed,object>
```







---



--------
> This document was automatically generated from source code comments on 2019-04-26 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
