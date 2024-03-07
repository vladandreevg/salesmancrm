<?php

/**
 * Copyright 2014 Shazam Entertainment Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this
 * file except in compliance with the License.
 *
 * You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 *
 * @author Toni Lopez <toni.lopez@shazam.com>
 */

namespace PhpSlack\Utils;

use Common\Config;
use Exception;

class RestApiClient
{
    /**
     * @const string
     */
    const BASE_URL = 'https://slack.com/api/';

    /**
     * @param string
     */
    private $token;

    /**
     * RestApiClient constructor.
     * @param $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @param string $path
     * @param array $params
     * @return array
     * @return array
     */
    public function get($path, $params = array())
    {
        $params['token'] = $this->token;

        $pairs = array();
        foreach ($params as $key => $value) {
            $pairs[] = "$key=$value";
        }

        $path .= '?' . implode('&', $pairs);

        return $this->query($path, 'GET');
    }

    /**
     * @param string $path
     * @param array $params
     * @return array
     */
    public function post($path, $params = array())
    {
        $path .= '?token=' . $this->token;

        return $this->query($path, 'POST', $params);
    }

    /**
     * @param $path
     * @param $method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    private function query($path, $method, $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        $jsonResponse = json_decode($response, true);

        if (!$jsonResponse['ok']) {
            throw new Exception($jsonResponse['error']);
        }

        return $jsonResponse;
    }
}
