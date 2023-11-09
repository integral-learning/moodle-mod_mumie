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

use auth_mumie\mumie_server;

/**
 * This class represents an xapi request used to retrieve grades for MUMIE Tasks.
 *
 * @package mod_mumie
 * @copyright  2017-2023 integral-learning GmbH (https://www.integral-learning.de/)
 * @author Tobias Goltz (tobias.goltz@integral-learning.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xapi_request {
    /**
     * @var mumie_server
     */
    private mumie_server $server;
    /**
     * @var payload
     */
    private payload $payload;

    /**
     * Create a new instance.
     * @param mumie_server $server
     * @param payload      $payload
     */
    public function __construct(mumie_server $server, payload $payload) {
        $this->server = $server;
        $this->payload = $payload;
    }

    /**
     * Send the request.
     * @return array
     */
    public function send(): array {
        $ch = $this->create_post_curl_request();
        $result = (array) json_decode(curl_exec($ch));
        error_log("TEST2".print_r($ch));
        curl_close($ch);
        if ($this->has_error($result)) {
            return array();
        }
        return $result;
    }

    /**
     * Check if the request failed.
     * @param array $response
     * @return bool
     */
    private function has_error(array $response): bool {
        return array_key_exists("status", $response) && $response["status"] !== 200;
    }

    /**
     * Creates a curl post request for a given url and json payload
     *
     * @return mixed curl handle for json payload
     */
    public function create_post_curl_request() {
        $ch = curl_init($this->server->get_grade_sync_url());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($this->payload)),
                "X-API-Key: " . get_config('auth_mumie', 'mumie_api_key'),
            )
        );
        return $ch;
    }
}
