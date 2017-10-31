<?php
namespace Vlif\Urlshortener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Application extends \Silex\Application
{
    /**
     * Main run method.
     *
     * @param Request $request
     */
    public function run(Request $request = null)
    {
        $app = $this;

        // Please set to false in a production environment
        $app['debug'] = true;

        $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
        $app['service_domain'] = $protocol . $_SERVER['SERVER_NAME'] . '/gate/';


        // Database
        $app['pdo'] = new \PDO('mysql:host=localhost;dbname=url_shortener', 'root', '');


        // Main error handler
        $app->error(function (\Exception $e, Request $request) use ($app) {
            if ($e instanceof NotFoundHttpException) {
                return (new JsonResponse)->setData(
                    array('error' => array(
                        'message' => "The requested page could not be found ('{$request->getRequestUri()}')."
                    ),
                ));
            }

//            $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
            return (new JsonResponse)->setData(
                array('error' => array(
                    'message' => 'We are sorry, but something went terribly wrong.'
                )
            ));
        });


        // Redis
        $app->register(new \Moust\Silex\Provider\CacheServiceProvider(), array(
            'cache.options' => array(
                'driver' => 'redis'
            )
        ));


        // Json
        $app->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });


        parent::run($request);
    }
}
