<?php

/** 
*     _    _ _                  ____ 
*    / \  | (_) ___ __ _ _ __  / ___|
*   / _ \ | | |/ __/ _` | '_ \| |    
*  / ___ \| | | (_| (_| | | | | |___ 
* /_/   \_\_|_|\___\__,_|_| |_|\____|
*                                 
*                                  
*  -I'm getting stronger if I'm not dying-
*
* @version 1.0
* @author AlicanCopur
* @copyright HashCube Network © | 2015 - 2019
* @license Açık yazılım lisansı altındadır. Tüm hakları saklıdır. 
*/                                   

namespace AlicanCopur;

use pocketmine\{
	plugin\PluginBase,
	command\Command,
	command\CommandSender,
        level\Level
};

class WorldUpdater extends PluginBase {

	public function onEnable() {
		$written = array_map(function($jarFile): ?int {

			if(file_exists(($out = $this->getServer()->getDataPath() . $jarFile))) return null;

			$resource = $this->getResource($jarFile);
			$fp = fopen($out, "wb");
			if($fp === false) throw new AssumptionFailedError("fopen() should not fail with wb flags");
	
			$ret = stream_copy_to_stream($resource, $fp) > 0;
			fclose($fp);
			fclose($resource);

			return $ret;
		}, ['AnviltoRegion.jar', 'AnvilConverter.jar']);

		// var_dump($written);
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
	{
		$this->startConversion($sender);
		return true;
	}

	private function startConversion(CommandSender $sender): void
	{
		system("cd ".$this->getServer()->getDataPath());
		$levels = scandir($this->getServer()->getDataPath()."worlds");
		foreach($levels as $level){
		    if(strpos($level, '.') === 0) continue;
			$this->convert($level);
		}
		$sender->sendMessage("Conversion successfully finished!");
	}

	public function convert($level): void
	{
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

	private function anvilToRegion($level): void
	{
		system("java -jar AnvilToRegion.jar worlds/".$level);
	}

	private function regionToPMAnvil($level): void
	{
		system("java -jar AnvilConverter.jar worlds ".$level." pmanvil");
	}

}
