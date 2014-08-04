<?php

use THCFrame\Module\Module as Module;

/**
 * Description of Integration_Etc_Module
 *
 * @author Tomy
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
        
    );

}
