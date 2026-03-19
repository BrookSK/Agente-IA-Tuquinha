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
        $params = [];

        // If exact match not found, try pattern matching for dynamic routes
        if (!$action && isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $routeAction) {
                // Convert {param} to regex capture group
                $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';
                
                if (preg_match($regex, $path, $matches)) {
                    $action = $routeAction;
                    // Extract parameter names from pattern
                    preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $paramNames);
                    // Map parameter values
                    for ($i = 0; $i < count($paramNames[1]); $i++) {
                        $params[$paramNames[1][$i]] = $matches[$i + 1];
                    }
                    break;
                }
            }
        }

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

        // Store route params in $_GET for controller access
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
        }

        $controller->{$methodName}();
    }
}
