<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\ArrayMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class Admin_Controller_Gallery extends Controller
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
     * Action method returns list of all galleries
     * 
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();

        $galleries = App_Model_Gallery::all();

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
            $errors = array();

            $urlKey = strtolower(
                str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));
            
            if (!$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Galerie s tímto názvem již existuje');
            }
            
            $gallery = new App_Model_Gallery(array(
                'title' => RequestMethods::post('title'),
                'isPublic' => RequestMethods::post('public', 1),
                'urlKey' => $urlKey,
                'description' => RequestMethods::post('description', '')
            ));

            if (empty($errors) && $gallery->validate()) {
                $id = $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Galerie byla úspěšně uložena');
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('gallery', $gallery)
                        ->set('errors', $errors + $gallery->getErrors());
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
        
        if($gallery === null){
            $view->warningMessage('Galerie nebyla nalezena');
            self::redirect('/admin/gallery/');
        }

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

        $gallery = App_Model_Gallery::first(array('id = ?' => (int) $id));

        if (NULL === $gallery) {
            $view->warningMessage('Galerie nebyla nalezena ');
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);

        if (RequestMethods::post('submitEditGallery')) {
            $this->checkToken();
            $errors = array();
            
            $urlKey = strtolower(
                    str_replace(' ', '-', StringMethods::removeDiacriticalMarks(RequestMethods::post('title'))));

            if ($gallery->getUrlKey() !== $urlKey && !$this->checkUrlKey($urlKey)) {
                $errors['title'] = array('Galerie s tímto názvem již existuje');
            }

            $collection->title = RequestMethods::post('title');
            $collection->isPublic = RequestMethods::post('public');
            $collection->active = RequestMethods::post('active');
            $collection->urlKey = $urlKey;
            $collection->description = RequestMethods::post('description', '');

            if (empty($errors) && $gallery->validate()) {
                $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Všechny změny byly úspěšne uloženy');
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
                        array('id = ?' => $id), array('id', 'title', 'created')
        );

        if (NULL === $gallery) {
            $view->warningMessage('Galerie nenalezena');
            self::redirect('/admin/gallery/');
        }

        $photos = App_Model_Photo::all(array('galleryId = ?' => $gallery->getId()));
        
        $view->set('gallery', $gallery);

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
                $view->successMessage('Galerie byla smazána');
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail', 'Gallery id: ' . $id));
                $view->errorMessage('Nastala neznámá chyba');
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
                $data = $fileManager->upload('secondfile', 'gallery/' . $gallery->getId());
            } catch (Exception $ex) {
                $errors['photo'] = array($ex->getMessage());
            }

            if (empty($data['errors']) && empty($errors['photo'])) {
                foreach ($data['files'] as $i => $value) {
                    $uploadedFile = ArrayMethods::toObject($value);

                    $photo = new App_Model_Photo(array(
                        'galleryId' => $gallery->getId(),
                        'imgMain' => trim($uploadedFile->file->path, '.'),
                        'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                        'title' => RequestMethods::post('title', $uploadedFile->file->filename),
                        'description' => RequestMethods::post('description', ''),
                        'photoName' => $uploadedFile->file->filename,
                        'mime' => $uploadedFile->file->ext,
                        'sizeMain' => $uploadedFile->file->size,
                        'sizeThumb' => $uploadedFile->thumb->size
                    ));

                    if ($photo->validate()) {
                        $aid = $photo->save();

                        Event::fire('app.log', array('success', 'Photo id: ' . $aid . ' in gallery ' . $gallery->getId()));
                    } else {
                        Event::fire('app.log', array('fail', 'Photo in gallery ' . $gallery->getId()));
                        $errors['photo'][] = $photo->getErrors();
                    }
                }
            }

            if (empty($errors)) {
                $view->successMessage('Fotografie byly úspěšně nahrány');
                self::redirect('/admin/gallery/detail/'.$gallery->getId());
            } else {
                $view->set('errors', $errors);
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
                echo 'Fotografie nebyla nalezena';
            } else {
                if ($photo->delete()) {
                    @unlink($photo->getUnlinkPath());
                    @unlink($photo->getUnlinkThumbPath());
                    
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'success';
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
                echo 'Fotografie nebyla nalezena';
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
            echo 'Bezpečnostní token není validní';
        }
    }

}
