<?php

namespace mod_mumie\synchronization;

class payload
{
    public array $users;
    public string $course;
    public array $objectIds;
    public int $lastSync;
    public bool $includeAll;
    public context $context;

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

    public function get_encoded(): string {
        return json_encode($this);
    }

}