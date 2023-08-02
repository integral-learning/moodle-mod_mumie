<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_mumie\synchronization;

use mod_mumie\synchronization\context\context;

class payload implements \JsonSerializable {
    private array $users;
    private string $course;
    private array $objectids;
    private int $lastsync;
    private bool $includeall;
    private context $context;

    /**
     * @param array  $users
     * @param string $course
     * @param array  $objectids
     * @param int    $lastsync
     * @param bool   $includeall
     */
    public function __construct(array $users, string $course, array $objectids, int $lastsync, bool $includeall)
    {
        $this->users = $users;
        $this->course = $course;
        $this->objectids = $objectids;
        $this->lastsync = $lastsync;
        $this->includeall = $includeall;
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
