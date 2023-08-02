<?php

namespace mod_mumie\synchronization\context;

class object_context implements \JsonSerializable {
    private array $usercontexts;

    public function __construct() {
        $this->usercontexts = array();
    }

    public function add_user_context(string $userId,  user_context $usercontext): void {
        $this->usercontexts[$userId] = $usercontext;
    }

    public function jsonSerialize() {
        return $this->usercontexts;
    }
}
