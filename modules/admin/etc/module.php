<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 *
 * @author Tomy
 */
class Admin_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Admin';

    /**
     * @read
     */
    protected $_observerClass = 'Admin_Etc_Observer';
    protected $_routes = array(
        array(
            'pattern' => '/login',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'login',
        ),
        array(
            'pattern' => '/logout',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'logout',
        ),
        array(
            'pattern' => '/admin/product/deleterecommended/:productId/:recommendedId',
            'module' => 'admin',
            'controller' => 'product',
            'action' => 'deleteRecommended',
            'args' => array(':productId', ':recommendedId')
        )
    );

}
