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
 * This class represents a single MUMIE Tasks context required for some XAPI requests.
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class object_context implements \JsonSerializable {
    /**
     * @var array
     */
    private array $usercontexts;

    /**
     * @var string
     */
    private string $language;

    /**
     * Create a new instance.
     * @param string       $lang
     */
    public function __construct($lang) {
        $this->usercontexts = array();
        $this->language = $lang;
    }

    /**
     * Add a new context for a given user.
     * @param string       $userid
     * @param user_context $usercontext
     * @return void
     */
    public function add_user_context(string $userid,  user_context $usercontext): void {
        $this->usercontexts[$userid] = $usercontext;
    }

    /**
     * Custom JSON serializer.
     * @return array
     */
    public function jsonSerialize(): array {
        return ['language' => $this->language, 'userContexts' => $this->usercontexts];
    }
}
