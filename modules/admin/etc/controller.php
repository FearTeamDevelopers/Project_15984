<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

/**
 * Module specific controller class extending framework controller class
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $database = Registry::get('database');
        $database->connect();

        $this->_security = Registry::get('security');

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
        $lastActive = $session->get('lastActive');

        $user = $this->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        //6h inactivity till logout
        if ($lastActive > time() - 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('Byl jste odhlášen z důvodu dlouhé neaktivity');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && !$this->_security->isGranted('role_member')) {
            $view->infoMessage('Přístup odepřen! Nemáte dostatečná oprávnění!');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isMember()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_member')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && !$this->_security->isGranted('role_admin')) {
            $view->infoMessage('Přístup odepřen! Nemáte dostatečná oprávnění!');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin')) {
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

        if ($this->_security->getUser() && !$this->_security->isGranted('role_superadmin')) {
            $view->infoMessage('Přístup odepřen! Nemáte dostatečná oprávnění!');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isSuperAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * load user from security context
     */
    public function getUser()
    {
        $security = Registry::get('security');
        $user = $security->getUser();

        return $user;
    }

    /**
     * 
     */
    public function checkToken()
    {
        $session = Registry::get('session');
        //$security = Registry::get('security');
        $view = $this->getActionView();

        if (base64_decode(RequestMethods::post('tk')) !== $session->get('csrftoken')) {
            $view->errorMessage('Bezpečnostní token není validní');
            //$security->logout();
            self::redirect('/');
        }
    }

    /**
     * 
     * @return boolean
     */
    public function checkTokenAjax()
    {
        $session = Registry::get('session');

        if (base64_decode(RequestMethods::post('tk')) === $session->get('csrftoken')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $user = $this->getUser();

        if ($view) {
            $view->set('authUser', $user);
            $view->set('isMember', $this->isMember())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCsrfToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $user);
            $layoutView->set('isMember', $this->isMember())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('token', $this->_security->getCsrfToken());
        }

        parent::render();
    }

}
