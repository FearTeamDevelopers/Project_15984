<?php

use App\Etc\Controller;
use THCFrame\Profiler\Profiler;

/**
 * 
 */
class App_Controller_System extends Controller
{

    /**
     * 
     */
    public function showProfiler()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $profiler = Profiler::getProfiler();
        echo $profiler->printProfilerRecord();
    }

}
