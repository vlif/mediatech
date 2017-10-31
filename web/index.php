<?php
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use MiniUrl\Repository\PdoRepository;
use MiniUrl\Service\ShortUrlService;

require_once __DIR__ . '/../protected/vendor/autoload.php';


$app = new Silex\Application();

// Please set to false in a production environment
$app['debug'] = true;

$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
$app['service_domain'] = $protocol . $_SERVER['SERVER_NAME'] . '/gate/';


$pdo = new PDO('mysql:host=localhost;dbname=url_shortener', 'root', '');


// main error handler
$app->error(function (\Exception $e, Request $request) use ($app) {
    if ($e instanceof NotFoundHttpException) {
        return (new JsonResponse)->setData(
            array('error' => array(
                'message' => "The requested page could not be found ('{$request->getRequestUri()}')."
            ),
        ));
    }

//    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    return (new JsonResponse)->setData(
        array('error' => array(
            'message' => 'We are sorry, but something went terribly wrong.'
        )
    ));
});


// redis
$app->register(new Moust\Silex\Provider\CacheServiceProvider(), array(
    'cache.options' => array(
        'driver' => 'redis'
    )
));


// json
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});


// routes
$app->post('', function (Request $request) use ($app, $pdo) {
        $longUrl = $request->request->get('url');

        $response = new JsonResponse();

        try {
            if (!$longUrl) {
                throw new \Exception('Empty url.', 400);
            }

            $service = new ShortUrlService($app['service_domain'], new PdoRepository($pdo));
            $url = $service->shorten($longUrl);

            $response->setData(array('short_url' => $url->getShortUrl()));
        } catch(\Exception $e) {
            $response
//                ->setStatusCode($e->getCode() ?: 500)
                ->setData(array('error' => array(
                    'message' => "Can't shorten: " . $e->getMessage(),
                )));

            return $response;
        }

        return $response;
    }
);

$app->get('/gate/{shortUrl}', function ($shortUrl) use ($app, $pdo) {
        $response = new JsonResponse();

        // redis
        $isRedisAvailable = false;
        try {
            $cachedUrl = $app['cache']->fetch($app['service_domain'] . $shortUrl);
            $isRedisAvailable = true;
            if ($cachedUrl) {

                return $app->redirect($cachedUrl);
            }
        } catch(\Exception $e) {

            // здесь отправляем уведомление администратору о том, что redis приболел

        }

        try {
            $service = new ShortUrlService($app['service_domain'], new PdoRepository($pdo));
            $url = $service->expand($shortUrl);

            if (!is_object($url)) {
                throw new \Exception('Short url not found', 404);
            }

            if ($isRedisAvailable) {
                $app['cache']->store($app['service_domain'] . $shortUrl, $url->getLongUrl(), 3600); // 3600 ttl
            }

            return $app->redirect($url->getLongUrl());
        } catch(\Exception $e) {
            $response
//                ->setStatusCode($e->getCode() ?: 500)
                ->setData(array('error' => array(
                    'message' => "Can't redirect: " . $e->getMessage()
                )));
        }

        return $response;
    }
);


// main page
$app->get('', function () use ($app) {

    return 'URL Shortener (ver 1.0)';
});

//$app->match('{url}', function($url) {
//    //do legacy stuff
//
//    return 'URL Shortener (ver 1.0)';
//})->assert('url', '.+');


$app->run();