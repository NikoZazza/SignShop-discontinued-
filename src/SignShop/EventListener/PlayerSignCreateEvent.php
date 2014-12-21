<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.0 */

namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Player;
use pocketmine\item\Item;

class PlayerSignCreateEvent implements Listener{
    protected $SignMain;

    public function __construct($SignShop){
        $this->SignMain = $SignShop;
    }
    
    public function signChangeEvent(SignChangeEvent $event){
        $line0 = strtolower(trim($event->getLine(0)));
        $line1 = trim($event->getLine(1));
        $line2 = trim($event->getLine(2));
        $line3 = trim($event->getLine(3));
        
        if($line0 == "[signshop]" || $line0 == "/signshop" || $line0 == "/sign"){
            $player = $event->getPlayer();
            $error = "";
            if($this->SignMain->getProvider()->getPlayer($player->getDisplayName())["authorized"] == "true"){
      
                if(is_numeric($line1) && $line1 > 0){
                    $id = $line1;
                    $damage = 0;                    
                }elseif(count(explode(":", $line1)) == 2){                    
                    $line1 = explode(":", $line1);
                    $id = $line1[0];
                    $damage = $line1[1];
                    
                    if(!is_numeric($id) || !is_numeric($damage)) $error = "Item NotNumeric";
                }else{
                    $item = $this->SignMain->getItems()->getBlock($line1); 
                    $id = $item->getID();
                    $damage = $item->getDamage();
                    if($id == 0) $error = "Item Invalid";
                }
                               
                $amount = 0;
                if(count(explode("x", $line2)) == 2){
                    $line2 = explode("x", $line2);
                    if(is_numeric($line2[0]) && is_numeric($line2[1])){
                        $amount = $line2[0];
                        $cost = $line2[1];
                        if($cost < 0 || $amount < 0 || $amount > 45 * 64) $error = "Invalid Amount|Cost";
                    }else $error = "Amount|Cost NotNumeric";
                }else $error = "Invalid Amount|Cost";
                              
                if(is_numeric($line3)){
                    if($line3 < $amount) $error = "Invalid Available";                    
                }else{
                    if($line3 == "unlimited"){
                        if($this->SignMain->getProvider()->getPlayer($player->getDisplayName())["authorized"] != "super")
                            $error = "Player NotAuthorized";
                    }else
                        $error = "Available NotNumeric";
                } 
                
                if($error == ""){
                    if($this->hasItemPlayer($player, Item::get($id, $damage, $line3)) == true && $this->removeItemPlayer($player, Item::get($id, $damage, $line3)) == true){
                        
                        $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());
                        $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
                            
                        $this->SignMain->getProvider()->setSign($var, [
                            "id" => $id, 
                            "damage" => $damage, 
                            "amount" => $amount, 
                            "available" => $line3, 
                            "cost" => $cost, 
                            "maker" => $player->getDisplayName(), 
                            "time" => time(), 
                            "sold" => 0, 
                            "earned" => 0, 
                            "direction" => $event->getBlock()->getDamage()]);
                            
                        $player->sendMessage("[SignShop] ".$this->SignMain->getMessages()["Sign_successfully_created"]);
                           
                        $event->setLine(0, "[".$player->getDisplayName()."]");
                        $event->setLine(1, $this->SignMain->getItems()->getName($id, $damage));
                        $event->setLine(2, "Amount: x".$amount);
                        $event->setLine(3, "Price: ".$cost.$this->SignMain->getMoneyManager()->getValue());
   
                        $this->SignMain->respawnSign($var);   
                    }else{
                        $player->sendMessage("[SignShop] ".$this->SignMain->getMessages()["The_item_was_not_found_or_does_not_have_enough_items"]); 
                        $error = "Player Error";                        
                    }
                }
                if($error != ""){
                    $error = explode(" ", $error);
                    
                    $event->setLine(0, "[SignShop]");
                    $event->setLine(1, ":ERROR:");
                    $event->setLine(2, $error[0]);
                    $event->setLine(3, $error[1]);
                    
                    $player->sendMessage("[SignShop] ".$this->SignMain->getMessages()["There_is_a_problem_with_the_creation_of_the_Sign"]);
                }
            }else{
                $player->sendMessage("[SignShop] ".$this->SignMain->getMessages()["You_are_not_authorized_to_run_this_command"]);
                $event->setLine(0, "[SignShop]");
                $event->setLine(1, "Error:");
                $event->setLine(2, "PlayerNot");
                $event->setLine(3, "Authorized");                
            }
        }
    }
    
    private function removeItemPlayer(Player $player, Item $item){
        if($this->SignMain->getProvider()->getPlayer($player->getDisplayName()) == "super") return true; 
        $ris = $item->getCount();
        
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage()){
                    $ris = $inv->getCount() - $ris;
                
                    if($ris <= 0){
                        $player->getInventory()->clear($i);
                        $ris = -($ris);
                    }else
                        $player->getInventory()->setItem($i, Item::get($item->getID(), $item->getDamage(), $ris));
                }
            }
        }
        return true;
    }

    private function hasItemPlayer(Player $player, Item $item){
        if($this->SignMain->getProvider()->getPlayer($player->getDisplayName()) == "super") return true;
        
        $ris = 0;
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); ++$i){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();      
            }
        }
        if($ris >= $item->getCount()) return true;
        return false;
    }
}