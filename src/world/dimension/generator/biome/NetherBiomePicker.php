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

namespace pocketmine\world\dimension\generator\biome;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\dimension\generator\math\Float32;
use pocketmine\world\dimension\generator\noise\NormalNoise;
use pocketmine\world\dimension\generator\random\LegacyRandom;

/**
 * Picks the nether biome whose climate point (temperature, humidity,
 * offset) is nearest to the temperature and humidity noises.
 */
final class NetherBiomePicker implements BiomePicker{

	private NormalNoise $temperatureNoise;
	private NormalNoise $humidityNoise;
	/** @var array<int, float[]> biome id => climate point */
	private array $points;

	public function __construct(LegacyRandom $random){
		$this->temperatureNoise = new NormalNoise($random->fork(), -10, [1.5, 0, 1, 0, 0, 0]);
		$this->humidityNoise = new NormalNoise($random->fork(), -8, [1, 1, 0, 0, 0, 0]);
		$this->points = [
			BiomeIds::BASALT_DELTAS => [-0.5, 0.0, Float32::of(0.175)],
			BiomeIds::CRIMSON_FOREST => [Float32::of(0.4), 0.0, 0.0],
			BiomeIds::HELL => [0.0, 0.0, 0.0],
			BiomeIds::SOULSAND_VALLEY => [0.0, -0.5, 0.0],
			BiomeIds::WARPED_FOREST => [0.0, 0.5, Float32::of(0.375)]
		];
	}

	public function pick(int $x, int $y, int $z) : NetherBiomeResult{
		$temperature = $this->temperatureNoise->getValue($x, $y, $z);
		$humidity = $this->humidityNoise->getValue($x, $y, $z);
		$distance = 3.4028234663852886E38;
		$biomeId = BiomeIds::HELL;
		foreach($this->points as $id => [$pointX, $pointY, $pointZ]){
			$dx = $temperature - $pointX;
			$dy = $humidity - $pointY;
			$dz = 0.0 - $pointZ;
			$delta = $dx * $dx + $dy * $dy + $dz * $dz;
			if($delta < $distance){
				$distance = $delta;
				$biomeId = $id;
			}
		}
		return new NetherBiomeResult($biomeId, $temperature, $humidity);
	}
}
