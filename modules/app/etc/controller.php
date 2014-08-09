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

        $cache = Registry::get('cache');
        $database = Registry::get('database');
        $database->connect();

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });

        $menuCat = $cache->get('menucat');

        if (NULL !== $menuCat) {
            $categories = $menuCat;
        } else {
            $categories = \App_Model_Category::fetchAllCategories();
            $cache->set('menucat', $categories);
        }

        $metaData = $cache->get('global_meta_data');

        if (NULL !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metakeywords' => $this->loadConfigFromDb('meta_keywords'),
                'metadescription' => $this->loadConfigFromDb('meta_description'),
                'metarobots' => $this->loadConfigFromDb('meta_robots'),
                'metaogurl' => $this->loadConfigFromDb('meta_og_url'),
                'metaogtype' => $this->loadConfigFromDb('meta_og_type'),
                'metaogsitename' => $this->loadConfigFromDb('meta_og_site_name'),
                'metaogtitle' => $this->loadConfigFromDb('meta_og_title')
            );

            $cache->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('category', $categories)
                ->set('metatile', 'Agentura Karneval')
                ->set('metakeywords', $metaData['metakeywords'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogsitename', $metaData['metaogsitename'])
                ->set('metaogtitle', $metaData['metaogtitle']);
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
