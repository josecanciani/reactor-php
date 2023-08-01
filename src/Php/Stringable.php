<?php declare(strict_types=1);

namespace Reactor\Php;

/** PHP < 8 polyfill */
if (interface_exists('\\Stringable')) {

    interface Stringable extends \Stringable{
    }

} else {

    interface Stringable {
        public function __toString(): string;
    }

}
