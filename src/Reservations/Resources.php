<?php

namespace Nopolabs\Yabot\Plugins\Reservations;

use DateTime;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\settle;
use Nopolabs\Yabot\Bot\SlackClient;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LoopTrait;
use Nopolabs\Yabot\Helpers\SlackTrait;
use Nopolabs\Yabot\Helpers\StorageTrait;
use Nopolabs\Yabot\Storage\StorageInterface;
use React\EventLoop\LoopInterface;
use Slack\User;

/**
 * $config = [
 *     'channel' => 'general',
 *     'storageName' => 'resources',
 *     'keys' => ['dev1','dev2'],
 *     'defaultReservation' => '+12 hours',
 * ];
 */
class Resources implements ResourcesInterface
{
    use StorageTrait;
    use LoopTrait;
    use SlackTrait;
    use ConfigTrait;

    protected $channel;
    protected $resources;

    public function __construct(
        SlackClient $slack,
        StorageInterface $storage,
        LoopInterface $eventLoop,
        array $config)
    {
        $this->setConfig($config);

        $this->channel = $this->get('channel');

        $this->setSlack($slack);

        $this->setStorage($storage);
        $this->setStorageKey($this->get('storageName', 'resources'));

        $this->setLoop($eventLoop);
        $this->addPeriodicTimer(60, [$this, 'expireResources']);

        $resources = $this->load() ?: [];
        $this->resources = [];
        foreach ($this->get('keys') as $key) {
            $resource = $resources[$key] ?? [];
            $this->resources[$key] = $resource;
        }

        $this->save($this->resources);
    }

    public function isResource($key)
    {
        return array_key_exists($key, $this->resources);
    }

    public function getResource($key)
    {
        return $this->isResource($key) ? $this->resources[$key] : null;
    }

    public function setResource($key, $resource)
    {
        $this->resources[$key] = $resource;
        $this->save($this->resources);
    }

    public function getAll() : array
    {
        return $this->resources;
    }

    public function getKeys() : array
    {
        return array_keys($this->resources);
    }

    public function isReserved($key) : bool
    {
        return !empty($this->resources[$key]);
    }

    public function findFreeResource()
    {
        foreach ($this->getKeys() as $key) {
            if (!$this->isReserved($key)) {
                return $key;
            }
        }

        return null;
    }

    public function reserve($key, User $user, DateTime $until = null)
    {
        $until = $until ?? $this->getDefaultReservationTime();

        $this->setResource($key, [
            'user' => $user->getUsername(),
            'userId' => $user->getId(),
            'until' => $until->format('Y-m-d H:i:s'),
        ]);
    }

    public function release($key)
    {
        $this->setResource($key, []);
    }

    public function getStatus($key)
    {
        return $this->getStatusAsync($key)->wait();
    }

    public function getAllStatuses() : array
    {
        $requests = [];

        foreach ($this->getKeys() as $key) {
            $requests[] = $this->getStatusAsync($key);
        }

        $statuses = [];
        /** @var array $results */
        $results = settle($requests)->wait();
        foreach ($results as $key => $result) {
            if ($result['state'] === PromiseInterface::FULFILLED) {
                $statuses[] = $result['value'];
            }
        }

        return $statuses;
    }

    public function forever() : DateTime
    {
        return new DateTime('3000-01-01');
    }

    protected function getStatusAsync($key) : PromiseInterface
    {
        $status = json_encode([$key => $this->resources[$key]]);

        return new FulfilledPromise($status);
    }

    protected function expireResources()
    {
        foreach ($this->getKeys() as $key) {
            if ($this->isExpired($key)) {
                $this->release($key);
                $this->say("released $key", $this->channel);
            }
        }
    }

    protected function isExpired($key) : bool
    {
        if ($this->isReserved($key)) {
            $until = $this->getResource($key)['until'];
            $expires = new DateTime($until);

            return $expires < new DateTime();
        }

        return false;
    }

    protected function getDefaultReservationTime() : DateTime
    {
        return new DateTime($this->get('defaultReservation', '+12 hours'));
    }
}