<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_News
 *
 * @author Tomy
 */
class App_Model_News extends Model
{

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
     * @type text
     * @length 85
     * 
     * @validate alphanumeric, max(85)
     * @label author
     */
    protected $_author;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate required, alphanumeric, max(100)
     * @label title
     */
    protected $_title;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(5000)
     * @label text
     */
    protected $_body;

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

}
