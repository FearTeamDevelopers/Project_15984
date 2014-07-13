<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_User
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
     * @type integer
     *      
     * @validate numeric, max(2)
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
     * @length 250
     * 
     * @validate required, alphanumeric, max(250)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, alphanumeric, max(5000)
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
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label meta keywords
     */
    protected $_metaKeywords;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(5000)
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
     * @validate alphanumeric, max(5000)
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
}
