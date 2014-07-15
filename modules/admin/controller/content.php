<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Content extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function checkUrlKey($key)
    {
        $status = App_Model_PageContent::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @before _secured, _member
     */
    private function _getPhotos()
    {
        $photos = App_Model_Photo::all(array('active = ?' => true));

        return $photos;
    }

    /**
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::all();

        $view->set('content', $content);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $photos = $this->_getPhotos();

        $view->set('photos', $photos);

        if (RequestMethods::post('submitAddContent')) {
            $this->checkToken();
            $errors = array();
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('page'))));

            if (!$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }
            
            $content = new App_Model_PageContent(array(
                'pageName' => RequestMethods::post('page'),
                'urlKey' => $urlKey,
                'body' => RequestMethods::post('text', ''),
                'bodyEn' => RequestMethods::post('texten', '')
            ));

            if (empty($errors) && $content->validate()) {
                $id = $content->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Obsah byl úspěšně uložen');
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $content->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $content) {
            $view->errorMessage('Obsah nenalezen');
            self::redirect('/admin/content/');
        }

        $photos = $this->_getPhotos();

        $view->set('photos', $photos)
                ->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            $this->checkToken();
            $errors = array();
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('page'))));

            if ($content->getUrlKey() !== $urlKey && !$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Obsah s tímto názvem již existuje');
            }
            
            $content->pageName = RequestMethods::post('page');
            $content->urlKey = $urlKey;
            $content->body = RequestMethods::post('text', '');
            $content->bodyEn = RequestMethods::post('texten', '');
            $content->active = RequestMethods::post('active');

            if (empty($errors) && $content->validate()) {
                $content->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Všechny změny byly úspěšně uloženy');
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $content->getErrors());
            }
        }
    }

    /**
     * @before _secured, _superadmin
     */
//    public function delete($id)
//    {
//        $view = $this->getActionView();
//
//        $content = App_Model_PageContent::first(array(
//                    'id = ?' => $id
//                        ), array('id', 'pageName', 'body')
//        );
//
//        if (NULL === $content) {
//            $view->errorMessage('Content not found');
//            self::redirect('/admin/content/');
//        }
//
//        $view->set('content', $content);
//
//        if (RequestMethods::post('submitDeleteContent')) {
//            $this->checkToken();
//
//            if ($content->delete()) {
//                Event::fire('admin.log', array('success', 'ID: ' . $id));
//                $view->successMessage('Content has been deleted');
//                self::redirect('/admin/content/');
//            } else {
//                Event::fire('admin.log', array('fail', 'ID: ' . $id));
//                $view->errorMessage('Unknown error eccured');
//                self::redirect('/admin/content/');
//            }
//        } elseif (RequestMethods::post('cancel')) {
//            self::redirect('/admin/content/');
//        }
//    }

}
