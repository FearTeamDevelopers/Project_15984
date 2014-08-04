<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

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

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" height=\"250px\" alt=\"Karneval\"/></a>";

                $body = str_replace("(!photo_{$id}!)", $tag, $body);

                $content->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"#\" class=\"ajaxLink news-read-more\" "
                        . "id=\"show_news-detail_{$content->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $content->$parsedField = $body;
            }
        }

        return $content;
    }

    /**
     * 
     */
    public function index()
    {
        $layoutView = $this->getLayoutView();
        
        $layoutView->set('active', 99)
            ->set('activecat', null)
            ->set('parentcat', null);
    }

    /**
     * 
     */
    public function aboutUs()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('aboutus');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
            $cache->set('aboutus', $content);
        }
        
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 1)
                
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

    /**
     * 
     */
    public function reference()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('reference');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_Reference::all(
                        array('active = ?' => true), 
                        array('*'), 
                        array('created' => 'desc'), 30);
            
            $cache->set('reference', $content);
        }
        
        $view->set('reference', $content);
        $layoutView->set('active', 2)
            ->set('activecat', null)
                ->set('parentcat', null);
    }

    /**
     * 
     */
    public function priceList()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('pricelist');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
            $cache->set('pricelist', $content);
        }
        
        $parsed = $this->_parseContentBody($content);

        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 3)
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
        $cache = Registry::get('cache');

        $content = $cache->get('contact');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
            $cache->set('contact', $content);
        }
        
        $parsed = $this->_parseContentBody($content);
        
        $view->set('content', $parsed);
        $layoutView->set('metatitle', $content->getMetaTitle())
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 4)
                ->set('metakeywords', $content->getMetaKeywords())
                ->set('metadescription', $content->getMetaDescription());
    }

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

        $products = $cache->get('category_products_'.$urlKey);
        
        if($products !== null){
            $products = $products;
        }else{
            $products = App_Model_Product::fetchProductsByCategory($urlKey, $orderby, $order);
            $cache->set('category_products_'.$urlKey, $products);
        }

        if ($category->parentId != 0) {
            $layoutView->set('parentcat', $category->parentId);
            $session->set('parentcat', $category->parentId);
        }else{
            $layoutView->set('parentcat', $category->getId());
        }

        $session->set('activecat', $urlKey);
        
        $view->set('category', $category)
                ->set('products', $products)
                ->set('catorderby', $orderby)
                ->set('catorder', $order);
        
        $layoutView
                ->set('activecat', $urlKey)
                ->set('active', 99)
                ->set('background', 1)
                ->set('metatitle', $category->getMetaTitle())
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
        $session = Registry::get('session');

        $product = App_Model_Product::fetchProductByUrlKey($urlKey);

        $view->set('product', $product);
        $layoutView->set('parentcat', null)
                ->set('activecat', null)
                ->set('active', 99);
        
        if ($product === null) {
            self::redirect('/neznamykostym');
        }

        $productCategory = App_Model_Category::fetchCategoryByProductUrlKey($urlKey);

        $isSelable = false;
        foreach ($productCategory as $cat) {
            if ($cat->isSelable) {
                $isSelable = true;
            }
        }

        $fblike = urlencode('http://' . RequestMethods::server('HTTP_HOST') . '/kostym/' . $product->getUrlKey() . '/');

        $activeCat = $session->get('activecat', 'unknown');
        $parentCat = $session->get('parentcat', 'unknown');

        $view->set('product', $product)
                ->set('selable', $isSelable)
                ->set('fblike', $fblike);

        $layoutView->set('activecat', $activeCat)
                ->set('parentcat', $parentCat)
                ->set('active', 99)
                ->set('metatitle', $product->getMetaTitle())
                ->set('metakeywords', $product->getMetaKeywords())
                ->set('metadescription', $product->getMetaDescription());
    }

    /**
     * 
     */
    public function unknownProduct()
    {
        $layoutView = $this->getLayoutView();
        $session = Registry::get('session');

        $activeCat = $session->get('activecat', 'unknown');
        $parentCat = $session->get('parentcat', 'unknown');

        $layoutView->set('activecat', $activeCat)
                ->set('parentcat', $parentCat)
                ->set('active', 99);
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

    /**
     * 
     */
    public function search()
    {
        $layoutView = $this->getLayoutView();
        $view = $this->getActionView();

        if (RequestMethods::issetpost('submitsearch')) {
            $query = RequestMethods::post('searchquery');
            $queryParts = explode(' ',$query);

            $args = array();
            $productWhereCond = "pr.deleted = 0 AND pr.variantFor = 0 AND pr.active = 1 AND (";
            for($i = 0; $i<count($queryParts); $i++){
                $productWhereCond .= "pr.productCode='?' OR pr.metaTitle LIKE '%%?%%' "
                    . "OR pr.metaKeywords LIKE '%%?%%' OR pr.title LIKE '%%?%%' OR ";
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
            }
            
            $productWhereCond = substr($productWhereCond, 0, strlen($productWhereCond)-4).")";
            array_unshift($args, $productWhereCond);
            
            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.urlKey', 'pr.productCode',
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb'));
            
            call_user_method_array('wheresql', $productQuery, $args);

            $productQuery->order('pr.created', 'DESC')
                    ->limit(50);
            $products = App_Model_Product::initialize($productQuery);

            $argscat = array();
            $catWhereCond = "ct.active = 1 AND (";
            for ($i = 0; $i < count($queryParts); $i++) {
                $catWhereCond .= "ct.metaTitle LIKE '%%?%%' "
                        . "OR ct.metaKeywords LIKE '%%?%%' OR ct.title LIKE '%%?%%' OR ";
                $argscat[] = $queryParts[$i];
                $argscat[] = $queryParts[$i];
                $argscat[] = $queryParts[$i];
            }
            
            $catWhereCond = substr($catWhereCond, 0, strlen($catWhereCond)-4).")";
            array_unshift($argscat, $catWhereCond);

            $categoryQuery = App_Model_Category::getQuery(
                            array('ct.id', 'ct.urlKey', 'ct.title'));
            
            call_user_method_array('wheresql', $categoryQuery, $argscat);

            $categoryQuery->order('ct.rank', 'asc')
                    ->limit(10);
            $categories = App_Model_Category::initialize($categoryQuery);

            $view->set('products', $products)
                   ->set('query', $query)
                    ->set('categories', $categories);
            
            $layoutView->set('background', 1);
        }
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
    public function feed()
    {
        
    }

}
