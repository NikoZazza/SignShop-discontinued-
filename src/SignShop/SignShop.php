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
class SignShop extends PluginBase implements Listener{    
    private $sign, $config, $plr, $action;
    private $PocketMoney = false;
    private $EconomyS = false;
     
    public function onEnable(){  
        if (!file_exists($this->getDataFolder())){
            @mkdir($this->getDataFolder(), 0755, true);
        } 
        //player: action: remove, param array()
        $this->action = new Config($this->getDataFolder(). "action.yml", Config::YAML);
        $this->sign = new Config($this->getDataFolder(). "sign.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML, array("version" => "0.4.0", "signCreated" => "admin"));
        $this->plr = new Config($this->getDataFolder(). "player_authorized.yml", Config::YAML);

        if($this->getServer()->getPluginManager()->getPlugin("PocketMoney") ==true){
            $this->PocketMoney = true;
        }
        /* TODO  
        if($this->getServer()->getPluginManager()->getPlugin("LoadEconomyAPI") ==true){
            $this->EconomyS = true;
        } */       
        if($this->PocketMoney == false && $this->EconomyS == false){
            $this->getLogger()->info(TextFormat::RED ."This plugin to work needs the plugin PocketMoney or EconomyS");
            $this->getServer()->shutdown();
        }
        $this->action->setAll(array());
        $this->action->save();        
        
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), 12000);  
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $player = $event->getPlayer();
            $world = $event->getBlock()->getLevel();
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world->getName();
            
