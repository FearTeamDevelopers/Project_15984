<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\ArrayMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Gallery extends Controller
{

    /**
     * Action method returns list of all galleries
     * 
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();

        $galleries = App_Model_Gallery::all(array('active = ?' => true));

        $view->set('galleries', $galleries);
    }

    /**
     * Action method shows and processes form used for new gallery creation
     * 
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('submitAddGallery')) {
            $this->checkToken();

            $gallery = new App_Model_Gallery(array(
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description', '')
            ));

            if ($gallery->validate()) {
                $id = $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Gallery has been successfully saved');
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('gallery', $gallery)
                        ->set('errors', $gallery->getErrors());
            }
        }
    }

    /**
     * Method shows detail of specific collection based on param id. 
     * From here can user upload photos and videos into collection.
     * 
     * @before _secured, _member
     * @param int $id   collection id
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::fetchGalleryById($id);

        $view->set('gallery', $gallery);
    }

    /**
     * Action method shows and processes form used for editing specific 
     * collection based on param id
     * 
     * @before _secured, _member
     * @param int $id   collection id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::first(array(
                    'id = ?' => (int)$id
        ));

        if (NULL === $gallery) {
            $view->warningMessage('Gallery not found');
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);
        
        if (RequestMethods::post('submitEditGallery')) {
            $this->checkToken();

            $collection->title = RequestMethods::post('title');
            $collection->active = RequestMethods::post('active');
            $collection->description = RequestMethods::post('description', '');

            if ($gallery->validate()) {
                $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail', 'Gallery id: ' . $id));
                $view->set('errors', $gallery->getErrors());
            }
        }
    }

    /**
     * Action method shows and processes form used for deleting specific 
     * collection based on param id. If is collection delete confirmed, 
     * there is option used for deleting all photos in collection.
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::first(
                        array('id = ?' => $id), 
                        array('id', 'title', 'created')
        );

        if (NULL === $gallery) {
            $view->warningMessage('Gallery not found');
            self::redirect('/admin/gallery/');
        }

        $photos = App_Model_Photo::all(array('galleryId = ?' => $gallery->getId()));

        if (RequestMethods::post('submitDeleteGallery')) {
            $this->checkToken();

            if ($gallery->delete()) {
                if (RequestMethods::post('action') == 1) {
                    $fm = new FileManager();
                    $configuration = Registry::get('config');

                    if (!empty($configuration->files)) {
                        $pathToImages = trim($configuration->files->pathToImages, '/');
                        $pathToThumbs = trim($configuration->files->pathToThumbs, '/');
                    } else {
                        $pathToImages = 'public/uploads/images';
                        $pathToThumbs = 'public/uploads/images';
                    }
                    
                    $ids = array();
                    foreach ($photos as $colPhoto) {
                        $ids[] = $colPhoto->getPhotoId();
                    }
                    
                    App_Model_Photo::deleteAll(array('id IN ?' => $ids));
                    
                    $path = APP_PATH . '/' . $pathToImages . '/gallery/' . $gallery->getId();
                    $pathThumbs = APP_PATH . '/' . $pathToThumbs . '/gallery/' . $gallery->getId();

                    if ($path == $pathThumbs) {
                        $fm->remove($path);
                    } else {
                        $fm->remove($path);
                        $fm->remove($pathThumbs);
                    }
                }

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Gallery has been deleted');
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail', 'Gallery id: ' . $id));
                $view->errorMessage('Unknown error eccured');
                self::redirect('/admin/gallery/');
            }
        }
    }

    /**
     * Action method shows and processes form used for uploading photos into
     * collection specified by param id
     * 
     * @before _secured, _member
     * @param int $id   collection id
     */
    public function addPhoto($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::first(
                        array(
                    'id = ?' => (int) $id,
                    'active = ?' => true
                        ), array('id', 'title')
        );

        $view->set('gallery', $gallery);

        if (RequestMethods::post('submitAddPhoto')) {
            $this->checkToken();
            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            try {
                $data = $fileManager->upload('file', 'gallery-' . $gallery->getId());
            } catch (Exception $ex) {
                $errors[] = array('file' => array($ex->getMessage()));
            }

            if (array_key_exists('files', $data)) {
                $errors = $errors + $data['errors'];

                foreach ($data['files'] as $i => $value) {
                    $uploadedFile = ArrayMethods::toObject($value);

                    $photo = new App_Model_Photo(array(
                        'galleryId' => $gallery->getId(),
                        'title' => RequestMethods::post('title'),
                        'photoName' => $uploadedFile->file->filename,
                        'description' => RequestMethods::post('description'),
                        'mime' => $uploadedFile->file->ext,
                        'imgMain' => trim($uploadedFile->file->path, '.'),
                        'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                        'sizeMain' => $uploadedFile->file->size,
                        'sizeThumb' => $uploadedFile->thumb->size,
                    ));

                    if ($photo->validate()) {
                        $aid = $photo->save();

                        Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' in gallery ' . $photo->getId()));
                    } else {
                        Event::fire('app.log', array('fail', 'Photo in gallery ' . $photo->getId()));
                        $errors[] = $photo->getErrors();
                    }
                }
                
                if(empty($errors)){
                    $view->successMessage('Photos has been successfully saved');
                    self::redirect('/admin/gallery/detail/' . $gallery->getId());
                }else{
                    $view->set('errors', $photo->getErrors());
                }
            } elseif (array_key_exists('file', $data)) {
                $uploadedFile = ArrayMethods::toObject($data);

                $photo = new App_Model_Photo(array(
                    'galleryId' => $gallery->getId(),
                    'title' => RequestMethods::post('title'),
                    'photoName' => $uploadedFile->file->filename,
                    'description' => RequestMethods::post('description'),
                    'mime' => $uploadedFile->file->ext,
                    'imgMain' => trim($uploadedFile->file->path, '.'),
                    'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                    'sizeMain' => $uploadedFile->file->size,
                    'sizeThumb' => $uploadedFile->thumb->size,
                ));

                if ($photo->validate()) {
                    $aid = $photo->save();

                    Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' in gallery ' . $photo->getId()));
                    $view->successMessage('Photo has been successfully saved');
                    self::redirect('/admin/gallery/detail/' . $gallery->getId());
                } else {
                    Event::fire('app.log', array('fail', 'Photo in gallery ' . $photo->getId()));
                    $view->set('photo', $photo)
                            ->set('errors', $photo->getErrors());
                }
            }
        }
    }

    /**
     * Method is called via ajax and deletes photo specified by param id
     * 
     * @before _secured, _member
     * @param int $id   photo id
     */
    public function deletePhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkTokenAjax()) {
            $photo = App_Model_Photo::first(
                            array('id = ?' => $id), array('id', 'imgMain', 'imgThumb')
            );

            if (null === $photo) {
                echo 'Photo not found';
            } else {
                if ($photo->delete()) {
                    @unlink($photo->getUnlinkPath());
                    @unlink($photo->getUnlinkThumbPath());
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo 'Unknown error eccured';
                }
            }
        } else {
            echo 'Security token is not valid';
        }
    }

    /**
     * Method is called via ajax and activate or deactivate photo specified by
     * param id
     * 
     * @before _secured, _member
     * @param int $id   photo id
     */
    public function changePhotoStatus($id)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        if ($this->checkTokenAjax()) {
            $photo = App_Model_Photo::first(array('id = ?' => $id));

            if (null === $photo) {
                echo 'Photo not found';
            } else {
                if (!$photo->active) {
                    $photo->active = true;
                    if ($photo->validate()) {
                        $photo->save();
                        Event::fire('admin.log', array('success', 'ID: ' . $id));
                        echo 'active';
                    } else {
                        echo join('<br/>', $photo->getErrors());
                    }
                } elseif ($photo->active) {
                    $photo->active = false;
                    if ($photo->validate()) {
                        $photo->save();
                        Event::fire('admin.log', array('success', 'ID: ' . $id));
                        echo 'inactive';
                    } else {
                        echo join('<br/>', $photo->getErrors());
                    }
                }
            }
        } else {
            echo 'Security token is not valid';
        }
    }

}
