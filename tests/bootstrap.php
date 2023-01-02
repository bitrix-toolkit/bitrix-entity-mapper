<?php

use Sheerockoff\BitrixCi\Bootstrap;

require __DIR__ . '/../vendor/autoload.php';

if (!getenv('SKIP_MIGRATION')) {
    ConsoleLog("Migration...");
    Bootstrap::migrate();
    ConsoleLog("COMPLETE\n");
}

Bootstrap::bootstrap();

while (ob_get_level()) {
    ob_end_clean();
}

require __DIR__ . '/resources/Entity/Book.php';
require __DIR__ . '/resources/Entity/Author.php';
require __DIR__ . '/resources/Entity/WithoutInfoBlockAnnotation.php';
require __DIR__ . '/resources/Entity/WithConflictPropertyAnnotations.php';

function ConsoleLog(string $message): void
{
    file_put_contents('php://stdout', $message);
}
