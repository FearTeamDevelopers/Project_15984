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

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        if ($object->getMetaKeywords() != '') {
            $layoutView->set('metakeywords', $object->getMetaKeywords());
        }

        if ($object instanceof App_Model_Product) {
            $layoutView->set('metaogimage', 'http://www.agenturakarneval.cz' . $object->getImgMain());
            $layoutView->set('metaogurl', 'http://www.agenturakarneval.cz/kostym/' . $object->getUrlKey() . '/');
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
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host;

        $layoutView->set('active', 99)
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
        $cache = Registry::get('cache');

        $content = $cache->get('o-nas');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'o-nas'));
            $cache->set('o-nas', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/o-nas';

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
        $cache = Registry::get('cache');

        $content = $cache->get('reference');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_Reference::all(
                            array('active = ?' => true), array('*'), array('created' => 'desc'), 30);

            $cache->set('reference', $content);
        }

        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/reference';

        $view->set('reference', $content);
        $layoutView->set('active', 2)
                ->set('activecat', null)
                ->set('parentcat', null)
                ->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function priceList()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('cenik');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'cenik'));
            $cache->set('cenik', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/cenik';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 3)
                ->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function contact()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $cache = Registry::get('cache');

        $content = $cache->get('kontakty');

        if (NULL !== $content) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::first(array('active = ?' => true, 'urlKey = ?' => 'kontakty'));
            $cache->set('kontakty', $content);
        }

        $parsed = $this->_parseContentBody($content);
        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/kontakt';

        $view->set('content', $parsed);

        $this->_checkMetaData($layoutView, $content);
        $layoutView->set('activecat', null)
                ->set('parentcat', null)
                ->set('active', 4)
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

        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/kostym/' . $product->getUrlKey() . '/';

        $fblike = urlencode('http://' . RequestMethods::server('HTTP_HOST') . '/kostym/' . $product->getUrlKey() . '/');

        $activeCat = $session->get('activecat', 'unknown');
        $parentCat = $session->get('parentcat', 'unknown');

        $view->set('product', $product)
                ->set('selable', $isSelable)
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

        $host = RequestMethods::server('HTTP_HOST');
        $canonical = 'http://' . $host . '/neznamykostym';

        $layoutView->set('activecat', $activeCat)
                ->set('parentcat', $parentCat)
                ->set('active', 99)
                ->set('canonical', $canonical);
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
            $queryParts = explode(' ', $query);

            $args = array();
            $productWhereCond = "pr.deleted = 0 AND pr.variantFor = 0 AND pr.active = 1 AND (";
            for ($i = 0; $i < count($queryParts); $i++) {
                $productWhereCond .= "pr.productCode='?' OR pr.metaTitle LIKE '%%?%%' "
                        . "OR pr.metaKeywords LIKE '%%?%%' OR pr.title LIKE '%%?%%' OR ";
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
                $args[] = $queryParts[$i];
            }

            $productWhereCond = substr($productWhereCond, 0, strlen($productWhereCond) - 4) . ")";
            array_unshift($args, $productWhereCond);

            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.urlKey', 'pr.productCode', 'pr.hasGroupPhoto',
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

            $catWhereCond = substr($catWhereCond, 0, strlen($catWhereCond) - 4) . ")";
            array_unshift($argscat, $catWhereCond);

            $categoryQuery = App_Model_Category::getQuery(
                            array('ct.id', 'ct.urlKey', 'ct.title'));

            call_user_method_array('wheresql', $categoryQuery, $argscat);

            $categoryQuery->order('ct.rank', 'asc')
                    ->limit(10);
            $categories = App_Model_Category::initialize($categoryQuery);

            $host = RequestMethods::server('HTTP_HOST');
            $canonical = 'http://' . $host . '/hledat';

            $view->set('products', $products)
                    ->set('query', $query)
                    ->set('categories', $categories);

            $layoutView->set('background', 1)
                    ->set('canonical', $canonical);
        }
    }

    /**
     * 
     */
    public function feed()
    {
        
    }

}
