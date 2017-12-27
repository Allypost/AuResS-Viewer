<?php

use Allypost\Api\Output;
use Allypost\Api\Room;
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
    /**
     * @var \Slim\Http\Cookies $cookie
     */
    $cookie = $this->cookie;

    $room = $cookie->get('room-number', null);

    return $this->view->render($response, 'pages/index.twig', compact('room'));
})->setName('room:join')->add($csrf);

$app->get('/{room:\d{4}}', function (Request $request, Response $response, array $args) {
    /**
     * @var \Slim\Container $this
     * @var \Slim\Container $settings
     */
    $settings = $this->get('settings');
    $redis = new \RedisClient\RedisClient($settings->get('redis'));
    $room = $args['room'];

    $roomData = Room::get($room, 'all', $redis);

    return Output::say($response, 'room join', compact('room', 'roomData'));
})->setName('room:view');

$app->post('/join', function (Request $request, Response $response, array $args) {
    $room = (int) $request->getParam('room');

    if ($room < 0 || $room > 9999) {
        return Output::err($response, 'room join', ['Invalid room number supplied']);
    }

    $paddedRoom = str_pad((string) $room, 4, '0', STR_PAD_LEFT);

    /**
     * @var \Slim\Router       $router
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
        'host' => $request->getServerParam('SERVER_NAME'),
    ]);

    return $response
        ->withHeader('Set-Cookie', $cookie->toHeaders())
        ->withRedirect(
            $router->pathFor('room:view', ['room' => $paddedRoom])
        );
})->setName('room:join.post')->add($csrf);
