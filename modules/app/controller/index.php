<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_Index extends Controller {

    /**
     *
     * @param \App_Model_PageContent $news
     */
    private function _parseContentBody(\App_Model_PageContent $content, $parsedField = 'body') {
        preg_match_all('/\(\!(photo|read)_[0-9a-z]+\!\)/', $content->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);
            list($type, $id) = explode('_', $match);

            $body = $content->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgMain', 'imgThumb')
                );

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" height=\"250px\" alt=\"Karneval\"/></a>";

                $body = str_replace("(!photo_{$id}!)", $tag, $body);

                $content->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"#\" class=\"ajaxLink news-read-more\" id=\"show_news-detail_{$content->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $content->$parsedField = $body;
            }
        }

        return $content;
    }

    /**
     * 
     */
    public function index() {
        $layoutView = $this->getLayoutView();
        $layoutView->set('active', 0);
    }

    /**
     * 
     */
    public function aboutUs() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', 1)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function reference() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $reference = App_Model_Reference::all(
                        array('active = ?' => true), array('*'), array('created' => 'desc'), 30);

        $view->set('reference', $reference);
        $layoutView->set('active', 2);
    }

    /**
     * 
     */
    public function priceList() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', 3)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function contact() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', 4)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     * @param type $urlKey
     */
    public function category($urlKey) {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));
        $products = App_Model_Product::fetchProductsByCategory($urlKey);

      

        if ($category->parentId != 0) {
            $layoutView->set('parentcat', $category->parentId);
            $session->set('parentcat', $category->parentId);
        }

        $session->set('activecat', $urlKey);
        $view->set('category', $category)
                ->set('products', $products);
        $layoutView
                ->set('activecat', $urlKey)
                ->set('metatitle', $category->getMetaTitle())
                ->set('metakeywords', $category->getMetaKeywords())
                ->set('metadescription', $category->getMetaDescription());
    }

    /**
     * 
     * @param type $urlKey
     */
    public function product($urlKey) {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');

        $product = App_Model_Product::fetchProductByUrlKey($urlKey);
        $productCategory = App_Model_Category::fetchCategoryByProductUrlKey($urlKey);

        $isSelable = false;
        foreach ($productCategory as $cat) {
            if ($cat->isSelable) {
                $isSelable = true;
            }
        }

        $fblike = urlencode('http://' . RequestMethods::server('HTTP_HOST') . '/kostym/' . $product->getUrlKey() . '/');



        if ($product === null) {
            $view->warningMessage('Kostým nebyl nalezen');
            self::redirect('/');
        }
        
        $activeCat = $session->get('activecat');
        $parentCat = $session->get('parentcat');
        
        if($parentCat != null){
            $layoutView->set('parentcat', $parentCat);
        }

        $view->set('product', $product)
                ->set('selable', $isSelable)
                ->set('fblike', $fblike);
        
        $layoutView->set('activecat', $activeCat)
                ->set('metatitle', $product->getMetaTitle())
                ->set('metakeywords', $product->getMetaKeywords())
                ->set('metadescription', $product->getMetaDescription());
    }

    /**
     * 
     */
    public function search() {
        $view = $this->getActionView();

        if (RequestMethods::issetpost('submitsearch')) {
            $query = RequestMethods::post('searchquery');

            $productWhereCond = "pr.deleted = 0 AND pr.variantFor = 0 AND pr.active = 1 "
                    . "AND (pr.productCode='?' OR pr.metaTitle LIKE '%%?%%' "
                    . "OR pr.metaKeywords LIKE '%%?%%' OR pr.title LIKE '%%?%%')";

            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.urlKey', 'pr.productCode',
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb'))
                    ->wheresql($productWhereCond, $query, $query, $query, $query)
                    ->order('pr.created', 'DESC')
                    ->limit(50);
            $products = App_Model_Product::initialize($productQuery);

            $catWhereCond = "ct.active = 1 "
                    . "AND (ct.metaTitle LIKE '%%?%%' "
                    . "OR ct.metaKeywords LIKE '%%?%%' OR ct.title LIKE '%%?%%')";

            $categoryQuery = App_Model_Category::getQuery(array('ct.id', 'ct.urlKey', 'ct.title'))
                    ->wheresql($catWhereCond, $query, $query, $query)
                    ->order('ct.rank', 'asc')
                    ->limit(10);
            $categories = App_Model_Category::initialize($categoryQuery);

            $view->set('products', $products)
                    ->set('categories', $categories);
        }
    }

    /**
     * 
     */
    public function feed() {
        
    }

}
