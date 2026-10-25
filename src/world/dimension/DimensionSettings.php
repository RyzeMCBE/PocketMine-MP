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

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use function abs;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function preg_match;

/** Validated settings for the separate-world dimension layout. */
final class DimensionSettings{

	/** @param array<int, string> $worldNames */
	private function __construct(
		private array $worldNames,
		public readonly int $portalLoadTimeoutTicks,
		public readonly bool $endUseWorldSpawn,
		public readonly int $endX,
		public readonly int $endY,
		public readonly int $endZ,
		public readonly bool $endCreatePlatform,
		/** @var array<int, int|null> */
		private array $sourceDimensions = [],
		public readonly bool $transitionDiagnostics = false,
		public readonly int $transitionTimeoutSeconds = 120,
		public readonly int $portalSearchChunksPerTick = 8,
		public readonly int $portalSearchBudgetMilliseconds = 2
	){}

	/** @param mixed[] $config */
	public static function fromArray(array $config) : self{
		$worldNames = [];
		$sourceDimensions = [];
		foreach(["nether" => DimensionIds::NETHER, "end" => DimensionIds::THE_END] as $key => $id){
			$section = self::section($config, $key);
			$sourceDimensions[$id] = match($section["source-dimension"] ?? "auto"){
				"auto" => null,
				"overworld" => DimensionIds::OVERWORLD,
				"nether" => DimensionIds::NETHER,
				"end" => DimensionIds::THE_END,
				default => throw new InvalidArgumentException("dimensions.yml: $key.source-dimension must be auto, overworld, nether or end")
			};
			$name = $section["world"] ?? ($key === "nether" ? "world_nether" : "world_the_end");
			if(!is_string($name) || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/D', $name) !== 1){
				throw new InvalidArgumentException("dimensions.yml: $key.world must be a world folder name without a path");
			}
			if(self::boolean($section, "enabled", true)){
				$worldNames[$id] = $name;
			}
		}
		if(isset($worldNames[DimensionIds::NETHER], $worldNames[DimensionIds::THE_END]) && $worldNames[DimensionIds::NETHER] === $worldNames[DimensionIds::THE_END]){
			throw new InvalidArgumentException("dimensions.yml: Nether and End must use different world folders");
		}
		$portal = self::section($config, "portal");
		$end = self::section($config, "end");
		$arrival = self::section($end, "arrival");
		$x = self::integer($arrival, "x", 100);
		$y = self::integer($arrival, "y", 49);
		$z = self::integer($arrival, "z", 0);
		$timeout = self::integer($portal, "load-timeout-ticks", 600);
		if(abs($x) > 29999872 || abs($z) > 29999872 || $y < 1 || $y > 253){
			throw new InvalidArgumentException("dimensions.yml: End arrival must have |x|, |z| <= 29999872 and y between 1 and 253");
		}
		if($timeout < 20 || $timeout > 12000){
			throw new InvalidArgumentException("dimensions.yml: portal.load-timeout-ticks must be between 20 and 12000");
		}
		$transitionTimeout = self::integer($portal, "transition-timeout-seconds", 120);
		$searchChunks = self::integer($portal, "search-chunks-per-tick", 8);
		$searchBudget = self::integer($portal, "search-budget-milliseconds", 2);
		if($searchChunks < 1 || $searchChunks > 64 || $searchBudget < 1 || $searchBudget > 50){
			throw new InvalidArgumentException("dimensions.yml: portal search limits must be 1..64 chunks and 1..50 milliseconds");
		}
		if($transitionTimeout < 10 || $transitionTimeout > 600){
			throw new InvalidArgumentException("dimensions.yml: portal.transition-timeout-seconds must be between 10 and 600");
		}
		return new self($worldNames, $timeout, self::boolean($arrival, "use-world-spawn", false), $x, $y, $z, self::boolean($arrival, "create-platform", true), $sourceDimensions, self::boolean($portal, "transition-diagnostics", false), $transitionTimeout, $searchChunks, $searchBudget);
	}

	/** null means automatic detection; this is storage identity, not the client's dimension. */
	public function getSourceDimension(int $dimension) : ?int{
		return $this->sourceDimensions[$dimension] ?? null;
	}

	/** @return array<int, string> */
	public function getWorldNames() : array{
		return $this->worldNames;
	}

	public function validateOverworld(string $worldName) : void{
		foreach($this->worldNames as $name){
			if($name === $worldName){
				throw new InvalidArgumentException("dimensions.yml: a dimension folder cannot be the default Overworld ($worldName)");
			}
		}
	}

	/**
	 * @param mixed[] $config
	 * @return mixed[]
	 */
	private static function section(array $config, string $key) : array{
		$value = $config[$key] ?? [];
		if(!is_array($value)){
			throw new InvalidArgumentException("dimensions.yml: $key must be a section");
		}
		return $value;
	}

	/** @param mixed[] $config */
	private static function boolean(array $config, string $key, bool $default) : bool{
		$value = $config[$key] ?? $default;
		if(!is_bool($value)){
			throw new InvalidArgumentException("dimensions.yml: $key must be true or false");
		}
		return $value;
	}

	/** @param mixed[] $config */
	private static function integer(array $config, string $key, int $default) : int{
		$value = $config[$key] ?? $default;
		if(!is_int($value)){
			throw new InvalidArgumentException("dimensions.yml: $key must be an integer");
		}
		return $value;
	}
}
