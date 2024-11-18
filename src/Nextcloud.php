<?php

/**
 * Nextcloud API class handler
 *
 * @copyright NoraCloud 2024 (https://www.noracloud.fr)
 * @license   Apache-2.0
 *
 * Copyright 2024 NoraCloud
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 */

namespace Box\Mod\Servicenextcloud;
use Exception;

class NextcloudAPI {

    private string $url;
    private string $username;
    private string $password;

    public function __construct(string $url, string $username, string $password) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }

    private function _callAPI(string $method, string $request, array $data = []) {
        $curl = curl_init();
        $url = $this->url . "/ocs/v1.php/cloud" . $request;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'OCS-APIRequest: true',
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_errno($curl)) {
            $msg = curl_error($curl);
            curl_close($curl);
            throw new Exception('API call failed: ' . $msg, '500');
        }

        curl_close($curl);

        switch ($status) {
            case 200:
            case 201:
                break;
            case 401:
            case 997:
                throw new Exception('API call failed: Unauthorized', '401');
            default:
                if (strpos($response, '<html>') !== false) {
                    $response = strip_tags($response);
                }
                throw new Exception('API call failed: ' . $response, $status);
        }

        return [
            'status' => $status,
            'content' => json_decode($response, true),
        ];

    }

    /**
     * Method to test server connection
     * @return bool True if the connection is successful
     */
    public function testConnection() {
        $response = $this->_callAPI('GET', '/capabilities');

        $status = $response['content']['ocs']['meta']['statuscode'];
        $version = $response['content']['ocs']['data']['version']['string'];
        
        return $status === 100 && !empty($version);

    }

    /**
     * Method to test server connection
     * @return bool True if the connection is successful
     */
    public function testAuthentication() {
        $response = $this->_callAPI('GET', '/users');

        return $response['content']['ocs']['meta']['statuscode'] === 100;
    }


    /**
     * Method to get the eggs list
     * @return array Eggs list
     */
    /*
    public function getEggsList() {
        $nests = $this->_callAPI('GET', '/api/application/nests');
        $eggs = [];
        foreach ($nests['content']['data'] as $nest) {
            $nest_id = $nest['attributes']['id'];
            $response = $this->_callAPI('GET', '/api/application/nests/' . $nest_id . '/eggs');
            $eggs = array_merge($eggs, $response['content']['data']);
        }
        return $eggs;
    }*/

    //get node list ??
    //
    //get panel detail
    //get ndode dertail
    
    /**
     * Generate server name
     * @param string $username User username
     * @return string Server name
     */
    private function _generateServerName(string $username) {
        return $username . '-' . bin2hex(random_bytes(4));
    }

}
