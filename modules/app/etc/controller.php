<?php

namespace App\Etc;

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

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $database = Registry::get('database');
        $database->connect();

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });

        $categories = \App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => 0));
        
        $this->getLayoutView()
                ->set('category', $categories)
                ->set('metatile', 'Agentura Karneval')
                ->set('metakeywords', $this->loadConfigFromDb('meta_keywords'))
                ->set('metadescription', $this->loadConfigFromDb('meta_description'))
                ->set('metarobots', $this->loadConfigFromDb('meta_robots'))
                ->set('metaogurl', $this->loadConfigFromDb('meta_og_url'))
                ->set('metaogtype', $this->loadConfigFromDb('meta_og_type'))
                ->set('metaogsitename', $this->loadConfigFromDb('meta_og_site_name'))
                ->set('metaogtitle', $this->loadConfigFromDb('meta_og_title'));
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
    public function render()
    {
        parent::render();
    }

}
