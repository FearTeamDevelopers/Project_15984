<?php

use Admin\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

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
    private function checkUrlKey($key)
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

        $categories = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => 0));

        $view->set('categories', $categories);

        if (RequestMethods::post('submitSaveCategoryRank')) {
            $ranks = RequestMethods::post('rank');

            foreach ($ranks as $key => $value) {
                $cat = App_Model_Category::first(array('id = ?' => (int) $key));
                $cat->rank = $value;
                $cat->save();
            }
            Event::fire('admin.log', array('success', 'Category update rank: ' . $cat->getId()));
            $view->successMessage('Poradi kategorii bylo uspesne ulozeno');
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

        $view->set('categories', $categories);

        if (RequestMethods::post('submitAddCategory')) {
            $this->checkToken();
            $errors = array();

            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            if (!$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Product with this title already exists');
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
                $view->successMessage('Section has been successfully saved');
                self::redirect('/admin/category/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $category->getErrors())
                        ->set('category', $category);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $categories = App_Model_Category::all(array('active = ?' => true));

        $category = App_Model_Category::first(array(
                    'id = ?' => (int) $id
        ));

        if (NULL === $category) {
            $view->warningMessage('Category not found');
            self::redirect('/admin/category/');
        }

        $view->set('category', $category)
                ->set('categories', $categories);

        if (RequestMethods::post('submitEditCategory')) {
            $this->checkToken();
            $errors = array();

            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            if ($category->getUrlKey() !== $urlKey && !$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Product with this title already exists');
            }

            $category->parentId = RequestMethods::post('parent', 0);
            $category->title = RequestMethods::post('title');
            $category->urlKey = $urlKey;
            $category->isGrouped = RequestMethods::post('group', 0);
            $category->isSelable = RequestMethods::post('selable', 0);
            $category->isSeparator = RequestMethods::post('separator', 0);
            $category->rank = RequestMethods::post('rank', 1);
            $category->mainText = RequestMethods::post('text');
            $category->metaTitle = RequestMethods::post('metaTitle');
            $category->metaKeywords = RequestMethods::post('metaKeywords');
            $category->metaDescription = RequestMethods::post('metaDescription');
            $category->active = RequestMethods::post('active');

            if (empty($errors) && $category->validate()) {
                $category->save();

                Event::fire('admin.log', array('success', 'Category id: ' . $category->getId()));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/category/');
            } else {
                Event::fire('admin.log', array('fail', 'Category id: ' . $category->getId()));
                $view->set('errors', $category->getErrors());
            }
        }
    }

    /**
     * 
     * @param type $id
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $parentCat = App_Model_Category::first(array('id = ?' => (int) $id));
        $categories = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => (int) $id));

        if ($parentCat === null) {
            $view->infoMessage('Kategorie nema zadne podkategorie');
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
            Event::fire('admin.log', array('success', 'Category update rank: ' . $cat->getId()));
            $view->successMessage('Poradi kategorii bylo uspesne ulozeno');
            self::redirect('/admin/category/');
        }
    }

    /**
     * @before _secured, _member
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $category = App_Model_Category::first(array(
                        'id = ?' => (int) $id
            ));

            if (NULL === $category) {
                echo 'Category not found';
            } else {
                if ($category->delete()) {
                    Event::fire('admin.log', array('success', 'Category id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Category id: ' . $id));
                    echo 'Unknown error eccured';
                }
            }
        } else {
            echo 'Security token is not valid';
        }
    }

}
