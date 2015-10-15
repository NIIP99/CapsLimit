<?php

/*
 * CapsLimit (v1.1.0)
 * Developer: deot (Minedox Network)
 * Website: http://deot.minedox.com
 * Copyright & License: (C) 2015 deot
 * Licensed under MIT (https://github.com/deotern/CapsLimit/blob/master/LICENSE)
 */

namespace CapsLimit;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class CapsLimit extends PluginBase implements Listener{
    
    /** @var int */
    private $maxcaps;
    
    /** @var SimpleAuth|null */
    public $simpleauth;
    
    public function onEnable(){
        $this->loadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info($this->getPrefix()."Maximum caps limited to ".$this->getMaxCaps());
        $this->getLogger()->info($this->getPrefix()."Mode has been set to ".$this->getConfig()->get("mode")." mode!");
        $auth = $this->getServer()->getPluginManager()->getPlugin("SimpleAuth");
        if($auth){
            $this->simpleauth = $auth;
            $this->getLogger()->info($this->getPrefix()."SimpleAuth installed! Caps detection will be disabled when player hasn't auth yet!");
        }else{
            $this->getLogger()->info($this->getPrefix()."SimpleAuth is not installed! Caps detection will be enabled when player joined!");
        }
    }
    
    public function loadConfig(){
        $this->saveDefaultConfig();
        $this->maxcaps = intval($this->getConfig()->get("max-caps"));
    }
    
    /**
     * @return string
     */
    public function getPrefix(){
        return TextFormat::DARK_GREEN."[Caps".TextFormat::GREEN."Limit] ".TextFormat::WHITE;
    }
    
    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $commandAlias
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, $commandAlias, array $args){
        if(!$sender->hasPermission("capslimit.set")){
            return false;
        }
        if(!is_array($args) or count($args) < 1){
            $sender->sendMessage($this->getPrefix()."/capslimit <limit value>");
            return true;
        }
        if (!is_array($args) or is_numeric($args[0]) > 0){
            $this->maxcaps = $args[0];
            $sender->sendMessage($this->getPrefix()."Maximum caps can be used by player has been set to ".$this->getMaxCaps());
            $this->saveConfig();
            return true;
        }
            $sender->sendMessage($this->getPrefix().TextFormat::RED."Value must be in positive numeric form");
            return false;
    }
    
    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event){
        if($this->getServer()->getPluginManager()->getPlugin("SimpleAuth")){
            if(!$this->simpleauth->isPlayerAuthenticated($event->getPlayer())){
                return false;
            }
        }
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $strlen = strlen($message);
        $asciiA = ord("A");
        $asciiZ = ord("Z");
        $count = 0;
        for($i = 0; $i < $strlen; $i++){
          $char = $message[$i];
          $ascii = ord($char);
            if($asciiA <= $ascii and $ascii <= $asciiZ){
             $count++;
            }
        }
        if(!$player->hasPermission("capslimit.exception")){
            if ($count > $this->getMaxCaps() and $this->getConfig()->get("mode") == "block") {
                $event->setCancelled(true);
                $player->sendMessage($this->getPrefix().TextFormat::RED."You used too much caps!");
            }
            elseif($count > $this->getMaxCaps() and $this->getConfig()->get("mode") === "lowercase"){
                $event->setMessage(strtolower($message));
            }
            elseif($count > $this->getMaxCaps() and $this->getConfig()->get("mode") === "kick"){
                $event->setCancelled(true);
                $player->kick("You have been kicked for overused caps!");
            }
            if($count > $this->getMaxCaps() and $this->getConfig()->getNested("broadcast.enable") === true){
                foreach($this->getServer()->getOnlinePlayers() as $p){
                    $subject = $this->getConfig()->getNested("broadcast.message");
                    $p->sendMessage($this->getPrefix().TextFormat::RED.str_replace("{PLAYER}", $player->getName(), $subject));
                }
            }
            else{
                return false;
            }
        }
    }
    
    /**
     * @return int
     */
    public function getMaxCaps(){
        return $this->maxcaps;
    }
    
    public function saveConfig(){
        $this->getConfig()->set("max-caps", $this->getMaxCaps());
        $this->getConfig()->save();
    }
    
    public function onDisable(){
        $this->saveConfig();
    }
}
