<?php

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;

/**
 * 
 */
class Cron_Etc_Observer implements SubscriberInterface
{

    /**
     * 
     * @return type
     */
    public function getSubscribedEvents()
    {
        return array(
            'cron.log' => 'cronLog'
        );
    }

    /**
     * 
     * @param array $params
     */
    public function cronLog()
    {
        $params = func_get_args();
        
        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);
            
            $paramStr = '';
            if (!empty($params)) {
                $paramStr = join(', ', $params);
            }
        } else {
            $result = 'fail';
            $paramStr = '';
        }

        $log = new Admin_Model_AdminLog(array(
            'userId' => 'cronjob',
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'params' => $paramStr
        ));

        if ($log->validate()) {
            $log->save();
        }
    }

}
