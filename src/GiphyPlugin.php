<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Psr\Log\LoggerInterface;

class GiphyPlugin implements PluginInterface
{
    use PluginTrait;
    use GuzzleTrait;

    public function __construct(
        LoggerInterface $logger,
        Client $guzzle,
        array $config = [])
    {
        $this->setLog($logger);
        $this->setGuzzle($guzzle);

        $this->setConfig(array_merge(
            [
                'prefix' => 'giphy',
                'channel' => 'general',
                'matchers' => [
                    'search' => '/^(.*)/',
                ],
            ],
            $config
        ));
    }

    public function search(MessageInterface $msg, array $matches)
    {
        $term = urlencode($matches[1]);
        $url = "http://api.giphy.com/v1/gifs/search\\?q\\=$term\\&api_key\\=dc6zaTOxFJmzC";
        $this->getAsync($url)->then(
            function(Response $response) use ($msg) {
                $data = GuzzleHttp\json_decode($response->getBody());
                // data[0].images.fixed_width_small.url
                $msg->reply($data[0]->images->fixed_width_small->url);
            },
            function(RequestException $e) {
                $this->getLog()->warning($e->getMessage());
            }
        );
    }
}