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
    /**
     * @var \Slim\Http\Cookies $cookie
     */
    $cookie = $this->cookie;

    $room = $cookie->get('room-number', null);

    return $this->view->render($response, 'pages/index.twig', compact('room'));
})->setName('room:join')->add($csrf);

$app->get('/{room:\d{4}}', function (Request $request, Response $response, array $args) {
    $room = $args['room'];

    $opts = [
        'http' => [
            'method' => "GET",
            'header' => "Host: www.auress.org\r\n" .
                        "User-Agent: Auress-Viewer-Bot\r\n" .
                        "Accept: text/html\r\n",
        ],
    ];
    $url = sprintf('http://www.auress.org/graf.php?brOdgovora=999&all=1&soba=%s', $room);

    $roomData = file_get_contents($url, false, stream_context_create($opts)) ?? '';
    $roomData = explode(',', $roomData);
    $roomData = array_map(function ($el) {
        return (int) $el;
    }, $roomData);

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
