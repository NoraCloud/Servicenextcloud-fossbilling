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

namespace Box\Mod\Servicenextcloud;

use FOSSBilling\{Exception,InformationException};
use Box\Mod\Servicenextcloud\NextcloudAPI;

class Service
{
    protected $di;
    protected NextcloudAPI $nextcloud_api;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    /**
     * Any module may define this function to return an array of permission keys that are related to it.
     * You may define either a `bool` or a `select` permission type.
     * Modules do not need to define this function.
     * 
     * We've included an example of how to check the permissions under the `/api/Admin.php` file and some front-end usage under `/html_admin/mod_example_index.html.twig`
     * 
     * @return array 
     */
    public function getModulePermissions(): array
    {
        return [
            'do_something' => [
                'type' => 'bool',
                'display_name' => 'Do something',
                'description' => 'Allows the staff member to do something',
            ],
            'a_select' => [
                'type' => 'select',
                'display_name' => 'A select',
                'description' => 'This is an example of the select permission type',
                'options' => [
                    'value_1' => 'Value 1',
                    'value_2' => 'Value 2',
                    'value_3' => 'Value 3',
                ]
            ],
            'manage_settings' => [], // Tells FOSSBilling that there should be a permission key to manage the module's settings (admin/extension/settings/example)
        ];
    }

    /**
     * Method to install the module. In most cases you will use this
     * to create database tables for your module.
     *
     * If your module isn't very complicated then the extension_meta
     * database table might be enough.
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function install(): bool
    {

        $sql = "
        CREATE TABLE IF NOT EXISTS `service_nextcloud_server` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            `url` varchar(255) DEFAULT NULL,
            `username` varchar(255) DEFAULT NULL,
            `password` varchar(255) DEFAULT NULL,
            `config` text,
            `active` bigint(20) DEFAULT NULL,
            `created_at` varchar(35) DEFAULT NULL,
            `updated_at` varchar(35) DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $this->di['db']->exec($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `service_nextcloud` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `client_id` bigint(20) DEFAULT NULL,
            `order_id` bigint(20) DEFAULT NULL,
            `nextcloud_server_id` bigint(20) DEFAULT NULL,
            `server_uuid` varchar(255) DEFAULT NULL,
            `hostname` varchar(255) DEFAULT NULL,
            `password` varchar(255) DEFAULT NULL,
            `config` text,
            `created_at` varchar(35) DEFAULT NULL,
            `updated_at` varchar(35) DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`nextcloud_server_id`) REFERENCES `service_nextcloud_server` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $this->di['db']->exec($sql);

        return true;
    }

    /**
     * Method to uninstall module. In most cases you will use this
     * to remove database tables for your module.
     *
     * You also can opt to keep the data in the database if you want
     * to keep the data for future use.
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function uninstall(): bool
    {
        $this->di['db']->exec('DROP TABLE IF EXISTS `service_nextcloud`');
        $this->di['db']->exec('DROP TABLE IF EXISTS `service_nextcloud_server`');
        return true;
    }

    /**
     * Method to update module. When you release new version to
     * extensions.fossbilling.org then this method will be called
     * after the new files are placed.
     *
     * @param array $manifest - information about the new module version
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function update(array $manifest): bool
    {
        throw new InformationException("Throw exception to terminate module update process with a message", array(), 125);
        return true;
    }

    /**
     * Method is used to create search query for paginated list.
     * Usually there is one paginated list per module.
     *
     * @param array $data
     *
     * @return array() = list of 2 parameters: array($sql, $params)
     */
    public function getSearchQuery(array $data): array
    {
        $params = [];
        $sql = "SELECT meta_key, meta_value
            FROM extension_meta
            WHERE extension = 'servicenextcloud'";

        $client_id = $data['client_id'] ?? null;

        if (null !== $client_id) {
            $sql .= ' AND client_id = :client_id';
            $params[':client_id'] = $client_id;
        }

        $sql .= ' ORDER BY created_at DESC';

        return [$sql, $params];
    }

    /**
     * Methods is a delegate for one database row.
     *
     * @param array $row - array representing one database row
     * @param string $role - guest|client|admin who is calling this method
     * @param bool $deep - true|false deep or light version of result to return to API
     *
     * @return array
     */
    public function toApiArray(array $row, string $role = 'guest', bool $deep = true): array
    {
        return $row;
    }

    /**
     * Method for creating a new service
     * Triggered when a new order is created
     * @param \Model_ClientOrder $order
     * @return mixed - service model
     */
    public function create(\Model_ClientOrder $order){
        $model = $this->di['db']->dispense('service_nextcloud');
        $model->client_id = $order->client_id;
        $model->order_id = $order->id;
        $model->updated_at = date('Y-m-d H:i:s');
        $model->created_at = date('Y-m-d H:i:s');

        $this->di['db']->store($model);

        return $model;

    }

    /**
        * Method for activating a service
        * Triggered when a service is activated
        * @param \Model_ClientOrder $order
        * @param \Model_Service $model - service model
     */
    public function activate(\Model_ClientOrder $order, \Model_Service $model){
        if(!is_object($model)){
            throw new Exception('Service not found. Could not activate order');
        }

        $client = $this->di['db']->load('client', $order->client_id);
        $product = $this->di['db']->load('product', $order->product_id);

        if(!$product){
            throw new Exception('Product not found. Could not activate order');
        }
        if(!$client){
            throw new Exception('Client not found. Could not activate order');
        }

        $server_data = $this->di['db']->load('service_nextcloud_server', $product->config['server_id']);



        // FAIRE LA SOUPE


    }

    /**
     * Method for renewing a service
     * Triggered when a service is renewed
     * @param \Model_ClientOrder $order
     */
    public function renew(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->setUpdatedAt(date('Y-m-d H:i:s'));
        $service->save();
    }

    /**
     * Method for suspending a service
     * Triggered when a service is suspended
     * @param \Model_ClientOrder $order
     */
    public function suspend(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->setActive(0);
        $service->setUpdatedAt(date('Y-m-d H:i:s'));
        $service->save();
    }

    /**
     * Method for unsuspending a service
     * Triggered when a service is unsuspended
     * @param \Model_ClientOrder $order
     */
    public function unsuspend(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->setActive(1);
        $service->setUpdatedAt(date('Y-m-d H:i:s'));
        $service->save();
    }

    /**
     * Method for cancelling a service
     * Triggered when a service is cancelled
     * @param \Model_ClientOrder $order
     */
    public function cancel(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->setActive(0);
        $service->setUpdatedAt(date('Y-m-d H:i:s'));
        $service->save();
    }

    /**
     * Method for uncancelled a service
     * Triggered when a service is uncancelled
     * @param \Model_ClientOrder $order
     */
    public function uncancel(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->setActive(1);
        $service->setUpdatedAt(date('Y-m-d H:i:s'));
        $service->save();
    }

    /**
     * Method for deleting a service
     * Triggered when a service is deleted
     * @param \Model_ClientOrder $order
     */
    public function delete(\Model_ClientOrder $order){
        $service = $order->getService();
        $service->delete();
    }



}

