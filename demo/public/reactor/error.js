export class ReactorError extends Error {
    constructor(message) {
        super(message);
        this.name = 'ReactorError';
    }
}
