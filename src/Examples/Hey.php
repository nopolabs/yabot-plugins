<?php

namespace Nopolabs\Yabot\Examples;

use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class Hey implements PluginInterface
{
    use PluginTrait;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLog($logger);

        $this->setConfig([
            'matchers' => [
                'hey' => [
                    'pattern' => "/^(?'hey'hey)\\b/",
                    'channel' => 'general',
                    'user' => 'dan',
                    'method' => 'hey',
                ],
                'thread' => [
                    'pattern' => "/^(?'thread'thread)\\b/",
                ],
            ],
        ]);
    }

    public function hey(MessageInterface $msg, array $matches)
    {
        $msg->reply('hey');
    }

    public function thread(MessageInterface $msg, array $matches)
    {
        $attachments = ['attachments' => json_encode([["text" => "Choose a game to play"]])];
        $msg->thread('thread', $attachments);
    }
}