<?php
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();

$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

//$app['twig'] = $app->extend('twig', function ($twig, $app) {
//    // add custom globals, filters, tags, ...
//
//    return $twig;
//});

$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
$app['service_domain'] = $protocol . $_SERVER['SERVER_NAME'] . '/gate/';

// db
//$app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
//    'db.options' => array(
//        'driver' => 'pdo_mysql',
//        'host' => 'localhost',
//        'dbname' => 'url_shortener',
//        'user' => 'root',
//        'password' => ''
//    ),
//));
$app->register(new \IPC\Silex\Provider\PDOServiceProvider(), array(
    'pdo.options' => array(
        'dsn' => 'mysql:host=localhost;dbname=url_shortener',
        'username' => 'root',
        'password' => '',
        'options' => [], // important!
    )
));

// redis
$app->register(new \Moust\Silex\Provider\CacheServiceProvider(), array(
    'cache.options' => array(
        'driver' => 'redis'
    )
));


// url shortener service
$app->register(new \App\Provider\UrlShortenerProvider(), array(
    'service.options' => array(
        'domain' => $app['service_domain'],
    )
));


return $app;
