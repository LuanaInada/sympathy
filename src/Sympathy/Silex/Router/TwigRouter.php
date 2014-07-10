<?php

namespace Sympathy\Silex\Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Symfony\Component\DependencyInjection\Container;
use Twig_Environment;

class TwigRouter extends Router
{
    protected $twig;

    public function __construct(Application $app, Container $container, Twig_Environment $twig)
    {
        parent::__construct($app, $container);
        $this->twig = $twig;
    }

    public function route($routePrefix = '', $servicePrefix = 'controller.web.', $servicePostfix = '')
    {
        $app = $this->app;
        $container = $this->container;

        $webRequestHandler = function ($controller, Request $request, $action = '') use ($app, $container, $servicePrefix, $servicePostfix) {
            if (!$action) {
                $action = 'index';
            }

            $prefix = strtolower($request->getMethod());
            $parts = explode('/', $action);

            $subResources = '';
            $params = array();

            $count = count($parts);

            for ($i = 0; $i < $count; $i++) {
                $subResources .= ucfirst($parts[$i]);

                if (isset($parts[$i + 1])) {
                    $i++;
                    $params[] = $parts[$i];
                }
            }

            $params[] = $request;
            $actionName = $prefix . $subResources . 'Action';

            $controllerService = $servicePrefix . strtolower($controller) . $servicePostfix;

            $controllerInstance = $this->getController($controllerService);

            if ($prefix == 'get' && !method_exists($controllerInstance, $actionName)) {
                $actionName = $subResources . 'Action';
            }

            if (!method_exists($controllerInstance, $actionName)) {
                throw new NotFoundException ('Web controller method not found: ' . $actionName);
            }

            $this->twig->addGlobal('controller', $controller);
            $this->twig->addGlobal('action', $subResources);

            $result = call_user_func_array(array($controllerInstance, $actionName), $params);

            $template = $controller . '/' . $subResources . '.twig';

            return $this->render($template, (array)$result);
        };

        $indexRequestHandler = function (Request $request) use ($app, $container, $servicePrefix, $servicePostfix, $webRequestHandler) {
            return $webRequestHandler('index', $request, 'index');
        };

        $app->get($routePrefix . '/', $indexRequestHandler);
        $app->match($routePrefix . '/{controller}', $webRequestHandler);
        $app->match($routePrefix . '/{controller}/{action}', $webRequestHandler)->assert('action', '.+');
    }

    protected function render($template, array $values, $httpCode = 200)
    {
        $result = $this->twig->render($template, $values);

        return new Response($result, $httpCode);
    }
}