
# Overview

Reactor uses [Mustache](https://mustache.github.io/) syntax, but in order to support targetted refreshes without full DOM render, some limitations on where to place them applies.

# CSS

You can use `{{serverVars}}` in the CSS template, but not client vars (JS vars). If you want to apply anything dynamic in the client (need use cases here, let me know), just do it in JS code.

# JS Code

All server vars are automatically available in your client Javascript code. The transpiler will add the server variables definitions and apply them before running the script in the client.

# HTML Template syntax

In order to produce targetted refreshes in the DOM, Reactor will wrap and mark certain elements in the Component HTML.

## Allowed:

* Sections
  * `#` and `^` for positive and negative matches
  * `#` for loops if prop is array
* `{{escapedVars}}` and `{{{unEscapedVars}}}`
* `{{vars}}` inside tag values: `<a href="{{link}}" />`

## NOT allowed:

* `{{vars}}` inside tag names: `<{{tag}} prop="myprop">`
* `{{vars}}` inside tag property names: <a {{propName}}="propValue" />


# Transpilation

Here's how the HTML template transpilation occurs:

## Example input:

    {{#people}}
        <h1>{{name}}</h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}">
            {{linkText}}
        </a>
    {{/people}}

## 1: Text-based manipulation

### 1a: Sections

First step: identify sections, and wrap them. This is just a preg_replace command.

```html
    <reactor type="section" var="people">{{#people}}
        <h1>{{name}}</h1>
        <a href="site/{{link}}/index" title="{{linkTitle}}">
            {{linkText}}
            {{{unEscapedLinkText}}}
        </a>
    {{/people}}</reactor>
```

## 2: DOM Parser manipulation

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
