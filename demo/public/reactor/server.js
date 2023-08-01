
import { ReactorError } from "./error.js";

export class ServerError extends ReactorError {
    constructor(message, statusCode) {
        super(message);
        this.name = 'ReactorServerError';
        this.statusCode = statusCode;
    }
}

/** @returns {Promise} */
async function doRequest(method, url, formData) {
    return new Promise(function (resolve) {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.onload = function () {
            if (this.status >= 200 && this.status < 300) {
                try {
                    const json = JSON.parse(xhr.response);
                    if (json.error) {
                        resolve(new ReactorError('Error detected on the server: ' + json.error));
                    } else {
                        resolve(json);
                    }
                } catch (err) {
                    resolve(new ReactorError('Cannot parse response from server: ' + err.message));
                }
            } else {
                resolve(new ReactorServerError('Reactor Server Error, XMLHttpRequest status: ' + xhr.statusText, this.status));
            }
        };
        xhr.onerror = function () {
            resolve(new ReactorServerError('Reactor Server Error, XMLHttpRequest status: ' + xhr.statusText, this.status));
        };
        xhr.send(formData);
    });
};

/**
 * Manages the connection with the server
 * No-goals: create objects other than ReactorErrors
 * TODO: worker, offline mode support
 *
 * @param {String} component Component name
 * @param {String} id Component unique ID (optional)
 * @param {object} serverVars Optional, variables that will get populated in the server component
 * @returns {object} JSON from the server
 * @throws {ReactorError} Error when response was not what we expected
 */
export async function reactorFetch(url, component, id, serverVars) {
    const formData = new FormData();
    if (serverVars) {
        for (const varName in serverVars) {
            formData.append('reactor_serverVariable_' + varName, JSON.stringify(serverVars[varName]));
        }
    }
    const instanceOrError = await doRequest(
        serverVars ? 'POST' : 'GET',
        url + '?mode=ssr&component=' + component + '&id=' + (id || ''),
        serverVars ? formData : undefined
    );
    if (instanceOrError instanceof ReactorError) {
        throw instanceOrError;
    }
    return instanceOrError;
};
