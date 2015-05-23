<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\Task;

use pocketmine\scheduler\PluginTask;

class TaskPingMySQL extends PluginTask{
    private $SignShop;
        
    public function __construct($SignShop){
        parent::__construct($SignShop);
	
        $this->SignShop = $SignShop;
    }
        
    public function onRun($currentTick){
        $this->SignShop->getProvider()->ping();
    }
}