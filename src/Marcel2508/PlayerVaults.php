<?php

/*********************

*********************/

namespace Marcel2508;
use SQLite3;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

class PlayerVaults extends PluginBase implements Listener,CommandExecutor{

    public $db;
    public $settings;
    public $commandProcessor;

    public function setLimit($newLimit){
        $this->getConfig()->set("max-vault-amount",$newLimit);
        $this->saveConfig();
        $this->settings = $this->getConfig()->getAll();
    }

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->settings = $this->getConfig()->getAll();

        $this->db = new SQLite3($this->getDataFolder() . "db.bin");
        //THIS WILL NEED THE SERVERS TO DELETE THE DATABASE FILE IN ORDER TO BE RECREATED...
        $this->db->exec("CREATE TABLE IF NOT EXISTS vaults (player text, vault integer, gamemode integer, itemid integer, amount integer, meta integer,nbt BLOB, slot integer); ");        //CREATE INDEX IF NOT EXISTS playerInventoriesIndex ON playerInventories (player);
        
        $this->commandProcessor = new CommandProcessor($this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEvents($this), $this);
    
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $params) : bool{
        return $this->commandProcessor->onCommand($sender,$command,$label,$params);
    }

    public function msg($msg) {
        return TextFormat::GRAY . "§5[" . TextFormat::BLUE . "§2Player§aVault" .
        TextFormat::GRAY . "§5] " . TextFormat::WHITE . $msg;
    }
}
