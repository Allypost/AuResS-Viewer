<?php

use Allypost\Api\Output;
use Allypost\Api\Room;
use Slim\Http\Request;
use Slim\Http\Response;

// Routes

/**
 * @var \Slim\Container  $container
 * @var \Slim\Csrf\Guard $csrf
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

$app->get('/{room:\d{4}}/[{type}]', function (Request $request, Response $response, array $args) {
    /**
     * @var \Slim\Container $this
     * @var \Slim\Container $settings
     */
    $settings = $this->get('settings');
    $redis = new \RedisClient\RedisClient($settings->get('redis'));
    $room = $args['room'];
    $type = $args['type'] ?? 'last';

    $data = Room::get($room, $type, $redis);

    return $this->view->render($response, 'pages/view.twig', compact('room', 'type', 'data'));
})->setName('room:view')->add($csrf);

$app->group('/api', function () {
    /**
     * @var \Slim\App $this
     */

    $this->get('/{room:\d{4}}/[{type}]', function (Request $request, Response $response, array $args) {
        /**
         * @var \Slim\Container $this
         * @var \Slim\Container $settings
         */
        $settings = $this->get('settings');
        $redis = new \RedisClient\RedisClient($settings->get('redis'));
        $room = $args['room'];
        $type = $args['type'] ?? 'last';

        $data = Room::get($room, $type, $redis);

        return Output::say($response, 'room data', compact('room', 'type', 'data'));
    })->setName('api:room:data');

    $this->get('/mock/[{type}]', function (Request $request, Response $response, array $args) {
        $room = 'mock';
        $type = $args['type'] ?? 'last';

        $data = [random_int(0, 200), random_int(0, 200), random_int(0, 200), random_int(0, 200), random_int(0, 200)];

        return Output::say($response, 'room data', compact('room', 'type', 'data'));
    })->setName('api:room:mock');

});

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

$app->post('/leave', function (Request $request, Response $response, array $args) {
    /**
     * @var \Slim\Router       $router
     * @var \Slim\Http\Cookies $cookie
     */
    $router = $this->router;
    $cookie = $this->cookie;

    $cookie->set('room-number', [
        'value' => '',
        'expires' => time() - 3600,
        'secure' => true,
        'httponly' => true,
        'path' => '/',
        'host' => $request->getServerParam('SERVER_NAME'),
    ]);

    return $response
        ->withHeader('Set-Cookie', $cookie->toHeaders())
        ->withRedirect(
            $router->pathFor('room:join')
        );
})->setName('room:leave.post')->add($csrf);
