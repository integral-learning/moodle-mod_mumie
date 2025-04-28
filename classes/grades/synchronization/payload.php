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

/**
 * This class represents the payload used in XAPI requests for MUMIE Task grade synchronization
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payload implements \JsonSerializable {
    /**
     * @var array
     */
    private array $users;
    /**
     * @var string
     */
    private string $course;
    /**
     * @var array
     */
    private array $objectids;
    /**
     * @var int
     */
    private int $lastsync;
    /**
     * @var bool
     */
    private bool $includeall;
    /**
     * @var context
     */
    private context $context;

    /**
     * Create a new instance.
     * @param array  $users
     * @param string $course
     * @param array  $objectids
     * @param int    $lastsync
     * @param bool   $includeall
     */
    public function __construct(array $users, string $course, array $objectids, int $lastsync, bool $includeall) {
        $this->users = $users;
        $this->course = $course;
        $this->objectids = $objectids;
        $this->lastsync = $lastsync;
        $this->includeall = $includeall;
    }

    /**
     * Add context to the payload.
     * @param context $context
     * @return $this
     */
    public function with_context(context $context): payload {
        $this->context = $context;
        return $this;
    }

    /**
     * Custom JSON serializer.
     * @return array
     */
    public function jsonSerialize(): array {
        $json = [
            "users" => $this->users,
            "course" => $this->course,
            "objectIds" => $this->objectids,
            "lastSync" => $this->lastsync,
            "includeAll" => $this->includeall,
        ];
        if (isset($this->context)) {
            $json["context"] = $this->context;
        }
        return $json;
    }
}
