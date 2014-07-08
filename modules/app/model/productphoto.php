<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Productphoto
 *
 * @author Tomy
 */
class App_Model_Productphoto extends Model
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
     * @type integer
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_productId;
    
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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @return type
     */
    public function getFormatedSize($unit = 'kb')
    {
        $bytes = floatval($this->_size);

        $units = array(
            'b' => 1,
            'kb' => 1024,
            'mb' => pow(1024, 2),
            'gb' => pow(1024, 3)
        );

        $result = $bytes / $units[strtolower($unit)];
        $result = strval(round($result, 2)) . ' ' . strtoupper($unit);

        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_path)) {
                return APP_PATH . $this->_path;
            } elseif (file_exists('.' . $this->_path)) {
                return '.' . $this->_path;
            } elseif (file_exists('./' . $this->_path)) {
                return './' . $this->_path;
            }
        } else {
            return $this->_path;
        }
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_thumbPath)) {
                return APP_PATH . $this->_thumbPath;
            } elseif (file_exists('.' . $this->_thumbPath)) {
                return '.' . $this->_thumbPath;
            } elseif (file_exists('./' . $this->_thumbPath)) {
                return './' . $this->_thumbPath;
            }
        } else {
            return $this->_thumbPath;
        }
    }

}
