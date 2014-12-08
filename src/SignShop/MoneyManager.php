<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.8.0 */

namespace SignShop;

class MoneyManager{
    protected $SignMain, $PocketMoney, $EconomyS;
    
    public function __construct($SignShop){
        $this->SignMain = $SignShop;
        if($SignShop->getServer()->getPluginManager()->getPlugin("PocketMoney")->isEnabled() == true)
            $this->PocketMoney = $SignShop->getServer()->getPluginManager()->getPlugin("PocketMoney");
            
        elseif($SignShop->getServer()->getPluginManager()->getPlugin("EconomyAPI")->isEnabled() == true)
            $this->EconomyS = $SignShop->getServer()->getPluginManager()->getPlugin("EconomyAPI");
            
        else{
            $SignShop->getLogger()->info(TextFormat::RED."This plugin to work needs the plugin PocketMoney or EconomyS.");
            $SignShop->getServer()->shutdown();
        }  
    }
    
    public function getValue(){
        if($this->PocketMoney->isEnabled() == true) 
            return "pm";
        elseif($this->EconomyS->isEnabled() == true) 
            return "$";              
    }    
    
    public function getMoney($player){
        if($this->PocketMoney->isEnabled() == true) 
            return $this->PocketMoney->getMoney($player);
        elseif($this->EconomyS->isEnabled() == true) 
            return $this->EconomyS->mymoney($player);         
    }
    
    public function addMoney($player, $value){
        if($this->PocketMoney->isEnabled() == true) 
            $this->PocketMoney->grantMoney($player, $value);
        
        elseif($this->EconomyS->isEnabled() == true) 
            $this->EconomyS->setMoney($player, $this->getMoney($player) + $value);
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