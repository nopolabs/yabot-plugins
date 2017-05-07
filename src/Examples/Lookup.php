<?php

namespace Nopolabs\Yabot\Plugins\Examples;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
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

    public function lookupUser(MessageInterface $msg, array $matches)
    {
        $msg->reply('user: '.$matches['user']);
    }

    public function lookupChannel(MessageInterface $msg, array $matches)
    {
        $msg->reply('channel: '.$matches['channel']);
    }
}