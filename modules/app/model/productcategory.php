<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Productcategory
 *
 * @author Tomy
 */
class App_Model_ProductCategory extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pc';

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
     * @validate required, numeric, max(8)
     */
    protected $_productId;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_categoryId;

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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $categoryId
     */
    public static function countProductsByCategoryId($categoryId)
    {
        return self::count(array('categoryId = ?' => (int)$categoryId));
    }
}
