<?php

use App\Etc\Controller;

/**
 * 
 */
class App_Controller_Index extends Controller
{

    /**
     *
     * @param \App_Model_PageContent $news
     */
    private function _parseContentBody(\App_Model_PageContent $content, $parsedField = 'body')
    {
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
    public function index()
    {
        
    }

    /**
     * 
     */
    public function aboutUs()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function contact()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function priceList()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function reference()
    {
        $view = $this->getActionView();
        $reference = App_Model_Reference::all(array('active = ?' => true));
        $view->set('reference', $reference);
    }
    
    /**
     * 
     * @param type $urlKey
     */
    public function category($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        
        $category = App_Model_Category::first(array('active = ?' => true, 'urlKey = ?' => $urlKey));
        
        if($category !== null && $category->getParentId() == 0){
            $subcats = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => $category->getId()));
            $view->set('category', $category)
                ->set('subcategory', $subcats);
        }
        
        $products = App_Model_Product::fetchProductsByCategory($urlKey);
        
        $view->set('products', $products);
        $layoutView->set('metatitle', $category->getMetaTitle())
                ->set('metakeywords', $category->getMetaKeywords())
                ->set('metadescription', $category->getMetaDescription());
    }

    /**
     * 
     * @param type $urlKey
     */
    public function product($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $product = App_Model_Product::fetchProductByUrlKey($urlKey);

        if ($product === null) {
            $view->warningMessage('Kostým nebyl nalezen');
            self::redirect('/');
        }

        $view->set('product', $product);
        $layoutView->set('metatitle', $product->getMetaTitle())
                ->set('metakeywords', $product->getMetaKeywords())
                ->set('metadescription', $product->getMetaDescription());
    }
    
    /**
     * 
     */
    public function search()
    {
        $view = $this->getActionView();
        
    }

    /**
     * 
     */
    public function feed()
    {
        
    }

}
