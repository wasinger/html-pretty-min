HTML Pretty-Min
===============

![tests](https://github.com/wasinger/html-pretty-min/actions/workflows/tests.yml/badge.svg?branch=master)
[![Latest Version](http://img.shields.io/packagist/v/wa72/html-pretty-min.svg)](https://packagist.org/packages/wa72/html-pretty-min)


HTML Pretty-Min is a PHP library for minifying and prettyprinting (indenting) HTML documents
that works directly on the DOM tree of an HTML document.

Currently, it has the following features:

- **Prettyprint**:
  - Indent Block-level elements, do not indent inline elements

- **Minify**: 
  - Remove whitespace and newlines
  - Compress embedded Javascript using [mrclay/jsmin-php](https://packagist.org/packages/mrclay/jsmin-php)
  - Compress embedded CSS using [tubalmartin/cssmin](https://packagist.org/packages/tubalmartin/cssmin)
  - Remove some attributes when their value is empty (by default "style" and "class" attributes)
  - Remove comments, except those matching some given regular expressions (by default, IE conditional comments are kept)

Installation
------------

HTML Pretty-Min is listed on [Packagist](https://packagist.org/packages/wa72/html-pretty-min).

`composer require wa72/html-pretty-min`

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

For prettyprinting, call the `indent()` method instead of `minify()`.


The `PrettyMin()` constructor can be given an associative options array. Here are the possible option keys and their default values:
```
    'minify_js' => true,
    'minify_css' => true,
    'remove_comments' => true,
    'remove_comments_exeptions' => ['/^\[if /'],
    'keep_whitespace_around' => [
        // keep whitespace around all inline elements
        'b', 'big', 'i', 'small', 'tt',
        'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var',
        'a', 'bdo', 'br', 'img', 'map', 'object', 'q', 'span', 'sub', 'sup',
        'button', 'input', 'label', 'select', 'textarea'
    ],
    'keep_whitespace_in' => ['script', 'style', 'pre'],
    'remove_empty_attributes' => ['style', 'class'],
    'indent_characters' => "\t"
```


**Attention**: Because the formatting is done directly on the DOM tree, a DOMDocument object given to the `load()` method
will be modified:

```php
$dom_document = new \DOMDocument('1.0', 'UTF-8');
$dom_document->loadHTML('<html>...some html code...</html>');

$pm->load($dom_document)->minify();

echo $dom_document->saveHTML(); // Will output the minified, not the original, document
```
