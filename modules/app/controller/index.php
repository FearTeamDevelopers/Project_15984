<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Model\Model;

/**
 * 
 */
class App_Controller_Index extends Controller
{

    /**
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, Model $object)
    {
        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if($object->getMetaKeywords() != ''){
            $layoutView->set('metakeywords', $object->getMetaKeywords());
        }
        
        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object instanceof \App_Model_Product) {
            $layoutView->set('metaogimage', "http://{$this->getServerHost()}" . $object->getImgMain());
            $layoutView->set('metaogurl', "http://{$this->getServerHost()}/kostym/" . $object->getUrlKey() . '/');
        }

        $layoutView->set('metaogtype', 'article');

        return;
    }

    /**
     * Method replace specific strings whit their equivalent images
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
        $canonical = 'http://' . $this->getServerHost();

        $layoutView->set('active', 99)
                ->set('showmenu', 1)
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function aboutUs()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('o-nas');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
            $this->getCache()->set('o-nas', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $canonical = 'http://' . $this->getServerHost() . '/o-nas';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 1)
                ->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function reference()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('reference');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_Reference::all(
                            array('active = ?' => true), array('*'), array('created' => 'desc'), 30);

            $this->getCache()->set('reference', $content);
        }

        $canonical = 'http://' . $this->getServerHost() . '/reference';

        $view->set('reference', $content);
        $layoutView->set('active', 2)
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('canonical', $canonical)
                ->set('metatitle', 'Agentura Karneval - Reference');
    }

    /**
     * 
     */
    public function news()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('aktuality');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_News::all(
                            array('active = ?' => true), array('*'), array('created' => 'desc'), 15);

            $this->getCache()->set('aktuality', $content);
        }

        $canonical = 'http://' . $this->getServerHost() . '/aktuality';

        $view->set('news', $content);
        $layoutView->set('active', 3)
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('canonical', $canonical)
                ->set('metatitle', 'Agentura Karneval - Novinky');
    }

    /**
     * 
     */
    public function priceList()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('cenik');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
            $this->getCache()->set('cenik', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $canonical = 'http://' . $this->getServerHost() . '/cenik';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 4)
                ->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function contact()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('kontakty');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
            $this->getCache()->set('kontakty', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $canonical = 'http://' . $this->getServerHost() . '/kontakt';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 5)
                ->set('canonical', $canonical);
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

        $layoutView->set('parentcat', null)
                ->set('activecat', null)
                ->set('active', 99);

        if ($product === null) {
            self::redirect('/neznamykostym');
        }

        $view->set('product', $product);

        $canonical = 'http://' . $this->getServerHost() . '/kostym/' . $product->getUrlKey() . '/';

        $fblike = urlencode('http://' . RequestMethods::server('HTTP_HOST') . '/kostym/' . $product->getUrlKey() . '/');

        $activeCat = $session->get('activecat', 'unknown');
        $parentCat = $session->get('parentcat', 'unknown');

        if($activeCat != 'unknown'){
            $actualCat = App_Model_Category::first(array('urlKey = ?' => $activeCat));
        }else{
            $categories = App_Model_Product::fetchCategoriesByProductId($product->getId());
            if(!empty($categories)){
                $actualCat = array_shift($categories);
            }
        }

        $view->set('product', $product)
                ->set('actualcat', $actualCat)
                ->set('fblike', $fblike);

        $this->_checkMetaData($layoutView, $product);
        $layoutView->set('activecat', $activeCat)
                ->set('parentcat', $parentCat)
                ->set('background', 1)
                ->set('active', 99)
                ->set('article', 1)
                ->set('artcreated', $product->getCreated())
                ->set('artmodified', $product->getModified())
                ->set('canonical', $canonical);
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

        $canonical = 'http://' . $this->getServerHost() . '/neznamykostym';

        $layoutView->set('activecat', $activeCat)
                ->set('parentcat', $parentCat)
                ->set('active', 99)
                ->set('canonical', $canonical)
                ->set('metatitle', 'Agentura Karneval - Neznámý kostým');
    }

    /**
     * 
     */
    public function search()
    {
        $layoutView = $this->getLayoutView();
        $view = $this->getActionView();
        $session = Registry::get('session');

        if (RequestMethods::get('search')) {
            $query = RequestMethods::get('lookfor');

            $query = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|'), '', $query);
            $query = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $query);
        
            if ($query != '') {
                $productWhereCond = "pr.deleted = 0 AND pr.variantFor = 0 AND pr.active = 1 AND (";
                $productWhereCond .= "pr.metaKeywords LIKE '%%?%%' OR pr.title LIKE '%%?%%' OR pr.productCode='?')";

                $productQuery = App_Model_Product::getQuery(
                                array('pr.id', 'pr.urlKey', 'pr.productCode', 'pr.hasGroupPhoto',
                                    'pr.title', 'pr.currentPrice', 'pr.weekendPrice', 'pr.imgMain', 'pr.imgThumb'));

                $productQuery->wheresql($productWhereCond, $query, $query, $query);
                $productQuery->order('pr.created', 'DESC');

                $products = App_Model_Product::initialize($productQuery);

                $catWhereCond = "ct.active = 1 AND ct.title LIKE '%%?%%'";

                $categoryQuery = App_Model_Category::getQuery(
                                array('ct.id', 'ct.urlKey', 'ct.title'));
                $categoryQuery->wheresql($catWhereCond, $query);
                $categoryQuery->order('ct.rank', 'asc')
                        ->limit(10);
                
                $categories = App_Model_Category::initialize($categoryQuery);
            } else {
                $products = $categories = array();
            }
            
            $canonical = 'http://' . $this->getServerHost() . '/hledat';
            $activeCat = $session->get('activecat', 'unknown');
            $parentCat = $session->get('parentcat', 'unknown');

            $view->set('products', $products)
                    ->set('query', $query)
                    ->set('categories', $categories);

            $layoutView
                    ->set('active', 0)
                    ->set('activecat', $activeCat)
                    ->set('parentcat', $parentCat)
                    ->set('background', 1)
                    ->set('canonical', $canonical)
                    ->set('metatitle', 'Agentura Karneval - Hledat');
        }
    }

    /**
     * 
     */
    public function feed()
    {
        
    }

}
