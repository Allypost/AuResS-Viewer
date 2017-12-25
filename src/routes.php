<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    /**
     * @var \Slim\Router $router ;
     */
    $router = $this->router;

    $postRoute = $router->pathFor('room:join.post');

    // Render index view
    return $this->renderer->render($response, 'index.phtml', compact('args', 'postRoute'));
});

$app->post('/join', function (Request $request, Response $response, array $args) {
    $room = (int)$request->getParam('room');

    return \Allypost\Api\Output::say($response, 'room join', compact('room'));
})->setName('room:join.post');
