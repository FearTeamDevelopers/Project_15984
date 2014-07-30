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
        $urlKey = $urlKeyCh = strtolower(
                str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

        for($i = 1; $i <= 50; $i++){
            if ($this->checkUrlKey($urlKeyCh)) {
                break;
            }else{
                $urlKeyCh = $urlKey.'-'.$i;
            }
            
            if($i == 50){
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

        $uploadTo = trim(substr(str_replace('.','',$urlKey), 0, 3));

        try {
            $data = $fileManager->upload('mainfile', 'product/' . $uploadTo);
            $uploadedFile = ArrayMethods::toObject($data);
        } catch (Exception $ex) {
            $this->_errors['mainfile'] = array($ex->getMessage());
        }

        if ($configurable) {
            $title = RequestMethods::post('title');
            $desc =  RequestMethods::post('description');
            
            $product = new App_Model_Product(array(
                'sizeId' => 0,
                'urlKey' => $urlKeyCh,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'basicPrice' => RequestMethods::post('basicprice'),
                'regularPrice' => RequestMethods::post('regularprice'),
                'currentPrice' => RequestMethods::post('currentprice'),
                'quantity' => RequestMethods::post('quantity', 1),
                'discount' => RequestMethods::post('discount'),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight', 1),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'hasGroupPhoto' => RequestMethods::post('photoType'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'metaTitle' => RequestMethods::post('metatitle', $title),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', $desc),
                'rssFeedTitle' => $title,
                'rssFeedDescription' => $desc,
                'rssFeedImg' => trim($uploadedFile->file->path, '.')
            ));
        } else {
            $title = RequestMethods::post('title');
            $desc =  RequestMethods::post('description');
            
            $product = new App_Model_Product(array(
                'sizeId' => RequestMethods::post('size'),
                'urlKey' => $urlKeyCh,
                'productType' => RequestMethods::post('producttype'),
                'variantFor' => 0,
                'productCode' => RequestMethods::post('productcode'),
                'title' => $title,
                'description' => $desc,
                'basicPrice' => RequestMethods::post('basicprice'),
                'regularPrice' => RequestMethods::post('regularprice'),
                'currentPrice' => RequestMethods::post('currentprice'),
                'quantity' => RequestMethods::post('quantity', 1),
                'discount' => RequestMethods::post('discount'),
                'discountFrom' => RequestMethods::post('discountfrom'),
                'discountTo' => RequestMethods::post('discountto'),
                'eanCode' => RequestMethods::post('eancode'),
                'weight' => RequestMethods::post('weight', 1),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'hasGroupPhoto' => RequestMethods::post('photoType'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'metaTitle' => RequestMethods::post('metatitle', $title),
                'metaKeywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', $desc),
                'rssFeedTitle' => $title,
                'rssFeedDescription' => $desc,
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
            $urlKey = $urlKeyCh = strtolower(
                            str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title')))) . '-' . $size;

            for ($i = 1; $i <= 50; $i++) {
                if ($this->checkUrlKey($urlKeyCh)) {
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
                'basicPrice' => 0,
                'regularPrice' => 0,
                'currentPrice' => RequestMethods::post('currentprice'),
                'quantity' => RequestMethods::post('quantity-' . $size),
                'discount' => 0,
                'discountFrom' => '',
                'discountTo' => '',
                'eanCode' => '',
                'weight' => 0,
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
                ->set('categories', $categories);
        
        if (RequestMethods::post('submitAddProduct')) {
            $this->checkToken();

            $categoryArr = RequestMethods::post('rcat');
            if (empty($categoryArr)) {
                $this->_errors['category'] = array('Musí být vybrána minimálně jedna kategorie');
            }

            if (RequestMethods::post('producttype') == 's variantami') {
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
                    $uploadTo = trim(substr(str_replace('.','',$product->getUrlKey()), 0, 3));
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
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $product = App_Model_Product::fetchProductById($id);

        if ($product === null) {
            $view->warningMessage('Produkt nebyl nalezen');
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

        $productRecomm = $product->recommendedProducts;
        $recomProductIds = array();
        if (!empty($productRecomm)) {
            foreach ($productRecomm as $recprod) {
                $recomProductIds[] = $recprod->getRecommendedId();
            }
        }

        if (!empty($recomProductIds)) {
            $recomproducts = App_Model_Product::all(array(
                        'deleted = ?' => false,
                        'active = ?' => true,
                        'id IN ?' => $recomProductIds
            ));
        } else {
            $recomproducts = array();
        }

        $view->set('product', $product)
                ->set('categories', $categories)
                ->set('recomproducts', $recomproducts)
                ->set('productcategoryids', $productCategoryIds)
                ->set('sizes', $sizes);

        if (RequestMethods::post('submitEditProduct')) {
            $this->checkToken();

            if ($product->getProductType() == 'varianta') {
                $product->sizeId = RequestMethods::post('size');
                $product->productCode = RequestMethods::post('productcode');
                $product->currentPrice = RequestMethods::post('currentprice');
                $product->quantity = RequestMethods::post('quantity', 1);
                $product->eanCode = RequestMethods::post('eancode');
                $product->weight = RequestMethods::post('weight', 1);

                if ($product->validate()) {
                    $product->save();

                    Event::fire('app.log', array('success', 'Product id: ' . $product->getId()));
                    $view->successMessage('Produkt byl úspěšně uložen');
                    self::redirect('/admin/product/edit/' . $product->getVariantFor());
                } else {
                    Event::fire('app.log', array('fail', 'Product id: ' . $product->getId()));
                    $view->set('product', $product)
                            ->set('errors', $product->getErrors());
                }
            }else{
                $urlKey = $urlKeyCh = strtolower(
                        str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('urlkey'))));

                if ($product->getUrlKey() !== $urlKey) {
                    for ($i = 1; $i <= 50; $i++) {
                        if ($this->checkUrlKey($urlKeyCh)) {
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

                $uploadTo = trim(substr(str_replace('.','',$product->getUrlKey()), 0, 3));
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
                $product->urlKey = $urlKeyCh;
                $product->productCode = RequestMethods::post('productcode');
                $product->title = RequestMethods::post('title');
                $product->description = RequestMethods::post('description');
                $product->basicPrice = RequestMethods::post('basicprice');
                $product->regularPrice = RequestMethods::post('regularprice');
                $product->currentPrice = RequestMethods::post('currentprice');
                $product->quantity = RequestMethods::post('quantity', 1);
                $product->discount = RequestMethods::post('discount');
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
                        Event::fire('app.log', array('fail','Product id: ' . $product->getId()));
                        $view->set('product', $product)
                                ->set('errors', $this->_errors + $product->getErrors());
                    }
                } else {
                    Event::fire('app.log', array('fail', 'Product id: ' . $product->getId()));
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
            $this->checkToken();
            $product->deleted = true;

            if ($product->validate()) {
                $product->save();

                Event::fire('admin.log', array('success', 'Product id: ' . $id));
                $view->successMessage('Produkt byl úspěšně smazán');
                self::redirect('/admin/product/');
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $id));
                $view->errorMessage('Nastala neznámá chyba');
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

        if ($this->checkTokenAjax()) {
            $product = App_Model_Product::first(
                            array('id = ?' => (int) $id, 'deleted = ?' => true));

            if (NULL === $product) {
                echo 'Produkt nebyl nalezen';
                return;
            }

            $product->deleted = false;

            if ($product->validate()) {
                $product->save();

                Event::fire('admin.log', array('success', 'Product id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $id));
                echo 'Nastala neznámá chyba';
            }
        } else {
            echo 'Bezpečnostní token není validní';
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

        if ($this->checkTokenAjax()) {
            $product = App_Model_RecommendedProduct::first(array(
                        'productId' => (int) $productId,
                        'recommendedId = ?' => (int) $recommendedId
            ));

            if (NULL === $product) {
                echo 'Produkt nebyl nalezen';
            } else {
                if ($product->delete()) {
                    Event::fire('admin.log', array('success', 'Recommended product ' . $recommendedId . ' for product ' . $productId));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Recommended product ' . $recommendedId . ' for product ' . $productId));
                    echo 'Nastala neznámá chyba';
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
        }
    }

    /**
     * @before _secured, _admin
     * @param type $productId
     */
    public function addRecommended($productId)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        $view->set('productid', $productId);

        if (RequestMethods::post('submitSaveRecommended')) {
            $this->checkToken();

            $recomprod = App_Model_Product::first(array(
                        'deleted = ?' => false,
                        'id = ?' => RequestMethods::post('recomproductid')
            ));

            if ($recomprod === null) {
                $view->warningMessage('Produkt nebyl nalezen');
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
                $view->successMessage('Doporučený produkt byl úspěšně přidán');
                self::redirect('/admin/product/edit/' . $productId . '#recommended');
            } else {
                Event::fire('admin.log', array('fail', 'Product id: ' . $productId));
                $view->errorMessage('Nastala chyba při ukládání doporučeného produktu');
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
            echo 'Fotografie nebyla nalezena';
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

        if ($this->checkTokenAjax()) {
            $photo = App_Model_ProductPhoto::first(array('id = ?' => (int) $id));

            if ($photo === null) {
                echo 'Fotografie nebyla nalezena';
            } else {
                if ($photo->delete()) {
                    @unlink($photo->getUnlinkPath());
                    @unlink($photo->getUnlinkThumbPath());

                    Event::fire('app.log', array('success', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo 'success';
                } else {
                    Event::fire('app.log', array('fail', 'Photo id: ' . $photo->getId() . ' for product ' . $photo->getProductId()));
                    echo 'Nastala chyba během mazání fotografie';
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteProductMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $product = App_Model_Product::first(array('deleted = ?' => false, 'id = ?' => (int) $id));

            if ($product === null) {
                echo 'Product nebyl nalezen';
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
                    echo 'success';
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
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;
        $errors = array();
        $errorsIds = array();

        $this->checkToken();
        $ids = RequestMethods::post('productsids');
        $action = RequestMethods::post('action');

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
                    echo 'Produkty byly úspěšně smazány';
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
                            $errors[] = 'Nastala chyba během přeceňování produktů ' . $product->getTitle();
                            $errorsIds [] = $product->getId();
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('overprice success', 'Product ids: ' . join(',', $ids)));
                    echo 'Produkty byly úspěšně přeceněny';
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
                    echo 'Produkty byly úspěšně aktivovány';
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
                    echo 'Produkty byly úspěšně deaktivovány';
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Product ids: ' . join(',', $errorsIds)));
                    $message = join('<br/>', $errors);
                    echo $message;
                }

                break;
            default:
                echo 'Neplatná akce';
                break;
        }
    }

    /**
     * @before _secured, _member
     */
    public function load()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $page = RequestMethods::post('page');
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "pr.deleted = 0 AND pr.variantFor = 0 "
                    . "AND (pr.productCode='?' OR pr.productType='?' "
                    . "OR pr.currentPrice='?' "
                    . "OR ca.title='?' OR pr.title LIKE '%%?%%')";

            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.productType', 'pr.variantFor', 'pr.urlKey', 'pr.productCode', 
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', 
                            array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', 
                            array('ca.title' => 'catTitle'))
                    ->wheresql($whereCond, $search, $search, $search, $search, $search);

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
                    $productQuery->order('pc.categoryId', $dir);
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
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', 
                            array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', 
                            array('ca.title' => 'catTitle'))
                    ->wheresql($whereCond);
            
            $productsCount = App_Model_Product::initialize($productCountQuery);
            unset($productCountQuery);

            $count = count($productsCount);
            unset($productsCount);
        } else {
            $productQuery = App_Model_Product::getQuery(
                            array('pr.id', 'pr.productType', 'pr.variantFor', 'pr.urlKey', 'pr.productCode', 
                                'pr.title', 'pr.currentPrice', 'pr.imgMain', 'pr.imgThumb'))
                    ->join('tb_productcategory', 'pr.id = pc.productId', 'pc', 
                            array('productId', 'categoryId'))
                    ->join('tb_category', 'pc.categoryId = ca.id', 'ca', 
                            array('ca.title' => 'catTitle'))
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
                    $productQuery->order('pc.categoryId', $dir);
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
            $count = App_Model_Product::count(array('deleted = ?' => false, 'variantFor = ?' => 0));
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $prodArr = array();
        if ($products !== null) {
            foreach ($products as $product) {
                $arr = array();
                $arr [] = "[ \"" . $product->getId() . "\"";
                $arr [] = "\"<img alt='' src='" . $product->imgThumb . "' height='80px'/>\"";
                $arr [] = "\"" . $product->getTitle() . "\"";
                $arr [] = "\"" . ucfirst($product->getProductType()) . "\"";
                $arr [] = "\"" . $product->catTitle . "\"";
                $arr [] = "\"" . $product->getProductCode() . "\"";
                $arr [] = "\"" . $product->getCurrentPrice() . "\"";

                $tempStr = "\"<a href='/kostym/" . $product->getUrlKey() . "/' class='btn btn3 btn_video' title='Live preview'></a>";
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
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

}
