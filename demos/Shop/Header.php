<?php
namespace Shop;

use Toknot\Boot\Object;
use Toknot\Db\ActiveRecord;
use Toknot\Config\ConfigLoader;

abstract class Header extends Object  {
    
    protected $db;


    public function __init() {
        $ar = ActiveRecord::singleton();
        $cfg = ConfigLoader::CFG();
        $ar->config($cfg->Database);
        $this->db = $ar->connect();
    }

    public function CLI() {
        $this->GET();
    }

}