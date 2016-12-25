<?php
/**
 * SignShop Copyright (C) 2016 NikoZazza
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author NikoZazza
 * @name SignShop
 * @main SignShop\SignShop
 * @link http://nikozazza.sixcosoft.net/plugins/SignShop
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @description Buy and Sell the items using Signs with virtual-money.
 * @version 3.0.0
 * @api 2.0.0
 */
namespace SignShop\Manager;

use economizer\Economizer;
use economizer\Transistor;
use SignShop\SignShop;
use pocketmine\plugin\Plugin;
use sll\LibLoader;

class MoneyManager
{
    /** @var Transistor */
    private $EconomyAPI;

    public function __construct(SignShop $SignShop, $getFile)
    {
        $this->SignShop = $SignShop;
        LibLoader::loadLib($getFile, "Economizer");
        $plugin = $SignShop->getServer()->getPluginManager()->getPlugin($SignShop->getSetup()->get("economyPlugin"));
        if (!($plugin instanceof Plugin)){
            $SignShop->getLogger()->critical("The plugin '".$SignShop->getSetup()->get("economyPlugin")."' wasn't loaded!");
            $SignShop->getPluginLoader()->disablePlugin($SignShop);
            return;
        }
        $transistor = Economizer::getTransistorFor($plugin);
        if(!$transistor) {
            $SignShop->getLogger()->critical("The plugin '".$SignShop->getSetup()->get("economyPlugin")."' isn't supported!");
            $SignShop->getPluginLoader()->disablePlugin($SignShop);
            return;
        }
        $this->EconomyAPI = new Economizer($SignShop, $transistor);
        $SignShop->getLogger()->notice("The plugin '".$SignShop->getSetup()->get("economyPlugin")."' has been associated with SignShop successfully");
    }

    /**
     * @return string
     */
    public function getValue() : string
    {
        return $this->EconomyAPI->getMoneyUnit();
    }

    /**
     * @param Player|string $player
     * @return int
     */
    public function getMoney($player) : int
    {
        return $this->EconomyAPI->balance($player);
    }

    /**
     * @param Player|string $player
     * @param integer $value
     */
    public function addMoney($player, $value)
    {
        if($value < 0)
            $this->EconomyAPI->takeMoney($player, $value);
        else
            $this->EconomyAPI->addMoney($player, $value);
//        $this->EconomyAPI->setMoney($player, $this->getMoney($player) + $value);
    }

    /**
     * @param Player|string $player
     * @return boolean
     */
    public function isExists($player) : bool
    {
        return (bool) $this->getMoney($player);
    }
}