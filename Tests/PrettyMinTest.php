<?php
namespace Wa72\HtmlPrettymin\Tests;


use Wa72\HtmlPrettymin\PrettyMin;

class PrettyMinTest extends \PHPUnit\Framework\TestCase
{


    private function getHtmlDocument()
    {
        $html =<<<HTML
<!doctype html>
<html>
    <head>
        <title>Test</title>
        <script type="text/javascript"></script>
        <script>
        $(document).ready(
            function () {
                if (this && that && a > b) {
                    doSomething();
                }
            }
        );
</script>
<style>
    body > div {
        border-top: 1px solid green;
    }
</style>
    </head>
    <body>
    <h1>Test</h1>
    <div class="keep">
        <div class="" style=""><p>This is <b>bold</b>
            Text.
            And some more text, still in the same paragraph.
            <strong>Inline tag </strong>whith whitespace at the end but not after.
            </p><p>This is another paragraph with a <a href="">link</a>.
            </p>
        </div>
        </div>
        <form><input type="text" name="a"><input type="text" name="b"></form>
    </body>
</html>
HTML;
        return $html;
    }
    public function testMinify()
    {
        $pm = new PrettyMin();
        $pm->load($this->getHtmlDocument());
        $pm->minify();

        $expected = <<<HTML
<!DOCTYPE html>
<html><head><title>Test</title><script>$(document).ready(function(){if(this&&that&&a>b){doSomething();}});</script><style>body>div{border-top:1px solid green}</style></head><body><h1>Test</h1><div class="keep"><div><p>This is <b>bold</b> Text. And some more text, still in the same paragraph. <strong>Inline tag </strong>whith whitespace at the end but not after.</p><p>This is another paragraph with a <a href="">link</a>.</p></div></div><form><input type="text" name="a"><input type="text" name="b"></form></body></html>

HTML;


        $this->assertEquals($expected, $pm->saveHtml());
    }

    public function testIndent()
    {
        $pm = new PrettyMin();
        $pm->load($this->getHtmlDocument());
        $pm->indent();
        $expected = <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>Test</title>
		<script type="text/javascript"></script>
		<script>
        $(document).ready(
            function () {
                if (this && that && a > b) {
                    doSomething();
                }
            }
        );
		</script>
		<style>
    body > div {
        border-top: 1px solid green;
    }
		</style>
	</head>
	<body>
		<h1>Test</h1>
		<div class="keep">
			<div class="" style="">
				<p>This is <b>bold</b> Text. And some more text, still in the same paragraph. <strong>Inline tag </strong>whith whitespace at the end but not after.</p>
				<p>This is another paragraph with a <a href="">link</a>.</p>
			</div>
		</div>
		<form><input type="text" name="a"><input type="text" name="b"></form>
	</body>
</html>

HTML;

        $this->assertEquals($expected, $pm->saveHtml());
    }
}
