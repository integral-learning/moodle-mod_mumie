<?php

namespace mod_mumie\synchronization\context;

class user_context implements \JsonSerializable
{
    private int $deadline;

    /**
     * @param int $deadline
     */
    public function __construct(int $deadline)
    {
        $this->deadline = $deadline;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}