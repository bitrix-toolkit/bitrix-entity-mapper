<?php

use Sheerockoff\BitrixCi\Bootstrap;

require __DIR__ . '/../vendor/autoload.php';

echo "SQL dump migrating...";
Bootstrap::migrate();
echo "COMPLETE\n";

Bootstrap::bootstrap();

require __DIR__ . '/resources/Entity/Book.php';
require __DIR__ . '/resources/Entity/Author.php';
require __DIR__ . '/resources/Entity/WithoutInfoBlockAnnotation.php';
require __DIR__ . '/resources/Entity/WithConflictPropertyAnnotations.php';
