<?php
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

class SignShop extends PluginBase implements Listener{    
    private $sign, $config, $plr, $mex;
    private $temp = [];
    private $PocketMoney = false;
    private $EconomyS = false;   
                    
    public function onEnable(){  
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) @mkdir($dataResources, 0755, true);
        
        $continue = false;
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
            if(isset($this->mex["version_mex"])){
                if($this->mex["version_mex"] != 061)
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else{
                $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
            }             
        }
        if($continue == false){            
            $this->mex = ["version_mex" => 061, "This_plugin_to_work_needs_the_plugin_PocketMoney_or_EconomyS" => "", "The_Sign_successfully_removed" => "", "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => "", "The_Sign_was_stocked_with_success" => "", "You_do_not_have_enough_blocks_to_fill_the_Sign" => "", "The_selected_Sign_is_not_your" => "", "You_do_not_have_enough_money" => "", "The_content_of_the_Sign_is_sold_out" => "", "You_bought_the_contents_of_the_Sign" => "", "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => "", "Sign_successfully_created" => "", "The_item_was_not_found_or_does_not_have_enough_items" => "", "Invalid_arguments" => "", "Invalid_value_of_@@" => "", "item" => "", "amount" => "", "cost" => "", "available" => "", "cost" => "", "player" => "", "Now_place_the_Sign" => "", "You_are_not_authorized_to_run_this_command" => "", "Now_touch_on_the_Sign_that_you_want_to_fill" => "", "You_have_authorized_@@_to_use_the_command_/sign" => "", "You_have_unauthorized_@@_to_use_the_command_/sign" => "", "Now_@@_can_use_the_command_/sign" => "", "There_is_not_Sign_in_the_world" => "", "All_Signs_respawned" => "", "The_player_@@_is_not_exists" => ""];
            foreach($this->mex as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->mex[$var] = $c;
            }                      
        }
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->config = new Config($dataResources. "config.yml", Config::YAML, ["version" => "0.6.1", "signCreated" => "admin", "lastChange" => time()]);
        $this->plr = new Config($dataResources. "player_authorized.yml", Config::YAML);
        
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
                        $this->chat($player, $this->mex["The_Sign_successfully_removed"], "succes");
                    }else{
                        $this->chat($player, $this->mex["You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign"], "error");
                        $event->setCancelled();
                    }                        
                }else{
                    $event->setCancelled();  
                }                        
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
                    if($this->temp[$player->getDisplayName()]["action"] == "refill" && $get["maker"] == $player->getDisplayName()){
                        $item = Item::get($get["id"], $get["meta"], $this->temp[$player->getDisplayName()]["amount"]);
                        if($this->hasItemPlayer($player, $item) == true && $this->removeItemPlayer($player, $item) == true){
                            $get["available"] = $get["available"] + $this->temp[$player->getDisplayName()]["amount"];
                            $this->sign->set($var, array_merge($get)); 
                            $this->sign->save();                    
                            
                            $this->chat($player, $this->mex["The_Sign_was_stocked_with_success"], "succes"); 
                            unset($this->temp[$player->getDisplayName()]);                                       
                        }else{
                            $this->chat($player, $this->mex["You_do_not_have_enough_blocks_to_fill_the_Sign"], "error");
                        }
                    }else{                      
                        $this->chat($player, $this->mex["The_selected_Sign_is_not_your"], "error"); 
                    }
                    $continue = false;                
                    }
                if($continue){
                    $money_player = $this->getMoney($player->getDisplayName());
                    if($money_player < $get["cost"]){
                        $this->chat($player, $this->mex["You_do_not_have_enough_money"], "error");                    
                    }else{
                        if($get["available"] - $get["amount"] < 0){
                            $this->chat($player, $this->mex["The_content_of_the_Sign_is_sold_out"], "error");
                        }else{
                            $item = Item::get($get["id"], $get["meta"], $get["amount"]);
                                                      
                            if($player->getInventory()->canAddItem($item)){
                                $this->addMoney($get["maker"], $get["cost"]);   
                                $this->addMoney($player->getDisplayName(), -($get["cost"]));
                                
                                $player->getInventory()->addItem($item);
                                
                                $get["available"] = $get["available"] - $get["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();
                                $this->chat($player, $this->mex["You_bought_the_contents_of_the_Sign"], "succes");
                            }else{
                                $this->chat($player, $this->mex["You_do_not_have_the_space_to_buy_the_contents_of_this_Sign"], "error");
                            }
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
                            $this->sign->set($var, ["id" => $z["id"], "meta" => $z["meta"], "amount" => $z["amount"], "available" => $z["available"], "cost" => $z["cost"], "maker" => $player->getDisplayName()]);
                            $this->sign->save();
                     
                            $this->respawnSign($var);
                            unset($this->temp[$player->getDisplayName()]);
                            $this->chat($player, $this->mex["Sign_successfully_created"], "success");
                        }else{
                            $this->chat($player, $this->mex["The_item_was_not_found_or_does_not_have_enough_items"], "error");
                        }               
                    }else{
                        $this->chat($player, $this->mex["The_item_was_not_found_or_does_not_have_enough_items"], "error");
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
                    }else{
                        $player->getInventory()->setItem($i, Item::get($item->getID(), $item->getDamage(), $ris));                        
                        return true;
                    }           
                }
            }
        }               
        return false;
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
                
                $this->chat($sender, "Using /".$command->getName()." ".$args[0], "info");
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
                            $mex=array("/sign create <".$this->mex["item"]."> <".$this->mex["amount"]."> <".$this->mex["cost"]."> <".$this->mex["available"].">", "/sign refill <".$this->mex["amount"].">", "/sign reload", "/sign setup <admin-list-all>", "/sign player <".$this->mex["player"].">");
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
                        if(!is_numeric($args[1]) || $args[1] < 0){
                            $this->chat($sender, str_replace("@@", $this->mex["amount"], $this->mex["Invalid_value_of_@@"]), "error");
                            break;
                        }
                        $this->temp[$sender->getName()] = ["action" => "refill", "amount" => $args[1]];
                        $this->chat($sender, $this->mex["Now_touch_on_the_Sign_that_you_want_to_fill"], "error");
                        break;
                    case "p":
                    case "player":
                        if(!isset($args[1])){
                            $player = strtolower($args[1]);
                            if($this->plr->exists($player)){
                                $authorized = !$this->plr->get($player)["authoried"];
                                $this->plr->set($player, ["authorized" => $authorized, "changed" => time()]);
                                               
                                if($authorized) $this->chat($sender, str_replace("@@", $player, $this->mex["You_have_authorized_@@_to_use_the_command_/sign"]), "info");
                                else $this->chat($sender, str_replace("@@", $player, $this->mex["You_have_unauthorized_@@_to_use_the_command_/sign"]), "info");
                                                         
                                $this->plr->set($player, ["authorized" => !$this->plr->get($player)["authorized"], "changed" => time()]);
                            }else{
                                $this->chat($sender, str_replace("@@", $player, $this->mex["The_player_@@_is_not_exists"]), "info");
                            }
                        }else{                                            
                            $this->chat($sender, $this->mex["Invalid_arguments"], "error");
                        }                                        
                        break;
                        /*TODO
                        case "s":
                        case "show":
                            break;*/
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
       
    private function playerFromString($player){
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player) return $var;
        }      
    }
           
    private function signSpawn(Vector3 $pos, $world, $get){            
        if($get["cost"] == 0) $get["cost"] = "FREE";
        
        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4, true), new Compound("", array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", $get["maker"]),
            new String("Text2", "Item: ".Item::get($get["id"], 0 , $get["meta"])->getName()),
            new String("Text3", "Cost: ".$get["cost"]." X".$get["amount"]),
            new String("Text4", "Available: ".$get["available"])
            )));
        $sign->saveNBT();
    }   
    
    private function respawnSign($var){
        $output = "";        
        if($this->sign->exists($var)){
            $g = explode(":", $var);
            if($g[3] == "")
                $level = Server::getInstance()->getDefaultLevel()->getName();
            else
                $level = Server::getInstance()->getLevelByName($g[3]);
            if(Server::getInstance()->loadLevel($level->getName()) != false){
                $this->signSpawn(new Vector3($g[0], $g[1], $g[2]), $level, $this->sign->get($var));            
            }
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
    
    private function getMoney($player){
        if($this->PocketMoney == true) return $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->getMoney($player);
        elseif($this->EconomyS == true) return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player);         
    }
    
    private function addMoney($player, $value){
        if($this->PocketMoney == true) $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->grantMoney($player, $value);
        elseif($this->EconomyS == true) $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->setMoney($player, $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player) + $value);
    }
        
    private function chat($player, $mex, $style_mex = "default"){
        $p= "[SignShop] ";
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
                $player->sendMessage(TextFormat::WHITE.$p.$mex);
                break;
        }
    }
    
    public function onDisable(){
        $this->sign->save();
        $this->plr->save(); 
        $this->config->save();
    }
}