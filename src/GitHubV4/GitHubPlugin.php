<?php

namespace Nopolabs\Yabot\Plugins\GitHubV4;

use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class GitHubPlugin implements PluginInterface
{
    use PluginTrait {
        init as private traitInit;
    }
    use GuzzleTrait;

    public function __construct(
        Guzzle $guzzle,
        LoggerInterface $logger,
        array $config = [])
    {
        $this->setGuzzle($guzzle);
        $this->setLog($logger);
        $this->setConfig(array_merge(
            [
                'matchers' => [
                    'graphql' => "/\\bgraphql\\b/i",
                ],
            ],
            $config
        ));
    }

    public function init(string $pluginId, array $params)
    {
        $this->traitInit($pluginId, $params);

        $token = $this->get('graphql.token');
        $options = [
            'headers' => [
                'Authorization' => "bearer $token",
            ],
        ];

        $this->setOptions($options);
    }

    public function graphql(Message $msg, array $matches)
    {
        $url = 'https://api.github.com/graphql';

        $this->postAsync($url, ['body' => '{"query": "query { viewer { login }}"}'])
            ->then(
                function (ResponseInterface $response) use ($msg) {
                    $json = $response->getBody();
                    $msg->reply($json);
                }
            );
    }
}
