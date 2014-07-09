<?php

namespace Sympathy\Silex\Router;

use Symfony\Component\HttpFoundation\Request;

class Rest extends Router
{
    public function route($routePrefix = '/api', $servicePrefix = 'controller.rest.', $servicePostfix = '')
    {
        $app = $this->app;
        $container = $this->container;

        $handler = function ($path, Request $request) use ($app, $container, $servicePrefix, $servicePostfix) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }

            $prefix = strtolower($request->getMethod());
            $parts = explode('/', $path);

            $controller = array_shift($parts);

            $subResources = '';
            $params = array();

            $count = count($parts);

            if ($count == 0) {
                $prefix = 'c' . $prefix;
            }

            for ($i = 0; $i < $count; $i++) {
                $params[] = $parts[$i];

                if (isset($parts[$i + 1])) {
                    $i++;
                    $subResources .= ucfirst($parts[$i]);
                }
            }

            $params[] = $request;
            $actionName = $prefix . $subResources . 'Action';

            $controllerService = $servicePrefix . strtolower($controller) . $servicePostfix;

            try{
                $controllerInstance = $container->get($controllerService);
            } catch (\Exception $e) {
                throw new NotFoundException ('API controller service not found: ' . $controllerService);
            }

            if (!method_exists($controllerInstance, $actionName)) {
                throw new NotFoundException ('API controller method not found: ' . $actionName);
            }

            $result = call_user_func_array(array($controllerInstance, $actionName), $params);

            return $app->json($result);
        };

        $app->match($routePrefix . '/{path}', $handler)->assert('path', '.+');
    }
}