<?php

namespace Nopolabs\Yabot\Plugins\Examples;


use Exception;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Nopolabs\Yabot\Plugins\Giphy\GiphyService;
use Nopolabs\Yabot\Plugins\Rss\RssService;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class NytPlugin implements PluginInterface
{
    use PluginTrait;

    private $rssService;
    private $giphyService;

    public function __construct(
        LoggerInterface $logger,
        RssService $rssService,
        GiphyService $giphyService,
        array $config = [])
    {
        $this->setLog($logger);
        $this->rssService = $rssService;
        $this->giphyService = $giphyService;
        $this->setConfig(array_merge(
            [
                'help' => '<prefix> number-of-items',
                'prefix' => 'nyt',
                'matchers' => [
                    'nyt' => "/^(?:(?'n'\\d\\d?)|)$/",
                ],
                'url' => 'http://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml',
                'format' => 'fixed_height_downsampled',
            ],
            $config
        ));
    }

    public function nyt(Message $msg, array $matches)
    {
        $n = $matches['n'] ? $matches['n'] : 1;
        $url = $this->get('url');

        $this->rssService
            ->fetch($url)
            ->then(
                function ($rss) use ($msg, $n) {
                    $items = $rss->channel->item;
                    $n = min($n, count($items));
                    $format = $this->get('format', 'fixed_height');
                    for ($i = 0; $i < $n; $i++) {
                        $title = $items[$i]->title;
                        $this->giphyService
                            ->search($title, $format)
                            ->then(
                                function (string $gifUrl) use ($msg, $title, $i) {
                                    $msg->reply("<$gifUrl|$i: $title>");
                                },
                                function (Exception $e) {
                                    $this->getLog()->warning($e->getMessage());
                                }
                            );
                    }
                },
                function (Exception $e) {
                    $this->getLog()->warning($e->getMessage());
                }
            );

    }
}
