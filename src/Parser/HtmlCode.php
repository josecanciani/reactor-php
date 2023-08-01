<?php declare(strict_types=1);

namespace Reactor\Parser;

use Reactor\Exception\ParseError;

class HtmlCode {
    private $html;
    private $jsCode;
    private $serial = 0;

    function __construct(string $component, string $html, Jscode $jsCode) {
        $this->jsCode = $jsCode;
        $doc = new DomDocument();
        $doc->loadComponent($component, $this->preProcessSections($html));
        $body = $doc->getElementsByTagName('body')[0];
        try {
            $this->processTree($doc, $body, 0);
        } catch (ParseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ParseError('Unexpected error parsing html from component "' . $component . '" ', 0, $e);
        }
        $this->html = trim(substr(substr($doc->saveHTML($body), 6), 0, -7));
    }

    function getCode(): string {
        return $this->html;
    }

    private function processTree(\DomDocument $doc, \DOMNode $parent, int $sectionDepth): void {
        if (!$parent->hasChildNodes()) {
            return;
        }
        $childs = $this->extractChilds($parent);
        if (count($childs) === 1 && $childs[0]->nodeName === 'reactor') {
            // found a pre-processed section, and it's the only child, so let's just append all childs to parent
            // TODO: transfer section to parent?
        }
        foreach ($childs as $child) {
            if ($child instanceof \DOMText) {
                $this->processTextNode($doc, $child, $parent);
            } else {
                $newSectionDepth = $sectionDepth;
                if ($child->nodeName === 'reactor-section') {
                    // our custom nodes, no need to process attributes
                    $newSectionDepth++;
                    $this->processReactorNode($child, $newSectionDepth);
                }
                $this->processElementAttributes($child);
                $parent->appendChild($child);
                // recursive call, keep working!
                $this->processTree($doc, $child, $newSectionDepth);
            }
        }
    }

    private function processReactorNode(\DOMElement $node, int $sectionDepth): void {
        $node->setAttribute('depth', (string) $sectionDepth);
    }

    /**
     * Searches for mustache vars and sections in text nodes.
     * We first match vars, 3 mustaches {{{ }}} and then recursively 2 mustaches {{ }}
     * Once no more matches, we match sections two mustaches starting with #, ^ or / in processTextNodeSections()
     */
    private function processTextNode(\DOMDocument $doc, \DOMText $node, \DOMNode $parent, int $mustacheCount = 3): void {
        $re = '/([\{]{' . $mustacheCount . '}\s*[&]{0,1}\s*(\w+)\s*[\}]{' . $mustacheCount . '})/mi';
        $nodeText = $node->wholeText;
        if (preg_match($re, $nodeText, $match, PREG_OFFSET_CAPTURE, 0)) {
            list($previous, $nodeText) = explode($match[1][0], $nodeText, 2);
            $previousNode = new \DOMText($previous);
            if ($mustacheCount === 3) {
                // now search for 2 {{ }}
                $this->processTextNode($doc, $previousNode, $parent, 2);
            } else {
                $parent->appendChild($previousNode);
            }
            $parent->appendChild($this->createReactorVar($doc, $match[2][0], $match[1][0], $mustacheCount));
        }
        if ($nodeText) {
            $newNode = new \DOMText($nodeText);
            if ($mustacheCount === 3) {
                // now search for 2 {{ }}
                $this->processTextNode($doc, $newNode, $parent, 2);
            } else {
                $parent->appendChild($newNode);
            }
        }
    }

    /**
     * Searches for mustache vars inside node attributes
     */
    private function processElementAttributes(\DOMElement $node): void {
        $variableAttributes = [];
        if ($node->hasAttributes()) {
            $functions = implode(
                '|',
                array_map(function (string $name) { return preg_quote($name);  }, $this->jsCode->getFunctions())
            );
            $functionRegex = '/\b(?<!\.)(' . $functions . ')\(/';
            $variableRegex = '/{\{\s*[&]{0,1}\s*(\w+)\s*\}\}/i';
            foreach ($node->attributes as $attr) {
                $value = $node->getAttribute($attr->nodeName);
                if (preg_match($variableRegex, $value)) {
                    $variableAttributes[] = $attr->nodeName;
                }
                $newValue = preg_replace($functionRegex, 'REACTOR.getComponent(\'{{{reactorComponent}}}\', \'{{{reactorId}}}\').$1(', $value);
                if ($newValue === null) {
                    throw new ParseError('Error trying to transpile functions calls: ' . $value);
                }
                if ($value !== $newValue) {
                    $node->setAttribute($attr->nodeName, $newValue);
                }
            }
            if ($variableAttributes) {
                $node->setAttribute('class', trim($node->getAttribute('class')  . ' hasReactorProperties'));
                $node->setAttribute('reactorProperties', implode(',', $variableAttributes));
            }
        }
        if ($node->nodeName === 'reactor-section' || $variableAttributes) {
            $this->serial++;
            $reactorId = 'reactorId_' . $this->serial;
            $node->setAttribute('class', trim($node->getAttribute('class') . ' ' . $reactorId));
            $node->setAttribute('reactor-id', $reactorId);
        }
    }

    /** @return \DOMNode[] childs */
    private function extractChilds(\DOMElement $parent): array {
        $childs = [];
        while ($parent->childNodes->count()) {
            $child = $parent->childNodes->item(0);
            if (!($child instanceof \DOMElement || $child instanceof \DOMText)) {
                throw new ParseError('Found HTML Node "' . get_class($child) . '", not supported');
            }
            // remove empty text nodes
            if ($child instanceof \DOMElement || trim($child->wholeText)) {
                $childs[] = $child;
            }
            $parent->removeChild($child);
        }
        return $childs;
    }

    private function createReactorVar(\DOMDocument $doc, string $name, string $content, int $mustacheCount): \DOMElement {
        $node = $doc->createElement('reactor');
        $node->setAttribute('type', 'var');
        $node->setAttribute('name', $name);
        $node->setAttribute('escaped', $mustacheCount === 3 ? 'false' : 'true');
        $node->textContent = $content;
        return $node;
    }

    private function preProcessSections(string $html): string {
        return preg_replace(
            [
                // opening tags
                '/{{\s*([\#\^]{1})\s*(\w+)\s*}}/i',
                // closing tag
                '/{{\s*(\/)\s*(\w+)\s*}}/i'
            ],
            [
                '<reactor-section var="$2" op="$1">{{$1$2}}',
                '{{$1$2}}</reactor-section>'
            ],
            $html
        );
    }
}
