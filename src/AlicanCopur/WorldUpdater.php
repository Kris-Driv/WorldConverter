<?php

namespace AlicanCopur;

use pocketmine\{
	plugin\PluginBase,
	command\Command,
	command\CommandSender,
        level\Level
};

class WorldUpdater extends PluginBase {
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
		$this->startConversion($sender);
		return true;
	}
	private function startConversion(CommandSender $sender): void{
		system("cd ".$this->getServer()->getDataPath());
		$levels = scandir($this->getServer()->getDataPath()."worlds");
		foreach($levels as $level){
		    if($level == "." || $level == "..") continue;
			$this->convert($level);
		}
		$sender->sendMessage("Conversion successfully finished!");
	}
	public function convert($level): void{
		$world = $this->getServer()->getLevelByName($level);
                if($world instanceof Level) $world->unload();
		$this->anvilToRegion($level);
		$this->regionToPMAnvil($level);
		$dir = $this->getServer()->getDataPath()."worlds/".$level."/region/";
		$files = scandir($dir);
		foreach($files as $file){
		    if($file == "." || $file == "..") continue;
			$info = pathinfo($file);
    		$format = $info['extension'];
    		if($format == "mca" || $format == "mcr")
    			unlink($dir.$file);
		}
		$this->getServer()->loadLevel($level); //Register tiles and entities
		$world = $this->getServer()->getLevelByName($level);
		$world->unload();
          $this->getServer()->loadLevel($level);
	}
	private function anvilToRegion($level): void{
		system("java -jar AnvilToRegion.jar worlds/".$level);
	}
	private function regionToPMAnvil($level): void{
		system("java -jar AnvilConverter.jar worlds ".$level." pmanvil");
	}
}
