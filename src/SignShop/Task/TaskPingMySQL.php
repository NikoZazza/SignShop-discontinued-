<?php
/**
 * SignShop Copyright (C) 2015 xionbig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @author xionbig
 * @name SignShop
 * @main SignShop\SignShop
 * @link http://xionbig.netsons.org/plugins/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @description Buy and Sell the items using Signs with virtual-money.
 * @version 1.1.2
 * @api 1.11.0
 */
namespace SignShop\Task;

use SignShop\SignShop;
use pocketmine\scheduler\PluginTask;

class TaskPingMySQL extends PluginTask{
    private $SignShop;
        
    public function __construct(SignShop $SignShop){
        parent::__construct($SignShop);
	
        $this->SignShop = $SignShop;
    }
        
    public function onRun($currentTick){
        $this->SignShop->getProvider()->ping();
    }
}