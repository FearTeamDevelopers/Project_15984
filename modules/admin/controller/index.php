<?php

use Admin\Etc\Controller;

/**
 * 
 */
class Admin_Controller_Index extends Controller
{

    /**
     * @before _secured
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $latestProducts = App_Model_Product::fetchLatestProducts();
        
        $latestRefs = App_Model_Reference::all(
                array('active = ?' => true), 
                array('id', 'author', 'title'),
                array('created' => 'desc'), 5);
        
        $latestNews = App_Model_News::all(
                        array(), 
                array('id', 'active', 'title', 'shortBody', 'created'), 
                array('created' => 'desc'), 3
        );
        
        $view->set('latestproducts', $latestProducts)
                ->set('latestrefs', $latestRefs)
                ->set('latestnews', $latestNews);
    }

}
