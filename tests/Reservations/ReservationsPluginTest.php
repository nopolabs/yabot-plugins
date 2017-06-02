<?php

namespace Nopolabs\Yabot\Plugins\Tests\Reservations;

use DateTime;
use Nopolabs\Test\MockWithExpectationsTrait;
use Nopolabs\Yabot\Message\Message;
use Nopolabs\Yabot\Plugins\Reservations\ReservationsPlugin;
use Nopolabs\Yabot\Plugins\Reservations\ResourcesInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slack\User;

class ReservationsPluginTest extends TestCase
{
    use MockWithExpectationsTrait;

    /** @var ReservationsPlugin */
    private $plugin;

    private $logger;
    private $resources;
    private $message;
    private $user;
    private $differentUser;
    private $forever;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resources = $this->createMock(ResourcesInterface::class);
        $this->message = $this->createMock(Message::class);
        $this->user = $this->createMock(User::class);
        $this->differentUser = $this->createMock(User::class);
        $this->forever = new DateTime('3000-01-01');

        $this->setExpectations($this->user, [
            'getUsername' => ['invoked' => 'any', 'result' => 'alice'],
        ]);

        $this->setExpectations($this->differentUser, [
            'getUsername' => ['invoked' => 'any', 'result' => 'bob'],
        ]);

