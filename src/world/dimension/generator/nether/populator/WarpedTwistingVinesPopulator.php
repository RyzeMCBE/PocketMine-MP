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
use function intdiv;

final class WarpedTwistingVinesPopulator extends SeededPopulator{

	public function populate(BlockManager $root, ChunkManager $world, int $chunkX, int $chunkZ, int $seed) : void{
		$random = $this->random;
		$random->setSeed($seed ^ ChunkHash::hash($chunkX, $chunkZ));
		$object = new BlockManager($world);
		$amount = $random->nextInt(6) + 2;
		for($i = 0; $i < $amount; ++$i){
			$x = GenerationMath::randomRange($random, $chunkX << 4, ($chunkX << 4) + 15);
			$z = GenerationMath::randomRange($random, $chunkZ << 4, ($chunkZ << 4) + 15);
			if(WorldQuery::biomeId($world, $x, 0, $z) !== BiomeIds::WARPED_FOREST){
				continue;
			}
			foreach($this->getHighestWorkableBlocks($object, $x, $z) as $y){
				if($y <= 1){
					continue;
				}
				if($random->nextInt(5) === 0){
					continue;
				}
				$endY = $this->getHighestEndingBlock($object, $x, $y, $z);
				$amountToDecrease = $random->nextInt($endY - $y + 1);
				for($yPos = $y; $yPos < $y + intdiv($amountToDecrease, 2); $yPos++){
					$object->setBlockStateAt($x, $yPos, $z, "minecraft:twisting_vines");
				}
			}
		}
		$root->merge($object);
	}

	private function getHighestEndingBlock(BlockManager $level, int $x, int $y, int $z) : int{
		for(; $y < 128; ++$y){
			if($level->getBlockIdAt($x, $y - 1, $z) === WorldQuery::AIR && $level->getBlockAt($x, $y, $z)->isSolid()){
				break;
			}
		}
		return --$y;
	}

	/**
	 * @return int[]
	 */
	private function getHighestWorkableBlocks(BlockManager $level, int $x, int $z) : array{
		$blockYs = [];
		for($y = 128; $y > 0; --$y){
			$b = $level->getBlockIdAt($x, $y, $z);
			if(($b === "minecraft:warped_nylium" || $b === "minecraft:warped_wart_block") && $level->getBlockAt($x, $y + 1, $z)->canBeReplaced()){
				$blockYs[] = $y + 1;
			}
		}
		return $blockYs;
	}
}
