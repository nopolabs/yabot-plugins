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
                'help' => '[search terms]',
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

        // Possible formats (not all guaranteed to exist).
        // fixed_height fixed_height_still fixed_height_downsampled fixed_height_small fixed_height_small_still
        // fixed_width fixed_width_still fixed_width_downsampled fixed_width_small fixed_width_small_still
        // downsized downsized_still downsized_large original original_still

        $format = $config['format'] ?? 'fixed_width';

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
                    $gif = $this->buildGifUrl($format, $response);
                    $msg->reply($gif);
                },
                function (RequestException $e) {
                    $this->getLog()->warning($e->getMessage());
                }
            );
        } else {
            $response = $promise->wait();
            $gif = $this->buildGifUrl($format, $response);
            $msg->reply($gif);
        }

        $msg->setHandled(true);
    }

    private function buildGifUrl($format, ResponseInterface $response)
    {
        $data = GuzzleHttp\json_decode($response->getBody(), true)['data'];

        return $data[0]['images'][$format]['url'];
    }
}