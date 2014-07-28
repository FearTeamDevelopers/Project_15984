<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\ArrayMethods;

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

        if (RequestMethods::post('submitAddReference')) {
            $this->checkToken();
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
                $view->successMessage('Reference byla úspěšně uložena');
                self::redirect('/admin/reference/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $reference->getErrors())
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
            $view->errorMessage('Novinka nebyla nalezena');
            self::redirect('/admin/reference/');
        }

        $view->set('reference', $reference);

        if (RequestMethods::post('submitEditReference')) {
            $this->checkToken();
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
                $view->successMessage('Všechny změny byly úspěšně uloženy');
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

        if ($this->checkTokenAjax()) {
            $reference = App_Model_Reference::first(
                            array('id = ?' => $id), array('id')
            );

            if (NULL === $reference) {
                echo 'Reference nebyla nalezena';
                return;
            } else {
                if ($reference->delete()) {
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'success';
                    return;
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo 'Nastala neznámá chyba';
                    return;
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
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

        if ($this->checkTokenAjax()) {
            $reference = App_Model_Reference::first(array('id = ?' => (int) $id));

            if ($reference === null) {
                echo 'Reference nebyla nalezena';
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

                    Event::fire('app.log', array('success', 'Reference id: ' . $reference->getId()));
                    echo 'success';
                    return;
                } else {
                    Event::fire('app.log', array('fail', 'Reference id: ' . $reference->getId()));
                    echo 'Nastala chyba během mazání fotky';
                    return;
                }
            }
        } else {
            echo 'Bezpečnostní token není validní';
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
            $this->checkToken();
            $ids = RequestMethods::post('refids');
            $action = RequestMethods::post('action');

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
                        $view->successMessage('Reference byly smazány');
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
                        $view->successMessage('Reference byly aktivovány');
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
                        $view->successMessage('Reference byly deaktivovány');
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
