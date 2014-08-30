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
    private $sign, $config, $plr, $mex, $temp;
    private $PocketMoney = false;
    private $EconomyS = false;   
    
    public function onEnable(){  
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) @mkdir($dataResources, 0755, true);
       
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
        }else{
            $this->mex = [1 => "This plugin to work needs the plugin PocketMoney or EconomyS!", 2 => "The Sign successfully removed!", 3 => "You need to free up space from your inventory to remove this Sign!", 4 => "The Sign is not yours!", 5 => "The Sign was stocked with success!", 6 => "You do not have enough blocks to fill the Sign!", 7 => "You do not have enough money!", 8 => "The content of the Sign is sold out!", 9 => "You bought the contents of the Sign!", 10 => "You dont have the space to buy the contents of this Sign!", 11 => "Sign successfully created!", 12 => "The item was not found or does not have enough items!", 13 => "Invalid arguments!", 14 => "Invalid value of @@ .", 15 => "world", 16 => "player", 17 => "item", 18 => "amount", 19 => "cost", 20 => "coordinates", 21 => "Now place the Sign!", 22 => "Now remove the Sign desired!", 23 => "You need to be admin to run this command!", 24 => "Now touch on the Sign that you want to fill!", 25 => "You have authorized @@ to use the command /sign.", 26 => "You are not authorized to run this command!", 27 => "You have unauthorized @@ to use the command /sign.", 28 => "Now @@ can use the command /sign.", 29 => "Sign at position @@ spawn success!", 30 => "The Sign at position @@ not found!", 31 => "There is not Sign in the world!", 32 => "All Signs respawned!", 33 => "The selected Sign is not your!", 34 => "available"];
        }
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->config = new Config($dataResources. "config.yml", Config::YAML, ["version" => "0.6.0", "signCreated" => "admin", "lastChange" => time()]);
        $this->plr = new Config($dataResources. "player_authorized.yml", Config::YAML);
        
        if($this->getServer()->getPluginManager()->getPlugin("PocketMoney") == true) $this->PocketMoney = true;
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == true) $this->EconomyS = true;
        
        if($this->PocketMoney == false && $this->EconomyS == false){
            $this->getLogger()->info(TextFormat::RED.$this->mex["1"]);
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
                        $this->chat($player, $this->mex["2"], "succes");
                    }else{
                        $this->chat($player, $this->mex["3"], "error");
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
                            
                            $this->chat($player, $this->mex["5"], "succes"); 
                            unset($this->temp[$player->getDisplayName()]);                                       
                        }else{
                            $this->chat($player, $this->mex["6"], "error");
                        }
                    }else{                      
                        $this->chat($player, $this->mex["33"], "error"); 
                    }
                    $continue = false;                
                    }
                if($continue){
                    $money_player = $this->getMoney($player->getDisplayName());
                    if($money_player < $get["cost"]){
                        $this->chat($player, $this->mex["7"], "error");                    
                    }else{
                        if($get["available"] - $get["amount"] < 0){
                            $this->chat($player, $this->mex["8"], "error");
                        }else{
                            $item = Item::get($get["id"], $get["meta"], $get["amount"]);
                                                      
                            if($player->getInventory()->canAddItem($item)){
                                $this->addMoney($get["maker"], $get["cost"]);   
                                $this->addMoney($player->getDisplayName(), -($get["cost"]));
                                
                                $player->getInventory()->addItem($item);
                                
                                $get["available"] = $get["available"] - $get["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();
                                $this->chat($player, $this->mex["9"], "succes");
                            }else{
                                $this->chat($player, $this->mex["10"], "error");
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
                            $this->chat($player, $this->mex["11"], "success");
                        }else{
                            $this->chat($player, $this->mex["12"], "error");
                        }               
                    }else{
                        $this->chat($player, $this->mex["12"], "error");
                    } 
                }                
            } 
        }
    }
        
    public function removeItemPlayer(Player $player, Item $item){
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage() && $inv->getCount() >= $item->getCount()){
                    $player->getInventory()->setItem($i, Item::get($item->getID(), $item->getDamage(), $inv->getCount() - $item->getCount()));
                    return true;              
                }
            }
        }               
        return false;
    }
    //TODO Remove this function
    public function hasItemPlayer(Player $player, Item $item){
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i +1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage() && $inv->getCount() >= $item->getCount()){
                    return true;
                }
            }
        }
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
                if($args==false){
                    if($sender->isOp()){  
                        $mex=array("/sign create <".$this->mex["17"]."> <".$this->mex["18"]."> <".$this->mex["19"]."> <".$this->mex["34"].">", "/sign refill <".$this->mex["18"].">", "/sign reload", "/sign auth <admin-list-all-player-view>");
                    }else{
                        $mex = array("/sign create <".$this->mex["17"]."> <".$this->mex["18"]."> <".$this->mex["19"]."> <".$this->mex["34"].">", "/sign refill <".$this->mex["18"].">", "/sign reload");
                    }                
                    foreach($mex as $var){
                        $this->chat($sender, $var, "info");
                    }    
                }else{                 
                    $this->chat($sender, "Using /".$command->getName()." ".$args[0], "info");
                    switch(strtolower($args[0])){
                        case "c":
                        case "create":
                            if(count($args) != 5){
                                $this->chat($sender, $this->mex["13"], "error");
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
                                $this->chat($sender, $this->mex["13"], "error");
                                break;
                            }
                            if($id <= 0 || $meta < 0){
                                $this->chat($sender, str_replace("@@", $this->mex["17"], $this->mex["14"]), "error");
                                break;
                            }
                            if($args[2] <= 0 || $args[2] > 64){
                                $this->chat($sender, str_replace("@@", $this->mex["18"], $this->mex["14"]), "error");
                                break;
                            }
                            if($args[3] < 0){
                                $this->chat($sender, str_replace("@@", $this->mex["19"], $this->mex["14"]), "error");
                                break;
                            }
                            if($args[4] < 0){
                                $this->chat($sender, str_replace("@@", $this->mex["34"], $this->mex["14"]), "error");
                                break;
                            }
                            $this->temp[$sender->getName()]= ["action" => "create", "id" => $id, "meta" => $meta, "amount" => $args[2], "cost" => $args[3], "available" => $args[4]];
                            $this->chat($sender, $this->mex["21"], "info");                            
                            break;
                            
                        case "reload":
                            if($sender->isOp()) $this->chat($sender, $this->respawnAllSign(), "info");
                            else $this->chat($sender, $this->mex["26"], "error");
                            break;
                            
                        case "r":
                        case "refill":
                            if(count($args) != 2){
                                $this->chat($sender, $this->mex["13"], "error");
                                break;                                    
                            }
                            if(!is_numeric($args[1]) || $args[1] < 0){
                                $this->chat($sender, str_replace("@@", $this->mex["14"], $this->mex["18"]), "error");
                                break;
                            }
                            $this->temp[$sender->getName()] = ["action" => "refill", "amount" => $args[1]];
                            $this->chat($sender, $this->mex["24"], "error");
                            break;
              
                        case "a":
                        case "auth":
                            if($sender->isOp() == true){
                                $args[1] = strtolower($args[1]);
                                switch($args[1]){
                                    case "view":
                                        if(!isset($args[2])){
                                            $player = strtolower($args[2]);
                                            if($this->plr->exists($player)){
                                                if($this->plr->get($player)["authorized"] == true) $this->chat($sender, str_replace("@@", $player, $this->mex["25"]), "info");
                                                else $this->chat($sender, str_replace("@@", $player, $this->mex["27"]), "info");
                                            }else{
                                                $this->chat($sender, "The player ".$player." isn't exists", "info");
                                            }
                                        }else{
                                            $this->chat($sender, $this->mex["13"], "error");
                                        }
                                        break;
                                    case "player":
                                        if(!isset($args[2])){
                                            $player = strtolower($args[2]);
                                            if($this->plr->exists($player)){
                                                $authorized = !$this->plr->get($player)["authoried"];
                                                $this->plr->set($player, ["authorized" => $authorized, "changed" => time()]);
                                                
                                                if($authorized) $this->chat($sender, str_replace("@@", $player, $this->mex["25"]), "info");
                                                else $this->chat($sender, str_replace("@@", $player, $this->mex["27"]), "info");
                                                           
                                                $this->plr->set($player, ["authorized" => !$this->plr->get($player)["authorized"], "changed" => time()]);
                                            }else{
                                                $this->chat($sender, "The player ".$player." isn't exists", "info");
                                            }
                                        }else{                                            
                                            $this->chat($sender, $this->mex["13"], "error");
                                        }                                        
                                        break;
                                    case "admin":
                                    case "list":
                                    case "all":
                                        $args[1] = strtolower($args[1]);
                                        $this->config->set("signCreated", $args[1]);
                                        $this->config->set("lastChange", time());
                                        $this->config->save();
                                        $this->chat($sender, str_replace("@@", $args[1], $this->mex["28"]), "info");
                                        
                                        foreach($this->plr->getAll() as $var => $c){
                                            $auth = false;
                                            if($args[1] == "all") $auth = true;
                                            
                                            if($args[1] == "admin"){
                                                if($this->isPlayerOnline($var) == true){
                                                    $player = $this->playerFromString($var);
                                                    if($player->isOp()) $auth = true;
                                                }
                                            }
                                            $this->plr->set($var, ["authorized" => $auth, "changed" => time()]);
                                            $this->plr->save();      
                                        }                                   
                                        break;
                                }                             
                            }else{
                                $this->chat($sender, $this->mex["26"], "error");
                            }                            
                            break;  
                    }
                }    
            }else{
                $this->chat($sender, $this->mex["26"], "error");
            }   
        }
    }
    
    public function isPlayerOnline($player){
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player) return true;
        }      
        return false;
    }
    
    public function playerFromString($player){
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player) return $var;
        }      
    }
           
    private function signSpawn(Vector3 $pos, $world, $get){    
        if($get["meta"] != 0) $get["id"] = $get["id"].":".$get["meta"]; 

        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4, true), new Compound("", array(
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
    
    private function respawnSign($var){
        $output = "";        
        if($this->sign->exists($var)){
            $g = explode(":", $var);
            if($g[3] == ""){
                $g[3] = Server::getInstance()->getDefaultLevel()->getName();
            }
            $this->signSpawn(new Vector3($g[0], $g[1], $g[2]), Server::getInstance()->getLevelByName($g[3]), $this->sign->get($var));
            
            return str_replace("@@", $var, $this->mex["29"]);
        }else{
            return str_replace("@@", $var, $this->mex["30"]);
        }        
    }
    
    public function respawnAllSign(){
        if(count($this->sign->getAll())<=0){
            return $this->mex["31"];
        }else{
            foreach($this->sign->getAll() as $var => $c){
                $this->respawnSign($var, false);    
            }
            return $this->mex["32"];
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