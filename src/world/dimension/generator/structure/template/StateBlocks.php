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

namespace pocketmine\world\dimension\generator\structure\template;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function array_key_exists;
use function array_merge;
use function ksort;
use function serialize;

/**
 * Converts Bedrock block states to server blocks, and builds block states
 * from an identifier plus the states to override. Results are cached per
 * thread. States the server does not know convert to null.
 */
final class StateBlocks{

	/** @var array<string, int> */
	private static array $stateIds = [];
	/** @var array<string, BlockStateData|null> */
	private static array $defaults = [];

	private function __construct(){
	}

	public static function toBlock(BlockStateData $state) : ?Block{
		$key = self::key($state);
		if(!isset(self::$stateIds[$key])){
			self::$stateIds[$key] = self::resolve($state);
		}
		$stateId = self::$stateIds[$key];
		if($stateId === -1){
			return null;
		}
		return RuntimeBlockStateRegistry::getInstance()->fromStateId($stateId);
	}

	/**
	 * State of $identifier with its default values, overridden by $states.
	 *
	 * @param array<string, Tag> $states
	 */
	public static function state(string $identifier, array $states = []) : ?BlockStateData{
		if(!array_key_exists($identifier, self::$defaults)){
			self::$defaults[$identifier] = self::resolveDefault($identifier);
		}
		$default = self::$defaults[$identifier];
		if($default === null){
			return null;
		}
		if($states === []){
			return $default;
		}
		return BlockStateData::current($identifier, array_merge($default->getStates(), $states));
	}

	public static function key(BlockStateData $state) : string{
		$values = [];
		foreach(Utils::stringifyKeys($state->getStates()) as $name => $tag){
			$values[$name] = $tag->getValue();
		}
		ksort($values);
		return $state->getName() . serialize($values);
	}

	private static function resolve(BlockStateData $state) : int{
		try{
			return GlobalBlockStateHandlers::getDeserializer()->deserialize($state);
		}catch(\Exception){
			return -1;
		}
	}

	private static function resolveDefault(string $identifier) : ?BlockStateData{
		$upgrader = GlobalBlockStateHandlers::getUpgrader();
		try{
			$default = $upgrader->getBlockStateUpgrader()->upgrade($upgrader->upgradeStringIdMeta($identifier, 0));
		}catch(\Exception){
			return null;
		}
		if($default->getName() !== $identifier){
			return null;
		}
		return $default;
	}
}
