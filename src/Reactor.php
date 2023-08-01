<?php declare(strict_types=1);

namespace Reactor;

class Reactor {
    /** @var \Reactor\Request */
    private $request;
    private $template;
    private $onErrorCallback;

    /**
     * @param \callable(\Throwable): void $errorCallback
     * @throws \Reactor\Exception\InvalidArgument
     *
     */
    function __construct(\Closure $onErrorCallback = null) {
        $this->onErrorCallback = $onErrorCallback;
        try {
            ob_start();
            $this->request = Request::load();
            $this->template = ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    function run(): void {
        header("Content-Type: application/json");
        $component = $this->createComponent();
        try {
            // TODO: does nothing so far
            $component->run();
            $response = $this->createResponse($component);
        } catch (\Throwable $e) {
            if ($this->onErrorCallback) {
                call_user_func($this->onErrorCallback, $e);
            } else {
                $this->defaultErrorCallback($e);
            }
            $response = [
                'error' => $e->getMessage()
            ];
        }
        echo json_encode($response);
    }

    private function createComponent(): Component {
        $class = $this->request->getComponent();
        $component = new $class($this->request->getId());
        $serverVarNames = $component->_reactorGetServerVarNames();
        foreach ($serverVarNames as $name) {
            if (property_exists($this->request->getVars(), $name)) {
                $component->$name = json_decode($this->request->getVars()->$name);
            }
        }
        return $component;
    }


    private function createResponse(Component $component): Response {
        $parser = new Parser\TemplateParser();
        return new Response(
            $parser->parse($this->request->getComponent(), $this->template, $component->_reactorGetServerVarNames()),
            $component->jsonSerialize(),
            // todo SSR
            ''
        );
    }

    private function defaultErrorCallback(\Throwable $e, bool $isPrevious = false): void {
        error_log(($isPrevious ? 'Previous e' : 'E') . 'xception ' . get_class($e) . ': ' . $e->getMessage());
        error_log($e->getTraceAsString());
        if ($e->getPrevious()) {
            $this->defaultErrorCallback($e->getPrevious(), true);
        }
    }
}
