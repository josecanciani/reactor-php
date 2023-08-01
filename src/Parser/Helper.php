<?php

namespace Reactor\Parser;

class Helper {
    /**
     * given a code, searches for the base indentation, the first if found is the one
     * @return array{charCount: int, paddingString: string}
     */
    static function getIndentation(string $code): array {
        foreach (explode(PHP_EOL, $code) as $line) {
            if (trim($line)) {
                $count = strlen($line) - strlen(ltrim($line));
                return [$count, substr($line, 0, $count)];
            }
        }
        return [0, ''];
    }
}
