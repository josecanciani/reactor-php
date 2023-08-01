<?php

namespace Reactor\Demo\MainMenu;

use Reactor\Component;

class Index extends Component {
    public $counter = 0;
}

?>

<script>
    // public variables, need a default value or Mustache.render() will fail
    let clientCounter = 1;
    let tooHighMessage = '';
    // we don't support reactive statements like Svelte, use the react method to update after changes
    let clientButtonTitle = 'Current count: ' + clientCounter;
    let serverCallCount = 0;
    let jsCode = '';
    let htmlCode = '';
    let cssCode = '';

    // you can define private variables just by prepending an underscore. Same is true for methods.
    let _hls;

    // start loading external modules, to highlight code
    Promise.all(
        [
            import('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/es/highlight.min.js'),
            import('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/es/languages/javascript.min.js'),
            import('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/es/languages/css.min.js')
        ]
    ).then(
        function (modules) {
            _hls = modules[0].default;
            _hls.registerLanguage('javascript', modules[1].default);
            _hls.registerLanguage('css', modules[2].default);
            _highlightCode();
        }
    );
    if (!document.getElementById('highlight.js.css')) {
        const sheet = document.createElement('link');
        sheet.setAttribute('rel', 'stylesheet');
        sheet.setAttribute('type', 'text/css');
        sheet.setAttribute('id', 'highlight.js.css');
        sheet.setAttribute('href', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/default.min.css');
        document.head.appendChild(sheet);
    }

    /**
     * Special function called when other methods change a variable
     * @returns {bool} return true if you want to avoid a server react
     */
    function react() {
        clientButtonTitle = 'Current count: ' + clientCounter;
        if (_hasReachedClientLimit()) {
            tooHighMessage = 'Client counter limit reached, avoiding new server request.';
            return true;
        }
        _highlightCode();
    }

    function serverIncrease() {
        counter += 1;
        if (!_hasReachedClientLimit()) {
            serverCallCount++;
        }
    }

    function clientIncrease() {
        clientCounter += 1;
    }

    /** we just set the public variables, react() will deal with highlighting as soon as hljs module is ready */
    function showCode() {
        // _getComponent => just to demostrate in the Demo, not to be used by devs using Reactor
        // PHP's LIBXML needs to escape closing tags in javascript code https://bugs.php.net/bug.php?id=80095
        jsCode = REACTOR._getComponent(reactorComponent, reactorId).template.getOriginalJsCode();
        //cssCode = '<pre>' + _escapeHtml(REACTOR._getComponent(reactorComponent, reactorId).template.cssCode) + '<\/pre>';
        //htmlCode = '<pre>' + _escapeHtml(REACTOR._getComponent(reactorComponent, reactorId).template.getCleanHtmlTemplate()) + '<\/pre>';
    }

    /** A component private method */
    function _hasReachedClientLimit() {
        return clientCounter > 4;
    }

    function _highlightCode() {
        if (_hls && jsCode) {
            jsCode = _hls.highlight(jsCode, {language: 'javascript'}).value;
            //cssCode = _hls.highlight(REACTOR._getComponent(reactorComponent, reactorId).template.cssCode, {language: 'css'}).value;
            //htmlCode = _hls.highlight(REACTOR._getComponent(reactorComponent, reactorId).template.getCleanHtmlTemplate(), {language: 'html'}).value;
            _hls = null;
        }
    }

    function _escapeHtml(html){
        const text = document.createTextNode(html);
        const p = document.createElement('p');
        p.appendChild(text);
        // avoid Mustach to render inside our vars!
        return p.innerHTML.replace(/{/mg, '&lcub;').replace(/}/mg, '&rcub;');
    }
</script>

<style>
    p {
        color: purple;
        font-size: 1em;
    }
    .tooHigh {
        background-color: yellow;
    }
    .small {
        font-size: 0.2em;
    }
    pre code {
        font-size: 0.5em;
    }
</style>

<p>
    Component <u>{{reactorComponent}}</u> says <em>Hello World!</em><br><br>
    Server Counter is: {{counter}}<br>
    Client Counter: {{clientCounter}}
</p>

<button onclick="showCode()">Show me the code</button>
<button onclick="serverIncrease()">Increase server</button>
<button onclick="clientIncrease()" title="{{clientButtonTitle}}">Increase client (*)</button>

{{#tooHighMessage}}
<ul>
    <li class="tooHigh">{{{tooHighMessage}}}</li>
    <li>Notice how Server Counter is increased locally, but server calls will remain in {{serverCallCount}} (no new requests are being made).</li>
</ul>
{{/tooHighMessage}}

<hr>
<span class="small">
(*) Hover this button to see the dynamic "title" property change as button is clicked.
</span>

{{#jsCode}}
<pre><code class="">{{{jsCode}}}</code></pre>
{{/jsCode}}

{{#cssCode}}
<code class="language-css">{{{cssCode}}}</code>
{{/cssCode}}

{{#htmlCode}}
<code class="language-html">{{{htmlCode}}}</code>
{{/htmlCode}}
