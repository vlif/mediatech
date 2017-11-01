<?php
use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

/*
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});*/

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

// json
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

// shorten
$app->post('', function (Request $request) use ($app) {
    $longUrl = $request->request->get('long_url');

    $response = new JsonResponse();

    try {
        if (!$longUrl) {
            throw new \Exception('Empty url.', 400);
        }

        $url = $app['url.shortener']->shorten($longUrl);

        $response->setData(array('short_url' => $url->getShortUrl()));
    } catch(\Exception $e) {
        $response
//            ->setStatusCode($e->getCode() ?: 500)
            ->setData(array('error' => array(
                'message' => "Can't shorten: " . $e->getMessage(),
            )));

        return $response;
    }

    return $response;
});

// gate
$app->get('/gate/{shortUrl}', function ($shortUrl) use ($app) {
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

        //
        // Here are sent the notice to the administrator that redis is sick
        //

    }

    try {
        $url = $app['url.shortener']->expand($shortUrl);
        if (!is_object($url)) {
            throw new \Exception('Short url not found', 404);
        }

        // redis
        if ($isRedisAvailable) {
            $app['cache']->store($app['service_domain'] . $shortUrl, $url->getLongUrl(), 3600); // 3600 ttl
        }

        return $app->redirect($url->getLongUrl());
    } catch(\Exception $e) {
        $response
//            ->setStatusCode($e->getCode() ?: 500)
            ->setData(array('error' => array(
                'message' => "Can't redirect: " . $e->getMessage()
            )));
    }

    return $response;
});

// main page
$app->get('', function () use ($app) {

    return 'URL Shortener (ver 2.0)';
});

//$app->match('{url}', function($url) {
//    //do legacy stuff
//
//    return 'URL Shortener (ver 1.0)';
//})->assert('url', '.+');
