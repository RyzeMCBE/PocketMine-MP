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

namespace pocketmine\world\dimension\generator\end;

use pocketmine\entity\Location;
use pocketmine\entity\object\EndCrystal;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\utils\Random;
use pocketmine\world\dimension\Dimensions;
use pocketmine\world\dimension\generator\end\populator\ObsidianPillarPopulator;
use pocketmine\world\World;

/**
 * Places the end crystal on top of each obsidian spike. Spikes are built on
 * the generation threads, which cannot create entities, so the crystal of a
 * spike is spawned on the main thread once the chunk that builds it has been
 * populated.
 */
final class EndCrystalSpawner{

	private ?int $seed = null;

	/** @var list<array{chunkX: int, chunkZ: int, position: \pocketmine\math\Vector3}> */
	private array $spawns = [];

	public function __construct(
		private Dimensions $dimensions
	){}

	public function onChunkPopulated(World $world, int $chunkX, int $chunkZ) : void{
		if($this->dimensions->getDimension($world) !== DimensionIds::THE_END){
			return;
		}
		if($this->seed !== $world->getSeed()){
			$this->seed = $world->getSeed();
			$this->spawns = ObsidianPillarPopulator::crystalSpawns($this->seed);
		}
		foreach($this->spawns as $spawn){
			if($spawn["chunkX"] !== $chunkX || $spawn["chunkZ"] !== $chunkZ){
				continue;
			}
			$yaw = (new Random($this->seed ^ ($spawn["chunkX"] * 31 + $spawn["chunkZ"])))->nextFloat() * 360;
			$crystal = new EndCrystal(Location::fromObject($spawn["position"], $world, $yaw, 0.0));
			$crystal->setShowBase(true);
			$crystal->spawnToAll();
		}
	}
}
