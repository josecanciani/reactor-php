<?php declare(strict_types=1);

namespace Reactor;

use Reactor\Php\ReadOnlyString;

class Component implements \JsonSerializable {
    /** @var String In reality a ReadOnlyString object, just to avoid devs from messing with it */
    public $reactorId;
    /** @var String In reality a ReadOnlyString object, just to avoid devs from messing with it */
    public $reactorComponent;

    /** @var String[] The list of variables available for this server component */
    private $serverVarNames = [];

    /**
     * @param String $id Unique identifier for this component
     * @param String $component Component name (defaults to Component PHP class name, provide only if you want different names)
     */
    function __construct(string $id, string $component = null) {
        $this->reactorComponent = new ReadOnlyString($component ?: str_replace('\\', '_', static::class));
        $this->reactorId = new ReadOnlyString($id);
        $reflection = new \ReflectionClass(static::class);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (substr($property->getName(), 0, 1) !== '_') {
                $this->serverVarNames[] = $property->getName();
            }
        }
    }

    /** Extend this method and put any logic to initialize your Component here */
    function run(): void {
        // empty and not abstract just to allow static components (client side only)
    }

    /** @var String[] The list of variables available for this server component */
    function _reactorGetServerVarNames(): array {
        return $this->serverVarNames;
    }

    function jsonSerialize() {
        $json = new \stdClass();
        foreach ($this->_reactorGetServerVarNames() as $name) {
            $json->$name = $this->$name;
        }
        return $json;
    }
}
