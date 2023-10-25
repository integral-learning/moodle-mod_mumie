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

namespace mod_mumie\synchronization\context;

/**
 * This class represents the context in which a user is working on a MUMIE Task.
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_context implements \JsonSerializable {
    /**
     * @var int
     */
    private int $deadline;

    /**
     * Create new instance.
     * @param int $deadline
     */
    public function __construct(int $deadline) {
        $this->deadline = $deadline;
    }

    /**
     * Custom JSON serializer.
     * @return array|mixed
     */
    public function jsonSerialize() {
        return get_object_vars($this);
    }
}
