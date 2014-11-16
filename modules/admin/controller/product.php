<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Product extends Controller
{

    private $_errors = array();

    /**
     * 
     * @param type $configurable
     * @return \App_Model_Product
     */
    private function _createMainProduct($configurable = false)
    {
        $urlKey = $urlKeyCh = $this->_createUrlKey(RequestMethods::post('title'));

        for ($i = 1; $i <= 50; $i++) {
            if ($this->_checkUrlKey($urlKeyCh)) {
                break;
            } else {
                $urlKeyCh = $urlKey . '-' . $i;
            }

            if ($i == 50) {
                $this->_errors['title'] = array('Nepodařilo se vytvořit jedinečný identifikátor produktu');
                break;
            }
        }

        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        $uploadTo = trim(substr(str_replace('.', '', $urlKeyCh), 0, 3));

        $fileErrors = $fileManager->upload('mainfile', 'product/' . $uploadTo, time() . '_')->getUploadErrors();
        $files = $fileManager->getUploadedFiles();

        if (!empty($fileErrors)) {
            $this->_errors['mainfile'] = $fileErrors;
        }

        if (!empty($files)) {
            foreach ($files as $i => $filemain) {
                if ($filemain instanceof \THCFrame\Filesystem\Image) {
                    $file = $filemain;
                    break;
                }
            }
        }

        if (RequestMethods::post('discount') &&
                RequestMethods::post('discountfrom') <= date('Y-m-d') && RequestMethods::post('discountto') >= date('Y-m-d')) {
            $floatPrice = RequestMethods::post('basicprice') - (RequestMethods::post('basicprice') * (RequestMethods::post('discount') / 100));
            $currentPrice = round($floatPrice);
        } else {
            $currentPrice = RequestMethods::post('basicprice');
        }

        if ($configurable) {
            $title = RequestMethods::post('title');
            $desc = RequestMethods::post('description');

            $product = new App_Model_Product(array(
                'sizeId' => 0,
                'urlKey' => $urlKeyCh,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => RequestMethods::post('basicprice', 0),
                'weekendPrice' => RequestMethods::post('weekendprice', (float) RequestMethods::post('basicprice', 0) + 140),
                'regularPrice' => RequestMethods::post('regularprice', 0),
                'currentPrice' => $currentPrice,
                'quantity' => RequestMethods::post('quantity', 0),
                'discount' => RequestMethods::post('discount', 0),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight', 1),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'hasGroupPhoto' => RequestMethods::post('photoType'),
                'imgMain' => trim($file->getFilename(), '.'),
                'imgThumb' => trim($file->getThumbname(), '.'),
                'metaTitle' => RequestMethods::post('metatitle', $title),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', $desc),
                'rssFeedTitle' => $title,
                'rssFeedDescription' => $desc,
                'rssFeedImg' => trim($file->getFilename(), '.'),
                'overlay' => RequestMethods::post('overlay')
            ));
        } else {
            $title = RequestMethods::post('title');
            $desc = RequestMethods::post('description');

            $product = new App_Model_Product(array(
                'sizeId' => RequestMethods::post('size'),
                'urlKey' => $urlKeyCh,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => $title,
                'description' => $desc,
                'basicPrice' => RequestMethods::post('basicprice', 0),
                'weekendPrice' => RequestMethods::post('weekendprice', (float) RequestMethods::post('basicprice', 0) + 140),
                'regularPrice' => RequestMethods::post('regularprice', 0),
                'currentPrice' => $currentPrice,
                'quantity' => RequestMethods::post('quantity', 0),
                'discount' => RequestMethods::post('discount', 0),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight', 1),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'hasGroupPhoto' => RequestMethods::post('photoType'),
                'imgMain' => trim($file->getFilename(), '.'),
                'imgThumb' => trim($file->getThumbname(), '.'),
                'metaTitle' => RequestMethods::post('metatitle', $title),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', $desc),
                'rssFeedTitle' => $title,
                'rssFeedDescription' => $desc,
                'rssFeedImg' => trim($file->getFilename(), '.'),
                'overlay' => RequestMethods::post('overlay')
            ));
        }

        if (empty($this->_errors) && $product->validate()) {
            $pid = $product->save();
            Event::fire('admin.log', array('success', 'Product id: ' . $pid));
        } else {
            Event::fire('admin.log', array('fail'));
            $this->_errors = $this->_errors + $product->getErrors();
        }

        return $product;
    }

    /**
     * 
     * @param App_Model_Product $productConf
     * @return boolean
     */
    private function _createVariants(App_Model_Product $productConf)
    {
        $sizeVariantsArr = RequestMethods::post('size');

        if (!is_array($sizeVariantsArr)) {
            $this->_errors['sizeId'] = array('Musí být vybráno více velikostí');
            return false;
        }

        foreach ($sizeVariantsArr as $size) {
            $urlKey = $urlKeyCh = $this->_createUrlKey(RequestMethods::post('title')) . '-' . $size;

            for ($i = 1; $i <= 50; $i++) {
                if ($this->_checkUrlKey($urlKeyCh)) {
                    break;
                } else {
                    $urlKeyCh = $urlKey . '-' . $i;
                }

                if ($i == 50) {
                    $this->_errors['title'] = array('Nepodařilo se vytvořit jedinečný identifikátor produktu');
                    break;
                }
            }

            $product = new App_Model_Product(array(
                'sizeId' => $size,
                'urlKey' => $urlKeyCh,
                'productType' => 'varianta',
                'variantFor' => $productConf->getId(),
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => RequestMethods::post('basicprice', 0),
                'weekendPrice' => RequestMethods::post('weekendprice', (float) RequestMethods::post('basicprice', 0) + 140),
                'regularPrice' => RequestMethods::post('regularprice', 0),
                'currentPrice' => 0,
                'quantity' => RequestMethods::post('quantity-' . $size),
                'discount' => 0,
                'discountFrom' => '',
                'discountTo' => '',
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight', 1),
                'isInAction' => '',
                'newFrom' => '',
                'newTo' => '',
                'hasGroupPhoto' => 0,
                'imgMain' => '',
                'imgThumb' => '',
                'metaTitle' => '',
                'metaKeywords' => '',
                'metaDescription' => '',
                'rssFeedTitle' => '',
                'rssFeedDescription' => '',
                'rssFeedImg' => '',
                'overlay' => ''
            ));

            if (empty($this->_errors) && $product->validate()) {
                $pid = $product->save();
                Event::fire('admin.log', array('success', 'Product variant id: ' . $pid));
            } else {
                Event::fire('admin.log', array('fail'));
                $this->_errors = $this->_errors + $product->getErrors();
            }
        }
    }

    /**
     * 
     * @param type $productId
     * @param type $categoryArr
     * @param type $update
     * @return boolean
     */
    private function _createCategoryRecords($productId, $categoryArr = array(), $update = false)
    {
        if ($update) {
            $deleteStatus = App_Model_ProductCategory::deleteAll(array('productId = ?' => (int) $productId));
            if ($deleteStatus == -1) {
                return false;
            }
        }

        foreach ($categoryArr as $category) {
            $pc = new App_Model_ProductCategory(array(
                'productId' => (int) $productId,
                'categoryId' => (int) $category
            ));

            $pc->save();
            Event::fire('admin.log', array('success', 'Product id: ' . $productId . ' into category ' . $category));
        }

        return true;
    }

    /**
     * 
     * @param type $productId
     * @param type $recommendedArr
     * @param type $update
     * @return boolean
     */
    private function _createRecommendedProductsRecords($productId, $recommendedArr = array(), $update = false)
    {
        if ($update) {
            $deleteStatus = App_Model_RecommendedProduct::deleteAll(array('productId = ?' => (int) $productId));
            if ($deleteStatus == -1) {
                return false;
            }
        }

        foreach ($recommendedArr as $recProduct) {
            $rp = new App_Model_RecommendedProduct(array(
                'productId' => $productId,
                'recommendedId' => $recProduct
            ));

            $rp->save();
            Event::fire('admin.log', array('success', 'Recommended product id: ' . $recProduct . ' for product ' . $productId));
        }

        return true;
    }

    /**
     * 
     * @return boolean
     */
    private function _uploadAdditionalPhotos($productId, $uploadTo)
    {
        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        $fileErrors = $fileManager->upload('secondfile', 'product/' . $uploadTo, time() . '_')->getUploadErrors();
        $files = $fileManager->getUploadedFiles();

        if (!empty($fileErrors)) {
            $this->_errors['secondfile'] = array($ex->getMessage());
        }

        if (!empty($files)) {
            foreach ($files as $i => $file) {
                if ($file instanceof \THCFrame\Filesystem\Image) {
                    $photo = new App_Model_ProductPhoto(array(
                        'productId' => (int) $productId,
                        'imgMain' => trim($file->getFilename(), '.'),
                        'imgThumb' => trim($file->getThumbname(), '.')
                    ));

                    if ($photo->validate()) {
                        $aid = $photo->save();

                        Event::fire('admin.log', array('success', 'Photo id: ' . $aid . ' in gallery ' . $gallery->getId()));
                    } else {
                        Event::fire('admin.log', array('fail', 'Photo in gallery ' . $gallery->getId()));
                        $this->_errors['secondfile'][] = $photo->getErrors();
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Product::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @before _secured, _member
     */
    public function index()
    {
        
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();

        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type = ?' => 'size'));
        $categories = App_Model_Category::fetchAllCategories();

        $view->set('sizes', $sizes)
                ->set('categories', $categories)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddProduct')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/product/');
            }

            $cache = Registry::get('cache');
            $categoryArr = RequestMethods::post('rcat');

            if (empty($categoryArr)) {
                $this->_errors['category'] = array('Musí být vybrána minimálně jedna kategorie');
            }

            if (RequestMethods::post('producttype') == 's variantami') {
                $product = $this->_createMainProduct(true);
                if (empty($this->_errors)) {
                    $this->_createVariants($product);
                }
            } else {
                $product = $this->_createMainProduct();
            }

            if (empty($this->_errors)) {
                /* category */
                $this->_createCategoryRecords($product->getId(), $categoryArr);

                /* recommended products */
                $recomProducts = RequestMethods::post('recomproductids');
                if (!empty($recomProducts)) {
                    $this->_createRecommendedProductsRecords($product->getId(), $recomProducts);
                }

                /* additional photos */
                if (RequestMethods::post('uplMoreImages') == 1) {
                    $uploadTo = trim(substr(str_replace('.', '', $product->getUrlKey()), 0, 3));
                    $this->_uploadAdditionalPhotos($product->getId(), $uploadTo);
                }

                if (empty($this->_errors)) {
                    $view->successMessage('Produkt' . self::SUCCESS_MESSAGE_1);
                    $cache->invalidate();
                    self::redirect('/admin/product/');
                } else {
                    $view->set('product', $product)
                            ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                            ->set('errors', $this->_errors);
                }
            } else {
                $view->set('product', $product)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $this->_errors);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();

        $product = App_Model_Product::fetchProductById($id);

        if ($product === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/product/');
        }

        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type = ?' => 'size'));
        $categories = App_Model_Category::fetchAllCategories();

        $productCategor = $product->inCategories;
        $productCategoryIds = array();
        if (!empty($productCategor)) {
            foreach ($productCategor as $prodcat) {
                $productCategoryIds[] = $prodcat->categoryId;
            }
        }

        $view->set('product', $product)
                ->set('categories', $categories)
                ->set('productcategoryids', $productCategoryIds)
                ->set('sizes', $sizes);

        if (RequestMethods::post('submitEditProduct')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/product/');
            }

            $cache = Registry::get('cache');

            if ($product->getProductType() == 'varianta') {
                $product->sizeId = RequestMethods::post('size');
                $product->productCode = RequestMethods::post('productcode');
                $product->basicPrice = RequestMethods::post('basicprice', 0);
                $product->weekendPrice = RequestMethods::post('weekendprice', (float) RequestMethods::post('basicprice', 0) + 140);
                $product->quantity = 0;
                $product->eanCode = RequestMethods::post('eancode');
                $product->weight = RequestMethods::post('weight', 1);

                if ($product->validate()) {
                    $product->save();

                    Event::fire('admin.log', array('success', 'Product id: ' . $product->getId()));
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    self::redirect('/admin/product/edit/' . $product->getVariantFor());
                } else {
                    Event::fire('admin.log', array('fail', 'Product id: ' . $product->getId()));
                    $view->set('product', $product)
                            ->set('errors', $product->getErrors());
                }
            } else {
                $urlKey = $urlKeyCh = $this->_createUrlKey(RequestMethods::post('urlkey'));

                if ($product->getUrlKey() !== $urlKey) {
                    for ($i = 1; $i <= 50; $i++) {
                        if ($this->_checkUrlKey($urlKeyCh)) {
                            break;
                        } else {
                            $urlKeyCh = $urlKey . '-' . $i;
                        }

                        if ($i == 50) {
                            $errors['urlKey'] = array('Nepodařilo se vytvořit jedinečný identifikátor produktu');
                            break;
                        }
                    }
                }

                $uploadTo = trim(substr(str_replace('.', '', $product->getUrlKey()), 0, 3));
                if ($product->imgMain == '') {
                    $fileManager = new FileManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                        'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                        'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                    ));

                    $fileErrors = $fileManager->upload('mainfile', 'product/' . $uploadTo, time() . '_')->getUploadErrors();
                    $files = $fileManager->getUploadedFiles();

                    if (!empty($fileErrors)) {
                        $errors['mainfile'] = $fileErrors;
                    }

                    if (!empty($files)) {
                        foreach ($files as $i => $filemain) {
                            if ($filemain instanceof \THCFrame\Filesystem\Image) {
                                $file = $filemain;
                                break;
                            }
                        }

                        $imgMain = trim($file->getFilename(), '.');
                        $imgThumb = trim($file->getThumbname(), '.');
                    }
                } else {
                    $imgMain = $product->imgMain;
                    $imgThumb = $product->imgThumb;
                }

                if (RequestMethods::post('discount') &&
                        RequestMethods::post('discountfrom') <= date('Y-m-d') && RequestMethods::post('discountto') >= date('Y-m-d')) {
                    $floatPrice = RequestMethods::post('basicprice') - (RequestMethods::post('basicprice') * (RequestMethods::post('discount') / 100));
                    $currentPrice = round($floatPrice);
                } else {
                    $currentPrice = RequestMethods::post('basicprice');
                }

                $product->active = RequestMethods::post('active');
                $product->sizeId = RequestMethods::post('size');
                $product->urlKey = $urlKeyCh;
                $product->productCode = RequestMethods::post('productcode');
                $product->title = RequestMethods::post('title');
                $product->description = RequestMethods::post('description');
                $product->basicPrice = RequestMethods::post('basicprice', 0);
                $product->weekendPrice = RequestMethods::post('weekendprice', (float) RequestMethods::post('basicprice', 0) + 140);
                $product->regularPrice = RequestMethods::post('regularprice', 0);
                $product->currentPrice = $currentPrice;
                $product->quantity = RequestMethods::post('quantity', 0);
                $product->discount = RequestMethods::post('discount', 0);
                $product->discountFrom = RequestMethods::post('discountfrom');
                $product->discountTo = RequestMethods::post('discountto');
                $product->eanCode = RequestMethods::post('eancode');
                $product->weight = RequestMethods::post('weight', 1);
                $product->isInAction = RequestMethods::post('inaction');
                $product->newFrom = RequestMethods::post('newfrom');
                $product->newTo = RequestMethods::post('newto');
                $product->hasGroupPhoto = RequestMethods::post('photoType');
                $product->imgMain = $imgMain;
                $product->imgThumb = $imgThumb;
                $product->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
                $product->metaKeywords = RequestMethods::post('metakeywords');
                $product->metaDescription = RequestMethods::post('metadescription', RequestMethods::post('description'));
                $product->rssFeedTitle = RequestMethods::post('title');
                $product->rssFeedDescription = RequestMethods::post('description');
                $product->rssFeedImg = $imgMain;
                $product->overlay = RequestMethods::post('overlay');

                $categoryArr = RequestMethods::post('rcat');
                if (empty($categoryArr)) {
                    $errors['category'] = array('Musí být vybrána minimálně jedna kategorie');
                }

                if (empty($errors) && $product->validate()) {
                    $product->save();

                    /* category */
                    $this->_createCategoryRecords($product->getId(), $categoryArr, true);

                    /* recommended products */
                    $recomProducts = RequestMethods::post('recomproductids');
                    if (!empty($recomProducts)) {
                        $this->_createRecommendedProductsRecords($product->getId(), $recomProducts, true);
                    }

                    if (RequestMethods::post('uplMoreImages') == 1) {
                        $this->_uploadAdditionalPhotos($product->getId(), $uploadTo);
                    }

                    if (empty($this->_errors)) {
                        Event::fire('admin.log', array('success', 'Product id: ' . $product->getId()));
                        $view->successMessage(self::SUCCESS_MESSAGE_2);
                        $cache->invalidate();
                        self::redirect('/admin/product/');
                    } else {
                        Event::fire('admin.log', array('fail', 'Product id: ' . $product->getId()));
                        $view->set('product', $product)
                                ->set('errors', $this->_errors + $product->getErrors());
                    }
                } else {
                    Event::fire('admin.log', array('fail', 'Product id: ' . $product->getId()));
                    $view->set('product', $product)
                            ->set('errors', $errors + $this->_errors + $product->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $product = App_Model_Product::first(
                        array('id = ?' => (int) $id));

        if (NULL === $product) {
            $view->warningMessage('Produkt nebyl nalezen');
            self::redirect('/admin/product/');
        }

        $view->set('product', $product);

        if (RequestMethods::post('submitDeleteProduct')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/product/');
            }

            $cache = Registry::get('cache');

            $product->deleted = true;

            if ($product->validate()) {
                $product->save();

                Event::fire('admin.log', array('success', 'Product id: ' . $id));
                $view->successMessage('Produkt' . self::SUCCESS_MESSAGE_3);
                $cache->invalidate();
                self::redirect('/admin/product/');
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $id));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/admin/product/');
            }
        }
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function undelete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $cache = Registry::get('cache');

            $product = App_Model_Product::first(
                            array('id = ?' => (int) $id, 'deleted = ?' => true));

            if (NULL === $product) {
                echo self::ERROR_MESSAGE_2;
                return;
            }

            $product->deleted = false;

            if ($product->validate()) {
                $product->save();
                $cache->invalidate();

                Event::fire('admin.log', array('success', 'Product id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function deleteRecommended($productId, $recommendedId)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $product = App_Model_RecommendedProduct::first(array(
                        'productId' => (int) $productId,
                        'recommendedId = ?' => (int) $recommendedId
            ));

            if (NULL === $product) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($product->delete()) {
                    Event::fire('admin.log', array('success', 'Recommended product ' . $recommendedId . ' for product ' . $productId));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Recommended product ' . $recommendedId . ' for product ' . $productId));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _member
     * @param type $productId
     */
    public function addRecommended($productId)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $view->set('productid', $productId);

        if (RequestMethods::post('submitSaveRecommended')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/product/');
            }

            $recomprod = App_Model_Product::first(array(
                        'deleted = ?' => false,
                        'id = ?' => RequestMethods::post('recomproductid')
            ));

            if ($recomprod === null) {
                $view->warningMessage(self::ERROR_MESSAGE_2);
                self::redirect('/admin/product/edit/' . $productId . '#recommended');
            }

            $recomExists = App_Model_RecommendedProduct::first(array(
                        'productId = ?' => (int) $productId,
                        'recommendedId = ?' => $recomprod->getId()
            ));

            if ($recomExists !== null) {
                $view->warningMessage('Doporučený produkt je již přiřazen');
                self::redirect('/admin/product/edit/' . $productId . '#recommended');
            }

            $recommended = new App_Model_RecommendedProduct(array(
                'productId' => (int) $productId,
                'recommendedId' => $recomprod->getId()
            ));

            if ($recommended->validate()) {
                $recommended->save();

                Event::fire('admin.log', array('success', 'Product id: ' . $productId . ' add recommended ' . $recomprod->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_9);
                self::redirect('/admin/product/edit/' . $productId . '#recommended');
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $productId));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/admin/product/edit/' . $productId . '#recommended');
            }
        }
    }

    /**
     * @before _secured, _member
     * @param type $photoId
     */
    public function changePhotoStatus($photoId)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $photo = App_Model_ProductPhoto::first(array('id = ?' => (int) $photoId));

        if (null === $photo) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if (!$photo->active) {
                $photo->active = true;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                    echo 'active';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            } elseif ($photo->active) {
                $photo->active = false;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                    echo 'inactive';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteProductPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $photo = App_Model_ProductPhoto::first(array('id = ?' => (int) $id));

            if ($photo === null) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $mainPath = $photo->getUnlinkPath();
                $thumbPath = $photo->getUnlinkThumbPath();

                if ($photo->delete()) {
                    @unlink($mainPath);
                    @unlink($thumbPath);
                    
                    Event::fire('admin.log', array('success', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteProductMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $product = App_Model_Product::first(array('deleted = ?' => false, 'id = ?' => (int) $id));

            if ($product === null) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $unlinkMainImg = $product->getUnlinkPath();
                $unlinkThumbImg = $product->getUnlinkThumbPath();
                $product->imgMain = '';
                $product->imgThumb = '';

                if ($product->validate()) {
                    $product->save();
                    @unlink($unlinkMainImg);
                    @unlink($unlinkThumbImg);

                    Event::fire('admin.log', array('success', 'Product id: ' . $product->getId()));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Product id: ' . $product->getId()));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;
        $errors = array();
        $errorsIds = array();

        if ($this->checkCSRFToken()) {
            $ids = RequestMethods::post('productsids');
            $action = RequestMethods::post('action');
            $cache = Registry::get('cache');

            if (empty($ids)) {
                echo 'Nějaký řádek musí být označen';
                return;
            }

            switch ($action) {
                case 'delete':
                    $products = App_Model_Product::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            $product->deleted = true;

                            if ($product->validate()) {
                                $product->save();
                            } else {
                                $errors[] = 'Nastala chyba během mazání' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Product ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_6;
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                case 'overprice':
                    $products = App_Model_Product::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));

                    $val = (int) RequestMethods::post('price');
                    $oper = RequestMethods::post('operation');

                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            if ($val > 0 && $val < 1) {
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->basicPrice;
                                $product->basicPrice = $product->basicPrice + ($oper == '+' ? 1 : -1) * ($product->basicPrice * $val);
                                $product->currentPrice = $product->basicPrice;
                                $product->weekendPrice = $product->basicPrice + 140;
                            } else {
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->basicPrice;
                                $product->basicPrice = $product->basicPrice + ($oper == '+' ? 1 : -1) * $val;
                                $product->currentPrice = $product->basicPrice;
                                $product->weekendPrice = $product->basicPrice + 140;
                            }

                            if ($product->validate()) {
                                $product->save();
                            } else {
                                $errors[] = 'Nastala chyba během přeceňování produktů ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('overprice success', 'Product ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_8;
                    } else {
                        Event::fire('admin.log', array('overprice fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                case 'activate':
                    $products = App_Model_Product::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            $product->active = true;

                            if ($product->validate()) {
                                $product->save();
                            } else {
                                $errors[] = 'Nastala chyba při aktivování ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Product ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_4;
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                case 'deactivate':
                    $products = App_Model_Product::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            $product->active = false;

                            if ($product->validate()) {
                                $product->save();
                            } else {
                                $errors[] = 'Nastala chyba během deaktivování' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Product ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_5;
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                default:
                    echo self::ERROR_MESSAGE_3;
                    break;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _member
     */
    public function load()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "pr.deleted = 0 AND pr.variantFor = 0 "
                    . "AND (pr.productCode='?' OR pr.productType='?' "
                    . "OR pr.currentPrice='?' OR pr.overlay='?' "
                    . "OR ca.title='?' OR pr.title LIKE '%%?%%')";

            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.active', 'pr.productType', 'pr.variantFor', 'pr.urlKey',
                                'pr.productCode', 'pr.discount', 'pr.discountFrom', 'pr.discountTo',
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb', 'pr.overlay'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', array('ca.title' => 'catTitle'))
                    ->wheresql($whereCond, $search, $search, $search, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $productQuery->order('pr.id', $dir);
                } elseif ($column == 2) {
                    $productQuery->order('pr.title', $dir);
                } elseif ($column == 3) {
                    $productQuery->order('pr.productType', $dir);
                } elseif ($column == 4) {
                    $productQuery->order('ca.title', $dir);
                } elseif ($column == 5) {
                    $productQuery->order('pr.productCode', $dir);
                } elseif ($column == 6) {
                    $productQuery->order('pr.currentPrice', $dir);
                }
            } else {
                $productQuery->order('pr.id', 'asc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $productQuery->limit($limit, $page + 1);
            $products = App_Model_Product::initialize($productQuery);

            $productCountQuery = App_Model_Product::getQuery(array('pr.id'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', array('ca.title' => 'catTitle'))
                    ->wheresql($whereCond, $search, $search, $search, $search, $search, $search);

            $productsCount = App_Model_Product::initialize($productCountQuery);
            unset($productCountQuery);
            $count = count($productsCount);
            unset($productsCount);
        } else {
            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.active', 'pr.productType', 'pr.variantFor', 'pr.urlKey',
                                'pr.productCode', 'pr.discount', 'pr.discountFrom', 'pr.discountTo',
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb', 'pr.overlay'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', array('ca.title' => 'catTitle'))
                    ->where('pr.deleted = ?', false)
                    ->where('pr.variantFor = ?', 0);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $productQuery->order('pr.id', $dir);
                } elseif ($column == 2) {
                    $productQuery->order('pr.title', $dir);
                } elseif ($column == 3) {
                    $productQuery->order('pr.productType', $dir);
                } elseif ($column == 4) {
                    $productQuery->order('ca.title', $dir);
                } elseif ($column == 5) {
                    $productQuery->order('pr.productCode', $dir);
                } elseif ($column == 6) {
                    $productQuery->order('pr.currentPrice', $dir);
                }
            } else {
                $productQuery->order('pr.id', 'DESC');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $productQuery->limit($limit, $page + 1);
            $products = App_Model_Product::initialize($productQuery);

            $productCountQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.active', 'pr.productType', 'pr.variantFor', 'pr.urlKey',
                                'pr.productCode', 'pr.discount', 'pr.discountFrom', 'pr.discountTo',
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb', 'pr.overlay'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', array('ca.title' => 'catTitle'))
                    ->where('pr.deleted = ?', false)
                    ->where('pr.variantFor = ?', 0);

            $productsCount = App_Model_Product::initialize($productCountQuery);
            unset($productCountQuery);
            $count = count($productsCount);
            unset($productsCount);
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $prodArr = array();
        if ($products !== null) {
            foreach ($products as $product) {
                $label = '';
                if ($product->getDiscount() != 0 && $product->getDiscountFrom() <= date('Y-m-d') && $product->getDiscountTo() >= date('Y-m-d')) {
                    $label .= "<span class='labelProduct labelProductBlue'>Ve slevě</span>";
                }

                if ($product->overlay !== '') {
                    $label .= "<span class='labelProduct labelProductGreen'>{$product->overlay}</span>";
                }

                if ($product->active) {
                    $label .= "<span class='labelProduct labelProductGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductGray'>Neaktivní</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $product->getId() . "\"";
                $arr [] = "\"<img alt='' src='" . $product->imgThumb . "' height='80px'/>\"";
                $arr [] = "\"" . $product->getTitle() . "\"";
                $arr [] = "\"" . ucfirst($product->getProductType()) . "\"";
                $arr [] = "\"" . $product->catTitle . "\"";
                $arr [] = "\"" . $product->getProductCode() . "\"";
                $arr [] = "\"" . $product->getCurrentPrice() . "\"";
                $arr [] = "\"" . $label . "\"";

                $tempStr = "\"<a href='/kostym/" . $product->getUrlKey() . "/' target=_blank class='btn btn3 btn_video' title='Live preview'></a>";
                $tempStr .= "<a href='/admin/product/edit/" . $product->id . "' class='btn btn3 btn_pencil' title='Edit'></a>";

                if ($this->isAdmin()) {
                    $tempStr .= "<a href='/admin/product/delete/" . $product->id . "' class='btn btn3 btn_trash' title='Delete'></a>";
                }
                $arr [] = $tempStr . "\"]";
                $prodArr[] = join(',', $arr);
            }

            $str .= join(',', $prodArr) . "]}";

            echo $str;
        } else {
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

}
