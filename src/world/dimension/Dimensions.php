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

namespace pocketmine\world\dimension;

use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Server;
use pocketmine\world\World;

/**
 * Maps worlds to Bedrock dimensions. The overworld is the default world; the
 * nether and the end are the worlds named in dimensions.yml. Any other world is
 * treated as an overworld.
 */
final class Dimensions{

	/**
	 * @param array<int, string> $worldNames dimension id => world folder name
	 */
	public function __construct(
		private Server $server,
		private array $worldNames
	){}

	/** @phpstan-return DimensionIds::* */
	public function getDimension(World $world) : int{
		$name = $world->getFolderName();
		if(($this->worldNames[DimensionIds::NETHER] ?? null) === $name){
			return DimensionIds::NETHER;
		}
		if(($this->worldNames[DimensionIds::THE_END] ?? null) === $name){
			return DimensionIds::THE_END;
		}
		return DimensionIds::OVERWORLD;
	}

	public function isEnabled(int $dimension) : bool{
		return $dimension === DimensionIds::OVERWORLD || isset($this->worldNames[$dimension]);
	}

	public function getWorldName(int $dimension) : ?string{
		return $this->worldNames[$dimension] ?? null;
	}

	/**
	 * Returns the loaded world of a dimension, the default world for the
	 * overworld, or null when the dimension is disabled or its world is not
	 * loaded.
	 */
	public function getWorld(int $dimension) : ?World{
		$manager = $this->server->getWorldManager();
		if($dimension === DimensionIds::OVERWORLD){
			return $manager->getDefaultWorld();
		}
		$name = $this->worldNames[$dimension] ?? null;
		return $name === null ? null : $manager->getWorldByName($name);
	}

	/** Resolves a loaded world for a stable portal destination; does not load or generate worlds. */
	public function getWorldByName(string $worldName) : ?World{
		return $this->server->getWorldManager()->getWorldByName($worldName);
	}

	/**
	 * Lowest block Y the client of this dimension can see.
	 */
	public static function getMinY(int $dimension) : int{
		return $dimension === DimensionIds::OVERWORLD ? World::Y_MIN : 0;
	}

	/**
	 * Exclusive upper block Y the client of this dimension can see.
	 */
	public static function getMaxY(int $dimension) : int{
		return match($dimension){
			DimensionIds::NETHER => 128,
			DimensionIds::THE_END => 256,
			default => World::Y_MAX
		};
	}
}
