
# Overview

Reactor uses [Mustache](https://mustache.github.io/) syntax, but in order to support targetted refreshes without full DOM render, some limitations on where to place them applies.

## CSS

You can't use `{{vars}}` in the CSS template. If you want to add style with dynamic variables (need use cases here, let me know), for now do it with JS code.

## JS Code

All server vars are automatically available in your client Javascript code. The transpiler will add the server variables definitions and apply them before running the script in the client.

So there's no need for `{{vars}}` in JS Code. This also means the code can be parsed by your IDE without issues (although server vars may be reported as missing -or warnings of public variables may be shown-).

## HTML

Here's where you can use `{{mustacheVars}}`.

In order to produce targetted refreshes in the DOM, Reactor will wrap or modify certain elements during the transpilation process, when parsing the HTML defined in the Component.

### Allowed:

* Sections
  * `#` and `^` for positive and negative matches
  * `#` for loops if prop is array
* `{{escapedVars}}` and `{{{unEscapedVars}}}`
* `{{vars}}` inside attribute values: `<a href="{{link}}" />`

### NOT allowed:

* `{{vars}}` for tag names: `<{{tag}} prop="myprop">`
* `{{vars}}` for property names: `<a {{propName}}="propValue" />`

Parsing the HTML will fail in this cases.


# Transpilation

Here's how the HTML template transpilation occurs:

## Example input:

```html
    {{#people}}
        <h1>{{name}}</h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}">
            {{linkText}}
        </a>
        <div class="phones">{{#phones}}
            {{number}}
        {{/phones}}</div>
    {{/people}}
```

## 1: Text-based manipulation

### 1a: Sections

First step: identify sections, and wrap them. This is just a `preg_replace` command, no real parsing was done. Notice how sections are now enclosed by `<reactor-section>` nodes.

```html
    <reactor-section var="people" op="#">
        <h1>{{name}}</h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}">
            {{linkText}}
            {{{unEscapedLinkText}}}
        </a>
        <div class="phones"><reactor-section var="phone" op="#">{{#phones}}
            {{number}}
        </reactor-section>{{/phones}}</div>
    </reactor-section>
```

## 2: DOM Parser manipulation

Here the document is opened by the parser, and invalid syntax would be detected here. The parser will start cycling the nodes in search for

### 2a: Property values

```html
    <reactor type="section" var="people">{{#people}}
        <h1>{{name}}</h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}" reactorProperties="link,linkTitle">
            {{linkText}}
            {{{unEscapedLinkText}}}
        </a>
    {{/people}}</reactor>
```

### 2b: Variables as text nodes

```html
    <reactor type="section" var="people">{{#people}}
        <h1><reactor type="var" name="name">{{name}}</reactor></h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}" class="hasReactorProperties" reactorProperties="link,linkTitle">
            <reactor type="var" name="linkText" escaped="true">{{linkText}}</reactor>
            <reactor type="var" name="linkText" escaped="false">{{{unEscapedLinkText}}}</reactor>
        </a>
    {{/people}}</reactor>
```
