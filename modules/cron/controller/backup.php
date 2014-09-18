<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class Cron_Controller_Backup extends Controller
{

    /**
     * @before _cron
     */
    public function databaseBackup()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
    }
    
    /**
     * @before _cron
     */
    public function createSitemap()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');

        $pageContentXml = "<url><loc>http://{$host}</loc></url>"
                . "<url><loc>http://{$host}/o-nas</loc></url>"
                . "<url><loc>http://{$host}/cenik</loc></url>"
                . "<url><loc>http://{$host}/kontakty</loc></url>"
                . "<url><loc>http://{$host}/reference</loc></url>" . PHP_EOL;

        $categories = App_Model_Category::all(
                        array('active = ?' => true), 
                        array('urlKey'));

        $categoryXml = '';
        foreach ($categories as $category) {
            $categoryXml .= "<url><loc>http://{$host}/kategorie/{$category->urlKey}/</loc></url>" . PHP_EOL;
        }

        $products = App_Model_Product::all(
                        array('active = ?' => true, 'deleted = ?' => false, 'variantFor = ?' => 0), 
                        array('urlKey'));

        $productXml = '';
        foreach ($products as $product) {
            $productXml .= "<url><loc>http://{$host}/kostym/{$product->urlKey}/</loc></url>" . PHP_EOL;
        }

        file_put_contents('./sitemap.xml', $xml . $pageContentXml . $categoryXml . $productXml . $xmlEnd);
        Event::fire('cron.log', array('success'));
    }
}
