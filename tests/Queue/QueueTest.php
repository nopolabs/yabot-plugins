<?php

namespace Nopolabs\Yabot\Plugins\Tests\Queue;

use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugins\Queue\Queue;
use Nopolabs\Yabot\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $queue;
    private $msg;
    private $el1;
    private $el2;
    private $el3;

    protected function setUp()
    {
        $storage = $this->createMock(StorageInterface::class);

        $this->queue = $this->newPartialMock(Queue::class, ['save'], [$storage, []]);

        $this->msg = $this->newPartialMockWithExpectations(Message::class, [
            'getUsername' => ['invoked' => 'any', 'result' => 'alice'],
        ]);

        $this->el1 = $this->queue->buildElement($this->msg, ['item' => '1234']);
        $this->el2 = $this->queue->buildElement($this->msg, ['item' => '2345']);
        $this->el3 = $this->queue->buildElement($this->msg, ['item' => '3456']);
    }

    public function testPush()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
            ['save', ['params' => [[$this->el1, $this->el2, $this->el3]]]],
        ]);

        $this->queue->push($this->el1);
        $this->queue->push($this->el2);
        $this->queue->push($this->el3);
    }

    public function testInsert()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el2, $this->el1]]]],
            ['save', ['params' => [[$this->el2, $this->el3, $this->el1]]]],
        ]);

        $this->queue->insert($this->el1, 1);
        $this->queue->insert($this->el2, 0);
        $this->queue->insert($this->el3, 1);
    }

    public function testNext()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
            ['save', ['params' => [[$this->el1, $this->el2, $this->el3]]]],
            ['save', ['params' => [[$this->el2, $this->el3]]]],
            ['save', ['params' => [[$this->el3]]]],
            ['save', ['params' => [[]]]],
            ['save', ['params' => [[]]]],
        ]);

        $this->queue->push($this->el1);
        $this->queue->push($this->el2);
        $this->queue->push($this->el3);
        $this->queue->next();
        $this->queue->next();
        $this->queue->next();
        $this->queue->next();
    }

    public function testRemove()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
            ['save', ['params' => [[$this->el1, $this->el2, $this->el3]]]],
            ['save', ['params' => [[$this->el1, $this->el3]]]],
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[]]]],
            ['save', ['params' => [[]]]],
        ]);

        $this->queue->push($this->el1);
        $this->queue->push($this->el2);
        $this->queue->push($this->el3);
        $this->queue->remove($this->el2);
        $this->queue->remove($this->el3);
        $this->queue->remove($this->el1);
        $this->queue->remove($this->el3);
    }

    public function testClear()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
            ['save', ['params' => [[$this->el1, $this->el2, $this->el3]]]],
            ['save', ['params' => [[]]]],
            ['save', ['params' => [[]]]],
        ]);

        $this->queue->push($this->el1);
        $this->queue->push($this->el2);
        $this->queue->push($this->el3);
        $this->queue->clear();
        $this->queue->clear();
    }

    public function testGetQueue()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
        ]);

        $this->queue->push($this->el1);
        $this->assertEquals([$this->el1], $this->queue->getQueue());
        $this->queue->push($this->el2);
        $this->assertEquals([$this->el1, $this->el2], $this->queue->getQueue());
    }

    public function testGetDetails()
    {
        $this->setAtExpectations($this->queue, [
            ['save', ['params' => [[$this->el1]]]],
            ['save', ['params' => [[$this->el1, $this->el2]]]],
        ]);

        $this->queue->push($this->el1);
        $this->assertEquals(['{"user":"alice","item":"1234"}'], $this->queue->getDetails());
        $this->queue->push($this->el2);
        $this->assertEquals(['{"user":"alice","item":"1234"}', '{"user":"alice","item":"2345"}'], $this->queue->getDetails());
    }
}