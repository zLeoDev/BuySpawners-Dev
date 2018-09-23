<?php

namespace uleodev;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\tile\MobSpawner;
use onebone\economyapi\EconomyAPI;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\entity\Entity;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
class Main extends PluginBase implements Listener{
	public $eco;
	public $c;
	public $shop;
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->getServer()->getPluginManager()->registerEvents(new Spawner($this),$this);
		$this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		if($this->eco == null){
			$this->getServer()->getLogger()->info("§cPlugin §aSpawnersSell§c foi desabilitado pela falta da dependência do plugin §aEconomyAPI§c.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			} else {
				$this->getServer()->getLogger()->info("§aPlugin §bEconomyAPI §afoi detectado!\n" . $this->getType(32));
				}
				$this->shop = (new Config($this->getDataFolder()."Shops.yml", Config::YAML))->getAll();
	}
	public function onDisable(){
		$config = (new Config($this->getDataFolder()."Shops.yml", Config::YAML));
		$config->setAll($this->shop);
		$config->save();
	}
	public function getType($index){
		$t = null;
		switch($index){
			case 32:
      $t = "Zombie";
      break;
      case 35:
       $t = "Spider";
       break;

      case 20:
      $t = "IronGolem";
      break;
      
      case "37":
     $t = "Blaze";
      break;
      
      case 47:
      $t = "Criatura";
      break;
      
      case 11:
      $t = "Vaca";
      break;
      
      case 37:
      $t = "IronGolem";
      break;

      case 38:
      $t = "Enderman";
			}
			return $t;
		}
		//null
		// tipo
		//preco
		//qntd
	public function aoPlaca(SignChangeEvent $event){
		$player = $event->getPlayer();
			if(!$player->hasPermission("spawnersshop.admin")){
				$player->sendMessage("§cSem perm");
				return;
			}
			$u = $event->getLine(0);
			$d = $event->getLine(1);
			$t = $event->getLine(2);
			$q = $event->getLine(3);
			if(is_numeric($d) && is_numeric($t) &&  is_numeric($q)){
				$tipo = $this->getType($d);
				$player->sendMessage($d);
				$player->sendMessage($tipo);
			if($tipo != null){
				$block = $event->getBlock();
				$this->shop[$block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getFolderName()] = array(
				"x" => $block->getX(),
				"y" => $block->getY(),
				"z" => $block->getZ(),
				"level" => $block->getLevel()->getFolderName(),
				"preco" => (int) $t,
				"tipo" => (int) $d,
				"quantidade" => (int) $q
			);
			$player->sendMessage("Shop criado!");
			$player->sendMessage("Spawner de " . $tipo);
			$player->sendMessage("Quantidade " . $q);
			$player->sendMessage("preco " . $t);
			$event->setLine(0, "SpawnersBuy");
			$event->setLine(1, "Spawner de " .$tipo);
			$event->setLine(2, "Preco " . $t);
			$event->setLine(3, "Quantidade ".$q);
			
				} else {
					$player->sendMessage("Tipo de spawner inválido");
					}
				} else {$player->sendMessage("formato");
				
             
		}
	}
	public function onBreakEvent(BlockBreakEvent $event){
		$block = $event->getBlock();
		if(isset($this->shop[$block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getFolderName()])){
			$player = $event->getPlayer();
			if(!$player->hasPermission("spawnersbuy.admin")){
				$player->sendMessage("Sem perm");
				$event->setCancelled(true);
				return;
			}
			$this->shop[$block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getFolderName()] = null;
			unset($this->shop[$block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getFolderName()]);
			$player->sendMessage("Placa de venda removida");
		}
	}
	
	
	
	public function onPlayerTouch(PlayerInteractEvent $event){
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		$block = $event->getBlock();
		$loc = $block->getX().":".$block->getY().":".$block->getZ().":".$block->getLevel()->getFolderName();
		if(isset($this->shop[$loc])){
			$shop = $this->shop[$loc];
			$player = $event->getPlayer();
			if($player->getGamemode() % 2 == 1){
				$player->sendMessage("Modo de jogo inválido");
				$event->setCancelled();
				return;
			}
			$item = Item::get(52, 0, 20);
			$tipo = $this->getType($shop["tipo"]);
			$nome = "Gerador de " . $tipo;
			$item->setCustomName($nome);
			if(!$player->getInventory()->canAddItem(Item::get(52, 0))){
				$player->sendMessage("seu inventário está cheio");
				return;
			}

			$money = EconomyAPI::getInstance()->myMoney($player);
			if($shop["preco"] > $money){
				$player->sendMessage("voce nao tem dinheiro para isso");
				$event->setCancelled(true);
				
				return;
			}else{
				
			    $b = new Item(52, 0, $shop["quantidade"]);
			$b->setCustomName($nome);
				$player->getInventory()->addItem($item);
				EconomyAPI::getInstance()->reduceMoney($player, $shop["preco"], true, "SpawnerShop");
				$player->sendMessage("Comprado com sucesso / " . $nome);
				$event->setCancelled(true);
				
			}
		}
	}

	
	
	
	
	
	
}
