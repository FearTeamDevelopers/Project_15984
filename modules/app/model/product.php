<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Product
 *
 * @author Tomy
 */
class App_Model_Product extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pr';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate numeric, max(8)
     * @label sizeId
     */
    protected $_sizeId;

    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate numeric, max(8)
     * @label variant for
     */
    protected $_variantFor;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *      
     * @validate alpha, max(30)
     * @label product type
     */
    protected $_productType;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, alphanumeric, max(250)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     *      
     * @validate required, alphanumeric, max(50)
     * @label productCode
     */
    protected $_productCode;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * 
     * @validate required, alphanumeric, max(200)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(30000)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate numeric
     * @label basic price
     */
    protected $_basicPrice;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate numeric
     * @label regular price
     */
    protected $_regularPrice;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate numeric
     * @label current price
     */
    protected $_currentPrice;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate numeric
     * @label old price one
     */
    protected $_priceOldOne;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate numeric
     * @label old price two
     */
    protected $_priceOldTwo;

    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate numeric, max(3)
     * @label quantity
     */
    protected $_quantity;
    
    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate numeric
     * @label discount
     */
    protected $_discount;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label discount From
     */
    protected $_discountFrom;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label discount to
     */
    protected $_discountTo;

    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate numeric
     * @label ean
     */
    protected $_eanCode;

    /**
     * @column
     * @readwrite
     * @type decimal
     *      
     * @validate numeric
     * @label weight
     */
    protected $_weight;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_isInAction;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label new from
     */
    protected $_newFrom;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label new to
     */
    protected $_newTo;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_hasGroupPhoto;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label thum path
     */
    protected $_imgThumb;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label meta title
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(5000)
     * @label meta keywords
     */
    protected $_metaKeywords;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html, max(30000)
     * @label meta description
     */
    protected $_metaDescription;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate alphanumeric, max(250)
     * @label rss feed title
     */
    protected $_rssFeedTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html, max(30000)
     * @label rss feed description
     */
    protected $_rssFeedDescription;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label rss feed img
     */
    protected $_rssFeedImg;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_deleted;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * @readwrite
     */
    protected $_additionalPhotos;

    /**
     * @readwrite
     */
    protected $_variants;

    /**
     * @readwrite
     */
    protected $_inCategories;

    /**
     * @readwrite
     */
    protected $_recommendedProducts;
    
    /**
     * @readwrite
     */
    protected $_recommendedProductObjects;
    
    /**
     * @readwrite
     */
    protected $_fbLikeUrl;
    
    /**
     * @readwrite
     */
    protected $_realPrice;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
            $this->setDeleted(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     */
    public function isActive()
    {
        return (boolean) $this->_active;
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgMain)) {
                return APP_PATH . $this->_imgMain;
            } elseif (file_exists('.' . $this->_imgMain)) {
                return '.' . $this->_imgMain;
            } elseif (file_exists('./' . $this->_imgMain)) {
                return './' . $this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgThumb)) {
                return APP_PATH . $this->_imgThumb;
            } elseif (file_exists('.' . $this->_imgThumb)) {
                return '.' . $this->_imgThumb;
            } elseif (file_exists('./' . $this->_imgThumb)) {
                return './' . $this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }

    /**
     * 
     * @param type $urlKey
     * @return type
     */
    public static function fetchProductByUrlKey($urlKey)
    {
        $product = self::first(array('urlKey = ?' => $urlKey, 'deleted = ?' => false));

        if ($product !== null) {
            if ($product->sizeId != 0) {
                $productQuery = App_Model_Product::getQuery(array('pr.*'))
                        ->join('tb_codebook', 'pr.sizeId = cb.id', 'cb', 
                                array('cb.title' => 'sizeTitle'))
                        ->where('pr.urlKey = ?', $urlKey)
                        ->where('pr.deleted = ?', false);
                $productArr = App_Model_Product::initialize($productQuery);
                $product = array_shift($productArr);
            }
            
            return $product->getProductByIdForUser();
        } else {
            return null;
        }
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchProductById($id)
    {
        $product = self::first(array('id = ?' => (int) $id, 'deleted = ?' => false));
        
        if ($product->sizeId != 0) {
            $productQuery = App_Model_Product::getQuery(array('pr.*'))
                    ->join('tb_codebook', 'pr.sizeId = cb.id', 'cb', 
                            array('cb.title' => 'sizeTitle'))
                    ->where('pr.id = ?', (int) $id)
                    ->where('pr.deleted = ?', false);
            $productArr = App_Model_Product::initialize($productQuery);
            $product = array_shift($productArr);
        }
        return $product->getProductById();
    }

    /**
     * 
     * @return \App_Model_Product
     */
    public function getProductByIdForUser()
    {
        $variantsQuery = App_Model_Product::getQuery(array('pr.*'))
                ->join('tb_codebook', 'pr.sizeId = cb.id', 'cb', 
                        array('cb.title' => 'sizeTitle'))
                ->where('pr.variantFor = ?', $this->getId())
                ->where('pr.deleted = ?', false);
        $this->_variants = App_Model_Product::initialize($variantsQuery);

        $this->_additionalPhotos = App_Model_ProductPhoto::all(array('active = ?' => true,'productId = ?' => $this->getId()));
        $this->_inCategories = App_Model_ProductCategory::all(array('productId = ?' => $this->getId()));
        $this->_recommendedProducts = App_Model_RecommendedProduct::all(array('productId = ?' => $this->getId()));
        
        if (!empty($this->_recommendedProducts)) {
            $recomProductIds = array();
            foreach ($this->_recommendedProducts as $recprod) {
                $recomProductIds[] = $recprod->getRecommendedId();
            }
            
            $this->_recommendedProductObjects = self::all(array(
                        'deleted = ?' => false,
                        'active = ?' => true,
                        'id IN ?' => $recomProductIds
            ),array('id', 'title', 'urlKey', 'productCode', 'imgThumb'));
        }else{
            $this->_recommendedProductObjects = array();
        }

        return $this;
    }
    
    /**
     * 
     */
    public function getProductById()
    {
        $variantsQuery = App_Model_Product::getQuery(array('pr.*'))
                ->join('tb_codebook', 'pr.sizeId = cb.id', 'cb', 
                        array('cb.title' => 'sizeTitle'))
                ->where('pr.variantFor = ?', $this->getId())
                ->where('pr.deleted = ?', false);
        $this->_variants = App_Model_Product::initialize($variantsQuery);

        $this->_additionalPhotos = App_Model_ProductPhoto::all(array('productId = ?' => $this->getId()));
        $this->_inCategories = App_Model_ProductCategory::all(array('productId = ?' => $this->getId()));
        $this->_recommendedProducts = App_Model_RecommendedProduct::all(array('productId = ?' => $this->getId()));
        
        if (!empty($this->_recommendedProducts)) {
            $recomProductIds = array();
            foreach ($this->_recommendedProducts as $recprod) {
                $recomProductIds[] = $recprod->getRecommendedId();
            }
            
            $this->_recommendedProductObjects = self::all(array(
                        'deleted = ?' => false,
                        'active = ?' => true,
                        'id IN ?' => $recomProductIds
            ),array('id', 'title', 'urlKey', 'productCode', 'imgThumb'));
        }else{
            $this->_recommendedProductObjects = array();
        }

        return $this;
    }

    /**
     * 
     * @return type
     */
    public static function fetchLatestProducts()
    {
        $productQuery = App_Model_Product::getQuery(
                    array('pr.id', 'pr.urlKey', 'pr.productCode', 'pr.title', 
                        'pr.currentPrice', 'pr.imgThumb', 'pr.created'))
                ->where('pr.deleted = ?', false)
                ->where('pr.active = ?', true)
                ->where('pr.variantFor = ?', 0)
                ->order('pr.created', 'desc')
                ->limit(10);
        
        return App_Model_Product::initialize($productQuery);
    }

    /**
     * 
     * @param type $category
     */
    public static function fetchProductsByCategory($categoryUrlKey, $orderby = 'created', $order = 'desc')
    {
        $productsQuery = App_Model_Product::getQuery(array('pr.*'))
                ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', 
                        array('productId', 'categoryId'))
                ->join('tb_category', 'pc.categoryId = ct.id', 'ct', 
                        array('ct.id' => 'catId', 'parentId', 'ct.title' => 'catTitle', 'ct.urlKey' => 'catUrlKey', 
                            'isGrouped', 'isSelable', 'mainText', 
                            'ct.metaTitle' => 'catMetaTitle', 'ct.metaKeywords' => 'catMetaKeywords', 
                            'ct.metaDescription' => 'catMetaDescription'))
                ->where('ct.active = ?', true)
                ->where('ct.urlKey = ?', $categoryUrlKey)
                ->order('pr.'.$orderby, $order)
                ->where('pr.active = ?', true)
                ->where('pr.deleted = ?', false);
        $products = App_Model_Product::initialize($productsQuery);
        
        return $products;
    }
    
}