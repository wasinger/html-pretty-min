<?php
namespace Wa72\HtmlPrettymin;

use JSMin\JSMin;
use tubalmartin\CssMin\Minifier as CSSmin;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * PrettyMin is a HTML minifier and code formatter that works directly on the DOM tree
 *
 */
class PrettyMin
{
    /**
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'minify_js' => true,
            'minify_css' => true,
            'remove_comments' => true,
            'remove_comments_exeptions' => ['/^\[if /'],
            'keep_whitespace_around' => [
                // keep whitespace around inline elements
                'b', 'big', 'i', 'small', 'tt',
                'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var',
                'a', 'bdo', 'br', 'img', 'map', 'object', 'q', 'span', 'sub', 'sup',
                'button', 'input', 'label', 'select', 'textarea'
            ],
            'keep_whitespace_in' => ['script', 'style', 'pre'],
            'remove_empty_attributes' => ['style', 'class'],
            'indent_characters' => "\t"
        ]);
    }

    /**
     * Load an HTML document
     *
     * @param \DOMDocument|\DOMElement|\SplFileInfo|string $html
     * @return PrettyMin
     */
    public function load($html) {
        if ($html instanceof \DOMDocument) {
            $d = $html;
        } elseif ($html instanceof \DOMElement) {
            $d = $html->ownerDocument;
        } elseif ($html instanceof \SplFileInfo) {
            $d = new \DOMDocument();
            $d->preserveWhiteSpace = false;
            $d->validateOnParse = true;
            $d->loadHTMLFile($html->getPathname());
        } else {
            $d = new \DOMDocument();
            $d->preserveWhiteSpace = false;
            $d->validateOnParse = true;
            $d->loadHTML($html);
        }
        $d->formatOutput = false;
        $d->normalizeDocument();
        $this->doc = $d;
        return $this;
    }

