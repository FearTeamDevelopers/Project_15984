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
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, \App_Model_Category $object)
    {
        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object->getMetaImage() != '') {
            $layoutView->set('metaogimage', "http://{$this->getServerHost()}/public/images/meta_image.jpg");
        }

        $layoutView->set('metaogurl', "http://{$this->getServerHost()}/kategorie/" . $object->getUrlKey() . '/');
        $layoutView->set('metaogtype', 'article');

        return;
    }

    /**
     * Basic method show products in category
     * @param type $urlKey
     */
    public function category($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');

        $orderby = $session->get('catvieworderby', 'created');
        $order = $session->get('catvieworder', 'desc');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));

        $layoutView->set('parentcat', null)
                ->set('activecat', $urlKey)
                ->set('active', 99);

        if ($category === null) {
            self::redirect('/neznamakategorie');
        }

        $maxCatPage = $session->get('catmaxpage_' . $urlKey);

        if ($maxCatPage === null) {
            $productCount = App_Model_ProductCategory::countProductsByCategoryId($category->getId());
            $maxCatPage = ceil($productCount / 30);
            $session->set('catmaxpage_' . $urlKey, $maxCatPage);
        }

        $products = $this->getCache()->get('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_1');

        if ($products !== null) {
            $products = $products;
            $background = 1;
        } else {
            $products = App_Model_Product::fetchProductsByCategory($urlKey, 30, 1, $orderby, $order);

            if ($products == null) {
                $background = null;
            } else {
                $this->getCache()->set('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_1', $products);
                $background = 1;
            }
        }

        if ($category->parentId != 0) {
            $layoutView->set('parentcat', $category->parentId);
            $session->set('parentcat', $category->parentId);
        } else {
            $layoutView->set('parentcat', $category->getId());
            $session->set('parentcat', $category->getId());
        }

        if ((int) $maxCatPage == 1) {
            $layoutView->set('catrelnext', 0);
        } else {
            $layoutView->set('catrelnext', 2);
        }

        $canonical = 'http://' . $this->getServerHost() . '/kategorie/' . $urlKey . '/';

        $session->set('activecat', $urlKey)
                ->set('activepage', 1);

        $view->set('category', $category)
                ->set('products', $products)
                ->set('catorderby', $orderby)
                ->set('catorder', $order);

        $this->_checkMetaData($layoutView, $category);
        $layoutView->set('activecat', $urlKey)
                ->set('active', 99)
                ->set('background', $background)
                ->set('canonical', $canonical);
    }

    /**
     * Method used by ajax in category view for infinite scroll
     */
    public function categoryLoadProducts()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        $session = Registry::get('session');

        $urlKey = $session->get('activecat');
        $orderby = $session->get('catvieworderby', 'created');
        $order = $session->get('catvieworder', 'desc');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));

        if ($category === null) {
            self::redirect('/neznamakategorie');
        }

        $page = (int) $session->get('activepage') + 1;
        $products = $this->getCache()->get('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . $page);

        if ($products !== null) {
            $products = $products;
        } else {
            $products = App_Model_Product::fetchProductsByCategory($urlKey, 30, $page, $orderby, $order);

            if ($products !== null) {
                $this->getCache()->set('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . $page, $products);
            }
        }

        $session->set('activepage', $page);
        $view->set('products', $products)
                ->set('category', $category);
    }

    /**
     * 
     * @param type $urlKey
     * @param type $page
     */
    public function categoryPaged($urlKey, $page)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');

        $orderby = $session->get('catvieworderby', 'created');
        $order = $session->get('catvieworder', 'desc');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));

        $layoutView->set('parentcat', null)
                ->set('activecat', $urlKey)
                ->set('active', 99);

        if ($category === null) {
            self::redirect('/neznamakategorie');
        }

        $maxCatPage = $session->get('catmaxpage_' . $urlKey);

        if ($maxCatPage === null) {
            $productCount = App_Model_ProductCategory::countProductsByCategoryId($category->getId());
            $maxCatPage = ceil($productCount / 30);
            $session->set('catmaxpage_' . $urlKey, $maxCatPage);
        }

        $products = $this->getCache()->get('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . (int) $page);

        if ($products !== null) {
            $products = $products;
            $background = 1;
        } else {
            $products = App_Model_Product::fetchProductsByCategory($urlKey, 30, (int) $page, $orderby, $order);

            if ($products == null) {
                $background = null;
            } else {
                $this->getCache()->set('category_products_' . $urlKey . '_' . $orderby . '_' . $order . '_' . (int) $page, $products);
                $background = 1;
            }
        }

        if ($category->parentId != 0) {
            $layoutView->set('parentcat', $category->parentId);
            $session->set('parentcat', $category->parentId);
        } else {
            $layoutView->set('parentcat', $category->getId());
        }

        if ($page > 1) {
            $layoutView->set('catrelprev', $page - 1);
        } else {
            $layoutView->set('catrelprev', 0);
        }

        if ((int) $page >= (int) $maxCatPage) {
            $layoutView->set('catrelnext', 0);
        } else {
            $layoutView->set('catrelnext', $page + 1);
        }

        $canonical = 'http://' . $this->getServerHost() . '/kategorie/' . $urlKey . '/' . $page . '/';

        $session->set('activecat', $urlKey)
                ->set('activepage', (int) $page);

        $view->set('category', $category)
                ->set('products', $products)
                ->set('catorderby', $orderby)
                ->set('catorder', $order);

        $this->_checkMetaData($layoutView, $category);
        $layoutView->set('activecat', $urlKey)
                ->set('active', 99)
                ->set('background', $background)
                ->set('canonical', $canonical);
    }

    /**
     * Method called by ajax used for ordering products in category view
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

        $canonical = 'http://' . $this->getServerHost() . '/neznamakategorie';

        $layoutView->set('activecat', 'unknown')
                ->set('parentcat', 'unknown')
                ->set('active', 99)
                ->set('canonical', $canonical)
                ->set('metatitle', 'Agentura Karneval - Neznámá kategorie');
    }

}
