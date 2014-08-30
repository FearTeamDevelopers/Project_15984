<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;

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
                'metatitle' => $this->loadConfigFromDb('meta_title'),
                'metaogurl' => $this->loadConfigFromDb('meta_og_url'),
                'metaogtype' => $this->loadConfigFromDb('meta_og_type'),
                'metaogimage' => $this->loadConfigFromDb('meta_og_image'),
                'metaogsitename' => $this->loadConfigFromDb('meta_og_site_name')
            );

            $cache->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('category', $categories)
                ->set('metatitle', $metaData['metatitle'])
                ->set('metakeywords', $metaData['metakeywords'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogimage', $metaData['metaogimage'])
                ->set('metaogsitename', $metaData['metaogsitename']);
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
        $this->getLayoutView()
                ->set('env', ENV);
        
        parent::render();
    }

}