            if($this->sign->exists($var)){
                if($this->action->exists($player->getDisplayName()) && $this->action->get($player->getDisplayName())["action"] == "remove"){
                    $get = $this->sign->get($var);
                    if($get["maker"] == $player->getDisplayName()){
                        $item = Item::get($get["id"], 0, $get["available"]);
                        if($player->getInventory()->canAddItem($item)){
                            $player->getInventory()->addItem($item);
                              
                            $this->sign->remove($var);
                            $this->sign->save();
                            $this->action->remove($player->getDisplayName());
                            $this->action->save();
                            $this->chat($player, "The Sign successfully removed!", "succes");
                        }else{
                            $this->chat($player, "You need to free up space from your inventory to remove this Sign!", "warning");
                            $event->setCancelled();
                        }
                    }else{
                        $this->chat($player, "The Sign isn't yours!", "error");
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
                if($this->action->exists($player->getDisplayName()) && $this->action->get($player->getDisplayName())["action"] == "refill"){
                  
                        if($get["maker"] == $player->getDisplayName()){
                            $item = Item::get($get["id"], 0, $this->action->get($player->getDisplayName())["amount"]);
                            if($this->hasItemPlayer($player, $item) == true && $this->removeItemPlayer($player, $item) == true){
                                $get["available"] = $get["available"] + $this->action->get($player->getDisplayName())["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();                    
                            
                                $this->chat($player, "The Sign was stocked with success", "succes"); 
                                $this->action->remove($player->getDisplayName());
                                $this->action->save();
                                        
                            }else{
                                $this->chat($player, "You do not have enough blocks to fill the Sign!", "warning");
                            }
                        }else{                      
                            $this->chat($player, "The selected Sign isn't your!", "error"); 
                        }
                        $continue = false;

                }
                if($continue == true){
                    $money_player = $this->getMoney($player->getDisplayName());
                    if($money_player < $get["cost"]){
                        $this->chat($player, "You do not have enough money!", "error");                    
                    }else{
                        if($get["available"] - $get["amount"] < 0){
                            $this->chat($player, "The content of the sign is sold out!", "warning");
                        }else{
                            $item = Item::get($get["id"], 0, $get["amount"]);
                                                      
                            if($player->getInventory()->canAddItem($item)){
                                $this->addMoney($get["maker"], $get["cost"]);     
                                $this->addMoney($player->getDisplayName(), -($get["cost"]));
                                
                                $player->getInventory()->addItem($item);
                                
                                $get["available"] = $get["available"] - $get["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();
                                $this->chat($player, "You bought the contents of the sign!", "succes");
                                
                            }else{
                                $this->chat($player, "You do not have the space to buy the contents of this Sign!", "warning");
                            }
                        } 
                    }
                }
                $this->respawnSign($var, false);
            }
        }   
    }   
    
    public function playerBlockPlace(BlockPlaceEvent $event){
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $player = $event->getPlayer();
                
            $world = $event->getBlock()->getLevel();   
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world->getName();
               
            if($this->action->exists($player->getDisplayName()) && $this->action->get($player->getDisplayName())["action"] == "create"){
                $z = $this->action->get($player->getDisplayName());
                if($this->hasItemPlayer($player, Item::get($z["id"], 0, $z["available"])) == true){      
                    if($this->removeItemPlayer($player, Item::get($z["id"], 0, $z["available"])) == true){
                        $this->sign->set($var, array("id" => $z["id"], "amount" => $z["amount"], "available" => $z["available"], "cost" =>  $z["cost"], "maker" => $player->getDisplayName()));
                        $this->sign->save();
                     
                        $this->respawnSign($var, false);
                        $this->action->remove($player->getDisplayName());
                        $this->action->save();
                        $this->chat($player, "Sign successfully created!", "success");
                    }else{
                        $this->chat($player, "The item was not found or does not have enough items...", "error");
                    }              
                        
                }else{
                    $this->chat($player, "The item was not found!", "error");
                }               
                
            } 
        }
    }
        
    public function removeItemPlayer(Player $player, Item $item){
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getCount() >= $item->getCount()){
                    $player->getInventory()->setItem($i, Item::get($item->getID(), 0, $inv->getCount() - $item->getCount()));
                    return true;              
                }
            }
        }               
        return false;
    }
    
    public function hasItemPlayer(Player $player, Item $item){
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i +1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getCount() >= $item->getCount()){
                    return true;
                }
            }
        }
        return false;
    }
    
    public function playerAuthorized($player){
        $player = strtolower($player);
        if($this->plr->exists($player)){
            if($this->plr->get($player)["authorized"] == true){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        if($this->config->get("signCreated") == "admin" && $player->isOp()){
            $this->plr->set(strtolower($player->getDisplayName()), array("authorized" => true));
        }
        if($this->config->get("signCreated") == "all"){
            $this->plr->set(strtolower($player->getDisplayName()), array("authorized" => true));
        }
        $this->plr->save(); 
        
        $this->respawnAllSign();    
    }
    
    public function onCommand(\pocketmine\command\CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "sign"){ 
            if($this->playerAuthorized($sender->getName()) == true || strtolower($sender->getName()) == "console"){
                if($args==false){
                    if($sender->isOp()){  
                        $mex=array("/sign create <item> <amount> <cost> <available>", "/sign remove", "/sign refill <item> <amount>", "/sign respawn <x:y:z> <world>", "/sign auth <player>", "/sign unauth <player>", "/sign authorize <admin-list-all>");
                    }else{
                        $mex = array("/sign create <item> <amount> <cost> <available>", "/sign remove", "/sign refill <item> <amount>");
                    }                
                    foreach($mex as $var){
                        $this->chat($sender, $var, "info");
                    }    
                }else{
                    $this->chat($sender, "Using /".$command->getName()." ".$args[0], "info");
                    switch($args[0]){
                        case "create":
                            if(count($args) != 5){
                                $this->chat($sender, "Invalid arguments", "error");
                                break;
                            }                          
                            if(!( is_numeric($args[1]) && is_numeric($args[2]) && is_numeric($args[3]) && is_numeric($args[4]))){
                                $this->chat($sender, "Invalid arguments", "error");
                                break;
                            }
                            if($args[1] <= 0){
                                $this->chat($sender, "Invalid item", "error");
                                break;
                            }
                            if($args[2] <= 0 || $args[2] > (45 * 64)){
                                $this->chat($sender, "Invalid amount", "error");
                                break;
                            }
                            if($args[3] < 0){
                                $this->chat($sender, "Invalid cost", "error");
                                break;
                            }
                            if($args[4] < 0){
                                $this->chat($sender, "Invalid available", "error");
                                break;
                            }
                            $this->action->set($sender->getName(), array("action" => "create" ,"id" => $args[1], "amount" => $args[2], "cost" => $args[3], "available" => $args[4]));
                            $this->action->save();
                            $this->chat($sender, "Now place the Sign", "info");
                            
                            break;
                            
                        case "remove":
                            $this->action->set($sender->getName(), array("action" => "remove"));
                            $this->action->save();
                            $this->chat($sender, "Now remove the sign desired", "info");
                            break;   
                        
                        case "respawn":
                            if(!$sender->isOp()){
                                $this->chat($sender, "You need to be admin/OP to run this command", "error");
                                break;
                            }
                            if(count($args) != 3){
                                $this->chat($sender, "Invalid arguments", "error");
                                break;
                            }                                            
                            if(!(is_numeric($c[0]) && is_numeric($c[1]) && is_numeric($c[2]))){
                                $this->chat($sender, "Invalid coordinates", "error");
                                break;
                            }                  
             
                            $this->chat($sender, $this->respawnSign((Int)$c[0].":".(Int)$c[1].":".(Int)$c[2].":".$args[2], true), "info");
                            break;
                                
                        case "refill":
                            if(count($args) != 2){
                                $this->chat($sender, "Invalid arguments", "error");
                                break;                                    
                            }
                            if(!is_numeric($args[1]) || $args[1] < 0){
                                $this->chat($sender, "Invalid amount", "error");
                                break;
                            }
                            $this->action->set($sender->getName(), array("action" => "refill", "amount" => $args[1]));
                            $this->action->save();
                            $this->chat($sender, "Now touch on the sign that you want to fill!", "error");
                            break;
                        case "auth":
                            if($sender->isOp() == true){
                                // TODO Fix when player join on the server
                                if(count($args) != 2){
                                    $this->chat($sender, "Invalid arguments", "error");
                                    break;                                    
                                }else{
                                    $this->plr->set(strtolower($args[1]), array("authorized" => true));
                                    $this->plr->save();    
                                    $this->chat($sender, "You have authorized ".$args[1]." to use the command /sign", "info");
                                }     
                                
                            }else{
                                $this->chat($sender, "You are not authorized to run this command", "error");
                            }
                            break;
                        case "unauth":
                            if($sender->isOp() == true){
                                // TODO Fix when player join on the server
                                if(count($args) != 2){
                                    $this->chat($sender, "Invalid arguments", "error");
                                    break;                                    
                                }else{                                   
                                    $this->plr->set(strtolower($args[1]), array("authorized" => true));
                                    $this->plr->save(); 
                                    $this->chat($sender, "You have un-authorized ".$args[1]." to use the command /sign", "info");
                                }                                
                            }else{
                                $this->chat($sender, "You are not authorized to run this command", "error");
                            }
                            break;
                        case "authorize":
                            if($sender->isOp() == true){
                                if(count($args) != 2){
                                    $this->chat($sender, "Invalid arguments", "error");
                                    break;                                    
                                }else{
                                    $args[1] = strtolower($args[1]);
                                    if(!($args[1] != "admin" && $args[1] != "list" && $args[1] != "all")){
                                        $this->config->set("signCreated", $args[1]);
                                        $this->config->save();
                                        $this->chat($sender, "Now ".$args[1]." now everyone can use the command /sign", "success");
                                        $this->reloadAuth();
                                    }                                    
                                }                                
                            }else{
                                $this->chat($sender, "You are not authorized to run this command", "error");
                            }                            
                            break;  
                    }
                }    
            }else{
                $this->chat($sender, "You are not authorized to run this command", "error");
            }   
        }
    }
    
    public function isPlayerOnline($player){
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player){
                return true;
            }
        }      
        return false;
    }
    
    public function playerFromString($player){
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player){
                return $var;
            }
        }      
    }
    
    public function reloadAuth(){
        $auth = $this->config->get("signCreated");
        foreach($this->plr->getAll() as $var => $c){
            $r = false;
            if($auth == "all"){
                $r = true;
            }
            if($auth == "admin"){
                if($this->isPlayerOnline($var) == true){
                    $player = $this->playerFromString($var);
                    if($player->isOp()){
                        $r = true;
                    }
                }
            }
            $this->plr->set($var, array("authorized" => $r));
            $this->plr->save();      
        }        
    }    
       
    public function signSpawn(Vector3 $pos, $world, $get){    
        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4), new Compound("", array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", $get["maker"]),
            new String("Text2", "ID: ".$get["id"]." x".$get["amount"]),
            new String("Text3", "Price: ".$get["amount"]),
            new String("Text4", "Available: ".$get["available"])
            )));
        $sign->saveNBT();
    }   
    
    public function respawnSign($var, $mex){
        $output = "";        
        if($this->sign->exists($var)){
            $g = explode(":", $var);
            if($g[3] == ""){
                $g[3] = Server::getInstance()->getDefaultLevel();
            }
            $this->signSpawn(new Vector3($g[0], $g[1], $g[2]), Server::getInstance()->getLevelByName($g[3]), $this->sign->get($var));
            
            $output = "Sign at position ".$var." spawn success";
        }else{
            $output = "The sign not found";
        }
        
        if($mex == true){
            return $output;
        }
    }
    
    public function respawnAllSign(){
        if(count($this->sign->getAll())<=0){
            return "There isn't sign in the world";
        }else{
            foreach($this->sign->getAll() as $var => $c){
                $this->respawnSign($var, false);    
            }
            return "All signs respawned";
        }
    }
    
    public function getMoney($player){
        if($this->PocketMoney == true){
            return $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->getMoney($player);
        }else{
            if($this->EconomyS == true){
            // TODO return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player);
            }
        }
    }
    
    public function addMoney($player, $value){
        if($this->PocketMoney == true){
            $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->grantMoney($player, $value);
        }else{
            if($this->EconomyS == true){
            // $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->useMoney($player, $value);
            }
        }
    }
        
    public function chat($player, $mex, $style_mex){
        $p= "[SignShop] ";
        switch($style_mex){
            case "error":
                $player->sendMessage(TextFormat::RED.$p.$mex);
                break;
            case "warning":
                $player->sendMessage(TextFormat::YELLOW.$p.$mex);
                break;
            case "success":
                $player->sendMessage(TextFormat::GREEN.$p.$mex);
                break;
            case "info":
                $player->sendMessage(TextFormat::AQUA.$p.$mex);
                break;
            default:
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