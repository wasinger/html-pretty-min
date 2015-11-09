<?php
/**
 * This file contains benchmark functions used during development
 * in order to find the most performant implementation for a specific task
 */

require_once(__DIR__ . '/../vendor/autoload.php');

////// TESTS //////

runTest('Select empty attributes using simple XPath attribute selector and PHP test for empty string', function($d) {
    $xpath = new \DOMXPath($d);
    foreach (['style', 'class'] as $attr) {
        /** @var \DOMElement $el */
        foreach ($xpath->query('//*[@' . $attr . ']') as $el) {
            if (trim($el->getAttribute($attr)) == '') {
                $el->removeAttribute($attr);
            }
        }
    }
});

runTest('Select empty attributes using XPath string functions', function($d) {
    $xpath = new \DOMXPath($d);
    foreach (['style', 'class'] as $attr) {
        /** @var \DOMElement $el */
        foreach ($xpath->query('//*[string-length(normalize-space(@' . $attr . ')) = 0]') as $el) {
            $el->removeAttribute($attr);
        }
    }
});

runTest('Select empty attributes using XPath | operator and PHP test for empty string', function($d) {
    $xpath = new \DOMXPath($d);
    $query = '';
    foreach (['style', 'class'] as $no => $attr) {
       $query .= ($no == 0 ? '' : ' | ') . '//@' . $attr;
    }

//    echo "Query: $query \n";
    /** @var \DOMNode $attr */
    foreach ($xpath->query($query) as $no => $attr) {
//        if ($no < 10) echo "Found attr " . $attr->nodeName . " with value: " . $attr->textContent . "\n";
        if (trim($attr->textContent) == '') {
            $attr->parentNode->removeAttribute($attr->nodeName);
        }
    }
});

runTest('Remove whitespace v1', function($d) {
    $x = new \DOMXPath($d);
    $keep_whitespace_in = ['pre', 'style', 'script'];
    $keep_whitespace_around = ['a', 'b', 'i'];
    $nodeList = $x->query("//text()");
    foreach($nodeList as $node) {
        /** @var \DOMNode $node */

        if (in_array($node->parentNode->nodeName, $keep_whitespace_in)) {
            continue;
        };

        // 1. "Trim" each text node by removing its leading and trailing spaces and newlines.
        // Modified by CS: keep whitespace around inline elements
        if (in_array($node->parentNode->nodeName, $keep_whitespace_around)) {
            $replacement = ' ';
        } else {
            $replacement = '';
        }

        $r_replacement = $replacement;
        if ($node->previousSibling && in_array($node->previousSibling->nodeName, $keep_whitespace_around)) {
            $r_replacement = ' ';
        }
        $node->nodeValue = preg_replace('/^[\s\r\n]+/', $r_replacement, $node->nodeValue);

        $l_replacement = $replacement;
        if ($node->nextSibling && in_array($node->nextSibling->nodeName, $keep_whitespace_around)) {
            $l_replacement = ' ';
        }
        $node->nodeValue = preg_replace('/[\s\r\n]+$/', $l_replacement, $node->nodeValue);

        $node->nodeValue = preg_replace('/[\s]+/', ' ', $node->nodeValue);


        // 2. Resulting text node may have become "empty" (zero length nodeValue) after trim. If so, remove it from the dom.
        if((strlen($node->nodeValue) == 0)) {
            $node->parentNode->removeChild($node);
        }
    } 
});


runTest('Remove whitespace v2', function($d) {
    $x = new \DOMXPath($d);
    $keep_whitespace_in = ['pre', 'style', 'script'];
    $keep_whitespace_around = ['a', 'b', 'i'];
    $nodeList = $x->query("//text()");
    foreach($nodeList as $node) {
        /** @var \DOMNode $node */

        if (in_array($node->parentNode->nodeName, $keep_whitespace_in)) {
            continue;
        };

        $node->nodeValue = str_replace(["\r", "\n", "\t"], ' ', $node->nodeValue);
        $node->nodeValue = preg_replace('/ {2,}/', ' ', $node->nodeValue);

        // 1. "Trim" each text node by removing its leading and trailing spaces and newlines.
        if (!($node->previousSibling && in_array($node->previousSibling->nodeName, $keep_whitespace_around))) {
                $node->nodeValue = ltrim($node->nodeValue);
        }

        if (!($node->nextSibling && in_array($node->nextSibling->nodeName, $keep_whitespace_around))) {
            $node->nodeValue = rtrim($node->nodeValue);
        }

        if((strlen($node->nodeValue) == 0)) {
            $node->parentNode->removeChild($node);
        }
    }
});


