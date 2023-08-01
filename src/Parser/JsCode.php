<?php

namespace Reactor\Parser;

use Reactor\Exception\ParseError;

class JsCode {
    /** called after a public methods had been executed */
    const REACT_FUNCTION = 'react';
    /** call before render */
    const TEAR_UP_FUNCTION = 'tearUp';
    /** call on dom remove */
    const TEAR_DOWN_FUNCTION = 'tearDown';
    /** call when there's an error, with the error message */
    const ON_ERROR_FUNCTION = 'onError';
    /** called after reacting, if state changed (will call default set in JS ReactorConfig object if not present) */
    const HIGHLIGHT_FUNCTION = 'highlight';
    /** called when state change was detected, before calling the server */
    const ON_BEFORE_SERVER_CHANGE_FUNCTION = 'reactorOnBefore';
    /** called    */
    const ON_AFTER_SERVER_CHANGE_FUNCTION = 'onAfterServerChange';

    static function getBuiltinFunctions(): array {
        return [
            static::REACT_FUNCTION,
            static::TEAR_UP_FUNCTION,
            static::TEAR_DOWN_FUNCTION,
            static::HIGHLIGHT_FUNCTION
        ];
    }

    /** @throws \Reactor\Exception\ParseError */
    static function fromCode(string $code, array $serverVars): self {
        list ($baseLevel, $pad) = Helper::getIndentation($code);
        $re = '/^(?: |\t){' . $baseLevel . '}(let|const|var|function)\s+(\w+)/m';
        $class = $pad . 'return {' . PHP_EOL;
        $clientVars = [];
        $functions = [];
        if (preg_match_all($re, $code, $matches, PREG_SET_ORDER, 0)) {
            foreach ($matches as $match) {
                if ($match[1] === 'function') {
                    if ($match[2][0] === '_' || in_array($match[2], static::getBuiltinFunctions())) {
                        // ignore "private vars" and builtin functions
                        continue;
                    }
                    $functions[] = $match[2];
                    // avoid infinit loops, react() must not call react again!
                    $class .= $pad . $pad . $match[2] . ': function(...params) { try { return ' . $match[2] . '(...params); } finally { REACTOR._react(reactorComponent, reactorId); } },' . PHP_EOL;
                } elseif (in_array($match[2], $serverVars)) {
                    throw new ParseError('Cannot redeclare server property ' . $match[2]. ' in client JS Code');
                } else {
                    $clientVars[] = $match[2];
                }
            }
        }
        $serverVarsCode = '';
        foreach ($serverVars as $serverVar) {
            // TODO: maybe annotations or check if default value is instance of ReadOnly (like ReadOnlyString) to use const?
            $type = in_array($serverVar, ['reactorId', 'reactorComponent']) ? 'const' : 'let';
            // it's not a const so client can change it and we can react and refresh the component
            $serverVarsCode .= "$pad$type $serverVar = {{{$serverVar}}};" . PHP_EOL;
        }
        $class .= $pad . $pad . '_reactorGetServerVarNames: function() { return ' . json_encode($serverVars) . '; },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorGetClientVarNames: function() { return ' . json_encode($clientVars) . '; },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorGetVarValue: function(varName) { switch (varName) { ' . implode(';', array_map(function (string $name) { return "case \"$name\": return $name"; }, array_merge($clientVars, $serverVars))) . '; default: return undefined; }; },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorCallFunction: function(funcName, ...args) { switch (funcName) { ' . implode(';', array_map(function (string $name) { return "case \"$name\": return $name ? $name(...args) : undefined;"; }, static::getBuiltinFunctions())) . ' default: throw new ReactorError("Undefined function " + funcName); }; },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorSetServerVar: function(name, value) { if (name === "reactorId" || name === "reactorComponent") { return name; }; switch (name) { ' . implode(';', array_map(function (string $name) { return "case \"$name\": $name = value; return $name"; }, $serverVars)) . '; default: throw new ReactorError("Undefined server variable " + name); }; },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorHighlight: function(defaultHighlight, node) { if (typeof highlight === \'function\') { highlight(node); } else if (defaultHighlight) { defaultHighlight(node); } },' . PHP_EOL;
        $class .= $pad . $pad . '_reactorOnError: function(defaultOnError, err, node) { if (typeof onError === \'function\') { onError(err, node); } else if (defaultOnError) { defaultOnError(err, node); } },' . PHP_EOL;
        $class .= $pad . '};';
        return new static(
            'return function(ReactorError) {' . PHP_EOL .
                $serverVarsCode . PHP_EOL . $pad . '//REACTOR_CLIENT_CODE_STARTS//' .
                $code . PHP_EOL . $pad . '//REACTOR_CLIENT_CODE_ENDS//' . PHP_EOL .
                $class . PHP_EOL .
            '};',
            $clientVars,
            $functions
        );
    }

    private $jsCode;
    private $clientVars;
    private $functions;

    function __construct(string $jsCode, array $clientVars, array $functions) {
        $this->jsCode = $jsCode;
        $this->clientVars = $clientVars;
        $this->functions = $functions;
    }

    function getCode(): string {
        return $this->jsCode;
    }

    function _reactorGetClientVarNames(): array {
        return $this->clientVars;
    }

    function getFunctions(): array {
        return $this->functions;
    }
}
