<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class Admin_Controller_Product extends Controller
{

    /**
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();

        $products = App_Model_Product::all(array('deleted = ?' => false));
        $view->set('products', $products);
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();

        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type' => 'size'));
        $view->set('sizes', $sizes);

        if (RequestMethods::post('submitAddProduct')) {
            $this->checkToken();
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            try {
                $data = $fileManager->upload('mainfile', 'product');
                $uploadedFile = ArrayMethods::toObject($data);
            } catch (Exception $ex) {
                $view->set('uploadErr', array('mainfile' => array($ex->getMessage())));
            }

            $product = new App_Model_Product(array(
                'sizeId' => RequestMethods::post('size'),
                'urlKey' => $urlKey,
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
                'mu' => RequestMethods::post('mu'),
                'weight' => RequestMethods::post('weight'),
                'isInAction' => RequestMethods::post('inaction'),
                'newFrom' => RequestMethods::post('newfrom'),
                'newTo' => RequestMethods::post('newto'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaKeaywords' => RequestMethods::post('metakeywords'),
                'metaDescription' => RequestMethods::post('metadescription', RequestMethods::post('description')),
                'rssFeedTitle' => RequestMethods::post('title'),
                'rssFeedDescription' => RequestMethods::post('description'),
                'rssFeedImg' => trim($uploadedFile->file->path, '.')
            ));

            if ($product->validate()) {
                $pid = $product->save();

                if (RequestMethods::post('uplMoreImages') == 1) {
                    try {
                        $data = $fileManager->upload('secondfile', 'product');
                    } catch (Exception $ex) {
                        $view->set('uploadErr', array('secondfile' => array($ex->getMessage())));
                    }

                    if (array_key_exists('files', $data)) {
                        $errors = $errors + $data['errors'];

                        foreach ($data['files'] as $i => $value) {
                            $uploadedFile = ArrayMethods::toObject($value);

                            $photo = new App_Model_Productphoto(array(
                                'productId' => $pid,
                                'imgMain' => trim($uploadedFile->file->path, '.'),
                                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                            ));

                            if ($photo->validate()) {
                                $aid = $photo->save();

                                Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' for product ' . $pid));
                            } else {
                                Event::fire('app.log', array('fail', 'Photo for product ' . $pid));
                                $errors[] = $photo->getErrors();
                            }
                        }

                        if (empty($errors)) {
                            $view->successMessage('Product and photos have been successfully saved');
                            self::redirect('/admin/product/detail/' . $pid);
                        } else {
                            $view->set('errors', $photo->getErrors());
                        }
                    } elseif (array_key_exists('file', $data)) {
                        $uploadedFile = ArrayMethods::toObject($data);

                        $photo = new App_Model_Productphoto(array(
                            'productId' => $pid,
                            'imgMain' => trim($uploadedFile->file->path, '.'),
                            'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                        ));

                        if ($photo->validate()) {
                            $aid = $photo->save();

                            Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' for product ' . $pid));
                            $view->successMessage('Product and photo have been successfully saved');
                            self::redirect('/admin/product/detail/' . $pid);
                        } else {
                            Event::fire('app.log', array('fail', 'Photo for product ' . $pid));
                            $view->set('photo', $photo)
                                    ->set('errors', $photo->getErrors());
                        }
                    }
                } else {
                    Event::fire('app.log', array('success', 'Product id: ' . $pid));
                    $view->successMessage('Product has been successfully saved');
                    self::redirect('/admin/product/detail/' . $pid);
                }
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('product', $product)
                        ->set('errors', $product->getErrors());
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $product = App_Model_Product::first(array('deleted = ?' => false, 'id = ?' => (int)$id));
        
        if($product === null){
            $view->warningMessage('Product not found');
            self::redirect('/admin/product/');
        }
        
        $sizes = App_Model_Codebook::all(array('active = ?' => true, 'type' => 'size'));
        
        $view->set('product', $product)
                ->set('sizes', $sizes);

        if (RequestMethods::post('submitEditProduct')) {
            $this->checkToken();
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

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
                        $data = $fileManager->upload('mainfile', 'product');
                        $uploadedFile = ArrayMethods::toObject($data);
                    } catch (Exception $ex) {
                        $view->set('uploadErr', array('mainfile' => array($ex->getMessage())));
                    }
                    $imgMain = trim($uploadedFile->file->path, '.');
                    $imgThumb = trim($uploadedFile->thumb->path, '.');
                } catch (Exception $ex) {
                    $errors['logo'] = $ex->getMessage();
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
            $product->mu = RequestMethods::post('mu');
            $product->weight = RequestMethods::post('weight');
            $product->isInAction = RequestMethods::post('inaction');
            $product->newFrom = RequestMethods::post('newfrom');
            $product->newTo = RequestMethods::post('newto');
            $product->imgMain = $imgMain;
            $product->imgThumb = $imgThumb;
            $product->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $product->metaKeaywords = RequestMethods::post('metakeywords');
            $product->metaDescription = RequestMethods::post('metadescription', RequestMethods::post('description'));
            $product->rssFeedTitle = RequestMethods::post('title');
            $product->rssFeedDescription = RequestMethods::post('description');
            $product->rssFeedImg = trim($uploadedFile->file->path, '.');

            if ($product->validate()) {
                $product->save();

                if (RequestMethods::post('uplMoreImages') == 1) {
                    try {
                        $data = $fileManager->upload('secondfile', 'product');
                    } catch (Exception $ex) {
                        $view->set('uploadErr', array('secondfile' => array($ex->getMessage())));
                    }

                    if (array_key_exists('files', $data)) {
                        $errors = $errors + $data['errors'];

                        foreach ($data['files'] as $i => $value) {
                            $uploadedFile = ArrayMethods::toObject($value);

                            $photo = new App_Model_Productphoto(array(
                                'productId' => $product->getId(),
                                'imgMain' => trim($uploadedFile->file->path, '.'),
                                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                            ));

                            if ($photo->validate()) {
                                $aid = $photo->save();

                                Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' for product ' . $product->getId()));
                            } else {
                                Event::fire('app.log', array('fail', 'Photo for product ' . $product->getId()));
                                $errors[] = $photo->getErrors();
                            }
                        }

                        if (empty($errors)) {
                            $view->successMessage('Product and photos have been successfully saved');
                            self::redirect('/admin/product/detail/' . $product->getId());
                        } else {
                            $view->set('errors', $photo->getErrors());
                        }
                    } elseif (array_key_exists('file', $data)) {
                        $uploadedFile = ArrayMethods::toObject($data);

                        $photo = new App_Model_Productphoto(array(
                            'productId' => $product->getId(),
                            'imgMain' => trim($uploadedFile->file->path, '.'),
                            'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                        ));

                        if ($photo->validate()) {
                            $aid = $photo->save();

                            Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' for product ' . $product->getId()));
                            $view->successMessage('Product and photo have been successfully saved');
                            self::redirect('/admin/product/detail/' . $product->getId());
                        } else {
                            Event::fire('app.log', array('fail', 'Photo for product ' . $product->getId()));
                            $view->set('photo', $photo)
                                    ->set('errors', $photo->getErrors());
                        }
                    }
                } else {
                    Event::fire('app.log', array('success', 'Product id: ' . $product->getId()));
                    $view->successMessage('Product has been successfully saved');
                    self::redirect('/admin/product/detail/' . $product->getId());
                }
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('product', $product)
                        ->set('errors', $product->getErrors());
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function detail($id)
    {
        
    }

    /**
     * @before _secured, _member
     */
    public function processRecommend($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        if (RequestMethods::post('performRecomendedAction')) {
            $this->checkToken();
            
            $errors = array();
            $uids = RequestMethods::post('productsids');

            $status = App_Model_Recommendedproduct::deleteAll(array('productId = ?' => $id));

            if ($status != -1) {
                if ($uids[0] == '') {
                    self::redirect('/admin/product/');
                }
                
                $recomIds = array();
                foreach ($uids as $recomId) {
                    $recomProduct = new App_Model_Recommendedproduct(array(
                        'recommendedId' => $recomId,
                        'productId' => $id
                    ));

                    if ($recomProduct->validate()) {
                        $recomProduct->save();
                        $recomIds[] = $recomId;
                    } else {
                        $errors[] = $recomProduct->getErrors();
                    }
                }

                if (empty($errors)) {
                    $view->successMessage('Product has been successfully updated');
                    Event::fire('app.log', array('success', 'Recommended products: ' . join(', ', $recomIds). ' to product: '.$id));
                    self::redirect('/product/detail/' . $id);
                } else {
                    Event::fire('app.log', array('fail', 'Recommended products: ' . join(', ', $recomIds). ' to product: '.$id));
                    $view->errorMessage('An error occured while saving recommended products');
                }
            }
        }
    }
    
    /**
     * @before _secured, _member
     */
    public function deleteProductPhoto($id)
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        
        if($this->checkTokenAjax()){
            $photo = App_Model_Productphoto::first(array('id = ?' => (int)$id));
            
            if($photo === null){
                echo 'Product photo not found';
            }else{
                if($photo->delete()){
                    @unlink($photo->getUnlinkPath());
                    @unlink($photo->getUnlinkThumbPath());
                
                    Event::fire('app.log', array('success', 'Photo id: ' . $photo->getId(). ' for product '. $photo->getProductId()));
                    echo 'success';
                }else{
                    echo 'An error occured while deleting the photo';
                }
                
            }
        }else{
            echo 'Security token is not valid';
        }
    }
    
    /**
     * @before _secured, _member
     */
    public function deleteProductMainPhoto($id)
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        
        if($this->checkTokenAjax()){
            $product = App_Model_Product::first(array('deleted = ?' => false, 'id = ?' => (int)$id));
            
            if($product === null){
                echo 'Product not found';
            }else{
                @unlink($product->getUnlinkPath());
                @unlink($product->getUnlinkThumbPath());
                $product->imgMain = '';
                $product->imgThumb = '';
                
                if($product->validate()){
                    $product->save();
                
                    Event::fire('app.log', array('success', 'Product id: ' . $product->getId()));
                    echo 'success';
                }else{
                    echo 'An error occured while deleting the photo';
                }
                
            }
        }else{
            echo 'Security token is not valid';
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
                            }else{
                                $errors[] = 'An error occured while activating ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Products have been deleted successfully');
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
                    
                    if (NULL !== $products) {
                        foreach ($products as $product) {
                            if($val > 0 && $val < 1){
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->currentPrice;
                                $product->currentPrice = $product->currentPrice + ($product->currentPrice * $val);
                            }else{
                                $product->priceOldTwo = $product->priceOldOne;
                                $product->priceOldOne = $product->currentPrice;
                                $product->currentPrice = $product->currentPrice + $val;
                            }
                            
                            if ($product->validate()) {
                                $product->save();
                            }else{
                                $errors[] = 'An error occured while activating ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('overprice success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Products have been saved successfully');
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
                            }else{
                                $errors[] = 'An error occured while activating ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Products have been activated successfully');
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
                            }else{
                                $errors[] = 'An error occured while deactivating ' . $product->getTitle();
                                $errorsIds [] = $product->getId();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Product ids: ' . join(',', $ids)));
                        $view->successMessage('Products have been deactivated successfully');
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
