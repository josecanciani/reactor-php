<?php declare(strict_types=1);

namespace Reactor;

use Reactor\Parser\Template;

class Response implements \JsonSerializable {
    private $template;
    private $serverVars;
    private $innerHTML;

    function __construct(Template $template, \stdClass $serverVars, string $innerHTML) {
        $this->template = $template;
        $this->serverVars = $serverVars;
        $this->innerHTML = $innerHTML;
    }

    function jsonSerialize() {
        return [
            'template' => $this->template,
            'serverVars' => $this->serverVars,
            'innerHTML' => $this->innerHTML
        ];
    }
}
