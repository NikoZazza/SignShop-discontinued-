<?php
 /*@author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @link https://github.com/xionbig/SignShop
 * @version 0.7.0
  *@Twitter xionbig
 */
namespace SignShop;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\tile\Tile;
use pocketmine\Player;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\command\CommandSender;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\level\Position;
use pocketmine\block\Block;
class SignShop extends PluginBase implements Listener{    
    private $sign, $config, $plr, $mex, $items, $temp = [];
    private $PocketMoney = false;
    private $EconomyS = false;   
    private $tag = "[SignShop] ";
    
    public function onEnable(){  
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) @mkdir($dataResources, 0755, true);
        
        $continue = false;
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
            if(isset($this->mex["version_mex"])){
                if($this->mex["version_mex"] != 70)
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else
                $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");            
        }
        $this->items = new ListItems();
        $this->items->onLoad();
        
        if($continue == false){            
            $this->mex = ["version_mex" => 70, "The_Sign_successfully_removed" => "", "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => "", "The_Sign_was_stocked_with_success" => "", "You_do_not_have_enough_blocks_to_fill_the_Sign" => "", "The_selected_Sign_is_not_your" => "", "You_do_not_have_enough_money" => "", "The_content_of_the_Sign_is_sold_out" => "", "You_bought_the_contents_of_the_Sign" => "", "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => "", "Sign_successfully_created" => "", "The_item_was_not_found_or_does_not_have_enough_items" => "", "Invalid_arguments" => "", "Invalid_value_of_@@" => "", "item" => "", "amount" => "", "cost" => "", "available" => "", "cost" => "", "player" => "", "You_are_not_authorized_to_run_this_command" => "", "Now_touch_on_the_Sign_that_you_want_to_do_this_action" => "", "You_have_authorized_@@_to_use_the_command_/sign" => "", "You_have_unauthorized_@@_to_use_the_command_/sign" => "", "Now_@@_can_use_the_command_/sign" => "", "There_is_not_Sign_in_the_world" => "", "All_Signs_respawned" => "", "The_player_@@_is_not_exists" => "", "The_cost_of_the_contents_of_this_Sign_is_now_@@" => "", "This_Sign_is_owned_by_@@" => "", "This_Sign_was_created_@@" => "", "There_are_still_@@_blocks/items" => "", "They_were_sold_@@_blocks/items_with_this_Sign" => "", "The_owner_has_earned_@@" => "", "You_set_the_amount_of_the_Sign_in_@@" => "", "The_player_@@_is_not_authorized_to_run_the_command_/sign" => "", "The_player_@@_is_authorized_to_run_the_command_/sign" => "", "Touch_on_the_Sign_that_you_want_to_know_the_information" => "", "You_have_authorized_@@_to_create_the_Signs_without_the_blocks_in_the_inventory" => "", "Now_this_Sign_is_owned_by_@@" => "", "The_player_was_not_found" => "", "You_can_not_buy_in_creative" => "", "There_is_a_problem_with_the_creation_of_the_Sign" => ""];
            foreach($this->mex as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->mex[$var] = $c;
            }                      
        }
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->config = new Config($dataResources. "config.yml", Config::YAML, ["version" => 70, "signCreated" => "admin", "lastChange" => time()]);
        $this->plr = new Config($dataResources. "player_authorized.yml", Config::YAML);
        
        if($this->config->get("version") != 70){
            foreach($this->sign->getAll() as $var => $c){
                if(!isset($c["time"])) $c["time"] = date("M-d h:ia", time());
                if(!isset($c["sold"]))$c["sold"] = 0;
                if(!isset($c["earned"]))$c["earned"] = 0;       
                if(!isset($c["direction"])) $c["direction"] = 0;
                    
                if(!isset($c["damage"])){
                    $c["damage"] = $c["meta"];
                    unset($c["meta"]);
                }
                $this->sign->set($var, array_merge($c));
                $this->sign->save();
            }        
            $this->config->set("version", 70);
            $this->config->save();    
        }     
        
