<?php

namespace mod_mumie\synchronization\context;

class context implements \JsonSerializable {
    private array $objectcontexts;

    public function __construct() {
        $this->objectcontexts = array();
    }

    public function add_object_context(string $objectid, object_context $objectcontext): void {
        $this->objectcontexts[$objectid] = $objectcontext;
    }

    public function jsonSerialize() : array {
        return $this->objectcontexts;
    }
}
