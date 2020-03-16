PHP WebVTT parser and validator
===========================

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
