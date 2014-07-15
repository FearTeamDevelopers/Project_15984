<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

class Admin_Controller_News extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function checkUrlKey($key)
    {
        $status = App_Model_News::first(array('urlKey = ?' => $key));

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

        $news = App_Model_News::all(array('active = ?' => true));

        $view->set('news', $news);
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();

        $photos = $this->_getPhotos();

        $view->set('photos', $photos);

        if (RequestMethods::post('submitAddNews')) {
            $this->checkToken();
            $errors = array();

            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            if (!$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Novinka s tímto názvem již existuje');
            }

            $news = new App_Model_News(array(
                'title' => RequestMethods::post('title'),
                'author' => RequestMethods::post('author', $this->getUser()->getWholeName()),
                'urlKey' => $urlKey,
                'body' => RequestMethods::post('text')
            ));

            if (empty($errors) && $news->validate()) {
                $id = $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('Novinka byla úspěšně uložena');
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $news->getErrors())
                        ->set('news', $news);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $photos = $this->_getPhotos();
        $news = App_Model_News::first(array('id = ?' => (int) $id));

        if ($news === null) {
            $view->errorMessage('Novinka nenalezena');
            self::redirect('/admin/news/');
        }

        $view->set('news', $news)
                ->set('photos', $photos);

        if (RequestMethods::post('submitEditNews')) {
            $this->checkToken();
            $errors = array();
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            if ($news->getUrlKey() !== $urlKey && !$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Novinka s tímto názvem již existuje');
            }
            
            $news->title = RequestMethods::post('title');
            $news->urlKey = $urlKey;
            $news->author = RequestMethods::post('author', $this->getUser()->getWholeName());
            $news->body = RequestMethods::post('text');
            $news->active = RequestMethods::post('active');

            if (empty($errors) && $news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('Všechny změny byly úspěšně uloženy');
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                $view->set('errors', $news->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $news = App_Model_News::first(
                            array('id = ?' => $id), array('id')
            );

            if (NULL === $news) {
                echo 'Novinka nenalezena';
            } else {
                if ($news->delete()) {
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'úspěch';
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo 'Nastala neznámá chyba';
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
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performNewsAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('newsids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            if (!$_news->delete()) {
                                $errors[] = 'Nastala chyba při mazání ' . $_news->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Novinky byly smazány');
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'activate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = true;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Novinky byly aktivovány');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'deactivate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = false;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Novinky byly deaktivovány');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');
                    break;
                default:
                    self::redirect('/admin/news/');
                    break;
            }
        }
    }

}
