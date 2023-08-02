<?php

namespace mod_mumie\synchronization;

use CurlHandle;
use auth_mumie\mumie_server;

class xapi_request {
    private mumie_server $server;
    private payload $payload;

    public function __construct(mumie_server $server, payload $payload)
    {
        $this->server = $server;
        $this->payload = $payload;
    }

    public function send() {
        $ch = $this->create_post_curl_request();
        $result = curl_exec($ch);

        curl_close($ch);
        return json_decode($result);
    }

    /**
     * Creates a curl post request for a given url and json payload
     *
     * @return CurlHandle curl handle for json payload
     */
    public function create_post_curl_request(): CurlHandle {
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