        $this->plugin = new ReservationsPlugin($this->logger, $this->resources);
    }

    public function testReserve_resourceNotFound()
    {
        preg_match("/^reserve (?'resource'\\w+)/", 'reserve dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => null]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 not found.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserve($this->message, $matches);
    }

    public function testReserve_resourceIsFree()
    {
        preg_match("/^reserve (?'resource'\\w+)/", 'reserve dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => []]],
            ['reserve', ['params' => ['dev1', $this->user, null], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Reserved dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserve($this->message, $matches);
    }

    public function testReserve_resourceIsReservedByUser()
    {
        preg_match("/^reserve (?'resource'\\w+)/", 'reserve dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'alice']]],
            ['reserve', ['params' => ['dev1', $this->user, null], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Updated dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserve($this->message, $matches);
    }

    public function testReserve_resourceIsReservedByDifferentUser()
    {
        preg_match("/^reserve (?'resource'\\w+)/", 'reserve dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'bob']]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["dev1 is reserved by bob.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserve($this->message, $matches);
    }

    public function testReserveForever_resourceNotFound()
    {
        preg_match("/^reserve (?'resource'\\w+) forever\\b/", 'reserve dev1 forever', $matches);

        $this->setAtExpectations($this->resources, [
            ['forever', ['result' => $this->forever]],
            ['getResource', ['params' => ['dev1'], 'result' => null]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 not found.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveForever($this->message, $matches);
    }

    public function testReserveForever_resourceIsFree()
    {
        preg_match("/^reserve (?'resource'\\w+) forever\\b/", 'reserve dev1 forever', $matches);

        $this->setAtExpectations($this->resources, [
            ['forever', ['result' => $this->forever]],
            ['getResource', ['params' => ['dev1'], 'result' => []]],
            ['reserve', ['params' => ['dev1', $this->user, $this->forever], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Reserved dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveForever($this->message, $matches);
    }

    public function testReserveForever_resourceIsReservedByUser()
    {
        preg_match("/^reserve (?'resource'\\w+) forever\\b/", 'reserve dev1 forever', $matches);

        $this->setAtExpectations($this->resources, [
            ['forever', ['result' => $this->forever]],
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'alice']]],
            ['reserve', ['params' => ['dev1', $this->user, $this->forever], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Updated dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveForever($this->message, $matches);
    }

    public function testReserveForever_resourceIsReservedByDifferentUser()
    {
        preg_match("/^reserve (?'resource'\\w+) forever\\b/", 'reserve dev1 forever', $matches);

        $this->setAtExpectations($this->resources, [
            ['forever', ['result' => $this->forever]],
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'bob']]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["dev1 is reserved by bob.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveForever($this->message, $matches);
    }

    public function testReserveUntil_resourceNotFound()
    {
        preg_match("/^reserve (?'resource'\\w+) until (?'until'.+)/", 'reserve dev1 until tomorrow', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => null]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 not found.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveUntil($this->message, $matches);
    }

    public function testReserveUntil_resourceIsFree()
    {
        preg_match("/^reserve (?'resource'\\w+) until (?'until'.+)/", 'reserve dev1 until tomorrow', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => []]],
            ['reserve', ['params' => ['dev1', $this->user, new DateTime('tomorrow')], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Reserved dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveUntil($this->message, $matches);
    }

    public function testReserveUntil_forever_resourceIsFree()
    {
        preg_match("/^reserve (?'resource'\\w+) until (?'until'.+)/", 'reserve dev1 until forever', $matches);

        $this->setAtExpectations($this->resources, [
            ['forever', ['result' => $this->forever]],
            ['getResource', ['params' => ['dev1'], 'result' => []]],
            ['reserve', ['params' => ['dev1', $this->user, $this->forever], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Reserved dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveUntil($this->message, $matches);
    }

    public function testReserveUntil_resourceIsReservedByUser()
    {
        preg_match("/^reserve (?'resource'\\w+) until (?'until'.+)/", 'reserve dev1 until tomorrow', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'alice']]],
            ['reserve', ['params' => ['dev1', $this->user, new DateTime('tomorrow')], 'result' => []]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["Updated dev1 for alice.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveUntil($this->message, $matches);
    }

    public function testReserveUntil_resourceIsReservedByDifferentUser()
    {
        preg_match("/^reserve (?'resource'\\w+) until (?'until'.+)/", 'reserve dev1 until tomorrow', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'bob']]],
            ['getStatus', ['params' => ['dev1'], 'result' => 'dev1-status']],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUser', ['result' => $this->user]],
            ['reply', ['params' => ["dev1 is reserved by bob.\ndev1-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->reserveUntil($this->message, $matches);
    }

    public function testRelease_resourceNotFound()
    {
        preg_match("/^release (?'resource'\\w+)/", 'release dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => null]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 not found.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->release($this->message, $matches);
    }

    public function testRelease_resourceNotReserved()
    {
        preg_match("/^release (?'resource'\\w+)/", 'release dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => []]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 is not reserved.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->release($this->message, $matches);
    }

    public function testRelease_resourceReleased()
    {
        preg_match("/^release (?'resource'\\w+)/", 'release dev1', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'chris']]],
            ['release', ['params' => ['dev1']]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['Released dev1.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->release($this->message, $matches);
    }

    public function testReleaseMine()
    {
        $plugin = $this->newPartialMockWithExpectations(ReservationsPlugin::class, [
            ['releaseReservation', ['params' => [$this->message, 'dev7'], 'result' => ['Released dev7.']]],
        ], [$this->logger, $this->resources]);

        $this->setAtExpectations($this->resources, [
            ['getAll', ['result' => ['dev1' => ['user' => 'chris'], 'dev7' =>  ['user' => 'alice']]]],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUsername', ['result' => 'alice']],
            ['reply', ['params' => ['Released dev7.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $plugin->releaseMine($this->message, []);
    }

    public function testReleaseAll()
    {
        $plugin = $this->newPartialMockWithExpectations(ReservationsPlugin::class, [
            ['releaseReservation', ['params' => [$this->message, 'dev1'], 'result' => ['Released dev1.']]],
            ['releaseReservation', ['params' => [$this->message, 'dev7'], 'result' => ['Released dev7.']]],
        ], [$this->logger, $this->resources]);

        $this->setAtExpectations($this->resources, [
            ['getKeys', ['result' => ['dev1', 'dev7']]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ["Released dev1.\nReleased dev7."]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $plugin->releaseAll($this->message, []);
    }

    public function testList()
    {
        $this->setAtExpectations($this->resources, [
            ['getAllStatuses', ['result' => ['dev1-status', 'dev2-status']]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ["dev1-status\ndev2-status"]]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->list($this->message, []);
    }

    public function testListMine()
    {
        $this->setAtExpectations($this->resources, [
            ['getAll', [
                'result' => [
                    'dev1' => ['user' => 'chris'],
                    'dev2' => ['user' => 'alice'],
                    'dev3' => ['user' => 'chris'],
                    'dev4' => ['user' => 'alice'],
                ]
            ]],
        ]);

        $this->setAtExpectations($this->message, [
            ['getUsername', ['result' => 'alice']],
            ['reply', ['params' => ['dev2,dev4']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->listMine($this->message, []);
    }

    public function testListFree()
    {
        $this->setAtExpectations($this->resources, [
            ['getAll', [
                'result' => [
                    'dev1' => [],
                    'dev2' => ['user' => 'alice'],
                    'dev3' => ['user' => 'chris'],
                    'dev4' => [],
                ]
            ]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1,dev4']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->listFree($this->message, []);
    }

    public function testIsFree_resourceNotFound()
    {
        preg_match("/^is (?'resource'\\w+) free\\b/", 'is dev1 free', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => null]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 not found.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->isFree($this->message, $matches);
    }

    public function testIsFree_resourceIsFree()
    {
        preg_match("/^is (?'resource'\\w+) free\\b/", 'is dev1 free', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => []]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 is free.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->isFree($this->message, $matches);
    }

    public function testIsFree_resourceIsReserved()
    {
        preg_match("/^is (?'resource'\\w+) free\\b/", 'is dev1 free', $matches);

        $this->setAtExpectations($this->resources, [
            ['getResource', ['params' => ['dev1'], 'result' => ['user' => 'dan']]],
        ]);

        $this->setAtExpectations($this->message, [
            ['reply', ['params' => ['dev1 is reserved by dan.']]],
            ['setHandled', ['params' => [true]]],
        ]);

        $this->plugin->isFree($this->message, $matches);
    }
}