<?php declare(strict_types=1);

namespace Reactor\Parser;

/** Represents the non-variable -at least until a Component code changes- parts of a Component */
class Template implements \JsonSerializable {
    private $component;
    private $jsCode;
    private $cssCode;
    private $htmlCode;

    function __construct(string $component, JsCode $jsCode, string $cssCode, HtmlCode $htmlCode) {
        $this->component = $component;
        $this->jsCode = $jsCode;
        $this->cssCode = $cssCode;
        $this->htmlCode = $htmlCode;
    }

    function getHtmlCode(): string {
        return $this->htmlCode->getCode();
    }

    function jsonSerialize() {
        return [
            'component' => $this->component,
            'jsCode' => $this->jsCode->getCode(),
            'cssCode' => $this->cssCode,
            'htmlCode' => $this->getHtmlCode()
        ];
    }
}
