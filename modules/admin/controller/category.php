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
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $categories = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => 0));
        $view->set('categories', $categories);
    }
       public function detail($id)
    {
        $view = $this->getActionView();
        $current = App_Model_Category::first(array('id = ?' => $id));
        $categories = App_Model_Category::all(array('active = ?' => true, 'parentId = ?' => $id));
        $view->set('categories', $categories)
            ->set('current', $current);
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
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            $category = new App_Model_Category(array(
                'parentId' => RequestMethods::post('parent', 0),
                'title' => RequestMethods::post('title'),
                'rank' => RequestMethods::post('rank', 1),
                'isGrouped' => RequestMethods::post('group', 0),
                'isSelable' => RequestMethods::post('selable', 0),
                'urlKey' => $urlKey,
                'mainText' => RequestMethods::post('text'),
                'metaTitle' => RequestMethods::post('metaTitle'),
                'metaKeywords' => RequestMethods::post('metaKeywords'),
                'metaDescription' => RequestMethods::post('metaDescription')
            ));

            if ($category->validate()) {
                $cid = $category->save();

                Event::fire('admin.log', array('success', 'Category id: ' . $cid));
                $view->successMessage('Section has been successfully saved');
                self::redirect('/admin/category/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $category->getErrors())
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
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

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

            if ($category->validate()) {
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
     * @before _secured, _member
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $category = App_Model_Category::first(array(
                        'id = ?' => (int)$id
            ));

            if (NULL === $category) {
                echo 'Category not found';
            } else {
                if ($category->delete()) {
                    Event::fire('admin.log', array('success', 'Category id: ' . $id));
                    ob_clean();
                    echo 'ok';
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
