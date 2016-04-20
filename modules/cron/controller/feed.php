<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class Cron_Controller_Feed extends Controller
{

    /**
     *
     */
    public function generateHeurekaFeed()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $host = RequestMethods::server('HTTP_HOST');
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<SHOP xmlns="http://www.zbozi.cz/ns/offer/1.0">';

        $xmlEnd = '</SHOP>';

        $productIds = App_Model_Product::all(array('active = ?' => true, 'deleted = ?' => false, 'variantFor = ?' => 0), array('id'));

        $productXml = '';
        foreach ($productIds as $prt) {
            $product = App_Model_Product::fetchProductById($prt->getId());

            $productXml .= "<SHOPITEM>"
                    . "<ITEM_ID>{$product->getId()}</ITEM_ID>"
                    . "<PRODUCTNAME>{$product->getTitle()}</PRODUCTNAME>"
                    . "<DESCRIPTION>{$product->getDescription()}</DESCRIPTION>"
                    . "<URL>http://{$host}/kostym/{$product->getUrlKey()}/</URL>"
                    . "<IMGURL>http://{$host}{$product->getImgMain()}</IMGURL>"
                    . "<PRICE_VAT>{$product->getCurrentPrice()}</PRICE_VAT>"
                    . "<PRODUCTNO>{$product->getProductCode()}</PRODUCTNO>"
                    . "<HEUREKA_CPC>0</HEUREKA_CPC>"
                    . "<CATEGORY><CATEGORY_ID>2868</CATEGORY_ID><CATEGORY_NAME>Karnevalové kostýmy</CATEGORY_NAME><CATEGORY_FULLNAME>Heureka.cz | Hobby | Karnevalové kostýmy</CATEGORY_FULLNAME></CATEGORY>"
                    . "<DELIVERY_DATE>0</DELIVERY_DATE>";

            if (!empty($product->getVariants())) {
                $productXml .= "<ITEMGROUP_ID>{$product->getProductCode()}</ITEMGROUP_ID>";
            }

            $productXml .= "</SHOPITEM>" . PHP_EOL;

            if (!empty($product->getVariants())) {
                foreach ($product->getVariants() as $variant) {
                    $productXml .= "<SHOPITEM>"
                            . "<PRODUCTNAME>{$product->getTitle()} {$variant->sizeTitle}</PRODUCTNAME>"
                            . "<DESCRIPTION>{$product->getDescription()}</DESCRIPTION>"
                            . "<URL>http://{$host}/kostym/{$product->getUrlKey()}/</URL>"
                            . "<IMGURL>http://{$host}{$product->getImgMain()}</IMGURL>"
                            . "<PRICE_VAT>{$product->getCurrentPrice()}</PRICE_VAT>"
                            . "<PRODUCTNO>{$product->getProductCode()}</PRODUCTNO>"
                            . "<HEUREKA_CPC>0</HEUREKA_CPC>"
                            . "<CATEGORY><CATEGORY_ID>2868</CATEGORY_ID><CATEGORY_NAME>Karnevalové kostýmy</CATEGORY_NAME><CATEGORY_FULLNAME>Heureka.cz | Hobby | Karnevalové kostýmy</CATEGORY_FULLNAME></CATEGORY>"
                            . "<DELIVERY_DATE>0</DELIVERY_DATE>"
                            . "<ITEMGROUP_ID>{$product->getProductCode()}</ITEMGROUP_ID>";
                    $productXml .= "</SHOPITEM>" . PHP_EOL;
                    unset($variant);
                }
            }

            unset($product);
        }

        @file_put_contents(APP_PATH . '/temp/feed/heureka.xml', $xml . $productXml . $xmlEnd);
        Event::fire('cron.log', array('success', 'Heureka.cz feed created'));
    }

    /**
     *
     */
    public function generateZboziFeed()
    {
        //ini_set('max_execution_time', 0);
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        $host = RequestMethods::server('HTTP_HOST');
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<SHOP xmlns="http://www.zbozi.cz/ns/offer/1.0">';

        $xmlEnd = '</SHOP>';

        $productIds = App_Model_Product::all(array('active = ?' => true, 'deleted = ?' => false, 'variantFor = ?' => 0), array('id'));

        $productXml = '';
        foreach ($productIds as $prt) {
            $product = App_Model_Product::fetchProductById($prt->getId());

            $productXml .= "<SHOPITEM>"
                    . "<PRODUCTNAME>{$product->getTitle()}</PRODUCTNAME>"
                    . "<DESCRIPTION>{$product->getDescription()}</DESCRIPTION>"
                    . "<URL>http://{$host}/kostym/{$product->getUrlKey()}/</URL>"
                    . "<IMGURL>http://{$host}{$product->getImgMain()}</IMGURL>"
                    . "<PRICE_VAT>{$product->getCurrentPrice()}</PRICE_VAT>"
                    . "<PRODUCTNO>{$product->getProductCode()}</PRODUCTNO>"
                    . "<DELIVERY_DATE>0</DELIVERY_DATE>";

            if (!empty($product->getVariants())) {
                $productXml .= "<ITEMGROUP_ID>{$product->getProductCode()}</ITEMGROUP_ID>";
            }

            $productXml .= "</SHOPITEM>" . PHP_EOL;

            if (!empty($product->getVariants())) {
                foreach ($product->getVariants() as $variant) {
                    $productXml .= "<SHOPITEM>"
                            . "<PRODUCTNAME>{$product->getTitle()} {$variant->sizeTitle}</PRODUCTNAME>"
                            . "<DESCRIPTION>{$product->getDescription()}</DESCRIPTION>"
                            . "<URL>http://{$host}/kostym/{$product->getUrlKey()}/</URL>"
                            . "<IMGURL>http://{$host}{$product->getImgMain()}</IMGURL>"
                            . "<PRICE_VAT>{$product->getCurrentPrice()}</PRICE_VAT>"
                            . "<PRODUCTNO>{$product->getProductCode()}</PRODUCTNO>"
                            . "<DELIVERY_DATE>0</DELIVERY_DATE>"
                            . "<ITEMGROUP_ID>{$product->getProductCode()}</ITEMGROUP_ID>";
                    $productXml .= "</SHOPITEM>" . PHP_EOL;
                    unset($variant);
                }
            }

            unset($product);
        }

        @file_put_contents(APP_PATH . '/temp/feed/zbozi.xml', $xml . $productXml . $xmlEnd);
        Event::fire('cron.log', array('success', 'Zbozi.cz feed created'));
    }

}
