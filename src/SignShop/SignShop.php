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
use pocketmine\event\player\PlayerRespawnEvent;
class SignShop extends PluginBase implements Listener{
    public $version_plugin= "0.3.1";
    
    public $sign, $timer, $config;
    public $var_create = array();
    public $var_remove= array();
    public $var_refill = array();
    public $PocketMoney = false;
    public $EconomyS = false;
 
    public function onLoad(){}
    
    public function onEnable(){  
        $this->timer = new Timer($this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->timer, 12000);   
        if (!file_exists($this->getDataFolder())){
            @mkdir($this->getDataFolder(), 0755, true);
        }
        $this->getLogger()->info($this->getDataFolder());
        $this->sign = new Config($this->getDataFolder(). "sign.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML, array("version" => $this->version_plugin, "started" => time(), "enabled" => true));
        
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
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        if($this->config->get("enabled") == true){
            $player = $event->getPlayer();
            $block = $event->getBlock()->getID();        
        
            if($block == 323 || $block == 63 || $block == 68){
                $x= $event->getBlock()->getX();
                $y= $event->getBlock()->getY();
                $z= $event->getBlock()->getZ();
                $world = $event->getBlock()->getLevel();
  
                $var = $x.":".$y.":".$z.":".$world->getName();
                if($this->sign->exists($var)){
                    if($player->isOp()==false){      
                        $event->setCancelled();  
                    }else{
                        if($this->isExists($this->var_remove, $player->getDisplayName())){
                            if($this->var_remove[$player->getDisplayName()] != false){
                                if($this->sign->get($var)["maker"] == $player->getDisplayName()){
                                    $this->sign->remove($var);
                                    $this->sign->save;
                                    $this->var_remove[$player->getDisplayName()]= false;
                                
                                    $this->chat($player, "The Sign successfully removed", 3);
                                }else{
                                    $this->chat($player, "The Sign is not yours", 1);
                                    $event->setCancelled();  
                                }
                            }else{
                                $event->setCancelled();  
                            }
                        }else{
                            $event->setCancelled();  
                        }    
                    }                    
                }
            }
        }
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){
        if($this->config->get("enabled") == true){
            $player = $event->getPlayer();
            $block = $event->getBlock()->getID();        
            $continue = true;
        
            if($block == 323 || $block == 63 || $block == 68){
                $x= $event->getBlock()->getX();
                $y= $event->getBlock()->getY();
                $z= $event->getBlock()->getZ();
                $world = $event->getBlock()->getLevel();
           
                $var = $x.":".$y.":".$z.":".$world->getName();           
                if($this->sign->exists($var)){
                    $get = $this->sign->get($var);
                    if($player->isOp()){
                        if($this->isExists($this->var_refill, $player->getDisplayName())){
                            if($this->var_refill[$player->getDisplayName()] != false){
                                if($get["maker"] == $player->getDisplayName()){
                                    $get["available"] = $get["available"] + $this->var_refill[$player->getDisplayName()];
                                    $this->sign->set($var, array_merge($get)); 
                                    $this->sign->save();
                                    
                                    $this->chat($player, "The Sign was stocked with success", 1); 
                                    $this->var_refill[$player->getDisplayName()] = false;
                                    $continue = false;
                                }else{                      
                                    $this->chat($player, "The Sign is not yours", 1);
                                }
                            }
                        }
                    }
                    if($continue == true){
                        $money_player = $this->getMoney($player->getDisplayName());
                        if($money_player < $get["cost"]){
                            $this->chat($player, "You do not have enough money", 1);                    
                        }else{
                            if($get["available"] - $get["amount"]<=0){
                                $this->chat($player, "The content of the sign is sold out", 2);
                            }else{
                                $this->addMoney($get["maker"], $get["cost"]);     
                                $this->addMoney($player->getDisplayName(), -$get["cost"]);
                                
                                $id= Item::get($get["id"], 0, $get["amount"]);
                                $player->getInventory()->addItem($id);
                                
                                /* TODO 
                                 * if($player->getInventory()->canAddItem($item))
                                 */
                                $get["available"]= $get["available"] - $get["amount"];
                                $this->sign->set($var, array_merge($get)); 
                                $this->sign->save();
                                $this->chat($player, "You bought the contents of the sign", 1);
                            } 
                        }
                    }
                    $this->respawnSign($var, 0);
                }
            }   
        }
    }
    
    public function playerBlockPlace(BlockPlaceEvent $event){
        if($this->config->get("enabled") == true){
            $player = $event->getPlayer();
            $block = $event->getBlock()->getID();     
        
            if($block == 323 || $block == 63 || $block == 68){
                $x=(Int) $event->getBlock()->getX();
                $y=(Int) $event->getBlock()->getY();
                $z=(Int) $event->getBlock()->getZ();
            
                $world = $event->getBlock()->getLevel();
   
                $var = $x.":".$y.":".$z.":".$world->getName();
                if($player->isOp()==true){
                    if($this->isExists($this->var_create, $player->getDisplayName())){
                        if($this->var_create[$player->getName()] != false){
                         $z = $this->var_create[$player->getName()];
                            $this->sign->set($var, array("id" => $z["id"], "amount" => $z["amount"], "available" => $z["available"], "cost" =>  $z["cost"], "maker"=> $player->getDisplayName()));
                            $this->sign->save();
                    
                            $pos= new Vector3($x, $y, $z);  
                            $this->signSpawn($pos, $world, $z["id"], $z["amount"], $z["amount"], $z["cost"], $player->getDisplayName());
                    
                            $this->var_create[$player->getName()]= false;
                        }
                    }
                }
            }
        }
    }
    
    public function isExists($var, $value){
        if(count($var) <=0){
            return false;
        }else{
            foreach($var as $c => $z){
                if($c == $value){
                    return true;
                    break;
                }
            }
        }
    }
    
    public function playerSpawn(PlayerRespawnEvent $event){
        if($this->config->get("enabled") == true){
            $this->respawnAllSign();        
        }
    }
    
    public function onCommand(\pocketmine\command\CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "sign"){ 
            if($this->config->get("enabled") == false){
                $this->chat($sender, "Sorry the plugin is disabled, please enable it running the command /sign enable <yes> as an admin", 1);
                
            }else{
            if($args==true && $args[0]=="respawnall"){
                    $this->chat($sender, "Using /".$command->getName()." ".$args[0], 4);
                    $this->chat($sender, $this->respawnAllSign(), 4);
            }else{
                if($sender->isOp()==true){
                    if($args==false){
                        $mex=array("/sign create <item> <amount> <cost> <available>","/sign remove","/sign refill","/sign respawn <x> <y> <z> <world>","/sign respawnall","/sign refill <value>","/sign enable <yes-no>");
                        foreach($mex as $var){
                            $this->chat($sender, $var, 4);
                        }    
                    }else{
                        switch($args[0]){
                            case "create":
                                if(count($args) != 5){
                                    $this->chat($sender, "Invalid arguments",1);
                                    break;
                                }
                                if($args[1]== "" || $args[2]== "" || $args[3]== "" || $args[4]== ""){
                                    $this->chat($sender, "Invalid arguments",1);
                                    break;
                                }      
                                $id = $args[1];
                                $amount = $args[2];
                                $cost = $args[3];
                                $available = $args[4];
                                if(!(is_numeric($amount) && is_numeric($cost) && is_numeric($available))){
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;
                                }
                                if($id <=0){
                                    $this->chat($sender, "Invalid item",1);
                                    break;
                                }
                                if($amount<=0 || $amount > 45*64){
                                    $this->chat($sender, "Invalid amount",1);
                                    break;
                                }
                                if($cost<0){
                                    $this->chat($sender, "Invalid cost",1);
                                    break;
                                }
                                if($available < 0){
                                    $this->chat($sender, "Invalid available",1);
                                    break;
                                }
                                $this->var_create[$sender->getName()] = array("id" => $id, "amount" => $amount, "cost" => $cost, "available" => $available);
                                $this->chat($sender, "Using /".$command->getName()." ".$args[0]." <item:".$id.">"." <amount:".$amount.">" ." <cost:".$cost."> <available:". $available , 4);
                                $this->chat($sender, "Now place the Sign", 4);
                                break;
                            
                            case "remove":
                                $this->chat($sender, "Using /".$command->getName()." ".$args[0], 4);
                                $this->var_remove[$sender->getName()] = true;
                                break;   
                        
                            case "respawn":
                                if($args[1]==false || $args[2]== false || $args[3]==false ||$args[4]==false){
                                    $this->chat($sender, "Invalid arguments",1);
                                    break;
                                }                        
                                
                                $this->chat($sender, "Using /".$command->getName()." ".$args[0]." <x:".$args[1].">"." <y:".$args[2].">" ." <z:".$args[3].">"." <w:".$args[4].">", 4);
                                
                                $x = $args[1];
                                $y = $args[2];
                                $z = $args[3];
                                $world = $args[4];
                                
                                if(!(is_numeric($x) && is_numeric($y) && is_numeric($z))){
                                    $this->chat($sender, "Invalid coordinates", 1);
                                    break;
                                }
                                
                                $x= (Int)$x;
                                $y= (Int)$y;
                                $z= (Int)$z;
                                
                                $var = $x.":".$y.":".$z.":".$world;
                                
                                $this->chat($sender, $this->respawnSign($var, 1), 1);
                                break;
                                
                            case "refill":
                                if($args[1] == false || $args[1] == "" || count($args) > 2){
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;                                    
                                }
                                if(!is_numeric($args[1])){
                                    $this->chat($sender, "Invalid amount", 1);
                                    break;
                                }
                                $this->var_refill[$sender->getName()] = $args[1];
                                $this->chat($sender, "Now touch the sign", 4);
                                break;
                            case "enable":
                                $this->chat($sender, "Using /".$command->getName()." ".$args[0], 4);
                                if($args[1] == "yes" || $args[1] == "y" || $args[1] == "true"){
                                    $this->config->set("enable", true);        
                                    $this->chat($sender, "Now the plugin is activated", 3);
                                }else{
                                    if($args[1] == "no" || $args[1] == "n" || $args[1] == "false"){
                                        $this->config->set("enable", false);   
                                        $this->chat($sender, "Now the plugin is disabled", 1);
                                    }else{
                                        $this->chat($sender, "Invalid arguments", 1);
                                    }                        
                                }
                                $this->config->save();                                                           
                                break;
                        }
                    }    
                }else{  
                    $this->chat($sender, "You need to be admin/OP to run this command", 1);
                }
            }
            }
        }
    }
    
