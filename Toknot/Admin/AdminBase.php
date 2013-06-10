<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Control\FMAI;
use Toknot\User\ClassUserControl;
use Toknot\Exception\FileIOException;
use Toknot\Config\ConfigLoader;
use Toknot\User\Nobody;
use Toknot\User\CurrentUser;

class AdminBase extends ClassUserControl {

    protected $FMAI = null;
    protected $AR = null;
    protected $view = null;
    protected $CFG = null;
    protected $dbConnect = null;

    public function __construct(FMAI $FMAI) {
        $this->FMAI = $FMAI;
        $this->loadAdminConfig();
        $this->startSession();
        $FMAI->registerAccessDeniedController('Toknot\Admin\Login');
        $this->initDatabase();
        $user = $this->checkUserLogin();
        $FMAI->checkAccess($this, $user);

        $this->view = $FMAI->newTemplateView($this->CFG->View);
    }

    public function initDatabase() {
        $this->AR = $this->FMAI->getActiveRecord();
        $dbSectionName = $this->CFG->Admin->databaseOptionSectionName;
        if ($this->CFG->Admin->multiDatabase) {
            $i = 0;
            while (true) {
                $section = $this->CFG->Admin->databaseOptionSectionName . $i;
                if (!isset($this->CFG->$section)) {
                    break;
                }
                $this->AR->config($this->CFG->$section);
                $this->dbConnect[$i] = $this->AR->connect();
                $i++;
            }
        } else {
            $this->AR->config($this->CFG->$dbSectionName);
            $this->dbConnect = $this->AR->connect();
        }
    }

    public function loadAdminConfig() {
        if (!file_exists($this->FMAI->appRoot . '/Config/config.ini')) {
            throw new FileIOException('must create ' . $this->FMAI->appRoot . '/Config/config.ini');
        }
        ConfigLoader::$cacheFile = $this->FMAI->appRoot . '/Data/config';
        $this->CFG = $this->FMAI->loadConfigure($this->FMAI->appRoot . '/Config/config.ini');
    }

    public function CLI() {
        $this->GET();
    }

    public function startSession() {
        if (!empty($this->CFG->Admin->adminSessionName)) {
            
        }
        
    }

    public function checkUserLogin() {
        if (isset($_SESSION['uid']) && isset($_SESSION['Flag'])) {
            $user = CurrentUser::getInstanceByUid($_SESSION['uid']);
            if($user->checkUserFlag()) {
                return $user;
            }
        } elseif (isset($_COOKIE['uid']) && isset($_COOKIE['Flag']) && isset($_COOKIE['TokenKey'])) {
            $user = CurrentUser::checkLogin($_COOKIE['uid'], $_COOKIE['Flag'], $_COOKIE['TokenKey']);
            if ($user) {
                return $user;
            }
        }
        return new Nobody;
    }

    protected function setAdminLogin(CurrentUser $user) {
        $_SESSION['Flag'] = $user->getUserFlag();
        $_SESSION['uid'] = $user->getUid();
        if ($user->loginExpire > 0) {
            setcookie('uid', $user->getUid(), $user->loginExpire);
            setcookie('Flag', $_SESSION['Flag'], $user->loginExpire);
            setcookie('TokenKey', $user->generateLoginKey(), $user->loginExpire);
        }
    }

}

?>
