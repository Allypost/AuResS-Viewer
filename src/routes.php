<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

/**
 * @var \Slim\Container $container
 * @var Slim\Csrf\Guard $csrf
 */

$container = $app->getContainer();
$csrf = $container->get('csrf');

$app->get('/', function (Request $request, Response $response, array $args) {
    // Render index view
    return $this->view->render($response, 'pages/index.twig', $args);
})->setName('room:join')->add($csrf);

$app->post('/join', function (Request $request, Response $response, array $args) {
    $room = (int)$request->getParam('room');

    return \Allypost\Api\Output::say($response, 'room join', compact('room'));
})->setName('room:join.post')->add($csrf);
