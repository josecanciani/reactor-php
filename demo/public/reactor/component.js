import { Renderer } from "./renderer.js";
import { Template } from "./template.js";

export class Component {
    /**
     * @param {String} name
     * @param {string} id Optional
     */
    static createComponentId(name, id) {
        return name + (id ? '_' + id : '');
    }

    static NO_CHANGE() {
        return 0;
    }

    static CLIENT_CHANGED() {
        return 1;
    }

    static SERVER_CHANGED() {
        return 2;
    }

    /**
     * @param {Element} node
     * @param {Template} template
     * @param {String} id Unique component id
     * @param {Object} serverVars The current state of the server
     * @param {String} innerHTML If SSR is enabled, the full HTML of the component
     */
    constructor(node, template, id, serverVars, innerHTML) {
        this.node = node;
        this.template = template;
        this.id = id;
        this.serverVars = serverVars;
        this.instance = this.template.createInstance(serverVars);
        this.state = this._getAllVars();
        // TODO: SSR
        this.innerHTML = innerHTML;
    }

    updateStateFromServer(state) {
        for (const name in state) {
            this.instance._reactorSetServerVar(name, state[name]);
        }
    }

    getName() {
        return this.serverVars.reactorComponent;
    }

    getId() {
        return this.serverVars.reactorComponentId;
    }

    getComponentId() {
        return Component.createComponentId(this.serverVars.reactorComponent, this.serverVars.reactorId);
    }

    getServerVars() {
        const vars = {};
        for (let serverVariable of this.instance._reactorGetServerVarNames()) {
            vars[serverVariable] = this.instance._reactorGetVarValue(serverVariable);
        }
        return vars;
    }

    getChangeStatus() {
        const old = this.state;
        const current = this._getAllVars();
        const serverVarNames = Object.keys(this.getServerVars());
        let clientChange = false;
        for (const name in old) {
            if (old[name] === current[name]) {
                continue;
            }
            if (serverVarNames.includes(name)) {
                return Component.SERVER_CHANGED();
            } else {
                clientChange = true;
            }
        }
        return clientChange ? Component.CLIENT_CHANGED() : Component.NO_CHANGE();
    }

    render(refreshCallback) {
        const renderer = this._createRenderer(refreshCallback);
        renderer.render(this.node, this.template, this._getAllVars(), this._getChangedState());
        this.state = this._getAllVars();
    }

    reflow(refreshCallback) {
        const renderer = this._createRenderer(refreshCallback);
        renderer.reflow(this.node, this.template, this._getAllVars(), this._getChangedState());
        this.state = this._getAllVars();
    }

    onError(err, defaultOnErrorCallback) {
        this.instance._reactorOnError(defaultOnErrorCallback, err, this.node);
    }

    /** TODO: interface for at least the default methods */
    getInstance() {
        return this.instance;
    }

    _getAllVars() {
        const vars = this.getServerVars();
        for (let clientVariable of this.instance._reactorGetClientVarNames()) {
            vars[clientVariable] = this.instance._reactorGetVarValue(clientVariable);
        }
        return vars;
    }

    _getChangedState() {
        const current = this._getAllVars();
        const changedState = {};
        for (const name in this.state) {
            if (this.state[name] !== current[name]) {
                changedState[name] = this.state[name];
            }
        }
        return changedState;
    }

    /** @returns {Renderer} */
    _createRenderer(refreshCallback) {
        if (!refreshCallback) {
            throw new Error('invalid refresh callback');
        }
        const instance = this.instance;
        return new Renderer(this.getComponentId(), function (node) {
            instance._reactorHighlight(refreshCallback, node);
        });
    }
}
