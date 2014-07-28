<?php

use Admin\Etc\Controller;

class Admin_Controller_Index extends Controller
{

    /**
     * @before _secured
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $latestProducts = App_Model_Product::fetchLatestProducts();
        
        $latestGallery = App_Model_Gallery::all(
                array('active = ?' => true), 
                array('id', 'title', 'urlKey', 'created'), 
                array('created' => 'desc'), 5);
        
        $latestNews = App_Model_Reference::all(
                array('active = ?' => true), 
                array('id', 'author', 'title'),
                array('created' => 'desc'), 5);
        
        $view->set('latestproducts', $latestProducts)
                ->set('latestgallery', $latestGallery)
                ->set('latestnews', $latestNews);
    }

}
