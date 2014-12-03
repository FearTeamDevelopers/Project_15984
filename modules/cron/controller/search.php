<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Cron_Controller_Search extends Controller
{

    /**
     * 
     */
    public function index()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $products = App_Model_Product::all(array('deleted = ?' => false, 'variantFor = ?' => 0, 'active = ?' => true));

        if ($products !== null) {
            foreach ($products as $product) {
                $productTitle = $product->getTitle();

                if (strpos($productTitle, ' ') !== false) {
                    $titleParts = explode(' ', $productTitle);

                    $normalizedParts = array();
                    foreach ($titleParts as $part) {
                        $part = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', '-'), '', $part);
                        $part = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $part);

                        $normalizedParts[] = $part;
                    }

                    $complete = join(' ', $normalizedParts);
                    $exploded = join(',', $normalizedParts);
                    $keywords = $complete . ',' . $exploded;

                    $product->metaKeywords = $keywords;
                    $product->save();
                } else {
                    $productTitle = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|'), '', $productTitle);
                    $productTitle = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $productTitle);

                    $product->metaKeywords = $productTitle;
                    $product->save();
                }
            }
            Event::fire('cron.log', array('success', 'Search keywords updated'));
        } else {
            Event::fire('cron.log', array('fail', 'Error while updating search keywords'));
        }
    }

}
