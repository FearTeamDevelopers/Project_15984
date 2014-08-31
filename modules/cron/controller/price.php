<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Core\Core;
use THCFrame\Registry\Registry;

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
        $cache = Registry::get('cache');

        $start = microtime(true);
        $errorCount = 0;

        $file = './application/logs/croninfo.log';

        if (file_exists($file)) {
            $info = unserialize(file_get_contents($file));
        } else {
            $info = array();
        }

        if (isset($info['actualPage']) && $info['actualPage'] > 5) {
            exit();
        }

        if (!isset($info['productCount']) || empty($info['productCount'])) {
            $productsCount = App_Model_Product::count();
            $productsCountPerBadge = round($productsCount / 5);
            $page = 1;
        } else {
            $productsCount = $info['productCount'];
            $productsCountPerBadge = $info['productCountPerBadge'];
            $page = $info['actualPage'];
        }

        $products = App_Model_Product::all(array('variantFor = ?' => 0), array('*'), array(), $productsCountPerBadge, (int) $page);

        if ($products !== null) {
            foreach ($products as $product) {
                if ($product->getDiscount() != 0 &&
                        $product->getDiscountFrom() <= date('Y-m-d') && $product->getDiscountTo() >= date('Y-m-d')) {
                    $floatPrice = $product->getBasicPrice() - ($product->getBasicPrice() * ($product->getDiscount() / 100));
                    $product->currentPrice = round($floatPrice);
                } else {
                    $product->currentPrice = $product->getBasicPrice();
                }

                if ($product->validate()) {
                    $product->save();
                } else {
                    $errorCount++;
                    Core::getLogger()->log(serialize($product->getErrors()), 'cronlog.log');
                }
            }
        }

        $time = microtime(true) - $start;

        if ($page == 5) {
            $saveInfo = array(
                'productCount' => '',
                'productCountPerBadge' => '',
                'actualPage' => 1);
        } else {
            $saveInfo = array(
                'productCount' => $productsCount,
                'productCountPerBadge' => $productsCountPerBadge,
                'actualPage' => (int) $page + 1);
        }

        file_put_contents('./application/logs/croninfo.log', serialize($saveInfo));

        if ($errorCount == 0) {
            Event::fire('cron.log', array('success', 'Total time: ' . gmdate('H:i:s', $time)));
            $cache->invalidate();
        } else {
            Event::fire('cron.log', array('fail', 'Total time: ' . gmdate('H:i:s', $time) . ' - Errors count: ' . $errorCount));
        }
    }

}
