import { Component } from "./component.js";
import { ReactorError } from "./error.js";

export class Renderer {
    /** @param {Component} component */
    constructor(componentId, highlightCallback) {
        this.styleId = 'reactor.style.' + componentId;
        this.highlightCallback = highlightCallback;
    }

    /** First time rendering, full DOM insertion */
    render(node, template, vars, changedState) {
        const styleNode = document.getElementById(this.styleId);
        if (!styleNode) {
            // style, only needs to be updated once
            const style = document.createElement('style');
            style.id = this.styleId;
            // TODO: questionable: do we want this dynamism?
            style.innerHTML = template.renderCss();
            document.body.appendChild(style);
            node.innerHTML = template.renderHtml(vars);
            this.highlightCallback(node);
        } else {
            this.reflow(node, template, vars, changedState);
        }
    }

    reflow(reactorNode, template, vars, changedState) {
        const reactor = this._extractReactorElements(reactorNode);
        this._reflowVars(reactor.vars, template, vars, changedState);
        this._reflowProperties(reactor.properties, template, vars, changedState);
        this._reflowSections(reactor.sections, template, vars, changedState);
    }

    _reflowVars(varNodes, template, vars, changedState) {
        for (const node of varNodes) {
            for (const name in changedState) {
                if (node.getAttribute('name') === name && changedState[name] !== vars[name]) {
                    if (node.getAttribute('escaped') === 'true') {
                        node.innerHTML = template.renderInlineHtml('{{' + name + '}}', vars);;
                    } else {
                        node.innerHTML = template.renderInlineHtml('{{{' + name + '}}}', vars);;
                    }
                    this.highlightCallback(node);
                }
            }
        }
    }

    /** Possible improvement: track what properties has what mustaches vars, we are changing all properties now */
    _reflowProperties(propertyNodes, template, vars, changedState) {
        for (const node of propertyNodes) {
            const attributes = node.getAttribute('reactorProperties').split(',');
            for (const attribute of attributes) {
                node.setAttribute(attribute, template.renderNodeAttribute(attribute, node.getAttribute('reactorId'), vars));
            }
        }
    }

    _reflowSections(sectionNodes, template, vars, changedState) {
        let changedDepth = undefined;
        for (const node of sectionNodes) {
            const sectionName = node.getAttribute('var');
            const currentDepth = parseInt(node.getAttribute('depth'), 10);
            if (changedDepth !== undefined && currentDepth <= changedDepth) {
                changedDepth = undefined;
            }
            if (changedDepth !== undefined && currentDepth > changedDepth) {
                // no need to change, the previous section was already modified
                continue;
            }
            if (typeof changedState[sectionName] !== 'undefined' && changedState[sectionName] !== vars[sectionName]) {
                changedDepth = currentDepth;
                node.innerHTML = template.renderSection(node.getAttribute('reactorId'), vars);
            }
        }
    }

    /** @param {HTMLElement} componentNode */
    _extractReactorElements(componentNode) {
        const sections = [];
        const vars = [];
        const properties = [];
        for (const node of componentNode.getElementsByTagName('reactor')) {
            switch (node.getAttribute('type')) {
                case 'section': sections.push(node); break;
                case 'var': vars.push(node); break;
                default: throw new ReactorError('Unexpected reactor node (type: ' + node.type + ')');
            }
        }
        for (const node of componentNode.getElementsByClassName('hasReactorProperties')) {
            properties.push(node);
        }
        return {
            sections: sections,
            vars: vars,
            properties: properties
        };
    }
}
