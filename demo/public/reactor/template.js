/**
 * A template engine
 * @interface TemplateRenderer
 */
/**
 * @method TemplateRenderer#render
 * @desc Convert a template string applying some variables
 * @param {String} template
 * @param {object} variables
 * @returns {String}
 */

import { ReactorError } from "./error.js";

export class Template {
    /**
     * @param {String} name
     * @param {String} jsCode
     * @param {String} cssCode
     * @param {String} htmlCode
     * @param {TemplateRenderer} templateRenderer
     */
    constructor(name, jsCode, cssCode, htmlCode, templateRenderer) {
        this.name = name;
        this.jsCode = jsCode;
        this.cssCode = cssCode;
        this.htmlCode = htmlCode;
        this.templateRenderer = templateRenderer;
    }

    createInstance(serverVars) {
        const codedVars = this._toCodedVars(serverVars);
        const code = this._renderCapturingErrors('js', this.jsCode, codedVars);
        try {
            const ComponentClass = Function(code)();
            return new ComponentClass(ReactorError);
        } catch (err) {
            throw new ReactorError('Error instantiating component "' + this.name + '": ' + err.message);
        }
    }

    renderCss() {
        return this.cssCode;
    }

    renderHtml(vars) {
        return this._renderCapturingErrors('html', this.htmlCode, vars);
    }

    renderInlineHtml(htmlCode, vars) {
        return this._renderCapturingErrors('inlineHtml', htmlCode, vars);
    }

    renderSection(classId, vars) {
        this._createHtmlDivIfNeeded();
        const section = this.htmlDiv.getElementsByClassName(classId)[0];
        console.log('reflow section ' + classId);
        console.log(section.innerHTML);
        console.log(vars);
        console.log(this.renderInlineHtml(section.innerHTML, vars));
        return this.renderInlineHtml(section.innerHTML, vars);
    }

    renderNodeAttribute(attribute, classId, vars) {
        this._createHtmlDivIfNeeded();
        const property = this.htmlDiv.getElementsByClassName(classId)[0];
        return this.renderInlineHtml(property.getAttribute(attribute), vars);
    }

    getCleanHtmlTemplate() {
        const regex1 = /\<reactor\s.*\>/gm;
        const regex2 = /\<\/reactor\>/gm;
        return this.htmlCode.replace(regex1, '').replace(regex2, '');
    }

    getOriginalJsCode() {
        const regex = /(?:\/\/REACTOR\_CLIENT\_CODE\_STARTS\/\/)(.*)(?:\/\/REACTOR\_CLIENT\_CODE\_ENDS\/\/)/ms;
        const m = regex.exec(this.jsCode);
        if (m !== null) {
            return m[1];
        }
        throw new ReactorError('Did not find start and end tags for client code in jsCode template');
    }

    _renderCapturingErrors(what, code, vars) {
        try {
            return this.templateRenderer.render(code, vars);
        } catch (err) {
            let message = String(err.message || err);
            if (message.match(/^Can't find /)) {
                message += '. Review your template, check mustache vars be sure to assign default values to client properties.'
            }
            throw new ReactorError('Error rendering ' + what + ' for component "' + this.name + '": ' + message );
        }
    }

    _toCodedVars(vars) {
        const codedVars = {};
        for (const key in vars) {
            codedVars[key] = JSON.stringify(vars[key]);
        }
        return codedVars;
    }

    _createHtmlDivIfNeeded() {
        if (!this.htmlDiv) {
            this.htmlDiv = document.createElement('div');
            this.htmlDiv.innerHTML = this.htmlCode;
        }
    }
}
