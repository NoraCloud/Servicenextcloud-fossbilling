<?php

/**
 * Nextcloud module for FOSSBilling
 *
 * @copyright NoraCloud 2024 (https://www.noracloud.fr)
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   Apache-2.0
 *
 * Copyright FOSSBilling 2022
 * This software may contain code previously used in the BoxBilling project.
 * Copyright BoxBilling, Inc 2011-2021
 *
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

/**
 * Pterodactyl module Admin API.
 *
 * API can be access only by admins
 */

namespace Box\Mod\Servicenextcloud\Api;

use Box\Mod\Servicenextcloud\NextcloudAPI as NextcloudAPI;

require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Nextcloud.php');

class Admin extends \Api_Abstract
{

    /**
     * Method to list all nextcloud servers
     * @return array
     */
    public function server_list(): array
    {
        $servers = $this->di['db']->find('service_nextcloud_server');
        $result = [];
        foreach ($servers as $server) {
            $result[] = [
                'id' => $server->id,
                'name' => $server->name,
                'url' => $server->url,
                'active' => $server->active,
            ];
        }

        return $result;
    }

    /**
     * Method to get a nextcloud server
     * @param int $server_id
     * @return array
     */
    public function server_get($server_id): array
    {
        $server = $this->di['db']->load('service_nextcloud_server', $server_id);
        if (!$server) {
            throw new \FOSSBilling\NotFoundException('Server not found', [], 404);
        }

        return [
            'id' => $server->id,
            'name' => $server->name,
            'url' => $server->url,
            'active' => $server->active,
        ];
    }

    /**
     * Method to create a new nextcloud server
     * @param array $data
     * @return bool, true if the server was created successfully
     */
    public function server_create($data): bool
    {

        // Check if the required parameters are present
        $required = ['name' => 'Name', 'url' => 'Server URL'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        // Check if the server URL is valid
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new \FOSSBilling\InformationException('Invalid server URL', [], 400);
        }

        $server = $this->di['db']->dispense('service_nextcloud_server');
        $server->name = $data['name'];
        $server->url = $data['url'];
        $server->username = $data['username'];
        $server->password = $data['password'];
        $server->config = json_encode($data['config']);
        $server->created_at = date('Y-m-d H:i:s');
        $server->updated_at = $server->created_at;
        $server->active = 1;

        $this->di['db']->store($server);

        $this->di['logger']->info('Nextcloud server created', ['id' => $server->id]);

        return true;
    }

    /**
     * Method to update a nextcloud server
     * @param array $data
     * @return bool, true if the server was updated successfully
     */
    public function server_update($data): bool
    {
        $required = ['id' => 'Server ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        if (isset($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new \FOSSBilling\InformationException('Invalid server URL', [], 400);
        }

        $server = $this->di['db']->load('service_nextcloud_server', $data['id']);
        if (!$server) {
            throw new \FOSSBilling\NotFoundException('Server not found', [], 404);
        }

        $server->name = $data['name'] ?? $server->name;
        $server->url = $data['url'] ?? $server->url;
        $server->username = $data['username'] ?? $server->username;
        $server->password = $data['password'] ?? $server->password;
        $server->config = json_encode($data['config']) ?? $server->config;
        $server->active = $data['active'] ?? $server->active;
        $server->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($server);

        $this->di['logger']->info('Nextcloud server updated', ['id' => $server->id]);

        return true;

    }

    /**
     * Method to delete a nextcloud server
     * @param array $data
     * @return bool, true if the server was deleted successfully
     */
    public function server_delete($data): bool
    {
        $required = ['id' => 'Server ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $server = $this->di['db']->load('service_nextcloud_server', $data['id']);
        if (!$server) {
            throw new \FOSSBilling\NotFoundException('Server not found', [], 404);
        }

        $this->di['db']->trash($server);

        $this->di['logger']->info('Nextcloud server deleted', ['id' => $server->id]);

        return true;
    }

    /**
     * Method to test nextcloud server connection
     * @param array $data
     * @return bool, true if the connection was successful
     */
    public function server_test_connection($data): bool
    {
        $required = ['id' => 'Server ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $server = $this->di['db']->load('service_nextcloud_server', $data['id']);
        if (!$server) {
            throw new \FOSSBilling\NotFoundException('Server not found', [], 404);
        }

        $nextcloud = new NextcloudAPI($server->url, $server->username, $server->password);
        return $nextcloud->testConnection() && $nextcloud->testAuthentication();

    }

}
