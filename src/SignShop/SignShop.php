<?php
namespace SignShop;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\inventory\PlayerInventory;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\tile\Tile;
use pocketmine\level\Level;
use pocketmine\item\Block;
use PocketMoney\PocketMoneyAPI;
use pocketmine\utils\Utils;
use pocketmine\event\player\PlayerRespawnEvent;
class SignShop extends PluginBase implements Listener{
    public $version_plugin= 0.1;
    
    public $sign;
    public $var_create = array();
    public $var_create_id = array();
    public $var_create_aumont = array();
    public $var_create_cost= array();
    public $var_remove= array();
    public $default_world;
    
    public function onLoad(){}
    
    public function onEnable() {  
        if(Utils::getURL("http://mcpezazza.altervista.org/plugin/SignShop/latest_version.html") != $this->version_plugin){
            $this->getLogger()->info(TextFormat::YELLOW."Please update the plugin SignShop"); 
            $this->getLogger()->info(TextFormat::YELLOW."Please update the plugin SignShop"); 
        }
        $this->sign= new Config("./plugins/SignShop/src/SignShop/resources/sign.yml", Config::YAML);
        $this->config= new Config("./plugins/SignShop/src/SignShop/resources/config.yml", Config::YAML, array("version" => $this->version_plugin, "started" => time()));

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        //TODO remove when multiworld
        $this->default_world= $this->getServer()->getInstance()->getDefaultLevel();
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock()->getID();        
        
        if($block == 323 || $block == 63 || $block == 68){
            $x= $event->getBlock()->getX();
            $y= $event->getBlock()->getY();
            $z= $event->getBlock()->getZ();
            //$world = $event->getBlock()->getLevel();
            $world = $this->default_world->getName();
   
            $var = $x.":".$y.":".$z.":".$world;
            if($this->sign->exists($var)){
                if($player->isOp()==false){      
                    $event->setCancelled();  
                }else{
                    if(count($this->var_remove)<=0){
                        $event->setCancelled();
                    }else{
                        $i=0;
                        foreach($this->var_remove as $remove){
                            $i= $i+1;
                            if($remove== $player->getDisplayName()){
                                $this->sign->remove($var);
                                $this->sign->save;
                                $this->var_remove[$i]= NULL;
                                
                                $this->chat($player, "Sign successfully removed",3);
                                break;
                            }         
                        }
                    }                    
                }
            }else{
                $event->setCancelled();
            }
        }
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock()->getID();        
        
        if($block == 323 || $block == 63 || $block == 68){
            $x= $event->getBlock()->getX();
            $y= $event->getBlock()->getY();
            $z= $event->getBlock()->getZ();
            //$world = $event->getBlock()->getLevel();
            $world = $this->default_world->getName();
           
            $var = $x.":".$y.":".$z.":".$world;
            if($this->sign->exists($var)){
                $cost = $this->sign->get($var)['cost'];
                $money_player = PocketMoneyAPI::getAPI()->getMoney($player->getDisplayName());
                if($money_player<$cost){
                    $this->chat($player, "You do not have enough money", 1);                    
                }else{
                    $available = $this->sign->get($var)['available'];
                    $count =$this->sign->get($var)['amount'];
                    
                    if($available - $count<=0){
                        $this->chat($player, "The content of the sign is sold out", 2);
                    }else{
                        $maker = $this->sign->get($var)['maker'];
                        PocketMoneyAPI::getAPI()->grantMoney($maker, $cost);
                        PocketMoneyAPI::getAPI()->grantMoney($player->getDisplayName(), -$cost);
                                            
                        $id = $this->sign->get($var)['id'];
                        $id= Item::get($id, 0, $count);
                    
                        $player->getInventory()->addItem($id);
                    
                        //$this->sign->set($var, array("available" => $available - $count)); 
                        //$this->sign->save();
                        $this->chat($player, "You bought the contents of the sign", 1);
                    } 
                }
            }
        } 
    }
    
    public function playerBlockPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock()->getID();        
        
