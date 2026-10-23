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

namespace pocketmine\world\dimension\generator\structure\fortress;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\Utils;
use pocketmine\world\dimension\generator\structure\template\StateBlocks;
use function is_int;
use function is_string;

/**
 * Block states used by the nether fortress pieces, in piece coordinates.
 */
final class FortressBlocks{

	/** @var array<string, BlockStateData> */
	private static array $states = [];

	private function __construct(){
	}

	public static function air() : BlockStateData{
		return self::get("minecraft:air");
	}

	public static function netherBricks() : BlockStateData{
		return self::get("minecraft:nether_brick");
	}

	public static function netherBrickFence() : BlockStateData{
		return self::get("minecraft:nether_brick_fence");
	}

	public static function soulSand() : BlockStateData{
		return self::get("minecraft:soul_sand");
	}

	public static function netherWart() : BlockStateData{
		return self::get("minecraft:nether_wart", ["age" => new IntTag(3)]);
	}

	public static function lava() : BlockStateData{
		return self::get("minecraft:lava");
	}

	public static function fallingLava() : BlockStateData{
		return self::get("minecraft:flowing_lava", ["liquid_depth" => new IntTag(8)]);
	}

	public static function spawner() : BlockStateData{
		return self::get("minecraft:mob_spawner");
	}

	/**
	 * @param int $direction weirdo direction: 0 east, 1 west, 2 south, 3 north
	 */
	public static function netherBrickStairs(int $direction) : BlockStateData{
		return self::get("minecraft:nether_brick_stairs", ["weirdo_direction" => new IntTag($direction)]);
	}

	public static function chest(string $cardinal) : BlockStateData{
		return self::get("minecraft:chest", ["minecraft:cardinal_direction" => new StringTag($cardinal)]);
	}

	/**
	 * @param array<string, Tag> $states
	 */
	private static function get(string $name, array $states = []) : BlockStateData{
		$key = $name;
		foreach(Utils::stringifyKeys($states) as $state => $tag){
			$key .= ";" . $state . "=" . self::scalarValue($tag);
		}
		return self::$states[$key] ??= StateBlocks::state($name, $states) ?? BlockStateData::current($name, $states);
	}
	private static function scalarValue(Tag $tag) : int|string{
		$value = $tag->getValue();
		if(!is_int($value) && !is_string($value)){
			throw new \InvalidArgumentException("Block properties must be integer or string tags");
		}
		return $value;
	}

}
