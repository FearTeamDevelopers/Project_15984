<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;

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

                $tag = "<a href=\"{$photo->imgMain}\" class=\"highslide\" title=\"{$photo->photoName}\""
                        . " onclick=\"return hs.expand(this, confignews)\">"
                        . "<img src=\"{$photo->imgThumb}\" alt=\"Marko.in\"/></a>";

                $body = str_replace("(!photo_{$id}!)", $tag, $body);

                $content->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"#\" class=\"ajaxLink news-read-more\" id=\"show_news-detail_{$content->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $content->$parsedField = $body;
            }
        }

        //$news->fbLikeUrl = urlencode('http://'.RequestMethods::server('HTTP_HOST').'/news/detail/'.$content->getUrlKey());

        return $content;
    }

    /**
     * 
     */
    public function index() {
        
    }

    /**
     * 
     */
    public function aboutUs() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
        $parsed = $this->_parseContentBody($content);
        $active = 0;
        if (isset($content)) {
            $active = 1;
        }
        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', $active)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function contact() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
        $parsed = $this->_parseContentBody($content);
        $active = 0;
        if (isset($content)) {
            $active = 4;
        }
        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', $active)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function priceList() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
        $parsed = $this->_parseContentBody($content);
        $active = 0;
        if (isset($content)) {
            $active = 3;
        }
        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('active', $active)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function reference() {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $reference = App_Model_Reference::all(array('active = ?' => true));
        $view->set('reference', $reference);
        $active = 0;
        if(isset($reference)){
            $active=2;
        }
         $layoutView->set('active', $active);
    }

    /**
     * 
     * @param type $urlKey
     */
    public function category($urlKey) {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));
        $products = App_Model_Product::fetchProductsByCategory($urlKey);

        if ($category->parentId != 0) {

            $layoutView
                    ->set('parentcat', $category->parentId);
        }

        $view->set('products', $products);
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

        $product = App_Model_Product::fetchProductByUrlKey($urlKey);
        $productCategory = App_Model_Category::fetchCategoryByProductUrlKey($urlKey);
        
        $fblike =urlencode('http://'.RequestMethods::server('HTTP_HOST').'/kostym/'.$product->getUrlKey().'/');

        $isSelable = false;
        
        foreach ($productCategory as $cat){
            if($cat->isSelable){
                $isSelable = true;
            }
        }

        if ($product === null) {
            $view->warningMessage('Kostým nebyl nalezen');
            self::redirect('/');
        }

        $view->set('product', $product)
                ->set('selable', $isSelable)
                ->set('fblike', $fblike);
        $layoutView->set('metatitle', $product->getMetaTitle())
                ->set('metakeywords', $product->getMetaKeywords())
                ->set('metadescription', $product->getMetaDescription());
    }

    /**
     * 
     */
    public function search() {
        $view = $this->getActionView();
    }

    /**
     * 
     */
    public function feed() {
        
    }

}
