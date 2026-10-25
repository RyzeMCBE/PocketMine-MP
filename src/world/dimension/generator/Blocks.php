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

namespace pocketmine\world\dimension\generator;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function array_merge;
use function is_bool;
use function is_int;
use function ksort;
use function serialize;
use function str_contains;

/**
 * Builds blocks from their Bedrock identifier and state map, and gives the
 * Bedrock identifier of a block. Results are cached per thread.
 *
 * States may be given as nbt tags or as plain values: bool becomes a byte
 * tag, int an int tag, string a string tag. States left out take the value
 * of the block's default state.
 */
final class Blocks{

	/** @var array<string, int> */
	private static array $stateIds = [];
	/** @var array<int, string> */
	private static array $identifiers = [];
	/** @var array<string, list<BlockStateData>>|null */
	private static ?array $knownStates = null;

	private function __construct(){
	}

	/**
	 * @param array<string, Tag|bool|int|string> $states
	 *
	 * @throws \InvalidArgumentException when no registered block matches
	 */
	public static function get(string $identifier, array $states = []) : Block{
		if(!str_contains($identifier, ":")){
			$identifier = "minecraft:" . $identifier;
		}
		ksort($states);
		$tags = [];
		foreach(Utils::stringifyKeys($states) as $name => $value){
			$tags[$name] = self::toTag($value);
		}
		$key = $identifier . serialize($states);
		$stateId = self::$stateIds[$key] ??= self::resolve($identifier, $tags);
		return RuntimeBlockStateRegistry::getInstance()->fromStateId($stateId);
	}

	/**
	 * Bedrock identifier of the block, like "minecraft:glowstone".
	 */
	public static function id(Block $block) : string{
		$stateId = $block->getStateId();
		return self::$identifiers[$stateId] ??= GlobalBlockStateHandlers::getSerializer()->serialize($stateId)->getName();
	}

	/**
	 * @param array<string, Tag> $tags
	 */
	private static function resolve(string $identifier, array $tags) : int{
		$deserializer = GlobalBlockStateHandlers::getDeserializer();
		try{
			return $deserializer->deserialize(BlockStateData::current($identifier, $tags));
		}catch(\Exception){
		}

		$upgrader = GlobalBlockStateHandlers::getUpgrader();
		try{
			$default = $upgrader->getBlockStateUpgrader()->upgrade($upgrader->upgradeStringIdMeta($identifier, 0));
			if($default->getName() === $identifier){
				return $deserializer->deserialize(BlockStateData::current($identifier, array_merge($default->getStates(), $tags)));
			}
		}catch(\Exception){
		}

		foreach(self::knownStates()[$identifier] ?? [] as $candidate){
			if(self::matches($candidate, $tags)){
				return $deserializer->deserialize($candidate);
			}
		}
		throw new \InvalidArgumentException("No block state matches $identifier");
	}

	/**
	 * @param array<string, Tag> $tags
	 */
	private static function matches(BlockStateData $candidate, array $tags) : bool{
		foreach(Utils::stringifyKeys($tags) as $name => $tag){
			$state = $candidate->getState($name);
			if($state === null || !$state->equals($tag)){
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array<string, list<BlockStateData>>
	 */
	private static function knownStates() : array{
		if(self::$knownStates === null){
			$serializer = GlobalBlockStateHandlers::getSerializer();
			$known = [];
			foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $stateId => $block){
				try{
					$data = $serializer->serialize($stateId);
				}catch(\Exception){
					continue;
				}
				$known[$data->getName()][] = $data;
			}
			self::$knownStates = $known;
		}
		return self::$knownStates;
	}

	private static function toTag(Tag|bool|int|string $value) : Tag{
		if($value instanceof Tag){
			return $value;
		}
		if(is_bool($value)){
			return new ByteTag($value ? 1 : 0);
		}
		if(is_int($value)){
			return new IntTag($value);
		}
		return new StringTag($value);
	}
}
