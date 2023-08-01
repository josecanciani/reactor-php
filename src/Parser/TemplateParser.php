<?php

namespace Reactor\Parser;

use Reactor\Exception\ParseError;

class TemplateParser {
    /** @throws \Reactor\Exception\ParseError */
    function parse(string $component, string $template, array $serverVarNames): Template {
        $doc = new DomDocument();
        $doc->loadComponent($component, $template);
        $body = $doc->getElementsByTagName('body')[0];
        $js = $this->extractScript($body, $component, $serverVarNames);
        $style = $this->extractStyle($body, $component);
        $html = $this->extractHtml($component, $doc, $body, $js);
        return new Template($component, $js, $style, $html);
    }

    private function extractScript(\DOMElement $body, string $component, array $serverVarNames): JsCode {
        $scripts = $body->getElementsByTagName('script');
        $scriptsCount = count($scripts);
        if ($scriptsCount > 1) {
            throw new ParseError('Only one <script> tag allowed in component: ' . $component);
        }
        if ($scriptsCount) {
            $script = $scripts->item(0);
            $body->removeChild($script);
            $code = $script->textContent;
        } else {
            $code = '';
        }
        return JsCode::fromCode($code, $serverVarNames);
    }

    private function extractStyle(\DOMElement $body, string $component): string {
        $styles = $body->getElementsByTagName('style');
        $styleCount = count($styles);
        if ($styleCount > 1) {
            throw new ParseError('Only one <style> tag allowed in component: ' . $component);
        }
        if ($styleCount) {
            $styleNode = $styles->item(0);
            $body->removeChild($styleNode);
            return $styleNode->textContent;
        }
        return '';
    }

    private function extractHtml(string $component, DOMDocument $doc, \DOMElement $body, JsCode $js): HtmlCode {
        $code = trim(substr(substr($doc->saveHTML($body), 6), 0, -7));
        return new HtmlCode($component, $code, $js);
    }
}
