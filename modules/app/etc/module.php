<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 *
 * @author Tomy
 */
class App_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'App';

    /**
     * @read
     */
    protected $_observerClass = 'App_Etc_Observer';
    protected $_routes = array(
        array(
            'pattern' => '/news',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
        ),
        array(
            'pattern' => '/o-nas',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'aboutUs',
        ),
        array(
            'pattern' => '/cenik',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'priceList',
        ),
        array(
            'pattern' => '/news/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/kategorie/:urlkey',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'category',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/kostym/:urlkey',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'product',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/galerie/:id',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'index',
            'args' => ':id'
        ),
        array(
            'pattern' => '/login',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        )
    );

}
