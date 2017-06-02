<?php

namespace Nopolabs\Yabot\Plugins\Examples;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
use Psr\Log\LoggerInterface;

class Lookup implements PluginInterface
{
    use PluginTrait;

    public function __construct(
        LoggerInterface $logger,
        array $config = [])
    {
        $this->setLog($logger);

        $this->setConfig(array_merge(
            [
                'prefix' => 'lookup',
                'matchers' => [
                    'lookupUser' => "/^<@(?'user'\\w+)>/",
                    'lookupChannel' => "/^<#(?'channel'\\w+)\\|\\w+>/",
                ],
            ],
            $config
        ));
    }

    public function lookupUser(Message $msg, array $matches)
    {
        $msg->reply('user: '.$matches['user']);
    }

    public function lookupChannel(Message $msg, array $matches)
    {
        $msg->reply('channel: '.$matches['channel']);
    }
}