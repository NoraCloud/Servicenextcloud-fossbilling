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
 * This file connects FOSSBilling admin area interface and API
 * Class does not extend any other class.
 */

namespace Box\Mod\Servicenextcloud\Controller;

class Admin implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    /**
     * This method registers menu items in admin area navigation block
     * This navigation is cached in data/cache/{hash}. To see changes please
     * remove the file.
     *
     * @return array
     */
    public function fetchNavigation(): array
    {
        return [
            'subpages' => [
                [
                    'location' => 'system', // place this module in extensions group
                    'label' => __trans('Nextcloud'),
                    'index' => 1600,
                    'uri' => $this->di['url']->adminLink('servicenextcloud'),
                    'class' => '',
                ],
            ],
        ];
    }

    /**
     * Methods maps admin areas urls to corresponding methods
     * Always use your module prefix to avoid conflicts with other modules
     * in future.
     *
     * @example $app->get('/example/test',      'get_test', null, get_class($this)); // calls get_test method on this class
     * @example $app->get('/example/:id',        'get_index', array('id'=>'[0-9]+'), get_class($this));
     */
    public function register(\Box_App &$app): void
    {
        $app->get('/servicenextcloud', 'get_index', [], static::class);
        $app->get('/servicenextcloud/server/:id', 'get_server', ['id' => '[0-9]+'], static::class);
    }

     public function get_index(\Box_App $app)
    {
        // always call this method to validate if admin is logged in
        $this->di['is_admin_logged'];

        return $app->render('mod_service_nextcloud_index');
    }

    /**
     * Method to get server details
     * @param \Box_App $app
     * @param null $id
     * @return string
     */
    public function get_server(\Box_App $app, $id = null)
    {
        // always call this method to validate if admin is logged in
        $this->di['is_admin_logged'];

        $server = $this->di['db']->getExistingModelById('service_nextcloud_server', $id);

        $params = [];
        $params['server'] = $server;

        return $app->render('mod_servicenextcloud_server', $params);
    }

   }
