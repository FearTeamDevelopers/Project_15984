<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_User
 *
 * @author Tomy
 */
class App_Model_Product extends Model {

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
     * @validate required
     * @label sizeId
     */
    protected $sizeId;

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
     * @length 100
     * 
     * @validate required, alphanumeric, max(100)
     * @label url key
     */
    protected $_urlKey;
    
    /**
     * @column
     * @readwrite
     * @type integer
     *      
     * @validate required
     * @label productCode
     */
    protected $_productCode;
      /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label title
     */
    protected $_title;
    
     /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, alphanumeric, max(2048)
     * @label description
     */
    protected $_Description;
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
     * @validate alphanumeric, max(22)
     * @label discount From
     */
    protected $_discountFrom;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate alphanumeric, max(22)
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
     * @type text
     * @length 5
     * 
     * @validate alphanumeric, max(5)
     * @label measure unit
     */
    protected $_mu;
    
    /**
     * @column
     * @readwrite
     * @type integer
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
     * @validate alphanumeric, max(22)
     * @label new from
     */
    protected $_newFrom;
    
      /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate alphanumeric, max(22)
     * @label new to
     */
    protected $_newTo;
    
     /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, max(250)
     * @label thum path
     */
    protected $_imgThumb;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, max(250)
     * @label photo path
     */
    protected $_imgMain;
    
     /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label meta title
     */
    protected $_metaTitle;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label meta keywords
     */
    protected $_metaKeywords;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, alphanumeric, max(2048)
     * @label meta description
     */
    protected $_metaDescription;
    
     /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label rss feed title
     */
    protected $_rssFeedTitle;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, alphanumeric, max(2048)
     * @label rss feed description
     */
    protected $_rssFeedDescription;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, max(250)
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
    public function preSave() {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     */
    public function isActive() {
        return (boolean) $this->_active;
    }

}