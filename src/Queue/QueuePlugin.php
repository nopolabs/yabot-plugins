<?php

namespace Nopolabs\Yabot\Queue;

use Nopolabs\Yabot\Bot\MessageDispatcher;
use Nopolabs\Yabot\Bot\MessageInterface;
use Nopolabs\Yabot\Bot\PluginInterface;
use Nopolabs\Yabot\Bot\PluginTrait;
use Psr\Log\LoggerInterface;

class QueuePlugin implements PluginInterface
{
    use PluginTrait;

    /** @var Queue */
    protected $queue;

    public function __construct(
        LoggerInterface $logger,
        Queue $queue,
        array $config = [])
    {
        $this->setLog($logger);
        $this->queue = $queue;

        $this->setConfig(array_merge(
            [
                'channel' => 'general',
                'matchers' => [
                    'push' => "/^push\\s+#?(?'item'[0-9]{4,5})\\b/",
                    'next' => '/^next$/',
                    'remove' => "/^rm #?(?'item'[0-9]{4,5})\\b/",
                    'clear' => '/^clear$/',
                    'list' => '/^list$/',
                ],
            ],
            $config
        ));
    }

    public function push(MessageInterface $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->push($element);

        $this->list($msg);
    }

    public function next(MessageInterface $msg, array $matches)
    {
        $this->queue->next();

        $this->list($msg);
    }

    public function remove(MessageInterface $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->remove($element);

        $this->list($msg);
    }

    public function clear(MessageInterface $msg, array $matches)
    {
        $this->queue->clear();

        $this->list($msg);
    }

    public function list(MessageInterface $msg, array $matches = [])
    {
        $results = [];

        $details = $this->queue->getDetails();
        if (empty($details)) {
            $results[] = 'The queue is empty.';
        } else {
            foreach ($details as $detail) {
                $results[] = $detail;
            }
        }

        $msg->reply(implode("\n", $results));
        $msg->setHandled(true);
    }
}