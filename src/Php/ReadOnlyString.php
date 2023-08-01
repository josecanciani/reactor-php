<?php

namespace Reactor\Php;

/** Helper class to provide a read only string */
class ReadOnlyString implements Stringable, \JsonSerializable {
    private $value;

    function __construct(string $value) {
        $this->value = $value;
    }

    function __toString(): string {
        return $this->value;
    }

    function jsonSerialize() {
        return $this->value;
    }
}
