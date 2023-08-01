<?php declare(strict_types=1);

namespace Reactor;

class Request {
    const SERVER_SIDE_RENDER = 'ssr';
    const CLIENT_SIDE_RENDER = 'csr';

    static function load(): self {
        $request = new Request();
        $modes = [static::SERVER_SIDE_RENDER, static::CLIENT_SIDE_RENDER];
        $exists = function($code) {
            return isset($_GET[$code]) && is_string($_GET[$code]);
        };
        if (!$exists('mode') || !in_array($_GET['mode'], $modes)) {
            throw new Exception\InvalidArgument('Invalid "mode", choose one of [' . implode(', ', $modes) . ']');
        }
        $request->mode = $_GET['mode'];
        $componentName = str_replace('_', '\\', $exists('component') ? $_GET['component'] : '');
        if (!$componentName || !class_exists($componentName) || !is_subclass_of($componentName, Component::class)) {
            throw new Exception\InvalidArgument('Invalid "component": ' . ($exists('component') ? $componentName : '""'));
        }
        $request->component = $componentName;
        $request->id = $exists('id') ? $_GET['id'] : '';
        $serverVars = new \stdClass();
        foreach ($_POST as $name => $value) {
            if (substr($name, 0, 23) === 'reactor_serverVariable_') {
                $varName = substr($name, 23);
                $serverVars->$varName = $value;
            }
        }
        $request->vars = $serverVars;
        return $request;
    }

    /** @var String Either SERVER_SIDE_RENDER or CLIENT_SIDE_RENDER */
    private $mode;
    /** @var String Component to return */
    private $component;
    /** @var String Component ID (optional) */
    private $id;
    /** @var \stdClass server component variables the client sent (optional)  */
    private $vars;

    protected function __construct() {
        // use load()
    }

    function getMode(): string {
        return $this->mode;
    }

    function getComponent(): string {
        return $this->component;
    }

    function getId(): string {
        return $this->id;
    }

    function getVars(): \stdClass {
        return $this->vars;
    }
}
