<?php

namespace THCFrame\Logger;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Logger\Exception;
use THCFrame\Registry\Registry;

/**
 * Factory class
 * 
 * @author Tomy
 */
class Logger extends Base
{

    /**
     * @readwrite
     */
    protected $_type;

    /**
     * @readwrite
     */
    protected $_options;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Session\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }
    
    /**
     * Factory method
     * It accepts initialization options and selects the type of returned object, 
     * based on the internal $_type property.
     * 
     * @return \THCFrame\Configuration\Configuration\Driver\Ini
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Event::fire('framework.logger.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('configuration');

            if (!empty($configuration->get('logger')) && !empty($configuration->get('logger/type'))) {
                $this->type = $configuration->get('logger/type');
                $this->options = (array) $configuration->get('logger');
            } else {
                throw new \Exception('Error in configuration file');
            }
        }
        
        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Event::fire('framework.logger.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'file': {
                    return new Driver\File($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid type');
                    break;
                }
        }
    }

}
