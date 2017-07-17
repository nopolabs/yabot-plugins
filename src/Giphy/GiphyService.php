<?php

namespace Nopolabs\Yabot\Plugins\Giphy;

use GuzzleHttp;
use GuzzleHttp\Promise\PromiseInterface;
use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GiphyService
{
    use LogTrait;
    use GuzzleTrait;
    use ConfigTrait;

    public function __construct(
        LoggerInterface $logger,
        Guzzle $guzzle,
        array $config = [])
    {
        $this->setLog($logger);
        $this->setGuzzle($guzzle);
        $this->setConfig(array_merge(
            [
                'api_endpoint' => 'http://api.giphy.com/v1/gifs/search',
                'parameters' => [
                    'api_key' => 'dc6zaTOxFJmzC',
                    'limit' => 1,
                    'rating' => 'pg-13',
                ],
                'formats' => [
                    'fixed_height',
                    'fixed_height_still',
                    'fixed_height_downsampled',
                    'fixed_width',
                    'fixed_width_still',
                    'fixed_width_downsampled',
                    'fixed_height_small',
                    'fixed_height_small_still',
                    'fixed_width_small',
                    'fixed_width_small_still',
                    'preview',
                    'downsized_small',
                    'downsized',
                    'downsized_medium',
                    'downsized_large',
                    'downsized_still',
                    'original',
                    'original_still',
                    'looping',
                ],
            ],
            $config
        ));
    }

    public function getFormats()
    {
        return $this->get('formats');
    }

    public function search(string $query, string $format = 'fixed_width') : PromiseInterface
    {
        $endpoint = $this->get('api_endpoint');
        $params = $this->get('parameters');
        $params['q'] = $query;
        $url = $endpoint.'?'.http_build_query($params);

        $this->getLog()->info($url);

        return $this->getAsync($url)->then(
            function (ResponseInterface $response) use ($format) {
                return $this->extractGifUrl($format, $response);
            }
        );
    }

    private function extractGifUrl($format, ResponseInterface $response)
    {
        $data = GuzzleHttp\json_decode($response->getBody(), true)['data'];

        return $data[0]['images'][$format]['url'];
    }
}