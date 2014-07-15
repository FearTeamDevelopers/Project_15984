<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\Config;

/**
 * 
 */
class Admin_Controller_System extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {

    }

    /**
     * @before _secured, _admin
     */
    public function clearCache()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log');
            $cache = Registry::get('cache');
            $cache->clearCache();
            $view->successMessage('Cache byly úspěšně smazány');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create and download db bakcup
     * 
     * @before _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));
        $fm = new FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        $dump->create();
        $view->successMessage('Záloha databáze byla úspěšně vytvořena');
        Event::fire('app.log', array('success', 'Database backup ' . $dump->getBackupName()));
        unset($fm);
        unset($dump);
        self::redirect('/system');
    }

    /**
     * @before _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();
        $log = Admin_Model_AdminLog::all(array(), array('*'), array('created' => 'DESC'));
        $view->set('adminlog', $log);
    }

    /**
     * @before _secured, _admin
     */
    public function settings()
    {
        $view = $this->getActionView();
        $config = Config::all();
        $view->set('config', $config);
        
        if(RequestMethods::post('submitEditSet')){
            $this->checkToken();
            $errors = array();
            
            foreach($config as $conf){
                $conf->value = RequestMethods::post($conf->getXkey(), '');
                if($conf->validate()){
                    Event::fire('admin.log', array('success', $conf->getXkey().': ' . $conf->getValue()));
                    $conf->save();
                }else{
                    Event::fire('admin.log', array('fail', $conf->getXkey().': ' . $conf->getValue()));
                    $errors[$conf->xkey] = array_shift($conf->getErrors());
                }
            }

            if(empty($errors)){
                $view->successMessage('Nastavení bylo úspěšně změněno');
                self::redirect('/admin/system/');
            }else{
                $view->set('errors', $errors);
            }
        }
    }

}
