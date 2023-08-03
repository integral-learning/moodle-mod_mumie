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

class xapi_request {
    private mumie_server $server;
    private payload $payload;

    public function __construct(mumie_server $server, payload $payload) {
        $this->server = $server;
        $this->payload = $payload;
    }

    public function send(): array {
        $ch = $this->create_post_curl_request();
        $result = (array) json_decode(curl_exec($ch));
        curl_close($ch);
        if ($this->has_error($result)) {
            return array();
        }
        return $result;
    }

    private function has_error($response): bool {
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
        curl_setopt($ch, CURLOPT_USERAGENT, "My User Agent Name");
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
