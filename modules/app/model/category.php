<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Category
 *
 * @author Tomy
 */
class App_Model_Category extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'ct';

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
     * @index
     * 
     * @validate  numeric, max(8)
     */
    protected $_parentId;

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
     * @length 200
     * @unique
     * @index
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label rank
     */
    protected $_rank;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @label for group costumes
     */
    protected $_isGrouped;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @label for selable goods
     */
    protected $_isSelable;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @label category separator
     */
    protected $_isSeparator;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(30000)
     * @label text
     */
    protected $_mainText;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
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
     * @validate alphanumeric, max(30000)
     * @label meta description
     */
    protected $_metaDescription;

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
    protected $_subcategory;
    
    /**
     * @readwrite
     */
    protected $_fbLikeUrl;

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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @return type
     */
    public function getChildrens()
    {
        return self::all(
                        array('active = ?' => true, 'parentId = ?' => $this->getId())
        );
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchChildrens($id)
    {
        $category = new self(array('id' => $id));

        $category->_subcategory = $category->getChildrens();

        if($category->_subcategory !== null){
            foreach($category->_subcategory as $cat){
                $cat->_subcategory = self::fetchChildrens($cat->id);
            }
        }
        
        return $category->_subcategory;
    }

    /**
     * 
     * @param type $id
     * @return boolean
     */
    public static function fetchAllCategories()
    {
        $mainCat = self::all(array('active = ?' => true, 'parentId = ?' => 0));
        
        foreach ($mainCat as $category) {
            $category->_subcategory = self::fetchChildrens($category->id);
        }
        
        return $mainCat;
    }
    
}
