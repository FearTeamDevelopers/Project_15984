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
    protected $_observerClass = '';
    protected $_routes = array(
        array(
            'pattern' => '/kontakt',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'contact',
        ),
        array(
            'pattern' => '/o-nas',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'aboutus',
        ),
        array(
            'pattern' => '/neznamykostym',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'unknownProduct',
        ),
        array(
            'pattern' => '/neznamakategorie',
            'module' => 'app',
            'controller' => 'category',
            'action' => 'unknownCategory',
        ),
        array(
            'pattern' => '/cenik',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'pricelist',
        ),
        array(
            'pattern' => '/reference',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'reference',
        ),
        array(
            'pattern' => '/hledat',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'search',
        ),
        array(
            'pattern' => '/kategorie/:urlkey/',
            'module' => 'app',
            'controller' => 'category',
            'action' => 'category',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/kostym/:urlkey/',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'product',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/kategorie/:urlkey/:page/',
            'module' => 'app',
            'controller' => 'category',
            'action' => 'categoryPaged',
            'args' => array(':urlkey', ':page')
        ),
        array(
            'pattern' => '/feed',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'feed',
        ),
        array(
            'pattern' => '/feed/',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'feed',
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        )
    );

}
