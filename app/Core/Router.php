<?php

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $method = strtoupper($method);

        $action = $this->routes[$method][$path] ?? null;

        if (!$action) {
            http_response_code(404);
            $controllerClass = 'App\\Controllers\\ErrorController';
            if (class_exists($controllerClass) && method_exists($controllerClass, 'notFound')) {
                $controller = new $controllerClass();
                $controller->notFound();
            } else {
                echo '404 - Página não encontrada';
            }
            return;
        }

        [$controllerName, $methodName] = explode('@', $action);
        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo 'Controller não encontrado';
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            http_response_code(500);
            echo 'Método não encontrado';
            return;
        }

        $controller->{$methodName}();
    }
}
