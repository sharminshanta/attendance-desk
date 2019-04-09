<?php
// Application middleware

$authMiddleware = function ($request, $response, $next) {
    if (!isset($_SESSION['auth'])) {
        $route = $request->getAttribute('route');
        if ($route) {
            return $response->withStatus(403)->withHeader('Location', '/login');
        }
    }
    return $next($request, $response);
};


$adminMiddleware = function ($request, $response, $next) {
    if ($_SESSION['auth']['role'] != 1) {
        $route = $request->getAttribute('route');
        if ($route) {
            $this->flash->addMessage('error', 'You are not authorized to access this page');
            return $response->withStatus(403)->withHeader('Location', '/dashboard');
        }
    }
    return $next($request, $response);
};
