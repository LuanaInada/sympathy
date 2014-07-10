<?php

namespace Sympathy\Silex\Router;

use Silex\Application;
use Twig_Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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

    protected function isJsonRequest (Request $request) {
        $result = false;

        $headers = $request->headers;

        if(strpos($headers->get('Accept'), 'application/json') !== false) {
            $result = true;
        }

        if(strpos($headers->get('Content-Type'), 'application/json') !== false) {
            $result = true;
        }

        return $result;
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
            } else {
                $code = 500;
            }

            if ($this->isJsonRequest($request)) {
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
            $template = 'error/404.twig';
        } else {
            $template = 'error/default.twig';
        }

        $values = $this->getErrorDetails($exception, $code);

        $result = $this->twig->render($template, $values);

        return new Response($result, $code);
    }
}