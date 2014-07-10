<?php

namespace Sympathy\Silex\Router;

use Silex\Application;
use Twig_Environment;
use Symfony\Component\HttpFoundation\Response;

class ErrorRouter
{
    protected $app;
    protected $twig;
    protected $exceptionCodes = array();
    protected $exceptionMessages = array();
    protected $debug = false;

    public function __construct(Application $app, Twig_Environment $twig, array $exceptionCodes, array $exceptionMessages, $debug = false)
    {
        $this->app = $app;
        $this->twig = $twig;

        $this->exceptionCodes = $exceptionCodes;
        $this->exceptionMessages = $exceptionMessages;

        $this->debug = $debug;
    }

    public function route()
    {
        $app = $this->app;
        $exceptionCodes = $this->exceptionCodes;

        $app->error(function (\Exception $e, $code) use ($app, $exceptionCodes) {
            $request = $app['request'];
            $exceptionClass = get_class($e);

            if (isset($exceptionCodes[$exceptionClass])) {
                $code = $exceptionCodes[$exceptionClass];
            }

            if (0 === strpos($request->headers->get('Accept'), 'application/json')) {
                return $this->jsonError($e, $code);
            } else {
                return $this->htmlError($e, $code);
            }
        });
    }

    protected function getErrorDetails (\Exception $exception, $code) {
        if ($this->debug) {
            $message = $exception->getMessage();

            if (empty($message) && isset($this->exceptionMessages[$code])) {
                $message = $this->exceptionMessages[$code];
            }

            $class = get_class($exception);
            $trace = $exception->getTrace();
        } else {
            if (isset($this->exceptionMessages[$code])) {
                $message = $this->exceptionMessages[$code];
            } else {
                $message = $exception->getMessage();
            }

            $class = 'Exception';
            $trace = array();
        }

        $result = array(
            'message' => $message,
            'code' => $code,
            'class' => $class,
            'trace' => $trace
        );

        return $result;
    }

    protected function jsonError(\Exception $exception, $code)
    {
        $values = $this->getErrorDetails($exception, $code);

        return $this->app->json($values, $code);
    }

    protected function htmlError(\Exception $exception, $code)
    {
        if ($code == 404) {
            $template = 'errors/404.twig';
        } else {
            $template = 'errors/default.twig';
        }

        $values = $this->getErrorDetails($exception, $code);

        $result = $this->twig->render($template, $values);

        return new Response($result, $code);
    }
}