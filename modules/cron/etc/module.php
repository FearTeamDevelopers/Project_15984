<?php

use THCFrame\Module\Module as Module;

/**
 *
 */
class Cron_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Cron';

    /**
     * @read
     */
    protected $_observerClass = 'Cron_Etc_Observer';
    protected $_routes = array(
        array(
            'pattern' => '/c/search',
            'module' => 'cron',
            'controller' => 'search',
            'action' => 'index',
        ),
        array(
            'pattern' => '/c/price',
            'module' => 'cron',
            'controller' => 'price',
            'action' => 'calculateProductPrice',
        ),
        array(
            'pattern' => '/c/sitemap',
            'module' => 'cron',
            'controller' => 'backup',
            'action' => 'createSitemap',
        ),
        array(
            'pattern' => '/c/contentcheck',
            'module' => 'cron',
            'controller' => 'ContentCheck',
            'action' => 'checkSellableCategory',
        )
    );

}
