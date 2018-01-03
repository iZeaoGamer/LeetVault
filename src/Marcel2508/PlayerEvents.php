<?php
namespace Marcel2508;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\block\BlockFactory;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerCursorInventory;
use SQLite3;

class PlayerEvents implements Listener {

    private $plugin;
    private $inventorySpawner;
    private $commandProcessor;
    
    public function __construct(Playervaults $plugin) {
        $this->plugin = $plugin;
        $this->inventorySpawner = new InventorySpawner();
    }

    private function saveVault(ChestInventory $inventory, Player $player, int $vault, int $vaultPlayerId,int $vaultPlayerAdminId){

        $playerId = $player->getId();
        $gamemode = $player->getGamemode();
        if($playerId===$vaultPlayerId||($vaultPlayerAdminId!=-1&&$vaultPlayerAdminId==$playerId)){
            $queryList = "DELETE FROM `vaults` WHERE `player` = '".$vaultPlayerId."' AND `vault` = '".$vault."';";
            $contents = $inventory->getContents();
            foreach($contents as $slot => $item){
                $itemId=$item->getId();
                $itemCount = $item->getCount();
                $itemDamage = $item->getDamage();

                if($item->hasCompoundTag()){
                    $itemNbt = $item->getCompoundTag();
                    $queryList .= "INSERT INTO `vaults` (`player`, `vault`, `gamemode`, `itemid`, `amount`, `meta`,`nbt`, `slot`) VALUES ('".$vaultPlayerId."','".$vault."','".$gamemode."','".$itemId."','".$itemCount."','".$itemDamage."', x'".bin2hex($itemNbt)."', '".$slot."');";
                }
                else{
                    $queryList .= "INSERT INTO `vaults` (`player`, `vault`, `gamemode`, `itemid`, `amount`, `meta`,`nbt`, `slot`) VALUES ('".$vaultPlayerId."','".$vault."','".$gamemode."','".$itemId."','".$itemCount."','".$itemDamage."', NULL, '".$slot."');";
                }
            }
            if($this->plugin->db->exec($queryList)){
                if($vaultPlayerAdminId!=-1)
                    $player->sendMessage($this->plugin->msg("§6Saved the players vault!"));
                else
                    $player->sendMessage($this->plugin->msg("§6Saved your vault!"));
            }
            else{
                $player->sendMessage($this->plugin->msg("§cCan't save your vault! Please report this issue to http://leetforum.cc"));
            }
        }
        else{
            //Warning! Player somehow managed to open Vault from someone else!
            $this->plugin->getServer()->getLogger()->warn("Player may try to bypass Vault!! ".$player->getName());
        }
    }

    private function hasRight(Player $player, string $permissionNode) : bool{
        $nodeList = [];
        $parts = explode(".",$permissionNode);
        $akt = "";
        for($i=0; $i<count($parts)-1;$i++){
            $akt .= $parts[$i].".";
            if($player->hasPermission($akt."*")){
                return true;
            }
        }
        if($player->hasPermission($permissionNode)||$player->hasPermission("*")||$player->isOp()){
            return true;
        }
        //DONT NEED ON EVENTS $player->sendMessage($this->plugin->msg("You are not allowed to use this!"));
        return false;
    }


    public function onInventoryClose(InventoryCloseEvent $event){
        $inventory = $event->getInventory();
        $holder = $inventory->getHolder();
        $player = $event->getPlayer();
        $playerVault = isset($holder->getNBT()->PlayerVault)?$holder->getNBT()->PlayerVault:false;
        if($playerVault){
            if(($playerVault->AdminView.''==-1&&$this->hasRight($player,"pv.vault.use"))||($playerVault->AdminView.''!=-1&&$this->hasRight($player,"pv.admin.edit")))//ONLY SAVE IF USER IS PERMITTED TO USE THAT CHEST
                $this->saveVault($inventory,$player,$playerVault->PlayerVaultNumber.'',$playerVault->PlayerId.'',$playerVault->AdminView.'');

            //I KNOW, ITS A FAKE CHEST - Refresh Chunk to remove fake block from Players map!
            $player->getLevel()->requestChunk($player->getFloorX() >> 4, $player->getFloorZ() >> 4, $player);
        }
    }

    private function getCancelledStateForTransaction(Player $player) : bool {//, PlayerInventory $pinv, ChestInventory $cinv) : bool{
        if($this->hasRight($player,"pv.admin.edit"))return false;
        if($this->hasRight($player,"pv.admin.view"))return true;
        else return true;
    }

    public function onItemTransactionEvent(InventoryTransactionEvent $event){
        //$transactions = $event->getTransaction()->getInventories();
        $inventories = $event->getTransaction()->getInventories();
        /*foreach($transactions as $transaction){
            $inventories[]=$transaction->getInventory();
        }*/
        $ti = [];
        foreach($inventories as $tinv)$ti[]=$tinv;
        $inventories=$ti;
        if(count($inventories)==2){
            $cancelled = false;
            if($inventories[0] instanceof ChestInventory){
                if($inventories[1] instanceof PlayerInventory || $inventories[1] instanceof PlayerCursorInventory){
                    $nbt = $inventories[0]->getHolder()->getNBT();
                    if(isset($nbt->PlayerVault)&&$nbt->PlayerVault->AdminView.''!=-1){
                        $cancelled = $this->getCancelledStateForTransaction($inventories[1]->getHolder());
                    }
                }
                //,$inventories[1],$inventories[0]);
            }
            else if($inventories[0] instanceof PlayerInventory || $inventories[0] instanceof PlayerCursorInventory){
                if($inventories[1] instanceof ChestInventory){
                    $nbt = $inventories[1]->getHolder()->getNBT();
                    if(isset($nbt->PlayerVault)&&$nbt->PlayerVault->AdminView.''!=-1){
                        $cancelled = $this->getCancelledStateForTransaction($inventories[0]->getHolder());
                    }
                }
                
                //$cancelled = $this->getCancelledStateForTransaction($inventories[0]->getHolder());//,$inventories[0],$inventories[1]);
            }
            if($cancelled)$event->setCancelled(true);
        }
    }

}
