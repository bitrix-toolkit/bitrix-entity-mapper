build:
	composer install --prefer-dist --no-interaction

test:
	php vendor/bin/phpunit --whitelist src/ --coverage-text