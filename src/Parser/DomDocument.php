<?php declare(strict_types=1);

namespace Reactor\Parser;

use Reactor\Exception\ParseError;

/** A wrapper class for \DOMDocument that deals with the specific situation of Reactor's html template code */
class DomDocument extends \DOMDocument {
    /**
     * Load component HTML, by parsing and throwing errors as needed
     *
     * @throws \Reactor\Exception\ParseError
     */
    function loadComponent(string $component, string $source): void {
        libxml_use_internal_errors(true);
        try {
            $this->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><?xml encoding="UTF-8"><html><body>' . $source . '</body></html>');
            foreach (libxml_get_errors() as $error) {
                $message = trim($error->message);
                if ($message === 'Tag reactor-section invalid') {
                    // OK, we added <reactor> sections
                    continue;
                }
                if (trim($error->message) === 'Unexpected end tag : reactor-section') {
                    throw new ParseError("Error parsing sections for \"$component\". Check they are properly nested and closed.");
                }
                throw new ParseError("LIBXML error when parsing component \"$component\": {$error->message}");
            }
        } catch (ParseError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ParseError("Unexpected error parsing component \"$component\"", 0, $e);
        } finally {
            libxml_use_internal_errors(false);
        }
    }

    /** Returns the HTML representation of the modified body element */
    function saveComponent(\DOMElement $body): string {
        // strip <body> </body> tags
        return trim(substr(substr($this->saveHTML($body), 6), 0, -7));
    }
}
