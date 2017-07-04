<?php

namespace Nopolabs\Yabot\Plugins\Examples;


use Nopolabs\Yabot\Guzzle\Guzzle;
use Nopolabs\Yabot\Helpers\GuzzleTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class YesOrNoPlugin implements PluginInterface
{
    use PluginTrait;
    use GuzzleTrait;

    public function __construct(
        Guzzle $guzzle,
        LoggerInterface $logger)
    {
        $this->setGuzzle($guzzle);
        $this->setLog($logger);

        $this->setConfig([
            'matchers' => [
                'yes' => "/\\byes\\b/i",
                'no' => "/\\bno\\b/i",
                'maybe' => "/\\bmaybe\\b/i",
                'questionMark' => "/\?/i",
            ],
        ]);
    }

    public function yes(Message $msg, array $matches)
    {
        $this->respond($msg, 'yes');
    }

    public function no(Message $msg, array $matches)
    {
        $this->respond($msg, 'no');
    }

    public function maybe(Message $msg, array $matches)
    {
        $this->respond($msg, 'maybe');
    }

    public function questionMark(Message $msg, array $matches)
    {
        $this->respond($msg);
    }

    private function respond(Message $msg, $force = null)
    {
        $url = $this->url($force);
        $this->guzzle->getAsync($url)
            ->then(
                function (ResponseInterface $response) use ($msg) {
                    $json = $response->getBody();
                    $rsp = json_decode($json);

                    $msg->reply($rsp->image);
                }
            );
    }

    private function url($force = null)
    {
        $query = $force ? "?force=$force" : '';

        return "https://yesno.wtf/api/$query";
    }
}