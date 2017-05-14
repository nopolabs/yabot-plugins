<?php

namespace Nopolabs\Yabot\Plugins\Reservations;

use DateTime;
use GuzzleHttp\Promise\PromiseInterface;
use Slack\User;

interface ResourcesInterface
{
    public function isResource($key);

    public function getResource($key);

    public function getAll() : array;

    public function getKeys() : array;

    public function isReserved($key) : bool;

    public function isReservedBy($key, User $user) : bool;

    public function findFreeResource();

    public function findUserResource(User $user);

    public function reserve($key, User $user, DateTime $until = null);

    public function release($key);

    public function getStatus($key);

    public function getAllStatuses() : array;

    public function getUserStatuses(User $user) : array;

    public function getStatuses(array $keys) : array;

    public function forever() : DateTime;
}