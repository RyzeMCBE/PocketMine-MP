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
use pocketmine\world\dimension\generator\ChunkHash;
use pocketmine\world\dimension\generator\math\GenerationMath;
use pocketmine\world\dimension\generator\nether\object\SeededPopulator;
use pocketmine\world\dimension\generator\nether\object\WorldQuery;
use pocketmine\world\dimension\generator\object\BlockManager;

final class BasaltDeltaPillarPopulator extends SeededPopulator{

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void{
		$random = $this->random;
		$random->setSeed($seed ^ ChunkHash::hash($chunkX, $chunkZ));
		$object = new BlockManager($world);
		$amount = $random->nextBoundedInt(128) + 128;
		$maxHeight = $world->getMaxY();
		for($i = 0; $i < $amount; ++$i){
			$x = GenerationMath::randomRange($random, $chunkX << 4, ($chunkX << 4) + 15);
			$z = GenerationMath::randomRange($random, $chunkZ << 4, ($chunkZ << 4) + 15);
			if(WorldQuery::biomeId($world, $x, 0, $z) !== BiomeIds::BASALT_DELTAS){
				continue;
			}
			foreach($this->getHighestWorkableBlocks($object, $x, $z) as $y){
				if($y <= 1){
					continue;
				}
				if($random->nextBoundedInt(5) === 0){
					continue;
				}
				for($randomHeight = 0; $randomHeight < $random->nextBoundedInt(5) + 1; $randomHeight++){
					$placeLocation = $y + $randomHeight;
					if($placeLocation >= $maxHeight){
						break;
					}
					$object->setBlockStateAt($x, $placeLocation, $z, "minecraft:basalt");
				}
			}
		}
		$root->merge($object);
	}

	/**
	 * @return int[]
	 */
	private function getHighestWorkableBlocks(BlockManager $level, int $x, int $z) : array{
		$blockYs = [];
		for($y = 126; $y > 1; --$y){
			if($level->getBlockIdAt($x, $y, $z) === "minecraft:blackstone" && $level->getBlockIdAt($x, $y + 1, $z) === WorldQuery::AIR){
				$blockYs[] = $y + 1;
			}
		}
		return $blockYs;
	}
}
