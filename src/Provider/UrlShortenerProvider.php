<?php
namespace App\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
//use Silex\Api\BootableProviderInterface;

use MiniUrl\Repository\PdoRepository;
use MiniUrl\Service\ShortUrlService;

class UrlShortenerProvider implements ServiceProviderInterface //, BootableProviderInterface
{
    /**
     * Register the ShortUrlService
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['url.shortener'] = function($app) {
            $options = $app['service.options'];

            return new ShortUrlService(
                $options['domain'],
                new PdoRepository($app['pdo.connection'])
            );
        };
    }
}
