<?php
/**
 * Front Controller - SETAS-WEB
 */

class App
{
    private string $controller = 'HomeController';
    private string $method = 'index';
    private array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        if (isset($url[0]) && $url[0] !== '') {
            $controllerName = ucfirst($url[0]) . 'Controller';
            $controllerPath = BASE_PATH . '/app/Controllers/' . $controllerName . '.php';
            if (file_exists($controllerPath)) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
        }

        require_once BASE_PATH . '/app/Controllers/' . $this->controller . '.php';
        $controllerInstance = new $this->controller;

        if (isset($url[1])) {
            $method = str_replace(['-', ' '], '', lcfirst(ucwords(str_replace(['-', '_'], ' ', $url[1]))));
            if (method_exists($controllerInstance, $method)) {
                $this->method = $method;
                unset($url[1]);
            }
        }

        $this->params = $url ? array_values($url) : [];

        call_user_func_array([$controllerInstance, $this->method], $this->params);
    }

    private function parseUrl(): array
    {
        $url = trim($_GET['url'] ?? '', '/');
        if (empty($url)) {
            $url = 'home/index';
        }
        return array_filter(explode('/', $url));
    }
}
