<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
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
     * Store initialized cache object
     * @var type 
     * @read
     */
    protected $_cache;
    
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

        $this->_serverHost = RequestMethods::server('HTTP_HOST');
        $this->_cache = Registry::get('cache');
        $cfg = Registry::get('configuration');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });

        $menuCat = $this->getCache()->get('menucat');

        if (NULL !== $menuCat) {
            $categories = $menuCat;
        } else {
            $categories = \App_Model_Category::fetchAllCategories();
            $this->getCache()->set('menucat', $categories);
        }

        $metaData = $this->getCache()->get('global_meta_data');

        if (NULL !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metadescription' => $cfg->meta_description,
                'metarobots' => $cfg->meta_robots,
                'metatitle' => $cfg->meta_title,
                'metaogurl' => $cfg->meta_og_url,
                'metaogtype' => $cfg->meta_og_type,
                'metaogimage' => $cfg->meta_og_image,
                'metaogsitename' => $cfg->meta_og_site_name
            );

            $this->getCache()->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('category', $categories)
                ->set('metatitle', $metaData['metatitle'])
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
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('env', ENV);
        }

        if ($layoutView) {
            $layoutView->set('env', ENV);
        }

        parent::render();
    }

}
