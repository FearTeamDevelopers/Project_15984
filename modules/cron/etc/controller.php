<?php

namespace Cron\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class Controller extends BaseController
{

    private $_security;

    /**
     * Store server host name
     * @var type 
     * @read
     */
    protected $_serverHost;
    
    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');
        $this->_serverHost = RequestMethods::server('HTTP_HOST');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
    }
    
    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        if (time() - $session->get('lastActive') < 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _cron()
    {
        $view = $this->getActionView();

        if (null !== RequestMethods::server('HTTP_HOST')) {
            $view->warningMessage(self::ERROR_MESSAGE_6);
            $this->_willRenderActionView = false;
            self::redirect('/search/');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isCron()
    {
        if (RequestMethods::server('HTTP_HOST') === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {

        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    

    /**
     * load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
    }

    /**
     * 
     */
    public function render()
    {
        parent::render();
    }

}
