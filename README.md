PHP WebVTT parser and validator
===========================

This is a rough one to convert.  I should have written from scratch



Relevant links:

### Original library in JS

* [Live WebVTT Validator](http://quuz.org/webvtt/).
* [WebVTT Standard](http://dev.w3.org/html5/webvtt/)

## Install

Install using composer (`composer require sprky0/php-webvtt`)

## API

To parse a WebVTT string:

```php
$instance = new \PHPWebVTT\PHPWebVTT();
$instance->parse($vttString);
$instance->serialize($vttString);

```

## Tests

phpunit --bootstrap vendor/autoload.php tests/

seems to be this way for now

./vendor/phpunit/phpunit/phpunit --bootstrap vendor/autoload.php tests/
