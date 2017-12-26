<?php

use Allypost\Api\Output;
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

$app->get('/{room:\d{4}}', function (Request $request, Response $response, array $args) {
    $room = (int)$args['room'];

    return Output::say($response, 'room join', compact('room'));
})->setName('room:view');

$app->post('/join', function (Request $request, Response $response, array $args) {
    $room = (int)$request->getParam('room');

    if ($room < 0 || $room > 9999) {
        return Output::err($response, 'room join', ['Invalid room number supplied']);
    }

    $paddedRoom = str_pad((string)$room, 4, '0', STR_PAD_LEFT);

    /**
     * @var \Slim\Router $router
     * @var \Slim\Http\Cookies $cookie
     */
    $router = $this->router;
    $cookie = $this->cookie;

    $cookie->set('room-number', [
        'value' => $paddedRoom,
        'expires' => 60 * 60 * 8 + time(),
        'secure' => true,
        'httponly' => true,
        'path' => '/',
        'host' => $request->getServerParam('SERVER_NAME')
    ]);

    return $response
        ->withHeader('Set-Cookie', $cookie->toHeaders())
        ->withRedirect(
            $router->pathFor('room:view', ['room' => $paddedRoom])
        );
})->setName('room:join.post')->add($csrf);
