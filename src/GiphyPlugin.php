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
                'help' => '[search terms] [optional format] (giphy formats to list available)',
                'prefix' => 'giphy',
                'matchers' => [
                    'search' => '/^(.*)/',
                ],
                'api_endpoint' => 'http://api.giphy.com/v1/gifs/search',
                'parameters' => [
                    'api_key' => 'dc6zaTOxFJmzC',
                    'limit' => 1,
                    'rating' => 'pg-13',
                ],
                'formats' => [
                    'fixed_height', 'fixed_height_still', 'fixed_height_downsampled', 'fixed_height_small', 'fixed_height_small_still',
                    'fixed_width', 'fixed_width_still', 'fixed_width_downsampled', 'fixed_width_small', 'fixed_width_small_still',
                    'downsized', 'downsized_still', 'downsized_large', 'original', 'original_still',
                ],
            ],
            $config
        ));
    }

    public function search(MessageInterface $msg, array $matches)
    {
        $config = $this->getConfig();

        $formats = $config['formats'] ?? ['fixed_width'];

        if (trim($matches[1]) === 'formats') {
            $msg->reply('Available formats: '.implode(' ', $formats));
        } else {
            $terms = preg_split('/\s+/', $matches[1]);
            $search = [];
            $format = $config['format'] ?? 'fixed_width';
            foreach ($terms as $term) {
                if (in_array($term, $formats)) {
                    $format = $term;
                } else {
                    $search[] = $term;
                }
            }

            $params = $this->getConfig()['parameters'];
            $params['q'] = implode(' ', $search);
            $query = http_build_query($params);
            $endpoint = $config['api_endpoint'];
            $url = "$endpoint?$query";

            $this->getLog()->info($url);

            $promise = $this->getAsync($url);

            $promise->then(
                function (ResponseInterface $response) use ($msg, $format) {
                    $gifUrl = $this->extractGifUrl($format, $response);
                    $msg->reply($gifUrl);
                },
                function (RequestException $e) {
                    $this->getLog()->warning($e->getMessage());
                }
            );
        }

        $msg->setHandled(true);
    }

    private function extractGifUrl($format, ResponseInterface $response)
    {
        $data = GuzzleHttp\json_decode($response->getBody(), true)['data'];

        return $data[0]['images'][$format]['url'];
    }
}