import { Component } from "./component.js"
import { validateConfig } from "./config.js";
import { Template } from "./template.js";
import { reactorFetch } from "./server.js";
import { ReactorError } from "./error.js";

export class Reactor {
    /**
     * @param {ReactorConfig} config
     */
    constructor(config) {
        validateConfig(config);
        this.config = config;
        if (!window.REACTOR) {
            window.REACTOR = this;
            /** @var {Component[]} */
            this.components = {};
        }
    }

    /**
     * @param {String} name
     * @param {String} id Optional, if there are more than one instances
     * @returns {Object} The JSCode the user created, with access to it's public functions
     */
    getComponent(name, id) {
        return this._getComponent(name, id).getInstance();
    }

    /** @returns {Component} Only for internal library usage, use getComponent() to get the public interface of the Component */
    _getComponent(name, id) {
        return this.components[Component.createComponentId(name, id)];
    }

    async run(componentName, id, node) {
        const componentId = Component.createComponentId(componentName, id);
        try {
            const url = this._getRouteUrl(componentName);
            const json = await reactorFetch(url, componentName, id, typeof this.components[componentId] !== 'undefined' ? this.components[componentId].getServerVars() : undefined);
            if (typeof this.components[componentId] === 'undefined') {
                this.components[componentId] = new Component(
                    node,
                    new Template(
                        componentName,
                        json.template.jsCode,
                        json.template.cssCode,
                        json.template.htmlCode,
                        this.config.templateEngine
                    ),
                    id,
                    json.serverVars,
                    json.innerHTML
                );
            } else {
                this.components[componentId].updateStateFromServer(json.serverVars);
            }
            this.components[componentId].render(this.config.refreshCallback);
        } catch (error) {
            if (typeof this.components[componentId] !== 'undefined') {
                this.components[componentId].onError(error, this.config.onError);
            } else {
                this.config.onError(error, node);
            }
        }
    }

    /**
     * This should be called automatically after any public function in the Component JS code is run.
     * We will deal with redrawing the component, including fetching from the server if needed.
     * It will only modify the DOM if it detects changes on the Component's variables (server or client).
     * You should never need to call this method manually, instead talk to the Component using its public interface.
     *
     * @param {String} component
     * @param {String} id
     * @returns
     */
    _react(componentName, id) {
        const componentId = Component.createComponentId(componentName, id);
        if (!this.components[componentId]) {
            // race condition? TODO: log this, should never happen
            console.log('Component "' + componentId + '" no longer present');
            return;
        }
        const c = this.components[componentId];
        // TODO: debounce? let client define debounce?
        // call react() first, let the client define last minute actions and delay server fetch if needed
        const avoidServerReact = c.getInstance()._reactorCallFunction('react');
        const changedState = c.getChangeStatus();
        if (changedState === Component.NO_CHANGE()) {
            return;
        }
        if (changedState === Component.SERVER_CHANGED() && !avoidServerReact) {
            // server fetch needed
            this.run(componentName, id, c.node);
        } else {
            // avoid fetch, just redraw what has changed
            c.reflow(this.config.refreshCallback);
        }
    }

    /** @returns {String} server path */
    _getRouteUrl(componentName) {
        for (const route of this.config.routes) {
            if (route.regexp.exec(componentName)) {
                return route.url;
            }
        }
        throw new ReactorError('Route not found for "' + componentName + '"');
    }
}
