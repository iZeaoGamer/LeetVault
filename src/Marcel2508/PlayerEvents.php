<?php
namespace Marcel2508;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\block\BlockFactory;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use SQLite3;

class PlayerEvents implements Listener {

    private $plugin;
    private $inventorySpawner;
    private $commandProcessor;
    
    public function __construct(LeetVault $plugin) {
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
                    $player->sendMessage($this->plugin->msg("Saved the players vault!"));
                else
                    $player->sendMessage($this->plugin->msg("Saved your vault!"));
            }
            else{
                $player->sendMessage($this->plugin->msg("Can't save your vault! Please report this issue to http://leetforum.cc"));
            }
        }
        else{
            //Warning! Player somehow managed to open Vault from someone else!
            $this->plugin->getServer()->getLogger()->warn("Player may try to bypass Vault!! ".$player->getName());
        }
    }


    public function onInventoryClose(InventoryCloseEvent $event){
        $inventory = $event->getInventory();
        $holder = $inventory->getHolder();
        $player = $event->getPlayer();
        $playerVault = isset($holder->getNBT()->PlayerVault)?$holder->getNBT()->PlayerVault:false;
        if($playerVault){
            
            $this->saveVault($inventory,$player,$playerVault->PlayerVaultNumber.'',$playerVault->PlayerId.'',$playerVault->AdminView.'');

            //I KNOW, ITS A FAKE CHEST - Refresh Chunk to remove fake block from Players map!
            $player->getLevel()->requestChunk($player->getFloorX() >> 4, $player->getFloorZ() >> 4, $player);
        }
    }

    /*PROBABLY NOT NEEDED
    public function onItemTransactionEvent(InventoryTransactionEvent $event){
        $transaction = $event->getTransaction();
        $event->setCancelled(true);
        echo "STOPPED YOU FROM DOING IT!\r\n";
    }*/

    

}