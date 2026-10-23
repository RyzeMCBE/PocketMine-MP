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

namespace pocketmine\world\dimension\generator\nether\object;

use pocketmine\world\ChunkManager;
use pocketmine\world\dimension\generator\Blocks;

/**
 * World read helpers used by the populators.
 */
final class WorldQuery{

	public const AIR = "minecraft:air";

	private function __construct(){
	}

	public static function blockId(ChunkManager $world, int $x, int $y, int $z) : string{
		if(!$world->isInWorld($x, $y, $z)){
			return self::AIR;
		}
		return Blocks::id($world->getBlockAt($x, $y, $z));
	}

	public static function biomeId(ChunkManager $world, int $x, int $y, int $z) : int{
		$chunk = $world->getChunk($x >> 4, $z >> 4);
		if($chunk === null){
			return -1;
		}
		return $chunk->getBiomeId($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Highest non-air block of a column;
	 * minY - 1 when the column is empty.
	 */
	public static function heightMap(ChunkManager $world, int $x, int $z) : int{
		$minY = $world->getMinY();
		for($y = $world->getMaxY() - 1; $y >= $minY; --$y){
			if(self::blockId($world, $x, $y, $z) !== self::AIR){
				return $y;
			}
		}
		return $minY - 1;
	}
}
