import { Component } from "./component.js";
import { ReactorError } from "./error.js";

export class ReactorConfig {
    constructor(opts) {
        this.routes = opts.routes || [
            {
                regexp: /.*/,
                url: 'router.php'
            }
        ];
        this.templateEngine = opts.templateEngine;
        /**
         * Default error function. Have in mind that a Component may define an "onError" function
         * that would be called instead when available.
         *
         * @param {HTMLElement} OPTIONAL if we have it available
         */
        this.onError = opts.onError || function (error, node) {
            if (node) {
                node.innerHTML = '<span style="color: red;">' + error + '</span>';
            }
            console.log(error);
        };
        /** @param {HTMLElement} node */
        this.refreshCallback = opts.refreshCallback || function (node, component, id) {
            if (!window.REACTOR_refresher_timeouts) {
                window.REACTOR_refresher_timeouts = {};
                const style = document.createElement('style');
                style.innerHTML = `
                    .reactor-refresh-highlight {
                        -webkit-animation: target-fade 1s 1;
                        -moz-animation: target-fade 1s 1;
                    }

                    @-webkit-keyframes target-fade {
                        0% { background-color: rgba(255,255,153,.8); }
                        100% { background-color: rgba(255,255,153,0); }
                    }
                    @-moz-keyframes target-fade {
                        0% { background-color: rgba(255,255,153,.8); }
                        100% { background-color: rgba(255,255,153,0); }
                    }
                `;
                document.body.appendChild(style);
            }
            const componentId = Component.createComponentId(component, id);
            if (window.REACTOR_refresher_timeouts[componentId]) {
                return;
            }
            node.classList.add('reactor-refresh-highlight');
            window.REACTOR_refresher_timeouts[componentId] = setTimeout(
                function() {
                    node.classList.remove('reactor-refresh-highlight');
                    delete window.REACTOR_refresher_timeouts[componentId];
                },
                1100
            );
        };
    }
}

export const validateConfig = function(config) {
    if (!config.templateEngine || !config.templateEngine.render || typeof config.templateEngine.render !== 'function') {
        throw new ReactorError('Invalid config: no render method found in the templateEngine');
    }
    if (typeof config.refreshCallback !== 'function') {
        throw new ReactorError('Invalid config: no refreshCallback method found in the templateEngine');
    }
}
