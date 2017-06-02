<?php

namespace Nopolabs\Yabot\Plugins\Queue;

use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugin\PluginInterface;
use Nopolabs\Yabot\Plugin\PluginTrait;
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

        $help = <<<EOS
<prefix> push #[pr]
<prefix> insert #[pr] [index]
<prefix> next
<prefix> rm #[pr]
<prefix> clear
<prefix> list
EOS;

        $this->setConfig(array_merge(
            [
                'help' => $help,
                'matchers' => [
                    'push' => "/^push\\s+#?(?'item'[0-9]{4,5})\\b/",
                    'insert' => "/^insert\\s+#?(?'item'[0-9]{4,5})(?:\\s+(?'index'\d+))?\\b/",
                    'next' => '/^next$/',
                    'remove' => "/^rm #?(?'item'[0-9]{4,5})\\b/",
                    'clear' => '/^clear$/',
                    'list' => '/^list$/',
                ],
            ],
            $config
        ));
    }

    public function push(Message $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->push($element);

        $this->list($msg);
    }

    public function insert(Message $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $index = (int) $matches['index'] ?? 0;

        $this->queue->insert($element, $index);

        $this->list($msg);
    }

    public function next(Message $msg, array $matches)
    {
        $this->queue->next();

        $this->list($msg);
    }

    public function remove(Message $msg, array $matches)
    {
        $element = $this->queue->buildElement($msg, $matches);

        $this->queue->remove($element);

        $this->list($msg);
    }

    public function clear(Message $msg, array $matches)
    {
        $this->queue->clear();

        $this->list($msg);
    }

    public function list(Message $msg, array $matches = [])
    {
        $details = $this->queue->getDetails();
        if (empty($details)) {
            $details = ['The queue is empty.'];
        }

        $msg->reply(implode("\n", $details));
        $msg->setHandled(true);
    }
}