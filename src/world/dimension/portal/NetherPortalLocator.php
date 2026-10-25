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

use pocketmine\block\BlockTypeIds;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\dimension\Dimensions;
use pocketmine\world\World;
use function max;
use function min;

/**
 * Finds the nether portal an entity arrives in, or builds one with its
 * obsidian frame and platform when none is close enough.
 */
final class NetherPortalLocator{

	public const OVERWORLD_SEARCH_RADIUS = 128;
	public const NETHER_SEARCH_RADIUS = 16;
	public const NETHER_MAX_PORTAL_Y = 115;

	private function __construct(){
	}

	/**
	 * Returns the bottom block of the closest portal column: horizontal
	 * distance first, then the vertical distance to the given height.
	 */
	public static function findNearest(World $world, int $dimension, int $x, int $y, int $z) : ?Vector3{
		$search = new NetherPortalSearch($world, $dimension, $x, $y, $z);
		while(!$search->step()){}
		return $search->getResult();
	}

	/**
	 * Picks the height of a new portal in a column: the highest spot with five
	 * free blocks above solid ground, below the roof in the nether.
	 */
	public static function findBaseY(World $world, int $dimension, int $x, int $z) : int{
		$minY = max($world->getMinY(), Dimensions::getMinY($dimension));
		$maxY = min($world->getMaxY(), Dimensions::getMaxY($dimension));
		$start = $world->getHighestBlockAt($x, $z) ?? $maxY - 6;
		if($dimension === DimensionIds::NETHER){
			$start = min($start, self::NETHER_MAX_PORTAL_Y);
		}
		$y = $start;
		for($i = $start; $i > $minY + 2; $i--){
			$ground = $world->getBlockAt($x, $i - 1, $z);
			if(!$ground->isSolid() || $ground instanceof Liquid || $ground->getTypeId() === BlockTypeIds::BEDROCK){
				continue;
			}
			$space = true;
			for($h = 0; $h < 5; $h++){
				if($world->getBlockAt($x, $i + $h, $z)->getTypeId() !== BlockTypeIds::AIR){
					$space = false;
					break;
				}
			}
			if($space){
				$y = $i;
				break;
			}
		}
		return max($minY + 1, min($maxY - 6, $y));
	}

	/**
	 * Builds a 2x3 portal along the X axis whose left portal column is at the
	 * given position, on a small obsidian platform, and returns where an
	 * entity stands inside it.
	 */
	public static function build(World $world, int $x, int $y, int $z) : Vector3{
		$air = VanillaBlocks::AIR();
		$obsidian = VanillaBlocks::OBSIDIAN();
		$portal = VanillaBlocks::NETHER_PORTAL()->setAxis(Axis::X);

		for($bx = $x - 2; $bx <= $x + 3; $bx++){
			for($bz = $z - 1; $bz <= $z + 1; $bz++){
				for($by = $y; $by <= $y + 4; $by++){
					if($world->getBlockAt($bx, $by, $bz)->getTypeId() !== BlockTypeIds::BEDROCK){
						$world->setBlockAt($bx, $by, $bz, $air);
					}
				}
			}
		}

		foreach([[0, -1], [1, -1], [-1, 0], [0, 0], [1, 0], [2, 0], [0, 1], [1, 1]] as [$ox, $oz]){
			$world->setBlockAt($x + $ox, $y, $z + $oz, $obsidian);
		}
		for($h = 1; $h <= 3; $h++){
			$world->setBlockAt($x - 1, $y + $h, $z, $obsidian);
			$world->setBlockAt($x + 2, $y + $h, $z, $obsidian);
		}
		for($ox = -1; $ox <= 2; $ox++){
			$world->setBlockAt($x + $ox, $y + 4, $z, $obsidian);
		}
		for($h = 1; $h <= 3; $h++){
			$world->setBlockAt($x, $y + $h, $z, $portal);
			$world->setBlockAt($x + 1, $y + $h, $z, $portal);
		}

		return new Vector3($x + 1, $y + 1, $z + 0.5);
	}
}