runTest('Remove whitespace v3', function($d) {
    $x = new \DOMXPath($d);
    $keep_whitespace_in = ['pre', 'style', 'script'];
    $keep_whitespace_around = ['a', 'b', 'i'];
    $nodeList = $x->query("//text()");
    foreach($nodeList as $node) {
        /** @var \DOMNode $node */

        if (in_array($node->parentNode->nodeName, $keep_whitespace_in)) {
            continue;
        };

        $node->nodeValue = str_replace(["\r", "\n", "\t"], ' ', $node->nodeValue);
        while (strpos($node->nodeValue, '  ') !== false) {
          $node->nodeValue = str_replace('  ', ' ', $node->nodeValue);
        }


        // 1. "Trim" each text node by removing its leading and trailing spaces and newlines.
        if (!($node->previousSibling && in_array($node->previousSibling->nodeName, $keep_whitespace_around))) {
            $node->nodeValue = ltrim($node->nodeValue);
        }

        if (!($node->nextSibling && in_array($node->nextSibling->nodeName, $keep_whitespace_around))) {
            $node->nodeValue = rtrim($node->nodeValue);
        }

        if((strlen($node->nodeValue) == 0)) {
            $node->parentNode->removeChild($node);
        }
    }
});

runTest('Remove whitespace v4', function($d) {
    $x = new \DOMXPath($d);
    $keep_whitespace_in = ['pre', 'style', 'script'];
    $keep_whitespace_around = ['a', 'b', 'i'];
    $nodeList = $x->query("//text()");
    foreach($nodeList as $node) {
        /** @var \DOMNode $node */

        if (in_array($node->parentNode->nodeName, $keep_whitespace_in)) {
            continue;
        };

        $node->nodeValue = str_replace(["\r", "\n", "\t"], ' ', $node->nodeValue);
        while (strpos($node->nodeValue, '  ') !== false) {
            $node->nodeValue = str_replace('  ', ' ', $node->nodeValue);
        }


        // 1. "Trim" each text node by removing its leading and trailing spaces and newlines.
        if (substr($node->nodeValue, 0, 1) == ' ' && !($node->previousSibling && in_array($node->previousSibling->nodeName, $keep_whitespace_around))) {
            $node->nodeValue = ltrim($node->nodeValue);
        }

        if (substr($node->nodeValue, -1) == ' ' && !($node->nextSibling && in_array($node->nextSibling->nodeName, $keep_whitespace_around))) {
            $node->nodeValue = rtrim($node->nodeValue);
        }

        if((strlen($node->nodeValue) == 0)) {
            $node->parentNode->removeChild($node);
        }
    }
});

////// helper funtions //////

function runTest($description, callable $function)
{
    echo "\n" . $description;
    $d = createTestDocument();
    $begin = microtime(true);
    call_user_func($function, $d);
    $runtime = microtime(true) - $begin;
    echo "\nRuntime: $runtime\n";
}

/**
 * @return DOMDocument
 */
function createTestDocument()
{
    $d = new \DOMDocument('1.0', 'UTF-8');
    $html = '<!doctype html><html><head><title>Benchmark</title></head><body></body>';
    $d->loadHTML($html);
    $body = $d->getElementsByTagName('body')->item(0);
    $text = <<<TEXT
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore
magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing
elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos
et accusam et justo duo dolores et ea rebum.
Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor
sit amet.

TEXT;
    for ($i = 0; $i < 5000; $i++) {
        $p = $d->createElement('p', $text);
        $p->appendChild($d->createElement('b', ' Lorem ipsum dolor sit amet, consetetur sadipscing elitr '));
        $p->appendChild($d->createElement('a', 'Stet clita kasd gubergren'));
        $p->appendChild($d->createTextNode('    At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. '));
        $p->setAttribute('class', ($i % 2 ? 'test' : ''));
        $p->setAttribute('style', ($i % 2 ? ' ' : 'color: red;'));
        $body->appendChild  ($p);
    }
    return $d;
}



