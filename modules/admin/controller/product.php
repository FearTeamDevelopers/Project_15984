<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use THCFrame\Core\ArrayMethods;
use THCFrame\Filesystem\FileManager;

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
    private function createMainProduct($configurable = false)
    {
        $urlKey = strtolower(
                str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

        if (!$this->checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('Produkt s tímto názvem již existuje');
        }

        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        $uploadTo = substr($urlKey, 0, 3);

        try {
            $data = $fileManager->upload('mainfile', 'product/' . $uploadTo);
            $uploadedFile = ArrayMethods::toObject($data);
        } catch (Exception $ex) {
            $this->_errors['mainfile'] = array($ex->getMessage());
        }

        if ($configurable) {
            $product = new App_Model_Product(array(
                'sizeId' => 0,
                'urlKey' => $urlKey,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => RequestMethods::post('basicprice'),
                'regularPrice' => RequestMethods::post('regularprice'),
                'currentPrice' => RequestMethods::post('currentprice'),
                'discount' => RequestMethods::post('discount'),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight'),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', RequestMethods::post('description')),
                'rssFeedTitle' => RequestMethods::post('title'),
                'rssFeedDescription' => RequestMethods::post('description'),
                'rssFeedImg' => trim($uploadedFile->file->path, '.')
            ));
        } else {
            $product = new App_Model_Product(array(
                'sizeId' => RequestMethods::post('size'),
                'urlKey' => $urlKey,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => RequestMethods::post('basicprice'),
                'regularPrice' => RequestMethods::post('regularprice'),
                'currentPrice' => RequestMethods::post('currentprice'),
                'discount' => RequestMethods::post('discount'),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight'),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', RequestMethods::post('description')),
                'rssFeedTitle' => RequestMethods::post('title'),
                'rssFeedDescription' => RequestMethods::post('description'),
                'rssFeedImg' => trim($uploadedFile->file->path, '.')
            ));
        }

        if (empty($this->_errors) && $product->validate()) {
            $pid = $product->save();
            Event::fire('app.log', array('success', 'Product id: ' . $pid));
        } else {
            Event::fire('app.log', array('fail'));
            $this->_errors = $this->_errors + $product->getErrors();
        }

        return $product;
    }

    /**
     * 
     * @param App_Model_Product $productConf
     * @return boolean
     */
    private function createVariants(App_Model_Product $productConf)
    {
        $sizeVariantsArr = RequestMethods::post('size');

        if (!is_array($sizeVariantsArr)) {
            $this->_errors['sizeId'] = array('Musí být vybráno více velikostí');
            return false;
        }

        foreach ($sizeVariantsArr as $size) {
            $urlKey = strtolower(
                            str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title')))) . '-' . $size;

            if (!$this->checkUrlKey($urlKey)) {
                $this->_errors['title'] = array('Produkt s tímto názvem již existuje');
            }

            $product = new App_Model_Product(array(
                'sizeId' => $size,
                'urlKey' => $urlKey,
                'productType' => 3,
                'variantFor' => $productConf->getId(),
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => 0,
                'regularPrice' => 0,
                'currentPrice' => RequestMethods::post('currentprice'),
                'discount' => 0,
                'discountFrom' => '',
                'discountTo' => '',
                'eanCode' => '',
                'weight' => 0,
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

            if (empty($this->_errors) && $product->validate()) {
                $pid = $product->save();
                Event::fire('app.log', array('success', 'Product variant id: ' . $pid));
            } else {
                Event::fire('app.log', array('fail'));
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
    private function createCategoryRecords($productId, $categoryArr = array(), $update = false)
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
    private function createRecommendedProductsRecords($productId, $recommendedArr = array(), $update = false)
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
    private function uploadAdditionalPhotos($productId, $uploadTo)
    {
        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        try {
            $data = $fileManager->upload('secondfile', 'product/' . $uploadTo);
        } catch (Exception $ex) {
            $this->_errors['secondfile'] = array($ex->getMessage());
        }

        if (empty($data['errors']) && empty($this->_errors['secondfile'])) {
            foreach ($data['files'] as $i => $value) {
                $uploadedFile = ArrayMethods::toObject($value);

                $photo = new App_Model_ProductPhoto(array(
                    'productId' => (int) $productId,
                    'imgMain' => trim($uploadedFile->file->path, '.'),
                    'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                ));

                if ($photo->validate()) {
                    $aid = $photo->save();

                    Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' for product ' . $productId));
                } else {
                    Event::fire('app.log', array('fail', 'Photo for product ' . $productId));
                    $this->_errors['secondfile'][] = $photo->getErrors();
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
    private function checkUrlKey($key)
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
        $view = $this->getActionView();

        $products = App_Model_Product::all(array('deleted = ?' => false, 'variantFor = ?' => 0));
        $view->set('products', $products);
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();

        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type = ?' => 'size'));
        $categories = App_Model_Category::fetchAllCategories();
        $allProducts = App_Model_Product::all(
                        array('active = ?' => true, 'deleted = ?' => false, 'productType IN ?' => array(1, 2)));

        $view->set('sizes', $sizes)
                ->set('allproducts', $allProducts)
                ->set('categories', $categories);

        if (RequestMethods::post('submitAddProduct')) {
            $this->checkToken();

            $categoryArr = RequestMethods::post('rcat');
            if (empty($categoryArr)) {
                $this->_errors['category'] = array('Musí být vybrána minimálně jedna kategorie');
            }

            if (RequestMethods::post('producttype') == 2) {
                $product = $this->createMainProduct(true);
                if (empty($this->_errors)) {
                    $this->createVariants($product);
                }
            } else {
                $product = $this->createMainProduct();
            }

            if (empty($this->_errors)) {
                /* category */
                $this->createCategoryRecords($product->getId(), $categoryArr);

                /* recommended products */
                $recomProducts = RequestMethods::post('recomproductids');
                if (!empty($recomProducts)) {
                    $this->createRecommendedProductsRecords($product->getId(), $recomProducts);
                }

                /* additional photos */
                if (RequestMethods::post('uplMoreImages') == 1) {
                    $uploadTo = substr($product, 0, 3);
                    $this->uploadAdditionalPhotos($product->getId(), $uploadTo);
                }

                if (empty($this->_errors)) {
                    $view->successMessage('Produkt byl úspěšně uložen');
                    self::redirect('/admin/product/');
                } else {
                    $view->set('product', $product)
                            ->set('errors', $this->_errors);
                }
            } else {
                $view->set('product', $product)
                        ->set('errors', $this->_errors);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $product = App_Model_Product::fetchProductById($id);

        if ($product === null) {
            $view->warningMessage('Produkt nenalezen');
            self::redirect('/admin/product/');
        }

        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type = ?' => 'size'));
        $categories = App_Model_Category::fetchAllCategories();
        $allProducts = App_Model_Product::all(
                        array('active = ?' => true, 'deleted = ?' => false, 'productType IN ?' => array(1, 2)));

        if (!empty($product->inCategories)) {
            $productCategoryIds = array();
            foreach ($product->inCategories as $prcat) {
                $productCategoryIds[] = $prcat->getCategoryId();
            }
        }

        if (!empty($product->recommendedProducts)) {
            $recomProductIds = array();
            foreach ($product->recommendedProducts as $recprod) {
                $recomProductIds[] = $recprod->getRecommendedId();
            }
        }

        $view->set('product', $product)
                ->set('categories', $categories)
                ->set('allproducts', $allProducts)
                ->set('productcategoryids', $productCategoryIds)
                ->set('recommendedproductids', $recomProductIds)
                ->set('sizes', $sizes);

        if (RequestMethods::post('submitEditProduct')) {
            $this->checkToken();
            $errors = array();

            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));


            if ($product->getUrlKey() !== $urlKey && !$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Produkt s tímto názvem již existuje');
            }

            $uploadTo = substr($product->getUrlKey(), 0, 3);
            if ($product->imgMain == '') {
                try {
                    $fileManager = new FileManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                        'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                        'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                    ));

                    try {
                        $data = $fileManager->upload('mainfile', 'product/' . $uploadTo);
                        $uploadedFile = ArrayMethods::toObject($data);
                    } catch (Exception $ex) {
                        $errors['mainfile'] = array($ex->getMessage());
                    }
                    $imgMain = trim($uploadedFile->file->path, '.');
                    $imgThumb = trim($uploadedFile->thumb->path, '.');
                } catch (Exception $ex) {
                    $errors['mainfile'] = $ex->getMessage();
                }
            } else {
                $imgMain = $product->imgMain;
                $imgThumb = $product->imgThumb;
            }

            $product->sizeId = RequestMethods::post('size');
            $product->urlKey = $urlKey;
            $product->productCode = RequestMethods::post('productcode');
            $product->title = RequestMethods::post('title');
            $product->description = RequestMethods::post('description');
            $product->basicPrice = RequestMethods::post('basicprice');
            $product->regularPrice = RequestMethods::post('regularprice');
            $product->currentPrice = RequestMethods::post('currentprice');
            $product->discount = RequestMethods::post('discount');
            $product->discountFrom = RequestMethods::post('discountfrom');
            $product->discountTo = RequestMethods::post('discountto');
            $product->eanCode = RequestMethods::post('eancode');
            $product->weight = RequestMethods::post('weight');
            $product->isInAction = RequestMethods::post('inaction');
            $product->newFrom = RequestMethods::post('newfrom');
            $product->newTo = RequestMethods::post('newto');
            $product->imgMain = $imgMain;
            $product->imgThumb = $imgThumb;
            $product->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $product->metaKeywords = RequestMethods::post('metakeywords');
            $product->metaDescription = RequestMethods::post('metadescription', RequestMethods::post('description'));
            $product->rssFeedTitle = RequestMethods::post('title');
            $product->rssFeedDescription = RequestMethods::post('description');
            $product->rssFeedImg = $imgMain;

            $categoryArr = RequestMethods::post('rcat');
            if (empty($categoryArr)) {
                $errors['category'] = array('Musí být vybrána minimálně jedna kategorie');
            }

            if (empty($errors) && $product->validate()) {
                $product->save();

                /* category */
                $this->createCategoryRecords($product->getId(), $categoryArr, true);

                /* recommended products */
                $recomProducts = RequestMethods::post('recomproductids');
                if (!empty($recomProducts)) {
                    $this->createRecommendedProductsRecords($product->getId(), $recomProducts, true);
                }

                if (RequestMethods::post('uplMoreImages') == 1) {
                    $this->uploadAdditionalPhotos($product->getId(), $uploadTo);
                }

                if (empty($this->_errors)) {
                    Event::fire('app.log', array('success', 'Product id: ' . $product->getId()));
                    $view->successMessage('Produkt byl úspěšně uložen');
                    self::redirect('/admin/product/');
                } else {
                    Event::fire('app.log', array('fail'));
                    $view->set('product', $product)
                            ->set('errors', $this->_errors + $product->getErrors());
                }
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('product', $product)
                        ->set('errors', $errors + $this->_errors + $product->getErrors());
            }
        }
    }

    /**
     * 
     * @param type $id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $product = App_Model_Product::first(
                            array('id = ?' => (int) $id));

            if (NULL === $product) {
                echo 'Produkt nenalezen';
            } else {
                $product->deleted = true;
                
                if ($product->validate()) {
                    $product->save();
                    
                    Event::fire('admin.log', array('success', 'Product id: ' . $id));
                    echo 'úspěch';
                } else {
                    Event::fire('admin.log', array('fail', 'Product id: ' . $id));
                    echo 'Nastala neznámá chyba';
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
        }
    }

    /**
     * 
     * @param type $photoId
     */
    public function changePhotoStatus($photoId)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $photo = App_Model_ProductPhoto::first(array('id = ?' => (int) $photoId));

        if (null === $photo) {
            echo 'Fotka nenalezena';
        } else {
            if (!$photo->active) {
                $photo->active = true;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                    echo 'aktivní';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            } elseif ($photo->active) {
                $photo->active = false;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                    echo 'neaktivní';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function deleteProductPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $photo = App_Model_ProductPhoto::first(array('id = ?' => (int) $id));

            if ($photo === null) {
                echo 'Fotka produktu nenalezena';
            } else {
                if ($photo->delete()) {
                    @unlink($photo->getUnlinkPath());
                    @unlink($photo->getUnlinkThumbPath());

                    Event::fire('app.log', array('success', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo 'úspěch';
                } else {
                    Event::fire('app.log', array('fail', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo 'Nastala chyba během mazání fotky';
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
        }
    }

    /**
     * @before _secured, _member
     */
    public function deleteProductMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $product = App_Model_Product::first(array('deleted = ?' => false, 'id = ?' => (int) $id));

            if ($product === null) {
                echo 'Product not found';
            } else {
                $unlinkMainImg = $product->getUnlinkPath();
                $unlinkThumbImg = $product->getUnlinkThumbPath();
                $product->imgMain = '';
                $product->imgThumb = '';

                if ($product->validate()) {
                    $product->save();
                    @unlink($unlinkMainImg);
                    @unlink($unlinkThumbImg);

                    Event::fire('app.log', array('success', 'Product id: ' . $product->getId()));
                    echo 'úspěch';
                } else {
                    Event::fire('app.log', array('fail', 'Product id: ' . $product->getId()));
                    echo 'Nastala chyba během mazání fotky';
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
        }
    }

    /**
     * @before _secured, _member
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();
        $errorsIds = array();

        if (RequestMethods::post('performProductAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('productsids');
            $action = RequestMethods::post('action');

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
                                $errors[] = 'Nastala chyba během aktivování' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Produkty byly úspěšně smazány');
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/product/');
                    break;

                case 'overprice':
                    $products = App_Model_Product::all(array(
                                'deleted = ?' => false,
                                'id IN ?' => $ids
                    ));

                    $val = RequestMethods::post('price');
                    $oper = RequestMethods::post('operation');

                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            if ($val > 0 && $val < 1) {
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->currentPrice;
                                $product->currentPrice = $product->currentPrice + ($oper == '+' ? 1 : -1) * ($product->currentPrice * $val);
                            } else {
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->currentPrice;
                                $product->currentPrice = $product->currentPrice + ($oper == '+' ? 1 : -1) * $val;
                            }

                            if ($product->validate()) {
                                $product->save();
                            } else {
                                $errors[] = 'Nastala chyba během aktivování' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('overprice success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Produkty byly úspěšně smazány');
                    } else {
                        Event::fire('admin.log', array('overprice fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/product/');
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
                        $view->successMessage('Produkty byly úspěšně aktivovány');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/product/');
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
                        $view->successMessage('Produkty byly úspěšně deaktivovány');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Product ids: ' . join(',', $errorsIds)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/product/');
                    break;
                default:
                    self::redirect('/admin/product/');
                    break;
            }
        }
    }

}