        if($block == 323 || $block == 63 || $block == 68){
            $x=(Int) $event->getBlock()->getX();
            $y=(Int) $event->getBlock()->getY();
            $z=(Int) $event->getBlock()->getZ();
            
            //$world = $event->getBlock()->getLevel();
            $world = $this->default_world->getName();
   
            $var = $x.":".$y.":".$z.":".$world;
            if($player->isOp()==true){
                if(!count($this->var_create)<=0){              
                    $i=0;
                    foreach($this->var_create as $create){
                        $i=$i+1;
                        if($create== $player->getDisplayName()){
                            $this->sign->set($var, array("id" => $this->var_create_id[$i], "amount" => $this->var_create_aumont[$i], "available" => 9999, "cost" =>  $this->var_create_cost[$i], "maker"=> $player->getDisplayName()));
                            $this->sign->save();
                            
                            $pos= new Vector3($x, $y, $z);    
                            $world= $this->default_world;
                            $this->signSpawn($pos, $world, $this->var_create_id[$i], $this->var_create_aumont[$i], 99999, $this->var_create_cost[$i], $player->getDisplayName());
                            
                            $this->var_create[$i]= NULL;
                            $this->var_create_aumont[$i] = NULL;
                            $this->var_create_id[$i]= NULL;
                            $this->var_create_cost[$i]= NULL;
                         
                            $this->chat($player, "Sign successfully created", 3);
                        }                           
                    }
                }
            }
        }
    }
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $this->respawnAllSign();
    }
    
    public function onCommand(\pocketmine\command\CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "sign"){ 
            if($args==true && $args[0]=="respawnall"){
                    $this->chat($sender, "Using /".$command->getName()." ".$args[0], 4);
                    $this->chat($sender, $this->respawnAllSign(), 4);
            }else{
            if($sender->isOp()==true){
                if($args==false){
                    $mex=array("/sign create <item> <amount> <cost>","/sign remove","/sign refill","/sign respawn <x> <y> <z> world","/sign respawnall");
                    foreach($mex as $var){
                        $this->chat($sender, $var, 4);
                    }    
                }else{
                    switch($args[0]){
                        case "create":
                            $id = $args[1];
                            $amount = $args[2];
                            $cost = $args[3];
                            if($args[1]==false || $args[2]== false || $args[3]==false){
                                $this->chat($sender, "Invalid arguments",1);
                                break;
                            }                        
                            if($amount<=0){
                                $this->chat($sender, "Invalid amount",1);
                            
                                break;
                            }
                            if($cost<0){
                                $this->chat($sender, "Invalid cost",1);
                                break;
                            }
                            if($id <=0){
                                $this->chat($sender, "Invalid item",1);
                                break;
                            }
                        
                            $i = count($this->var_create)+1;
                        
                            $this->var_create[$i]= $sender->getName();
                            $this->var_create_id[$i]= $id;
                            $this->var_create_aumont[$i]= $amount;
                            $this->var_create_cost[$i]= $cost;      
                            
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0]." <item:".$args[1].">"." <amount:".$args[2].">" ." <cost:".$args[3].">", 4);
                            $this->chat($sender, "Now place the sign", 4);
                            break;
                            
                        case "remove":
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0], 4);
                            $i= count($this->var_remove)+1;
                            $this->var_remove[$i]= $sender->getName();
                            $this->chat($sender, "prova 3".count($this->var_remove), 2);
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
                            //$world = $args[4];
                            $world= $this->default_world->getName();
                            
                            if(!(is_numeric($x) && is_numeric($y) && is_numeric($z))){
                                $this->chat($sender, "Invalid coordinates",1);
                                break;
                            }
                        
                            $x= (Int)$x;
                            $y= (Int)$y;
                            $z= (Int)$z;
                            
                            $var = $x.":".$y.":".$z.":".$world;
                        
                            $this->chat($sender, $this->respawnSign($var, 1), 1);
                            break;
                    }
                }    
            }else{  
                $this->chat($sender, "You need to be admin/OP to run this command", 1);
            }
            }
        }
    }
    
    public function signSpawn(Vector3 $pos, $world, $id, $amount, $available, $cost, $maker){
        //TODO next update
        $world= $this->default_world;
        
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
    }   
    
    public function respawnSign($var, $mex){
        $output = "";
        
        if($this->sign->exists($var)){
            $id = $this->sign->get($var)['id'];
            $amount= $this->sign->get($var)['amount'];
            $available= $this->sign->get($var)['available'];
            $cost= $this->sign->get($var)['cost'];
            $maker= $this->sign->get($var)['maker'];
            
            $g= explode(":", $var);
            $pos = new Vector3($g[0], $g[1], $g[2]);
            
            $this->signSpawn($pos, $g[3], $id, $amount, $available, $cost, $maker);
            
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
        $i=0;
        $num = count($this->sign->getAll());
        if($num<=0){
            return "There isn't sign in the world";
        }else{
            
            foreach($this->sign->getAll() as $var => $var_var){
                $this->respawnSign($var, 0);    
                $i=$i+1;
            }
            return "All signs respawned";
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
    }
}