<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.8.0 */

namespace SignShop;

use pocketmine\scheduler\PluginTask;

class TaskSignAuction extends PluginTask{
    
    public function onRun($currentTick){
        $this->getOwner()->update();
    }
}
