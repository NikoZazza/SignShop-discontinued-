<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.1 */

namespace SignShop;

use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class MoneyManager{
    private $SignMain, $PocketMoney = false, $EconomyS = false, $MassiveEconomy = false;
    
    public function __construct($SignShop){
        $this->SignMain = $SignShop;
        
        if($SignShop->getServer()->getPluginManager()->getPlugin("PocketMoney") instanceof Plugin)
            $this->PocketMoney = $SignShop->getServer()->getPluginManager()->getPlugin("PocketMoney");

        elseif($SignShop->getServer()->getPluginManager()->getPlugin("EconomyAPI")  instanceof Plugin)
            $this->EconomyS = $SignShop->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        
        elseif($SignShop->getServer()->getPluginManager()->getPlugin("MassiveEconomy") instanceof Plugin)
            $this->MassiveEconomy = $SignShop->getServer()->getPluginManager()->getPlugin("MassiveEconomy");
        
        else{
            $SignShop->getLogger()->info(TextFormat::RED."This plugin to work needs the plugin PocketMoney or EconomyS or MassiveEconomy.");
            $SignShop->getServer()->shutdown();
        }  
    }
    
    public function getValue(){
        if($this->PocketMoney == true) 
            return "pm";
        elseif($this->EconomyS == true) 
            return "$";
        elseif($this->MassiveEconomy == true)
            return $this->MassiveEconomy->getMoneySymbol();
    }    
    
    public function getMoney($player){
        if($this->PocketMoney == true) 
            return $this->PocketMoney->getMoney($player);
        elseif($this->EconomyS == true) 
            return $this->EconomyS->mymoney($player);  
        elseif($this->MassiveEconomy == true)
            return $this->MassiveEconomy->getMoney($player);
        return 0;
    }
    
    public function addMoney($player, $value){
        if($this->PocketMoney == true) 
            $this->PocketMoney->grantMoney($player, $value);
        elseif($this->EconomyS == true) 
            $this->EconomyS->setMoney($player, $this->getMoney($player) + $value);
        elseif($this->MassiveEconomy == true)
            $this->MassiveEconomy->setMoney($player, $this->getMoney($player) + $value);
        else return;
        
        if($this->SignMain->getProvider()->existsPlayer($player)){
            $get = $this->SignMain->getProvider()->getPlayer($player);  
            if($value >=0){
                $get["totEarned"] += $value;
                $this->SignMain->getProvider()->setPlayer($player, $get);
            }else{
                $get["totSpent"] += $value;
                $this->SignMain->getProvider()->setPlayer($player, $get);
            }
        }
    }
}