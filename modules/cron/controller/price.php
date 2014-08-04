<?php

use Cron\Etc\Controller;

class Price_Controller_Index extends Controller
{
    /**
     * @before _cron
     */
    public function calculateProductPrice()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
    }
}