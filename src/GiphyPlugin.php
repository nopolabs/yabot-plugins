<?php

namespace Nopolabs\Yabot\Plugins;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GiphyPlugin implements PluginInterface
{
    use PluginTrait;
    use GuzzleTrait;

    public function __construct(
        LoggerInterface $logger,
        Guzzle $guzzle,
        array $config = [])
    {
        $this->setLog($logger);
        $this->setGuzzle($guzzle);

        $this->setConfig(array_merge(
            [
                'prefix' => 'giphy',
                'matchers' => [
                    'search' => '/^(.*)/',
                ],
                'async' => true,
                'api_endpoint' => 'http://api.giphy.com/v1/gifs/search',
                'parameters' => [
                    'api_key' => 'dc6zaTOxFJmzC',
                    'limit' => 1,
                    'rating' => 'pg-13',
                ]
            ],
            $config
        ));
    }

    public function search(MessageInterface $msg, array $matches)
    {
        $config = $this->getConfig();

        $format = $config['format'] ?? 'fixed_width_small';

        $params = $this->getConfig()['parameters'];
        $params['q'] = $matches[1];
        $query = http_build_query($params);
        $endpoint = $config['api_endpoint'];
        $url = "$endpoint?$query";

        $this->getLog()->info($url);

        $promise = $this->getAsync($url);

        if ($config['async'] ?? false) {
            $promise->then(
                function (ResponseInterface $response) use ($msg, $format) {
                    $this->reply($msg, $format, $response);
                },
                function (RequestException $e) {
                    $this->getLog()->warning($e->getMessage());
                }
            );
        } else {
            $response = $promise->wait();
            $this->reply($msg, $format, $response);
        }

        $msg->setHandled(true);
    }

    private function reply(MessageInterface $msg, $format, ResponseInterface $response)
    {
        $data = GuzzleHttp\json_decode($response->getBody(), true)['data'];
        $msg->reply($data[0]['images'][$format]['url']);
    }
}