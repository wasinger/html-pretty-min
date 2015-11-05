HTML Pretty-Min
===============

HTML Pretty-Min is a PHP library for minifying and prettyprinting (indenting) HTML documents
that works directly on the DOM tree of an HTML document.

Currently it has the following features:

- **Prettyprint**:
  - Indent Block-level elements, do not indent inline elements

- **Minify**: 
  - Remove whitespace and newlines
  - Compress embedded Javascript using [mrclay/jsmin-php](https://packagist.org/packages/mrclay/jsmin-php)
  - Compress embedded CSS using [tubalmartin/cssmin](https://packagist.org/packages/tubalmartin/cssmin)
  - Remove some attributes when their value is empty (by default "style" and "class" attributes)

Installation
------------

`composer require wasinger/html-pretty-min`

Usage
-----

```php
<?php
use Wa72\HtmlPrettymin\PrettyMin;

$pm = new PrettyMin();

$output = $pm
    ->load($html)   // $html may be a \DOMDocument, a string containing an HTML code, 
                    // or an \SplFileInfo pointing to an HTML document
    ->minify()
    ->saveHtml();
```