    public function signSpawn(Vector3 $pos, $world, $id, $amount, $available, $cost, $maker){    
        /* TODO
        $var = $world->getBlockIdAt($pos->x, $pos->y, $pos->z); 
        if(($var != 323 || $var != 63 || $var != 68)){
            $world->setBlock($pos, Block::get(323));
        }*/
        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4), new Compound("", array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", $maker),
            new String("Text2", "ID: ".$id." x".$amount),
            new String("Text3", "Price: ".$cost),
            new String("Text4", "Available:".$available)
            )));
        $sign->saveNBT();
        $sign->spawnToAll();
    }   
    
    public function respawnSign($var, $mex){
        $output = "";        
        if($this->sign->exists($var)){
            $g= explode(":", $var);
            $pos = new Vector3($g[0], $g[1], $g[2]);
            if($g[3] == ""){
                $g[3] = Server::getInstance()->getDefaultLevel();
            }
            $this->signSpawn($pos, Server::getInstance()->getLevelByName($g[3]), $this->sign->get($var)['id'], $this->sign->get($var)['amount'], $this->sign->get($var)['available'], $this->sign->get($var)['cost'], $this->sign->get($var)['maker']);
            
            $output =  "Sign at position ".$var." spawn success";
        }else{
            $output = "The sign not found";
        }
        
        if($mex== 1){
            return $output;
        }else{
            return "";
        }
    }
    
    public function respawnAllSign(){
        if(count($this->sign->getAll())<=0){
            return "There isn't sign in the world";
        }else{
            foreach($this->sign->getAll() as $var => $c){
                $this->respawnSign($var, 0);    
            }
            return "All signs respawned";
        }
    }
    
    public function getMoney($player){
        if($this->PocketMoney == true){
            return $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->getMoney($player);
        }else{
            if($this->EconomyS == true){
            /*/TODO*
             * return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->mymoney($player);
             */
            }
        }
    }
    
    public function addMoney($player, $value){
        if($this->PocketMoney == true){
            $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->grantMoney($player, $value);
        }else{
            if($this->EconomyS == true){
            /*/TODO*
             * $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->useMoney(4player, $value);
             */    
            }
        }
    }
    
    public function chat($player, $mex, $style){
        /*
         * 0 default
         * 1 error
         * 2 warning
         * 3 success
         * 4 info
         */ 
        $style_mex= $style;
        $p= "[SignShop] ";
        switch($style_mex){
            case 1:
                $player->sendMessage(TextFormat::RED.$p.$mex);
                break;
            case 2:
                $player->sendMessage(TextFormat::YELLOW.$p.$mex);
                break;
            case 3:
                $player->sendMessage(TextFormat::GREEN.$p.$mex);
                break;
            case 4:
                $player->sendMessage(TextFormat::AQUA.$p.$mex);
                break;
            default:
                $player->sendMessage($p.$mex);
                break;
        }
    }
    
    public function onDisable(){
        $this->sign->save();
        $this->config->save();
    }
}