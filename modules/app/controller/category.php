<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_Category extends Controller
{

    /**
     * 
     * @param type $urlKey
     */
    public function category($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');
        $cache = Registry::get('cache');

        $orderby = $session->get('catvieworderby', 'created');
        $order = $session->get('catvieworder', 'desc');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));

        $layoutView->set('parentcat', null)
                ->set('activecat', $urlKey)
                ->set('active', 99);

        if ($category === null) {
            self::redirect('/neznamakategorie');
        }

        $products = $cache->get('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_1');

        if ($products !== null) {
            $products = $products;
            $background = 1;
        } else {
            $products = App_Model_Product::fetchProductsByCategory($urlKey, 30, 1, $orderby, $order);
            $cache->set('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_1', $products);

            if ($products == null) {
                $background = null;
            } else {
                $background = 1;
            }
        }

        if ($category->parentId != 0) {
            $layoutView->set('parentcat', $category->parentId);
            $session->set('parentcat', $category->parentId);
        } else {
            $layoutView->set('parentcat', $category->getId());
        }

        $session->set('activecat', $urlKey)
                ->set('activepage', 1);

        $view->set('category', $category)
                ->set('products', $products)
                ->set('catorderby', $orderby)
                ->set('catorder', $order);

        $layoutView
                ->set('activecat', $urlKey)
                ->set('active', 99)
                ->set('background', $background)
                ->set('metatitle', $category->getMetaTitle())
                ->set('metakeywords', $category->getMetaKeywords())
                ->set('metadescription', $category->getMetaDescription());
    }

    /**
     * 
     */
    public function categoryLoadProducts()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        $session = Registry::get('session');
        $cache = Registry::get('cache');

        $urlKey = $session->get('activecat');
        $orderby = $session->get('catvieworderby', 'created');
        $order = $session->get('catvieworder', 'desc');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));

        if ($category === null) {
            self::redirect('/neznamakategorie');
        }

        $page = (int) $session->get('activepage') + 1;
        $products = $cache->get('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . $page);

        if ($products !== null) {
            $products = $products;
        } else {
            $products = App_Model_Product::fetchProductsByCategory($urlKey, 30, $page, $orderby, $order);
            $cache->set('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . $page, $products);
        }

        $session->set('activepage', $page);

        $view->set('products', $products);
    }

    /**
     * 
     */
    public function setProductOrder()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        $session = Registry::get('session');

        $referer = $view->getHttpReferer();
        $orderby = RequestMethods::post('catvieworderby');
        $order = RequestMethods::post('catvieworder');

        $session->set('catvieworderby', $orderby)
                ->set('catvieworder', $order);

        echo $referer;
    }

    /**
     * 
     */
    public function unknownCategory()
    {
        $layoutView = $this->getLayoutView();

        $layoutView->set('activecat', 'unknown')
                ->set('parentcat', 'unknown')
                ->set('active', 99);
    }

}
