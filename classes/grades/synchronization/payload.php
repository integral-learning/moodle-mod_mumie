<?php

namespace mod_mumie\synchronization;

use mod_mumie\synchronization\context\context;

class payload implements \JsonSerializable
{
    private array $users;
    private string $course;
    private array $objectIds;
    private int $lastSync;
    private bool $includeAll;
    private context $context;

    /**
     * @param array  $users
     * @param string $course
     * @param array  $objectIds
     * @param int    $lastSync
     * @param bool   $includeAll
     */
    public function __construct(array $users, string $course, array $objectIds, int $lastSync, bool $includeAll)
    {
        $this->users = $users;
        $this->course = $course;
        $this->objectIds = $objectIds;
        $this->lastSync = $lastSync;
        $this->includeAll = $includeAll;
    }

    public function with_context($context): payload {
        $this->context = $context;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
