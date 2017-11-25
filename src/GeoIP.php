<?php

namespace Jeremytubbs\FreegeoipClient;

use GuzzleHttp\{Client, HandlerStack};

use Illuminate\Support\Facades\Cache;

use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

class GeoIP
{
    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config;
        $this->client = $this->setClient();
    }

    public function setClient()
    {
        $cacheDriver = isset($this->config['cacheDriver']) ? $this->config['cacheDriver'] : config('cache.default');
        $TTL = isset($this->config['TTL']) ? $this->config['TTL'] : 82800;

        $stack = HandlerStack::create();

        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    new LaravelCacheStorage(
                        Cache::store($cacheDriver)
                    ),
                    $TTL
                )
            ),
            'cache'
        );

        return new Client([
            'handler' => $stack,
            'base_uri' => 'https://freegeoip.net',
        ]);
    }

    public function getLocation($ip)
    {
        $format = $this->config['format'] ? $this->config['format'] : 'json';

        $response = $this->client->request('GET', $format.'/'.$ip);

        return $this->handle($response);
    }

    public function handle($response)
    {
        $body = $response->getBody();
        return $body->getContents();
    }
}