    /**
     * Minify the loaded HTML document
     *
     * @param array $options
     * @return PrettyMin
     */
    public function minify($options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'minify_js' => $this->options['minify_js'],
            'minify_css' => $this->options['minify_css'],
            'remove_comments' => $this->options['remove_comments'],
            'remove_empty_attributes' => $this->options['remove_empty_attributes']
        ]);
        $options = $resolver->resolve($options);

        if ($options['minify_js']) {
            $this->minifyJavascript();
        }
        if ($options['minify_css']) {
            $this->minifyCss();
        }
        if ($options['remove_comments']) {
            $this->removeComments();
        }

        if ($options['remove_empty_attributes']) {
            $this->removeEmptyAttributes();
        }

        $this->removeWhitespace();

        return $this;
    }

    /**
     * nicely indent HTML code
     *
     * @return PrettyMin
     */
    public function indent()
    {
        $this->removeWhitespace();
        $this->indentRecursive($this->doc->documentElement, 0);
        return $this;
    }

    /**
     * Get the DOMDocument
     *
     * @return \DOMDocument
     */
    public function getDomDocument()
    {
        return $this->doc;
    }

    /**
     * Get the HTML code as string
     *
     * This is a shortcut for calling $this->getDomDocument()->saveHTML()
     *
     * @return string
     */
    public function saveHtml()
    {
        return $this->doc->saveHTML();
    }

    protected function minifyJavascript()
    {
        $elements = $this->doc->getElementsByTagName('script');

        $to_be_removed = [];
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $code = $element->textContent;
            $element->nodeValue = '';
            if (trim($code)) {
                $code = JSMin::minify($code);
                $ct = $this->doc->createCDATASection($code);
                $element->appendChild($ct);
            } elseif (!$element->hasAttribute('src')) {
                // script tag has neither content nor a src attribute, remove it completely
                array_push($to_be_removed, $element);
            }
        }
        foreach ($to_be_removed as $element) {
            $element->parentNode->removeChild($element);
        }
    }

    protected function minifyCss()
    {
        $elements = $this->doc->getElementsByTagName('style');
        $to_be_removed = [];
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $code = $element->nodeValue;
            $element->nodeValue = '';
            if (trim($code)) {
                $min = new CSSmin();
                if (trim($code)) {
                    $code = trim($min->run($code));
                }
                $ct = $this->doc->createCDATASection($code);
                $element->appendChild($ct);
            } else {
                // Style tag is empty, remove it completely
                array_push($to_be_removed, $element);
            }
        }
        foreach ($to_be_removed as $element) {
            $element->parentNode->removeChild($element);
        }
    }

    protected function removeEmptyAttributes()
    {
        if (!$this->options['remove_empty_attributes']) return;
        if (is_string($this->options['remove_empty_attributes'])) {
            $this->options['remove_empty_attributes'] = [$this->options['remove_empty_attributes']];
        }
        if (is_array($this->options['remove_empty_attributes'])) {
            $xpath = new \DOMXPath($this->doc);
            foreach ($this->options['remove_empty_attributes'] as $attr) {
                /** @var \DOMElement $el */
                foreach ($xpath->query('//*[@' . $attr . ']') as $el) {
                    if (trim($el->getAttribute($attr)) == '') {
                        $el->removeAttribute($attr);
                    }
                }
            }
        }
    }

    protected function removeComments($exception_patterns = null)
    {
        if ($exception_patterns === null) {
            $exception_patterns = $this->options['remove_comments_exeptions'];
        }
        $xpath = new \DOMXPath($this->doc);
        foreach ($xpath->query('//comment()') as $comment) {
            /** @var \DOMNode $comment */
            $remove = true;
            foreach ($exception_patterns as $exception) {
                if (preg_match($exception, $comment->textContent)) {
                    $remove = false;
                    break;
                }
            }
            if ($remove) $comment->parentNode->removeChild($comment);
        }
    }

    /**
     * originally based on http://stackoverflow.com/a/18260955
     */
    protected function removeWhitespace() {
        // Retrieve all text nodes using XPath
        $x = new \DOMXPath($this->doc);
        $nodeList = $x->query("//text()");
        foreach($nodeList as $node) {
            /** @var \DOMNode $node */

            if (in_array($node->parentNode->nodeName, $this->options['keep_whitespace_in'])) {
                continue;
            };

            $node->nodeValue = str_replace(["\r", "\n", "\t"], ' ', $node->nodeValue);
            //$node->nodeValue = preg_replace('/ {2,}/', ' ', $node->nodeValue);
            while (strpos($node->nodeValue, '  ') !== false) {
                $node->nodeValue = str_replace('  ', ' ', $node->nodeValue);
            }

            if (!in_array($node->parentNode->nodeName, $this->options['keep_whitespace_around'])) {
                if (!($node->previousSibling && in_array($node->previousSibling->nodeName,
                        $this->options['keep_whitespace_around']))
                ) {
                    $node->nodeValue = ltrim($node->nodeValue);
                }

                if (!($node->nextSibling && in_array($node->nextSibling->nodeName,
                        $this->options['keep_whitespace_around']))
                ) {
                    $node->nodeValue = rtrim($node->nodeValue);
                }
            }

            if((strlen($node->nodeValue) == 0)) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * indent HTML code
     *
     * originally based on http://stackoverflow.com/a/18260955
     *
     * @param \DOMNode $currentNode
     * @param int $depth
     * @return bool
     */
    protected function indentRecursive(\DOMNode $currentNode, $depth) {
        $indent_characters = $this->options['indent_characters'];

        $indentCurrent = true;
        $indentChildren = true;
        $indentClosingTag = false;
        if(($currentNode->nodeType == XML_TEXT_NODE)) {
            $indentCurrent = false;
        }

        if (in_array($currentNode->nodeName, $this->options['keep_whitespace_in'])) {
            $indentCurrent = true;
            $indentChildren = false;
            $indentClosingTag = (strpos($currentNode->nodeValue, "\n") !== false);
        }

        if (in_array($currentNode->nodeName, $this->options['keep_whitespace_around'])) {
            $indentCurrent = false;
        }
        if($indentCurrent && $depth > 0) {
            // Indenting a node consists of inserting before it a new text node
            // containing a newline followed by a number of tabs corresponding
            // to the node depth.
            $textNode = $currentNode->ownerDocument->createTextNode("\n" . str_repeat($indent_characters, $depth));
            $currentNode->parentNode->insertBefore($textNode, $currentNode);
        }
        if($indentCurrent && $currentNode->childNodes && $indentChildren) {
            foreach($currentNode->childNodes as $childNode) {
                $indentClosingTag = $this->indentRecursive($childNode, $depth + 1);
            }
        }
        if($indentClosingTag) {
            // If children have been indented, then the closing tag
            // of the current node must also be indented.
            if ($currentNode->lastChild && ($currentNode->lastChild->nodeType == XML_CDATA_SECTION_NODE || $currentNode->lastChild->nodeType == XML_TEXT_NODE) && preg_match('/\n\s?$/', $currentNode->lastChild->textContent)) {
                $currentNode->lastChild->nodeValue = preg_replace('/\n\s?$/', "\n" . str_repeat("\t", $depth), $currentNode->lastChild->nodeValue);
            } else {
                $textNode = $currentNode->ownerDocument->createTextNode("\n" . str_repeat("\t", $depth));
                $currentNode->appendChild($textNode);
            }
        }
        return $indentCurrent;
    }
}
