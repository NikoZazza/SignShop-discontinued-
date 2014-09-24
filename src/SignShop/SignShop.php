<?php
 /* @author xionbig
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @link https://github.com/xionbig/SignShop
 * @version 0.6.2
  *@Twitter xionbig
 */
namespace SignShop;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\math\Vector3;
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
use pocketmine\level\Level;
use pocketmine\block\Block;
class SignShop extends PluginBase implements Listener{    
    private $sign, $config, $plr, $mex, $items;
    private $temp = [];
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
                if($this->mex["version_mex"] != 062)
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else{
                $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
            }             
        }
        $this->items = new ListItems();
  
        if($continue == false){            
            $this->mex = ["version_mex" => 062, "This_plugin_to_work_needs_the_plugin_PocketMoney_or_EconomyS" => "", "The_Sign_successfully_removed" => "", "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => "", "The_Sign_was_stocked_with_success" => "", "You_do_not_have_enough_blocks_to_fill_the_Sign" => "", "The_selected_Sign_is_not_your" => "", "You_do_not_have_enough_money" => "", "The_content_of_the_Sign_is_sold_out" => "", "You_bought_the_contents_of_the_Sign" => "", "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => "", "Sign_successfully_created" => "", "The_item_was_not_found_or_does_not_have_enough_items" => "", "Invalid_arguments" => "", "Invalid_value_of_@@" => "", "item" => "", "amount" => "", "cost" => "", "available" => "", "cost" => "", "player" => "", "Now_place_the_Sign" => "", "You_are_not_authorized_to_run_this_command" => "", "Now_touch_on_the_Sign_that_you_want_to_do_this_action" => "", "You_have_authorized_@@_to_use_the_command_/sign" => "", "You_have_unauthorized_@@_to_use_the_command_/sign" => "", "Now_@@_can_use_the_command_/sign" => "", "There_is_not_Sign_in_the_world" => "", "All_Signs_respawned" => "", "The_player_@@_is_not_exists" => "", "The_cost_of_the_contents_of_this_Sign_is_now_@@" => "", "This_Sign_is_owned_by_@@" => "", "This_Sign_was_created_@@" => "", "There_are_still_@@_blocks/items" => "", "They_were_sold_@@_blocks/items_with_this_Sign" => "", "The_owner_has_earned_@@" => "", "You_set_the_amount_of_the_Sign_in_@@" => "", "The_player_@@_is_not_authorized_to_run_the_command_/sign" => "", "The_player_@@_is_authorized_to_run_the_command_/sign" => ""];
            foreach($this->mex as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->mex[$var] = $c;
            }                      
        }
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->config = new Config($dataResources. "config.yml", Config::YAML, ["version" => 062, "signCreated" => "admin", "lastChange" => time()]);
        $this->plr = new Config($dataResources. "player_authorized.yml", Config::YAML);
        
        if($this->config->get("version") != 062){
            foreach($this->sign->getAll() as $var => $c){
                $c["time"] = "unknown";
                $c["sold"] = 0;
                $c["earned"] = 0;         
                
                $this->sign->set($var, array_merge($c));
                $this->sign->save();
            }        
            $this->config->set("version", 062);
            $this->config->save();    
        }     
        
        if($this->getServer()->getPluginManager()->getPlugin("PocketMoney") == true) $this->PocketMoney = true;
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == true) $this->EconomyS = true;
        
        if($this->PocketMoney == false && $this->EconomyS == false){
            $this->getLogger()->info(TextFormat::RED.$this->mex["This_plugin_to_work_needs_the_plugin_PocketMoney_or_EconomyS"]);
            $this->getServer()->shutdown();
        }    
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->respawnAllSign();
    }    
        
    public function playerBlockBreak(BlockBreakEvent $event){
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $player = $event->getPlayer();
            $world = $event->getBlock()->getLevel();
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world->getName();
            
            if($this->sign->exists($var)){
                $get = $this->sign->get($var);
                if($get["maker"] == $player->getDisplayName()){
                    $item = Item::get($get["id"], $get["meta"], $get["available"]);
                    if($player->getInventory()->canAddItem($item)){
                        $player->getInventory()->addItem($item);
                              
                        $this->sign->remove($var);
                        $this->sign->save();
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["The_Sign_successfully_removed"]);
                    }else{
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign"]);
                        $event->setCancelled();
                    }                        
                }else
                    $event->setCancelled();                      
            }
        }
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){ 
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $player = $event->getPlayer();     
            $continue = true;
            
            $world = $event->getBlock()->getLevel();
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world->getName();
           
            if($this->sign->exists($var)){
                $get = $this->sign->get($var);                
                if(isset($this->temp[$player->getDisplayName()])){                    
                    if($this->temp[$player->getDisplayName()]["action"] == "refill"){
                        if($get["maker"] == $player->getDisplayName()){
                            $item = Item::get($get["id"], $get["meta"], $this->temp[$player->getDisplayName()]["amount"]);
                            if($this->hasItemPlayer($player, $item) == true && $this->removeItemPlayer($player, $item) == true){
                                $get["available"] = $get["available"] + $this->temp[$player->getDisplayName()]["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();                    
                                
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["The_Sign_was_stocked_with_success"]); 
                                unset($this->temp[$player->getDisplayName()]);                                       
                            }else{
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_enough_blocks_to_fill_the_Sign"]);
                            }
                        }else{                      
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_selected_Sign_is_not_your"]); 
                        }
                        $continue = false; 
                    }elseif($this->temp[$player->getDisplayName()]["action"] == "show"){
                        $mex = [str_replace("@@", $get["maker"], $this->mex["This_Sign_is_owned_by_@@"]), str_replace("@@", $get["time"], $this->mex["This_Sign_was_created_@@"]), str_replace("@@", $get["available"], $this->mex["There_are_still_@@_blocks/items"]), str_replace("@@", $get["sold"], $this->mex["They_were_sold_@@_blocks/items_with_this_Sign"]), str_replace("@@", $get["earned"].$this->getValue(), $this->mex["The_owner_has_earned_@@"])];

                        foreach($mex as $message)
                            $event->getPlayer()->sendMessage($this->tag. $message);
                        unset($this->temp[$player->getDisplayName()]);
                        $continue = false;
                    }elseif($this->temp[$player->getDisplayName()]["action"] == "set"){
                        if($get["maker"] == $player->getDisplayName()){
                            if($this->temp[$player->getDisplayName()]["arg"] == "amount"){
                                $get["amount"] = $this->temp[$player->getDisplayName()]["value"];
                                
                                $this->sign->set($var, array_merge($get));
                                $this->sign->save();
                                
                                $this->respawnSign($var);
                                
                                $event->getPlayer()->sendMessage($this->tag. str_replace("@@", $get["amount"], $this->mex["You_set_the_amount_of_the_Sign_in_@@"]));
                                $continue = false;
                            }elseif($this->temp[$player->getDisplayName()]["arg"] == "price"){
                                $get["cost"] = $this->temp[$player->getDisplayName()]["value"];
                                
                                $this->sign->set($var, array_merge($get));
                                $this->sign->save();
                                
                                $this->respawnSign($var);
                            
                                $event->getPlayer()->sendMessage($this->tag. str_replace("@@", $get["cost"], $this->mex["The_cost_of_the_contents_of_this_Sign_is_now_@@"]));
                                $continue = false;
                            }
                        }else{
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_selected_Sign_is_not_your"]); 
                        }
                        unset($this->temp[$player->getDisplayName()]);
                    }
                }
                if($continue){
                    if($this->getMoney($player->getDisplayName()) < $get["cost"])
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_enough_money"]);     
                    else{
                        if($get["available"] - $get["amount"] < 0)
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_content_of_the_Sign_is_sold_out"]);
                        else{
                            $item = Item::get($get["id"], $get["meta"], $get["amount"]);             
                            if($player->getInventory()->canAddItem($item)){
                                $this->addMoney($get["maker"], $get["cost"]);   
                                $this->addMoney($player->getDisplayName(), -($get["cost"]));

                                $player->getInventory()->addItem($item);
                                
                                $get["available"] = $get["available"] - $get["amount"];
                                $get["sold"] = $get["sold"] + $get["amount"];
                                $get["earned"] = $get["earned"] + $get["cost"];
                           
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();
     
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["You_bought_the_contents_of_the_Sign"]);
                            }else
                                $event->getPlayer()->sendMessage($this->tag. $this->mex["You_do_not_have_the_space_to_buy_the_contents_of_this_Sign"]);
                        } 
                    }
                }
                $this->respawnSign($var);
            }
        }   
        
    }   
    
    public function playerBlockPlace(BlockPlaceEvent $event){
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $player = $event->getPlayer();
                
            $world = $event->getBlock()->getLevel();   
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world->getName();
               
            if(isset($this->temp[$player->getDisplayName()])){
                if($this->temp[$player->getDisplayName()]["action"] == "create"){
                    $z = $this->temp[$player->getDisplayName()];
                    if($this->hasItemPlayer($player, Item::get($z["id"], $z["meta"], $z["available"])) == true){      
                        if($this->removeItemPlayer($player, Item::get($z["id"], $z["meta"], $z["available"])) == true){
                            $this->sign->set($var, ["id" => $z["id"], "meta" => $z["meta"], "amount" => $z["amount"], "available" => $z["available"], "cost" => $z["cost"], "maker" => $player->getDisplayName(), "time" => date("M-d h:ia", time()), "sold" => 0, "earned" => 0]);
                            $this->sign->save();
                     
                            $this->respawnSign($var);
                            unset($this->temp[$player->getDisplayName()]);
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["Sign_successfully_created"]);
                        }else{
                            $event->getPlayer()->sendMessage($this->tag. $this->mex["The_item_was_not_found_or_does_not_have_enough_items"]);
                        }               
                    }else{
                        $event->getPlayer()->sendMessage($this->tag. $this->mex["The_item_was_not_found_or_does_not_have_enough_items"]);
                    } 
                }                
            } 
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
        $ris = 0;
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();      
            }
        }
        if($ris >= $item->getCount())
            return true;
        else
            return false;
    }
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        $check = true;
        if($this->plr->exists(strtolower($player))){
            if($this->plr->get($player)["changed"] > $this->config->get("lastChanged")){
                $check = false;
            }            
        }     
        if($check == true){       
            $authorized = false;
            if($this->config->get("signCreated") == "admin" && $player->isOp()){
                $authorized = true;
            }
            if($this->config->get("signCreated") == "all"){
                $authorized = true;
            }
            $this->plr->set(strtolower($player->getDisplayName()), ["authorized" => $authorized, "changed" => time()]);
            $this->plr->save(); 
        }            
        $this->respawnAllSign();    
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "sign"){ 
            if($this->plr->get(strtolower($sender->getName()))["authorized"] == true || strtolower($sender->getName()) == "console"){
                if($args==false) $args[0] = "help";
                
                $this->chat($sender, "Usage: /".$command->getName()." ".$args[0], "info");
                switch(strtolower($args[0])){
                    case "c":
                    case "create":
                        if(count($args) != 5){
                            $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                            break;
                        }    
                        $meta = 0;
                        $id = $args[1];
                        $e = explode(":", $args[1]);
                        if(count($e) == 2){
                            $id = $e[0];
                            $meta = $e[1];
                        }
                         
                        if(!(is_numeric($id) && is_numeric($meta) && is_numeric($args[2]) && is_numeric($args[3]) && is_numeric($args[4]))){
                            $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                            break;
                        }
                        if($id <= 0 || $meta < 0){
                            $this->chat($sender, str_replace("@@", $this->mex["item"], $this->mex["Invalid_value_of_@@"]), "error");
                            break;
                        }
                        if($args[2] <= 0 || $args[2] > (64 * 45)){
                            $this->chat($sender, str_replace("@@", $this->mex["amount"], $this->mex["Invalid_value_of_@@"]), "error");
                            break;
                        }
                        if($args[3] < 0){
                            $this->chat($sender, str_replace("@@", $this->mex["cost"], $this->mex["Invalid_value_of_@@"]), "error");
                            break;
                        }
                        if($args[4] < 0){
                            $this->chat($sender, str_replace("@@", $this->mex["available"], $this->mex["Invalid_value_of_@@"]), "error");
                            break;
                        }
                        $this->temp[$sender->getName()]= ["action" => "create", "id" => $id, "meta" => $meta, "amount" => $args[2], "cost" => $args[3], "available" => $args[4]];
                        $this->chat($sender, $this->mex["Now_place_the_Sign"], "info");                            
                        break;
                            
                    case "reload":
                        if($sender->isOp()) $this->chat($sender, $this->respawnAllSign(), "info");
                        else $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
                        break;
                    case "h":
                    case "help":
                        if($sender->isOp()){  
                            $mex = array("/sign create <".$this->mex["item"]."> <".$this->mex["amount"]."> <".$this->mex["cost"]."> <".$this->mex["available"].">", "/sign refill <".$this->mex["amount"].">", "/sign reload", "/sign setup <admin-list-all>", "/sign player <show-auth-unauth> <".$this->mex["player"].">", "/sign show");
                        }else{
                            $mex = array("/sign create <".$this->mex["item"]."> <".$this->mex["amount"]."> <".$this->mex["cost"]."> <".$this->mex["available"].">", "/sign refill <".$this->mex["amount"].">", "/sign reload");
                        }                
                        foreach($mex as $var){
                            $this->chat($sender, $var, "info");
                        }    
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
                    case "p":
                    case "player":
                        if($sender->isOp() == false){
                            $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
                            break;
                        }
                        if(count($args) == 3){
                            switch(strtolower($args[1])){
                                case "auth":
                                    if($this->plr->exists(strtolower($args[2]))){
                                        $this->plr->set(strtolower($args[2]), ["authorized" => true, "changed" => time()]);
                                        $this->plr->save();   
                                        $this->chat($sender, str_replace("@@", $player, $this->mex["You_have_authorized_@@_to_use_the_command_/sign"]), "info");
                                    }else{
                                        $this->chat($sender, str_replace("@@", $player, $this->mex["The_player_@@_is_not_exists"]), "info");
                                    }                           
                                    break;
                                case "unauth":
                                    if($this->plr->exists(strtolower($args[2]))){
                                        $this->plr->set(strtolower($args[2]), ["authorized" => false, "changed" => time()]);
                                        $this->plr->save();            
                                        $this->chat($sender, str_replace("@@", $player, $this->mex["You_have_unauthorized_@@_to_use_the_command_/sign"]), "info");
                                    }else{
                                        $this->chat($sender, str_replace("@@", $player, $this->mex["The_player_@@_is_not_exists"]), "info");
                                    }      
                                    break;
                                case "show":
                                    if($this->plr->exists(strtolower($args[2]))){
                                        if($this->plr->get(strtolower($args[2]))["authorized"] == true)
                                            $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_authorized_to_run_the_command_/sign"]), "success");
                                        else
                                            $this->chat($sender, str_replace("@@", $args[2], $this->mex["The_player_@@_is_not_authorized_to_run_the_command_/sign"]), "success");
                                    }else{
                                        $this->chat($sender, str_replace("@@", $player, $this->mex["The_player_@@_is_not_exists"]), "info");
                                    }      
                                    break;                    
                            }
                        }else{
                            $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                        }                                  
                        break;
                                     
                    case "s":
                    case "show":
                        $this->chat($sender, "Adesso tocca il cartello vhe vuoi sapere le info", "success");
                        $this->temp[$sender->getName()] = ["action" => "show"];
                        break;
                    
                    case "set":
                        if(count($args) == 3){
                            switch(strtolower($args[1])){
                                case "amount":
                                    if(is_numeric($args[2]) && $args[2] > 0 && $args[2] < (64 * 45)){                                        
                                        $this->temp[$sender->getName()] = ["action" => "set", "arg" => "amount", "value" => $args[2]];
                                        $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");                             
                                    }else{
                                        $this->chat($sender, str_replace("@@", $this->mex["amount"], $this->mex["Invalid_value_of_@@"]), "error"); 
                                    }          
                                    break;
                                case "price":
                                    if(is_numeric($args[2]) && $args[2] > 0){                                        
                                        $this->temp[$sender->getName()] = ["action" => "set", "arg" => "price", "value" => $args[2]];
                                        $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_do_this_action"], "success");                                 
                                    }else{
                                        $this->chat($sender, str_replace("@@", $this->mex["cost"], $this->mex["Invalid_value_of_@@"]), "error");
                                    }   
                                    break;
                                /*case "maker":
                                    break;*/
                            }                            
                        }else{
                            $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                        }              
                        break;
                        
                    case "setup":
                        if($sender->isOp() == true){
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
                                            if(strtolower($player->getDisplayName()) == strtolower($var)){
                                                if($player->isOp()) $auth = true;                                                     
                                            }
                                        } 
                                    }
                                    $this->plr->set($var, ["authorized" => $auth, "changed" => time()]);
                                    $this->plr->save();      
                                }                                   
                                break;
                            }else{
                                $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                            }                             
                        }else{
                            $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
                        }                            
                        break;  
                }//switch
            }else{
                $this->chat($sender, $this->mex["You_are_not_authorized_to_run_this_command"], "error");
            }   
        }
    }
           
    private function signSpawn(Vector3 $pos, Level $world, $get){            
        if($get["cost"] == 0) 
            $get["cost"] = "FREE";        
        $id = "ID ".$get["id"].":".$get["meta"];
        
        if(!$world->isChunkGenerated($pos->x, $pos->z))
            $world->generateChunk($pos->x, $pos->z);
        if($this->items->isExistsItem($get["id"], $get["meta"]))
            $id = $this->items->getName($get["id"], $get["meta"]);      
        if($world->getBlockIdAt($pos->x, $pos->y, $pos->z) != 63 || $world->getBlockIdAt($pos->x, $pos->y, $pos->z) != 68)
            $world->setBlock($pos, Block::get(63), false, true);
        
        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4, true), new Compound("", array(
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
        $world->addTile($sign);        
    }   
    
    private function respawnSign($var){  
        if($this->sign->exists($var)){
            $g = explode(":", $var);
            if(!isset($g[3]))
                $level = Server::getInstance()->getDefaultLevel();
            else
                $level = Server::getInstance()->getLevelByName($g[3]);
            
            if(Server::getInstance()->loadLevel($level->getName()) == true)
                $this->signSpawn(new Vector3($g[0], $g[1], $g[2]), $level, $this->sign->get($var));            
        }     
    }
    
    public function respawnAllSign(){
        if(count($this->sign->getAll())<=0){
            return $this->mex["There_is_not_Sign_in_the_world"];
        }else{
            foreach($this->sign->getAll() as $var => $c){
                $this->respawnSign($var, false);    
            }
            return $this->mex["All_Signs_respawned"];
        }
    }
    
    public function getValue(){
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
        
    private function chat( $player, $mex, $style_mex = "default"){
        $p= "[SignShop] ";
        switch($style_mex){
            case "error":
               
                $player->sendMessage($p.$mex);
                break;
            case "success":
                $player->sendMessage($p.$mex);
                break;
            case "info":
                $player->sendMessage($p.$mex);
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