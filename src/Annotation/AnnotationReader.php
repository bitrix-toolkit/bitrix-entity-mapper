<?php

namespace BitrixToolkit\BitrixEntityMapper\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;

class AnnotationReader extends DoctrineAnnotationReader
{
    /**
     * AnnotationReader constructor.
     * @param DocParser|null $parser
     * @throws AnnotationException
     */
    public function __construct(DocParser $parser = null)
    {
        AnnotationRegistry::registerFile(__DIR__ . '/Entity/InfoBlock.php');
        AnnotationRegistry::registerFile(__DIR__ . '/Property/Field.php');
        AnnotationRegistry::registerFile(__DIR__ . '/Property/Property.php');
        parent::__construct($parser);
    }
}