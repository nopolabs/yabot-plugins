<?php

namespace Nopolabs\Yabot\Plugins\Rss;

use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Log\LoggerInterface;

class RssPlugin implements PluginInterface
{
    use PluginTrait;
    use GuzzleTrait;

    private $rssService;

    public function __construct(
        LoggerInterface $logger,
        RssService $rssService,
        array $config = [])
    {
        $this->setLog($logger);
        $this->rssService = $rssService;
        $this->setConfig(array_merge(
            [
                'help' => '<prefix> [raw|xml|json|object]? url',
                'prefix' => 'rss',
                'matchers' => [
                    'rss' => "/^(?:(?'format'raw|xml|json|object)\\s+)?(?'url'http[^\\s]+\\.xml)/",
                ],
            ],
            $config
        ));
    }

    public function rss(Message $msg, array $matches)
    {
        $url = $matches['url'];

        $this->rssService->fetchJson($url)->then(
            function (string $json) use ($msg) {
                $msg->reply($json);
            }
        );
    }
}