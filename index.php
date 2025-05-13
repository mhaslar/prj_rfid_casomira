<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Jednoduchý router pro zpracování API požadavků.
 * Tento router umožňuje přidávat cesty (endpoints) a přiřazovat jim funkce (handlery).
 */
class Router {
    protected $routes = [];

    /**
     * Přidá novou cestu (endpoint) s odpovídajícím handlerem a volitelným požadavkem na autentizaci.
     * @param string $path URL cesta (např. "/users")
     * @param callable $handler Funkce, která zpracuje požadavek
     * @param bool $requiresAuth Určuje, zda endpoint vyžaduje autentizaci (zatím false)
     */
    public function addRoute($path, $handler, $requiresAuth = false) {
        $this->routes[$path] = ['handler' => $handler, 'auth' => $requiresAuth];
    }

    /**
     * Placeholder pro ověřování autentizace. Zatím vrací true, protože autentizace není implementována.
     * @return bool
     */
    protected function checkAuth() {
        // Implementaci autentizace můžete přidat později (např. kontrola tokenu, session apod.)
        return true;
    }

    /**
     * Dispatcher, který zpracuje příchozí požadavek a spustí odpovídající handler.
     * @param string $uri URL požadavku
     * @param string $method HTTP metoda (GET, POST, ...)
     */
    public function dispatch($uri, $method) {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        if (isset($this->routes[$path])) {
            $route = $this->routes[$path];
            if ($route['auth'] && !$this->checkAuth()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            call_user_func($route['handler'], $method);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
    }
}

// Vytvoření instance routeru
$router = new Router();

// Načtení endpoint definic z externích souborů
require_once __DIR__ . '/endpoints/tagRead.php';
require_once __DIR__ . '/endpoints/isInRace.php';
require_once __DIR__ . '/endpoints/getStarts.php';
require_once __DIR__ . '/endpoints/updateStart.php';
require_once __DIR__ . '/endpoints/getStartList.php';
require_once __DIR__ . '/endpoints/getResults.php';

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>