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
 * This class holds context for multiple MUMIE Tasks required for some XAPI requests.
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context implements \JsonSerializable {
    /**
     * @var array
     */
    private array $objectcontexts;

    /**
     * Create a new instance.
     */
    public function __construct() {
        $this->objectcontexts = [];
    }

    /**
     * Add a new ObjectContext to this context.
     * @param string         $objectid
     * @param object_context $objectcontext
     * @return void
     */
    public function add_object_context(string $objectid, object_context $objectcontext): void {
        $this->objectcontexts[$objectid] = $objectcontext;
    }

    /**
     * Custom json serialization.
     * @return array
     */
    public function jsonSerialize(): array {
        return $this->objectcontexts;
    }
}
