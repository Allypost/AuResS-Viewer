<?php

use Psr\Container\ContainerInterface;
use Slim\Flash\Messages;
use Slim\Http\Cookies;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use Slim\Csrf\Guard;
use Allypost\Twig\CsrfExtension;

// DIC configuration

$container = $app->getContainer();

$container['csrf'] = function (): Guard {
    return new Guard;
};

$container['flash'] = function () {
    return new Messages;
};

$container['cookie'] = function (ContainerInterface $container) {
    /**
     * @var \Slim\Http\Request $request
     */

    $request = $container->get('request');

    return new Cookies($request->getCookieParams());
};

$container['view'] = function (ContainerInterface $container) {
    $settings = $container->get('settings')['renderer'];

    $view = new Twig($settings['template_path'], [
        'cache' => $settings['cache_path'] ?? false,
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');

    $view->addExtension(new TwigExtension($container['router'], $basePath));
    $view->addExtension(new CsrfExtension($container['csrf']));
    $view->addExtension(
        new Knlv\Slim\Views\TwigMessages(
            new Slim\Flash\Messages()
        )
    );

    return $view;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db']);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};
