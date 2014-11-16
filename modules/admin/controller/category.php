<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Category extends Controller
{
    
    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Category::first(array('urlKey = ?' => $key));

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
        $categories = App_Model_Category::all(array('parentId = ?' => 0));
        $view->set('categories', $categories);

        if (RequestMethods::post('submitSaveCategoryRank')) {
            $ranks = RequestMethods::post('rank');

            foreach ($ranks as $key => $value) {
                $cat = App_Model_Category::first(array('id = ?' => (int) $key));
                $cat->rank = $value;
                $cat->save();
            }
            Event::fire('admin.log', array('success', 'Category update rank: ' . $cat->getId()));
            $view->successMessage(self::SUCCESS_MESSAGE_8);
            self::redirect('/admin/category/');
        }
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();
        $categories = App_Model_Category::all(array('active = ?' => true));

        $view->set('categories', $categories)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddCategory')) {
            if($this->checkCSRFToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/category/');
            }
            
            $cache = Registry::get('cache');
            $errors = array();

            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Kategorie s tímto názvem již existuje');
            }

            $category = new App_Model_Category(array(
                'parentId' => RequestMethods::post('parent', 0),
                'title' => RequestMethods::post('title'),
                'rank' => RequestMethods::post('rank', 1),
                'isGrouped' => RequestMethods::post('group', 0),
                'isSelable' => RequestMethods::post('selable', 0),
                'isSeparator' => RequestMethods::post('separator', 0),
                'urlKey' => $urlKey,
                'mainText' => RequestMethods::post('text'),
                'metaTitle' => RequestMethods::post('metaTitle'),
                'metaKeywords' => RequestMethods::post('metaKeywords'),
                'metaDescription' => RequestMethods::post('metaDescription')
            ));

            if (empty($errors) && $category->validate()) {
                $cid = $category->save();

                Event::fire('admin.log', array('success', 'Category id: ' . $cid));
                $view->successMessage('Kategorie'.self::SUCCESS_MESSAGE_1);
                $cache->invalidate();
                self::redirect('/admin/category/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $category->getErrors())
                        ->set('category', $category)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $categories = App_Model_Category::all(array('active = ?' => true));

        $category = App_Model_Category::first(array(
                    'id = ?' => (int) $id
        ));

        if (NULL === $category) {
            $view->warningMessage('Kategorie nenalezena');
            self::redirect('/admin/category/');
        }

        $view->set('category', $category)
                ->set('categories', $categories);

        if (RequestMethods::post('submitEditCategory')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/category/');
            }
            
            $cache = Registry::get('cache');
            $errors = array();

            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($category->getUrlKey() !== $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Kategorie s tímto názvem již existuje');
            }

            $category->parentId = RequestMethods::post('parent', 0);
            $category->title = RequestMethods::post('title');
            $category->urlKey = $urlKey;
            $category->isGrouped = RequestMethods::post('group', 0);
            $category->isSelable = RequestMethods::post('selable', 0);
            $category->rank = RequestMethods::post('rank', 1);
            $category->mainText = RequestMethods::post('text');
            $category->metaTitle = RequestMethods::post('metaTitle');
            $category->metaKeywords = RequestMethods::post('metaKeywords');
            $category->metaDescription = RequestMethods::post('metaDescription');
            $category->active = RequestMethods::post('active');

            if (empty($errors) && $category->validate()) {
                $category->save();

                Event::fire('admin.log', array('success', 'Category id: ' . $category->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->invalidate();
                self::redirect('/admin/category/');
            } else {
                Event::fire('admin.log', array('fail', 'Category id: ' . $category->getId()));
                $view->set('errors', $category->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $parentCat = App_Model_Category::first(array('id = ?' => (int) $id));
        $categories = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => (int) $id));

        if ($parentCat === null) {
            $view->infoMessage('Kategorie nemá žádné podkategorie');
            self::redirect('/admin/category/');
        }

        $view->set('categories', $categories)
                ->set('parentcat', $parentCat);

        if (RequestMethods::post('submitSaveCategoryRank')) {
            $ranks = RequestMethods::post('rank');

            foreach ($ranks as $key => $value) {
                $cat = App_Model_Category::first(array('id = ?' => (int) $key));
                $cat->rank = $value;
                $cat->save();
            }
            Event::fire('admin.log', array('success', 'Update subcategories rank in category '. $parentCat->getId() ));
            $view->successMessage(self::SUCCESS_MESSAGE_8);
            self::redirect('/admin/category/');
        }
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $cache = Registry::get('cache');
            $category = App_Model_Category::first(array(
                        'id = ?' => (int) $id
            ));

            if (NULL === $category) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($category->delete()) {
                    Event::fire('admin.log', array('success', 'Category id: ' . $id));
                    $cache->invalidate();
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Category id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
