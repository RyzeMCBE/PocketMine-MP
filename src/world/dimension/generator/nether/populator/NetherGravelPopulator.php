<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\dimension\generator\nether\populator;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\dimension\generator\nether\object\OreGeneratorFeature;
use pocketmine\world\dimension\generator\nether\object\WorldQuery;
use pocketmine\world\dimension\generator\object\BlockManager;
use pocketmine\world\dimension\generator\random\Xoroshiro128;

class NetherGravelPopulator extends OreGeneratorFeature{

	public function name() : string{
		return "nether_gravel";
	}

	public function getState() : string{
		return "minecraft:gravel";
	}

	public function getClusterCount() : int{
		return 2;
	}

	public function getClusterSize() : int{
		return 33;
	}

	public function getMinHeight() : int{
		return 5;
	}

	public function getMaxHeight() : int{
		return 36;
	}

	protected function spawn(BlockManager $level, ChunkManager $world, Xoroshiro128 $rand, int $x, int $y, int $z) : void{
		if(WorldQuery::biomeId($world, $x, $y, $z) !== BiomeIds::BASALT_DELTAS){
			parent::spawn($level, $world, $rand, $x, $y, $z);
		}
	}
}
