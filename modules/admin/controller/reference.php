<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\ArrayMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Reference extends Controller
{

    /**
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();
        $reference = App_Model_Reference::all();
        $view->set('reference', $reference);
    }

    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();
        
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddReference')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/reference/');
            }
            
            $cache = Registry::get('cache');
            $errors = array();

            try {
                $fileManager = new FileManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));

                try {
                    $data = $fileManager->upload('mainfile', 'reference');
                    $uploadedFile = ArrayMethods::toObject($data);
                } catch (Exception $ex) {
                    $errors['mainfile'] = array($ex->getMessage());
                }
            } catch (Exception $ex) {
                $errors['mainfile'] = array($ex->getMessage());
            }

            $reference = new App_Model_Reference(array(
                'title' => RequestMethods::post('title'),
                'author' => RequestMethods::post('author', $this->getUser()->getWholeName()),
                'isCorporate' => RequestMethods::post('corporate'),
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                'body' => RequestMethods::post('text')
            ));

            if (empty($errors) && $reference->validate()) {
                $id = $reference->save();

                Event::fire('admin.log', array('success', 'Reference id: ' . $id));
                $view->successMessage('Reference'.self::SUCCESS_MESSAGE_1);
                $cache->erase('reference');
                self::redirect('/admin/reference/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $reference->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('reference', $reference);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $reference = App_Model_Reference::first(array('id = ?' => (int) $id));

        if ($reference === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/reference/');
        }

        $view->set('reference', $reference);

        if (RequestMethods::post('submitEditReference')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/reference/');
            }
            
            $cache = Registry::get('cache');
            $errors = array();

            if ($reference->imgMain == '') {
                try {
                    $fileManager = new FileManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                        'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                        'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                    ));

                    try {
                        $data = $fileManager->upload('mainfile', 'reference');
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
                $imgMain = $reference->imgMain;
                $imgThumb = $reference->imgThumb;
            }

            $reference->title = RequestMethods::post('title');
            $reference->isCorporate = RequestMethods::post('corporate');
            $reference->imgMain = $imgMain;
            $reference->imgThumb = $imgThumb;
            $reference->author = RequestMethods::post('author', $this->getUser()->getWholeName());
            $reference->body = RequestMethods::post('text');
            $reference->active = RequestMethods::post('active');

            if (empty($errors) && $reference->validate()) {
                $reference->save();

                Event::fire('admin.log', array('success', 'Reference id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->erase('reference');
                self::redirect('/admin/reference/');
            } else {
                Event::fire('admin.log', array('fail', 'Reference id: ' . $id));
                $view->set('errors', $reference->getErrors());
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

        if ($this->checkToken()) {
            $cache = Registry::get('cache');
            $reference = App_Model_Reference::first(
                            array('id = ?' => $id), array('id')
            );

            if (NULL === $reference) {
                echo self::ERROR_MESSAGE_2;
                return;
            } else {
                if ($reference->delete()) {
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    $cache->erase('reference');
                    echo 'success';
                    return;
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                    return;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
            return;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $reference = App_Model_Reference::first(array('id = ?' => (int) $id));

            if ($reference === null) {
                echo self::ERROR_MESSAGE_2;
                return;
            } else {
                $unlinkMainImg = $reference->getUnlinkPath();
                $unlinkThumbImg = $reference->getUnlinkThumbPath();
                $reference->imgMain = '';
                $reference->imgThumb = '';

                if ($reference->validate()) {
                    $reference->save();
                    @unlink($unlinkMainImg);
                    @unlink($unlinkThumbImg);

                    Event::fire('admin.log', array('success', 'Reference id: ' . $reference->getId()));
                    echo 'success';
                    return;
                } else {
                    Event::fire('admin.log', array('fail', 'Reference id: ' . $reference->getId()));
                    echo self::ERROR_MESSAGE_1;
                    return;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
            return;
        }
    }
    
    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performReferenceAction')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/reference/');
            }
            
            $ids = RequestMethods::post('refids');
            $action = RequestMethods::post('action');
            $cache = Registry::get('cache');

            switch ($action) {
                case 'delete':
                    $reference = App_Model_Reference::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $reference) {
                        foreach ($reference as $_reference) {
                            if (!$_reference->delete()) {
                                $errors[] = 'Nastala chyba při mazání ' . $_reference->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $cache->erase('reference');
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/reference/');

                    break;
                case 'activate':
                    $reference = App_Model_Reference::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $reference) {
                        foreach ($reference as $_reference) {
                            $_reference->active = true;

                            if ($_reference->validate()) {
                                $_reference->save();
                            } else {
                                $errors[] = "Reference id {$_reference->getId()} - {$_reference->getTitle()} errors: "
                                        . join(', ', $_reference->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $cache->erase('reference');
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/reference/');

                    break;
                case 'deactivate':
                    $reference = App_Model_Reference::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $reference) {
                        foreach ($reference as $_reference) {
                            $_reference->active = false;

                            if ($_reference->validate()) {
                                $_reference->save();
                            } else {
                                $errors[] = "Reference id {$_reference->getId()} - {$_reference->getTitle()} errors: "
                                        . join(', ', $_reference->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $cache->erase('reference');
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/reference/');
                    break;
                default:
                    self::redirect('/admin/reference/');
                    break;
            }
        }
    }

}
