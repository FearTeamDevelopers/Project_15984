<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Core\Core;

/**
 * 
 */
class Cron_Controller_Price extends Controller
{
    
    /**
     * @before _cron
     */
    public function calculateProductPrice()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
        $start = microtime(true);
        $errorCount = 0;
        
        $products = App_Model_Product::all();
        
        foreach ($products as $product){
            if($product->getDiscount() != 0 
                    && $product->getDiscountFrom() <= date('Y-m-d') 
                    && $product->getDiscountTo() >= date('Y-m-d')){
                $floatPrice = $product->getBasicPrice() - ($product->getBasicPrice() * ($product->getDiscount() / 100));
                $product->currentPrice = round($floatPrice);
            }else{
                $product->currentPrice = $product->getBasicPrice();
            }
            
            if($product->validate()){
                $product->save();
            }else{
                $errorCount++;
                Core::log(serialize($product->getErrors()), 'cronlog.log');
            }
        }
        
        $time = microtime(true) - $start;
        
        if($errorCount == 0){
            Event::fire('cron.log', array('success', 'Total time: ' . gmdate('H:i:s', $time)));
        }else{
            Event::fire('cron.log', array('fail', 'Total time: ' . gmdate('H:i:s', $time) . ' - Errors count: '.$errorCount));
        }
    }
}