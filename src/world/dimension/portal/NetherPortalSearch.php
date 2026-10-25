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

namespace pocketmine\world\dimension\portal;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\World;
use function abs;

/** @internal Resumable nearest-portal query, one chunk per step. */
final class NetherPortalSearch{

	private int $radius;
	private int $chunkX;
	private int $chunkZ;
	private int $maxChunkX;
	private int $maxChunkZ;
	private int $minChunkZ;
	private ?Vector3 $best = null;
	private float $bestHorizontal = 0.0;
	private float $bestVertical = 0.0;
	private NetherPortalIndex $index;

	public function __construct(World $world, private int $dimension, private int $x, private int $y, private int $z){
		$this->radius = $dimension === DimensionIds::NETHER ? NetherPortalLocator::NETHER_SEARCH_RADIUS : NetherPortalLocator::OVERWORLD_SEARCH_RADIUS;
		$this->chunkX = ($x - $this->radius) >> 4;
		$this->chunkZ = $this->minChunkZ = ($z - $this->radius) >> 4;
		$this->maxChunkX = ($x + $this->radius) >> 4;
		$this->maxChunkZ = ($z + $this->radius) >> 4;
		$this->index = NetherPortalIndex::getInstance($world);
	}

	/** Returns true after every candidate chunk has been examined. */
	public function step() : bool{
		if($this->chunkX > $this->maxChunkX){ return true; }
		foreach($this->index->getPortals($this->chunkX, $this->chunkZ, $this->dimension) as $portal){
			if(abs($portal->x - $this->x) > $this->radius || abs($portal->z - $this->z) > $this->radius){ continue; }
			$horizontal = ($portal->x - $this->x) ** 2 + ($portal->z - $this->z) ** 2;
			$vertical = ($portal->y - $this->y) ** 2;
			if($this->best === null || $horizontal < $this->bestHorizontal || ($horizontal === $this->bestHorizontal && $vertical < $this->bestVertical)){
				$this->best = $portal;
				$this->bestHorizontal = $horizontal;
				$this->bestVertical = $vertical;
			}
		}
		if(++$this->chunkZ > $this->maxChunkZ){ $this->chunkZ = $this->minChunkZ; ++$this->chunkX; }
		return $this->chunkX > $this->maxChunkX;
	}

	public function getResult() : ?Vector3{ return $this->best === null ? null : clone $this->best; }

	public function hasCachedCoverage() : bool{
		for($x = ($this->x - $this->radius) >> 4; $x <= $this->maxChunkX; ++$x){
			for($z = $this->minChunkZ; $z <= $this->maxChunkZ; ++$z){
				if(!$this->index->hasCachedChunk($x, $z, $this->dimension)){ return false; }
			}
		}
		return true;
	}
}
