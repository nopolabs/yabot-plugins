<?php

namespace Nopolabs\Yabot\Plugins\Tests\Reservations;


use DateTime;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Slack\Client;
use Nopolabs\Yabot\Plugins\Reservations\Resources;
use Nopolabs\Yabot\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Slack\User;

class ResourcesTest extends TestCase
{
    use MockWithExpectationsTrait;

    private $slackClient;
    private $storage;
    private $eventLoop;
    private $logger;
    private $user1;
    private $user2;
    private $userX;

    /** @var Resources */
    private $resources;

    protected function setUp()
    {
        $this->slackClient = $this->createMock(Client::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->eventLoop = $this->createMock(LoopInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user1 = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'U00000001'],
            'getUsername' => ['invoked' => 'any', 'result' => 'user1'],
        ]);
        $this->user2 = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'U00000002'],
            'getUsername' => ['invoked' => 'any', 'result' => 'user2'],
        ]);
        $this->userX = $this->newPartialMockWithExpectations(User::class, [
            'getId' => ['invoked' => 'any', 'result' => 'U0000000X'],
            'getUsername' => ['invoked' => 'any', 'result' => 'userX'],
        ]);

        $this->resources = new Resources(
            $this->slackClient,
            $this->storage,
            $this->eventLoop,
            $this->logger,
            [
                'keys' => ['dev1', 'dev2', 'dev3'],
            ]
        );

        $this->resources->reserve('dev2', $this->user1, new DateTime('2031-01-01'));
        $this->resources->reserve('dev3', $this->user2, new DateTime('2032-02-02'));
    }

    public function testIsResource()
    {
        $this->assertTrue($this->resources->isResource('dev1'));
        $this->assertTrue($this->resources->isResource('dev2'));
        $this->assertTrue($this->resources->isResource('dev3'));
        $this->assertFalse($this->resources->isResource('dev4'));
    }

    public function testGetResource()
    {
        $this->assertSame([], $this->resources->getResource('dev1'));
        $this->assertSame([
            'user' => 'user1',
            'userId' => 'U00000001',
            'until' => '2031-01-01 00:00:00',
        ], $this->resources->getResource('dev2'));
        $this->assertSame([
            'user' => 'user2',
            'userId' => 'U00000002',
            'until' => '2032-02-02 00:00:00',
        ], $this->resources->getResource('dev3'));
        $this->assertNull($this->resources->getResource('dev4'));
    }

    public function testGetAll()
    {
        $this->assertSame([
            'dev1' => [],
            'dev2' => [
                'user' => 'user1',
                'userId' => 'U00000001',
                'until' => '2031-01-01 00:00:00',
            ],
            'dev3' => [
                'user' => 'user2',
                'userId' => 'U00000002',
                'until' => '2032-02-02 00:00:00',
            ],
        ], $this->resources->getAll());
    }

    public function testGetKeys()
    {
        $this->assertSame(['dev1','dev2','dev3'], $this->resources->getKeys());
    }

    public function testIsReserved()
    {
        $this->assertFalse($this->resources->isReserved('dev1'));
        $this->assertTrue($this->resources->isReserved('dev2'));
        $this->assertTrue($this->resources->isReserved('dev3'));
        $this->assertFalse($this->resources->isReserved('dev4'));
    }

    public function testIsReservedBy()
    {
        $this->assertFalse($this->resources->isReservedBy('dev1', $this->user1));
        $this->assertTrue($this->resources->isReservedBy('dev2', $this->user1));
        $this->assertFalse($this->resources->isReservedBy('dev3', $this->user1));
        $this->assertFalse($this->resources->isReservedBy('dev4', $this->user1));

        $this->assertFalse($this->resources->isReservedBy('dev1', $this->user2));
        $this->assertFalse($this->resources->isReservedBy('dev2', $this->user2));
        $this->assertTrue($this->resources->isReservedBy('dev3', $this->user2));
        $this->assertFalse($this->resources->isReservedBy('dev4', $this->user2));
    }

    public function testFindFreeResource()
    {
        $this->assertSame('dev1', $this->resources->findFreeResource());
    }

    public function testFindUserResource()
    {
        $this->assertSame('dev2', $this->resources->findUserResource($this->user1));
        $this->assertSame('dev3', $this->resources->findUserResource($this->user2));
        $this->assertNull($this->resources->findUserResource($this->userX));
    }

    public function testReserve()
    {
        $this->resources->reserve('dev1', $this->userX);

        $resource = $this->resources->getResource('dev1');

        $this->assertSame('userX', $resource['user']);
        $this->assertSame('U0000000X', $resource['userId']);
        $this->assertEquals(new DateTime('+12 hours'), new DateTime($resource['until']), 'default reservation time', 10);
    }

    public function testRelease()
    {
        $this->resources->release('dev3');

        $this->assertSame([
            'dev1' => [],
            'dev2' => [
                'user' => 'user1',
                'userId' => 'U00000001',
                'until' => '2031-01-01 00:00:00',
            ],
            'dev3' => [],
        ], $this->resources->getAll());

        $this->resources->release('dev2');

        $this->assertSame([
            'dev1' => [],
            'dev2' => [],
            'dev3' => [],
        ], $this->resources->getAll());
    }
}