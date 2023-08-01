<?php declare(strict_types=1);

namespace Reactor\Tests\Transpiler;

use PHPUnit\Framework\TestCase;
use Reactor\Parser\TemplateParser;

final class SectionsTest extends TestCase {
    function testSingleSections(): void {
        $code = '{{#sectionName}}Content{{/sectionName}}';
        $parser = new TemplateParser();
        $template = $parser->parse('MyComponent', $code, []);
        $this->assertEquals(
            '<reactor-section var="sectionName" op="#" depth="1" class="reactorId_1" reactor-id="reactorId_1">{{#sectionName}}Content{{/sectionName}}</reactor-section>',
            $template->getHtmlCode()
        );
    }

    function testNestedSections(): void {
        $code = '{{#sectionName1}}Content1{{^sectionName2}}Content2{{/sectionName2}}{{/sectionName1}}';
        $parser = new TemplateParser();
        $template = $parser->parse('MyComponent', $code, []);
        $this->assertEquals(
            '<reactor-section var="sectionName1" op="#" depth="1" class="reactorId_1" reactor-id="reactorId_1">{{#sectionName1}}' .
                'Content1' .
                '<reactor-section var="sectionName2" op="^" depth="2" class="reactorId_2" reactor-id="reactorId_2">{{^sectionName2}}' .
                    'Content2' .
                '{{/sectionName2}}</reactor-section>' .
            '{{/sectionName1}}</reactor-section>',
            $template->getHtmlCode()
        );
    }
}
