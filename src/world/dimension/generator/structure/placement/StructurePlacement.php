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

namespace pocketmine\world\dimension\generator\structure\placement;

use pocketmine\world\dimension\generator\ChunkHash;
use pocketmine\world\dimension\generator\math\Int64;
use pocketmine\world\dimension\generator\random\RandomSource;
use function floor;

/**
 * Grid placement of a structure: the world is cut into square regions of
 * $maxDistance chunks and each region holds one candidate start chunk, at a
 * random offset below $maxDistance - $minDistance from the region corner.
 */
final class StructurePlacement{

	public const DEFAULT_SALT = 0x76694565C616765;

	/**
	 * @param \Closure(int) : bool|null $biomeFilter
	 */
	public function __construct(
		private int $salt,
		private int $minDistance,
		private int $maxDistance,
		private ?\Closure $biomeFilter = null
	){}

	public function isValidBiome(int $biome) : bool{
		return $this->biomeFilter === null || ($this->biomeFilter)($biome);
	}

	public function canGenerate(int $levelSeed, RandomSource $random, int $chunkX, int $chunkZ, int $biome) : bool{
		$regionX = self::floorDiv($chunkX, $this->maxDistance);
		$regionZ = self::floorDiv($chunkZ, $this->maxDistance);
		$random->setSeed(Int64::add($levelSeed ^ $this->salt, ChunkHash::hash($regionX, $regionZ)));
		return $this->isValidBiome($biome)
			&& $regionX * $this->maxDistance + $random->nextBoundedInt($this->maxDistance - $this->minDistance) === $chunkX
			&& $regionZ * $this->maxDistance + $random->nextBoundedInt($this->maxDistance - $this->minDistance) === $chunkZ;
	}

	public static function floorDiv(int $value, int $divisor) : int{
		return (int) floor($value / $divisor);
	}
}
