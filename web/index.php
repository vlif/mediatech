<?php
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpKernel\Exception\HttpException;
//use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use MiniUrl\Repository\PdoRepository;
use MiniUrl\Service\ShortUrlService;

require_once __DIR__ . '/../protected/bootstrap.php';


$app = new Vlif\Urlshortener\Application();


// Routes
$app->post('', function (Request $request) use ($app, $pdo) {
        $longUrl = $request->request->get('url');

        $response = new JsonResponse();

        try {
            if (!$longUrl) {
                throw new \Exception('Empty url.', 400);
            }

            $service = new ShortUrlService($app['service_domain'], new PdoRepository($app['pdo']));
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

        // Redis
        $isRedisAvailable = false;
        try {
            $cachedUrl = $app['cache']->fetch($app['service_domain'] . $shortUrl);
            $isRedisAvailable = true;
            if ($cachedUrl) {

                return $app->redirect($cachedUrl);
            }
        } catch(\Exception $e) {

            //
            // Here are sent the notice to the administrator that redis is sick
            //

        }

        try {
            $service = new ShortUrlService($app['service_domain'], new PdoRepository($app['pdo']));
            $url = $service->expand($shortUrl);

            if (!is_object($url)) {
                throw new \Exception('Short url not found', 404);
            }

            // Redis
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

// Main page
$app->get('', function () use ($app) {

    return 'URL Shortener (ver 1.0)';
});

//$app->match('{url}', function($url) {
//    //do legacy stuff
//
//    return 'URL Shortener (ver 1.0)';
//})->assert('url', '.+');


$app->run();