        if($this->getServer()->getPluginManager()->getPlugin("PocketMoney") == true) $this->PocketMoney = true;
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == true) $this->EconomyS = true;
        
        if($this->PocketMoney == false && $this->EconomyS == false){
            $this->getLogger()->info(TextFormat::RED."This plugin to work needs the plugin PocketMoney or EconomyS");
            $this->getServer()->shutdown();
        }  
      
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->respawnAllSign();
    }    
        
    public function playerBlockBreak(BlockBreakEvent $event){
        if($event->getBlock()->getID() == Item::SIGN || $event->getBlock()->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();
            
            $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());            
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
            
            if($this->sign->exists($var)){
                $get = $this->sign->get($var);
                if(strtolower($get["maker"]) == strtolower($player->getDisplayName())){
                    $item = Item::get($get["id"], $get["damage"], $get["available"]);
                    if($player->getInventory()->canAddItem($item)){
                        $player->getInventory()->addItem($item);
                              
                        $this->sign->remove($var);
                        $this->sign->save();
                        $player->sendMessage($this->tag. $this->mex["The_Sign_successfully_removed"]);
                    }else{
                        $player->sendMessage($this->tag. $this->mex["You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign"]);
                        $event->setCancelled();
                    }                        
                }else{
                    $event->getPlayer()->sendMessage($this->tag. $this->mex["The_selected_Sign_is_not_your"]);
                    $event->setCancelled();   
                }
            }
        }
    }
   
    public function playerBlockTouch(PlayerInteractEvent $event){ 
        if($event->getBlock()->getID() == Item::SIGN || $event->getBlock()->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();     
            
            $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
           
            if($this->sign->exists($var)){
                $get = $this->sign->get($var);                
                if(isset($this->temp[$player->getDisplayName()])){                    
                    if($this->temp[$player->getDisplayName()]["action"] == "refill"){
                        if($get["maker"] == $player->getDisplayName()){
                            $item = Item::get($get["id"], $get["damage"], $this->temp[$player->getDisplayName()]["amount"]);
                            if($this->hasItemPlayer($player, $item) == true && $this->removeItemPlayer($player, $item) == true){
                                $get["available"] += $item->getCount();
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();                    
                                
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["The_Sign_was_stocked_with_success"]);                                    
                            }else
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_enough_blocks_to_fill_the_Sign"]);
                        }else                      
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_selected_Sign_is_not_your"]); 
                        unset($this->temp[$player->getDisplayName()]);  
                        return;
                    
                    }elseif($this->temp[$player->getDisplayName()]["action"] == "view"){
                        $mex = [str_replace("@@", $get["maker"], $this->mex["This_Sign_is_owned_by_@@"]), str_replace("@@", $get["time"], $this->mex["This_Sign_was_created_@@"]), str_replace("@@", $get["available"], $this->mex["There_are_still_@@_blocks/items"]), str_replace("@@", $get["sold"], $this->mex["They_were_sold_@@_blocks/items_with_this_Sign"]), str_replace("@@", $get["earned"].$this->getValue(), $this->mex["The_owner_has_earned_@@"])];

                        foreach($mex as $message)
                            $event->getPlayer()->sendMessage($this->tag. $message);
                        unset($this->temp[$player->getDisplayName()]);  
                        return;
                        
                    }elseif($this->temp[$player->getDisplayName()]["action"] == "set"){
                        if($get["maker"] == $player->getDisplayName()){
                            if($this->temp[$player->getDisplayName()]["arg"] == "amount"){
                                $get["amount"] = $this->temp[$player->getDisplayName()]["value"];
                                
                                $this->sign->set($var, array_merge($get));
                                $this->sign->save();
                                
                                $event->getPlayer()->sendMessage($this->tag. str_replace("@@", $get["amount"], $this->mex["You_set_the_amount_of_the_Sign_in_@@"]));
                            }elseif($this->temp[$player->getDisplayName()]["arg"] == "price"){
                                $get["cost"] = $this->temp[$player->getDisplayName()]["value"];
                                
                                $this->sign->set($var, array_merge($get));
                                $this->sign->save();
                            
                                $event->getPlayer()->sendMessage($this->tag. str_replace("@@", $get["cost"], $this->mex["The_cost_of_the_contents_of_this_Sign_is_now_@@"]));
                            }elseif($this->temp[$player->getDisplayName()]["arg"] == "maker"){
                                $name = strtolower($this->temp[$player->getDisplayName()]["name"]);
                                $found = false;
                                foreach($this->plr->getAll() as $z => $c){
                                    if(strtolower($z) == $name){ 
                                        $name = $z;
                                        $found = true;
                                        break;
                                    }
                                }      
                                if($found){
                                    $get["maker"] = $name;                                
                                    $this->sign->set($var, array_merge($get));
                                    $this->sign->save();
                                    
                                    $event->getPlayer()->sendMessage($this->tag. str_replace("@@", $name, $this->mex["Now_this_Sign_is_owned_by_@@"]));
                                }else
                                    $event->getPlayer()->sendMessage($this->tag. $this->mex["The_player_was_not_found"]);
                            }
                            $this->respawnSign($var);
                                                        
                        }else
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_selected_Sign_is_not_your"]); 
                        unset($this->temp[$player->getDisplayName()]);  
                        return;
                    }
                }
                if($player->getGamemode() == 1){
                    $event->getPlayer()->sendMessage($this->tag. $this->mex["You_can_not_buy_in_creative"]);
                    return;
                }
                if($this->getMoney($player->getDisplayName()) < $get["cost"])
                    $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_enough_money"]);     
                else{
                    if($get["available"] - $get["amount"] < 0)
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["The_content_of_the_Sign_is_sold_out"]);
                    else{
                        $item = Item::get($get["id"], $get["damage"], $get["amount"]);             
                        if($player->getInventory()->canAddItem($item)){
                            $this->addMoney($get["maker"], $get["cost"]);   
                            $this->addMoney($player->getDisplayName(), -($get["cost"]));

                            $player->getInventory()->addItem($item);
                                
                            $get["available"] -= $get["amount"];
                            $get["sold"] += $get["amount"];
                            $get["earned"] += $get["cost"];
                           
                            $this->sign->set($var, array_merge($get)); 
                            $this->sign->save();
     
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["You_bought_the_contents_of_the_Sign"]);
                        }else
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_the_space_to_buy_the_contents_of_this_Sign"]);
                    } 
                }                
                $this->respawnSign($var);
            }
        }   
    }       
    
    public function signChangeEvent(SignChangeEvent $event){
        if(strtolower(trim($event->getLine(0))) == "[signshop]" || strtolower(trim($event->getLine(0))) == "/signshop"){
            $player = $event->getPlayer();
            $error = false;
            if($this->plr->get(strtolower($player->getName()))["authorized"] == true){
                $item = $event->getLine(1);
                if(is_numeric($item) && $item > 0){
                    $id = $item;
                    $damage = 0;                    
                }elseif(count(explode(":", $item)) == 2){
                    $item = explode(":", $item);
                    $id = $item[0];
                    $damage = $item[1];
                    if(!is_numeric($id) || !is_numeric($damage))
                        $error = true;
                }else{
                    $item = $this->items->getBlock($item);                   
                    $id = $item->getID();
                    $damage = $item->getDamage();
                    if($id == 0) $error = true;
                }
                
                $line2 = $event->getLine(2);
                if(count(explode("x", $line2)) == 2){
                    $line2 = explode("x", $line2);
                    if(is_numeric($line2[0]) && is_numeric($line2[1])){
                        $cost = $line2[0];
                        $amount = $line2[1];
                        if($cost < 0 || $amount < 0 || $amount > 45 * 64)
                            $error = true;
                    }else
                        $error = true;
                }else
                    $error = true;
                              
                $available = $event->getLine(3);
                if(is_numeric($available)){
                    if($available < $amount)
                        $error = true;                    
                }else $error = true;
                
                if($error == false){
                    if($this->hasItemPlayer($player, Item::get($id, $damage, $available)) == true){      
                        if($this->removeItemPlayer($player, Item::get($id, $damage, $available)) == true){
                            $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());
                            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
              
                            $this->sign->set($var, ["id" => $id, "damage" => $damage, "amount" => $amount, "available" => $available, "cost" => $cost, "maker" => $player->getDisplayName(), "time" => date("M-d h:ia", time()), "sold" => 0, "earned" => 0, "direction" => floor((($player->yaw + 180) * 16 / 360) + 0.5) & 0x0F]);
                            $this->sign->save();
   
                            $player->sendMessage($this->tag. $this->mex["Sign_successfully_created"]);
                            $event->setLine(0, "[".$player->getDisplayName()."]");
                            $event->setLine(1, $this->items->getName($id, $damage));
                            $event->setLine(2, "Amount: x".$amount);
                            $event->setLine(3, "Price: ".$cost.$this->getValue());
   
                            $this->respawnSign($var);                            
                        }else
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_item_was_not_found_or_does_not_have_enough_items"]);           
                    }else
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["The_item_was_not_found_or_does_not_have_enough_items"]); 
                }
                if($error == true){
                    $event->setLine(0, "");
                    $event->setLine(1, "");
                    $event->setLine(2, "");
                    $event->setLine(3, "");
                    $player->sendMessage($this->tag. $this->mex["There_is_a_problem_with_the_creation_of_the_Sign"]);
                }
            }else
                $player->sendMessage($this->tag.$this->mex["You_are_not_authorized_to_run_this_command"]);
        }   
    }    
        
    private function removeItemPlayer(Player $player, Item $item){
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
        if($this->plr->get(strtolower($player->getDisplayName()))["authorized"] == "super") return true;
        $ris = 0;
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); ++$i){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();      
            }
        }
        if($ris >= $item->getCount()) return true;
        else return false;
    }
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        $this->respawnAllSign();    
    
        if($this->plr->exists(strtolower($player)))
            if($this->plr->get($player)["changed"] > $this->config->get("lastChanged")) return;
        
        $authorized = false;
        if($this->config->get("signCreated") == "admin" && $player->isOp()) $authorized = true;            
        if($this->config->get("signCreated") == "all") $authorized = true;
            
        $this->plr->set(strtolower($player->getDisplayName()), ["authorized" => $authorized, "changed" => time()]);
        $this->plr->save(); 
    }
    
    public function onCommandUser(CommandSender $sender, Command $command, $label, array $args){
        if($this->plr->get(strtolower($sender->getName()))["authorized"] == true){           
            switch(strtolower($args[0])){
                case "reload":
                    if($sender->isOp()) $this->chat($sender, $this->respawnAllSign(), "info");
                    else $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
                    break;
        
                case "h":
                case "help":
                    if($sender->isOp())
                        $mex = ["/sign refill <".$this->mex["amount"].">", "/sign reload", "/sign view", "/sign set <".$this->mex["amount"]."-".$this->mex["cost"]."-".$this->mex["player"]];  
                    else
                        $mex = ["/sign refill <".$this->mex["amount"].">", "/sign view"];  
                    foreach($mex as $var)
                        $this->chat($sender, $var, "info");
                    break;
                                                    
                case "r":
                case "refill":
                    if(count($args) != 2){
                        $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                        break;                                    
                    }
                    if(!is_numeric($args[1]) || $args[1] < 0 || $args[1] > (64 * 45)){
                        $this->chat($sender, str_replace("@@", $this->mex["amount"], $this->mex["Invalid_value_of_@@"]), "error");
                        break;
                    }
                    $this->temp[$sender->getName()] = ["action" => "refill", "amount" => $args[1]];
                    $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");
                    break;
                       
                case "v":
                case "view":
                    $this->temp[$sender->getName()] = ["action" => "view"];
                    $this->chat($sender, $this->mex["Touch_on_the_Sign_that_you_want_to_know_the_information"], "success");
                    break;
                
                case "set":
                    if(count($args) == 3){
                        switch(strtolower($args[1])){
                            case "amount":
                                if(is_numeric($args[2]) && $args[2] > 0 && $args[2] < (64 * 45)){                                        
                                    $this->temp[$sender->getName()] = ["action" => "set", "arg" => "amount", "value" => $args[2]];
                                    $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");                             
                                }else
                                    $this->chat($sender, str_replace("@@", $this->mex["amount"], $this->mex["Invalid_value_of_@@"]), "error");           
                                break;
                            case "price":
                                if(is_numeric($args[2]) && $args[2] > 0){                                        
                                    $this->temp[$sender->getName()] = ["action" => "set", "arg" => "price", "value" => $args[2]];
                                    $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");                                 
                                }else
                                    $this->chat($sender, str_replace("@@", $this->mex["cost"], $this->mex["Invalid_value_of_@@"]), "error");
                                break;
                            case "maker": 
                                if($args[2] != " "){
                                    $this->temp[$sender->getName()] = ["action" => "set", "arg" => "maker", "name" => $args[2]];
                                    $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");                                 
                                }else
                                   $this->chat($sender, str_replace("@@", $this->mex["cost"], $this->mex["Invalid_value_of_@@"]), "error");
                                break;
                        }                            
                    }else
                        $this->chat($sender, $this->mex["Invalid_arguments"], "error");                  
                    break;
            }
        }else
            $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "sign"){ 
            $this->chat($sender, "Usage: /".$command->getName()." ".$args[0], "info");
            if($args == false) $args[0] = "help";
            if(strtolower($sender->getName()) != "console") return $this->onCommandUser($sender, $command, $label, $args);
                
            switch(strtolower($args[0])){    
                case "reload":
                    $this->chat($sender, $this->respawnAllSign(), "info");
                    break;
                    
                case "h":
                case "help":                         
                    $mex = ["/sign reload", "/sign setup <admin-list-all>", "/sign player <show-auth-unauth-super> <".$this->mex["player"].">"];                 
                    foreach($mex as $var)
                        $this->chat($sender, $var, "info");
                    break;
                        
                case "p":
                case "player":
                    if(count($args) == 3){
                        switch(strtolower($args[1])){
                            case "auth":
                                if($this->plr->exists(strtolower($args[2]))){
                                    $this->plr->set(strtolower($args[2]), ["authorized" => true, "changed" => time()]);
                                    $this->plr->save();   
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["You_have_authorized_@@_to_use_the_command_/sign"]), "info");
                                }else
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_exists"]), "info");
                                break;
                            case "unauth":
                                if($this->plr->exists(strtolower($args[2]))){
                                    $this->plr->set(strtolower($args[2]), ["authorized" => false, "changed" => time()]);
                                    $this->plr->save();            
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["You_have_unauthorized_@@_to_use_the_command_/sign"]), "info");
                                }else
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_exists"]), "info");
                                break;
                            case "super":
                                if($this->plr->exists(strtolower($args[2]))){
                                    $this->plr->set(strtolower($args[2]), ["authorized" => "super", "changed" => time()]);
                                    $this->plr->save();            
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["You_have_authorized_@@_to_create_the_Signs_without_the_blocks_in_the_inventory"]), "info");
                                }else
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_exists"]), "info");
                                break;
                            case "show":
                                if($this->plr->exists(strtolower($args[2]))){
                                    if($this->plr->get(strtolower($args[2]))["authorized"] == true)
                                        $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_authorized_to_run_the_command_/sign"]), "success");
                                    else
                                        $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_authorized_to_run_the_command_/sign"]), "success");
                                }else
                                    $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_exists"]), "info");
                                break;                    
                        }
                    }else
                        $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                    break;
                
                case "setup":
                    $args[1] = strtolower($args[1]);
                    if($args[1] == "admin" || $args[1] == "list" || $args[1] == "all"){                                 
                        $this->config->set("signCreated", $args[1]);
                        $this->config->set("lastChange", time());
                        $this->config->save();
                        $this->chat($sender, str_replace("@@", $args[1], $this->mex["Now_@@_can_use_the_command_/sign"]), "info");
                                    
                        foreach($this->plr->getAll() as $var => $c){
                            $auth = false;
                            if($args[1] == "all") $auth = true;
                                          
                            if($args[1] == "admin"){
                                foreach(Server::getInstance()->getOnlinePlayers() as $player){
                                    if(strtolower($player->getDisplayName()) == strtolower($var) && $player->isOp()) $auth = true;                                                     
                                } 
                            }
                            $this->plr->set($var, ["authorized" => $auth, "changed" => time()]);
                            $this->plr->save();      
                        }                                   
                    }else
                        $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                    break;  
            }            
        }
    }
           
    private function spawnSign(Position $pos, $get){  
        $id = $this->items->getName($get["id"], $get["damage"]);      
        if($get["cost"] == 0) $get["cost"] = "FREE";        
            
        if(!$pos->level->isChunkGenerated($pos->x, $pos->z)) $pos->level->generateChunk($pos->x, $pos->z);
    
        /* TODO if($pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN || $pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN_POST)
            $pos->level->setBlock($pos, Block::get(Item::SIGN_POST), $get["direction"], true);*/
        $sign = new Sign($pos->level->getChunk($pos->x >> 4, $pos->z >> 4, true), new Compound(false, array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", "[".$get["maker"]."]"),
            new String("Text2", $id),
            new String("Text3", "Amount: x".$get["amount"]),
            new String("Text4", "Price: ".$get["cost"].$this->getValue())
            )));
        $sign->saveNBT();
        $pos->level->addTile($sign);        
    }   
    
    private function respawnSign($var){  
        if($this->sign->exists($var)){
            $g = explode(":", $var);
            if(!isset($g[3]))
                $g[3] = Server::getInstance()->getDefaultLevel()->getName();
                        
            $g[3] = str_replace("%", " ", $g[3]);
            if(Server::getInstance()->isLevelGenerated($g[3]) == true)
                $this->spawnSign(new Position($g[0], $g[1], $g[2], Server::getInstance()->getLevelByName($g[3])), $this->sign->get($var));            
        }
    }
    
    public function respawnAllSign(){
        if(count($this->sign->getAll())<=0)
            return $this->mex["There_is_not_Sign_in_the_world"];
        else{
            foreach($this->sign->getAll() as $var => $c)
                $this->respawnSign($var);                
            return $this->mex["All_Signs_respawned"];
        }
    }
    
    private function getValue(){
        if($this->PocketMoney == true) return "pm";
        elseif($this->EconomyS == true) return "$";              
    }    
    
    private function getMoney($player){
        if($this->PocketMoney == true) return $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->getMoney($player);
        elseif($this->EconomyS == true) return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player);         
    }
    
    private function addMoney($player, $value){
        if($this->PocketMoney == true) $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->grantMoney($player, $value);
        elseif($this->EconomyS == true) $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->setMoney($player, $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player) + $value);
    }
        
    private function chat(Player $player, $mex, $style_mex = "default"){
        $p = "[SignShop] ";
        switch($style_mex){
            case "error":               
                $player->sendMessage(TextFormat::RED.$p.$mex);
                break;
            case "success":
                $player->sendMessage(TextFormat::GREEN.$p.$mex);
                break;
            case "info":
                $player->sendMessage(TextFormat::AQUA.$p.$mex);
                break;
            case "default":
                $player->sendMessage($p.$mex);
                break;
        }
    }
    
    public function onDisable(){
        $this->sign->save();
        $this->plr->save(); 
        $this->config->save();
    }
}