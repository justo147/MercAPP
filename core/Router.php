<?php

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, array $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function dispatch(string $method, string $uri, array $context): void
    {
        foreach ($this->routes as [$routeMethod, $path, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $pattern    = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
            $pattern    = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }

            // Extract named params from {param} placeholders
            preg_match_all('/\{([^}]+)\}/', $path, $paramNames);
            $params = [];
            foreach ($paramNames[1] as $i => $name) {
                $params[$name] = $matches[$i + 1];
            }

            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass(
                $context['twig'],
                $context['base'],
                $context['conn']
            );
            $controller->$action($params);
            return;
        }

        http_response_code(404);
        echo '<h1>404 — Página no encontrada</h1>';
    }
}
