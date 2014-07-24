<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\Config;

/**
 * 
 */
class Admin_Controller_System extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        
    }

    /**
     * @before _secured, _admin
     */
    public function clearCache()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log');
            $cache = Registry::get('cache');
            $cache->clearCache();
            $view->successMessage('Cache byly úspěšně smazány');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create and download db bakcup
     * 
     * @before _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));
        $fm = new FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        $dump->create();
        $view->successMessage('Záloha databáze byla úspěšně vytvořena');
        Event::fire('app.log', array('success', 'Database backup ' . $dump->getBackupName()));
        unset($fm);
        unset($dump);
        self::redirect('/system');
    }

    /**
     * @before _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();
        $log = Admin_Model_AdminLog::all(array(), array('*'), array('created' => 'DESC'));
        $view->set('adminlog', $log);
    }

    /**
     * @before _secured, _admin
     */
    public function settings()
    {
        $view = $this->getActionView();
        $config = Config::all();
        $view->set('config', $config);

        if (RequestMethods::post('submitEditSet')) {
            $this->checkToken();
            $errors = array();

            foreach ($config as $conf) {
                $oldVal = $conf->getValue();
                $conf->value = RequestMethods::post($conf->getXkey(), '');

                if ($conf->validate()) {
                    Event::fire('admin.log', array('success', $conf->getXkey() . ': ' . $oldVal . ' - ' . $conf->getValue()));
                    $conf->save();
                } else {
                    Event::fire('admin.log', array('fail', $conf->getXkey() . ': ' . $conf->getValue()));
                    $errors[$conf->xkey] = array_shift($conf->getErrors());
                }
            }

            if (empty($errors)) {
                $view->successMessage('Nastavení bylo úspěšně změněno');
                self::redirect('/admin/system/');
            } else {
                $view->set('errors', $errors);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function generateDummyProducts()
    {
        if (ENV != 'dev') {
            return;
        }

        ini_set('max_execution_time', 1800);

        $view = $this->getActionView();
        $numOfProducts = 500;

        for ($i = 1; $i <= $numOfProducts; $i++) {
            //create variants
            if ($i % 50 == 0) {
                $product = new App_Model_Product(array(
                    'sizeId' => 0,
                    'urlKey' => time() . '-' . time() . '-' . $i,
                    'productType' => 's variantami',
                    'variantFor' => 0,
                    'productCode' => time() . '-' . $i,
                    'title' => time() . '-' . $i,
                    'description' => time() . '-' . $i,
                    'basicPrice' => 0,
                    'regularPrice' => 0,
                    'currentPrice' => (int) substr(time(), 1, 3),
                    'quantity' => 1,
                    'discount' => 0,
                    'discountFrom' => '',
                    'discountTo' => '',
                    'eanCode' => '',
                    'isInAction' => '',
                    'newFrom' => '',
                    'newTo' => '',
                    'imgMain' => '',
                    'imgThumb' => '',
                    'metaTitle' => '',
                    'metaKeywords' => '',
                    'metaDescription' => '',
                    'rssFeedTitle' => '',
                    'rssFeedDescription' => '',
                    'rssFeedImg' => ''
                ));

                if ($product->validate()) {
                    $pid = $product->save();
                    $catId = rand(1, 4);
                    $size = rand(1, 17);

                    $productCategory = new App_Model_ProductCategory(array(
                        'productId' => $pid,
                        'categoryId' => $catId
                    ));
                    $productCategory->save();

                    if ($i > 100) {
                        $recomProduct = new App_Model_RecommendedProduct(array(
                            'productId' => $pid,
                            'recommendedId' => rand(1, 99)
                        ));
                        $recomProduct->save();
                    }

                    $productVariant = new App_Model_Product(array(
                        'sizeId' => $size,
                        'urlKey' => time() . '-' . time() . '-' . $i . '-' . $i,
                        'productType' => 'varianta',
                        'variantFor' => $pid,
                        'productCode' => time() . '-' . $i . '-' . $i,
                        'title' => time() . '-' . $i . '-' . $i,
                        'description' => time() . '-' . $i . '-' . $i,
                        'basicPrice' => 0,
                        'regularPrice' => 0,
                        'currentPrice' => (int) substr(time(), 1, 3),
                        'quantity' => 1,
                        'discount' => 0,
                        'discountFrom' => '',
                        'discountTo' => '',
                        'eanCode' => '',
                        'isInAction' => '',
                        'newFrom' => '',
                        'newTo' => '',
                        'imgMain' => '',
                        'imgThumb' => '',
                        'metaTitle' => '',
                        'metaKeywords' => '',
                        'metaDescription' => '',
                        'rssFeedTitle' => '',
                        'rssFeedDescription' => '',
                        'rssFeedImg' => ''
                    ));

                    if ($productVariant->validate()) {
                        $productVariant->save();
                    }
                }
            } else {
                $size = rand(1, 17);

                $product = new App_Model_Product(array(
                    'sizeId' => $size,
                    'urlKey' => time() . '-' . time() . '-' . $i,
                    'productType' => 'bez variant',
                    'variantFor' => 0,
                    'productCode' => time() . '-' . $i,
                    'title' => time() . '-' . $i,
                    'description' => time() . '-' . $i,
                    'basicPrice' => 0,
                    'regularPrice' => 0,
                    'currentPrice' => (int) substr(time(), 1, 3),
                    'quantity' => 1,
                    'discount' => 0,
                    'discountFrom' => '',
                    'discountTo' => '',
                    'eanCode' => '',
                    'isInAction' => '',
                    'newFrom' => '',
                    'newTo' => '',
                    'imgMain' => '',
                    'imgThumb' => '',
                    'metaTitle' => '',
                    'metaKeywords' => '',
                    'metaDescription' => '',
                    'rssFeedTitle' => '',
                    'rssFeedDescription' => '',
                    'rssFeedImg' => ''
                ));

                if ($product->validate()) {
                    $pid = $product->save();
                    $catId = rand(1, 4);
                    $size = rand(1, 17);

                    $productCategory = new App_Model_ProductCategory(array(
                        'productId' => $pid,
                        'categoryId' => $catId
                    ));
                    $productCategory->save();

                    if ($i > 100) {
                        $recomProduct = new App_Model_RecommendedProduct(array(
                            'productId' => $pid,
                            'recommendedId' => rand(1, 99)
                        ));
                        $recomProduct->save();
                    }
                }
            }
        }
        $view->infoMessage('Product import completed');
        self::redirect('/admin/');
    }

    /**
     * @before _secured, _admin
     */
    public function deletedProducts()
    {
        $view = $this->getActionView();
        
        $products = App_Model_Product::all(array('deleted = ?' => true));
        $view->set('deletedproducts', $products);
    }
}
