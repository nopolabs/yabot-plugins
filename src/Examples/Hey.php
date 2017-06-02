<?php

namespace Nopolabs\Yabot\Plugins\Examples;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Log\LoggerInterface;

class Hey implements PluginInterface
{
    use PluginTrait;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLog($logger);

        $this->setConfig([
            'prefix' => 'hey',
            'matchers' => [
                'hey-thread' => [
                    'patterns' => ["/^thread\\s+(?'text'[^?].*)/"],
                    'method' => 'thread',
                ],
                'hey' => [
                    'patterns' => ["/^(?'text'[^?].*)/"],
                ],
            ],
        ]);
    }

    public function hey(Message $msg, array $matches)
    {
        $msg->reply('hey => '.$matches['text']);
        $msg->setHandled(true);
    }

    public function thread(Message $msg, array $matches)
    {
        $attachments = ['attachments' => json_encode([["text" => $matches['text']]])];
        $msg->thread('thread', $attachments);
        $msg->setHandled(true);
    }
}