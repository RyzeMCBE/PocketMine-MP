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

namespace pocketmine\world\dimension\generator\density;

use pocketmine\world\dimension\generator\math\GenerationMath;
use function max;
use function min;

/**
 * Linear gradient from $fromValue at $fromY to $toValue at $toY, constant
 * outside that range.
 */
final class YClampedGradient implements DensityFunction{

	public function __construct(
		private int $fromY,
		private int $toY,
		private float $fromValue,
		private float $toValue
	){}

	public function compute(int $x, int $y, int $z) : float{
		if($this->fromY === $this->toY){
			return $y < $this->fromY ? $this->fromValue : $this->toValue;
		}
		$t = GenerationMath::clamp(($y - $this->fromY) / ($this->toY - $this->fromY), 0.0, 1.0);
		return $this->fromValue + $t * ($this->toValue - $this->fromValue);
	}

	public function minValue() : float{
		return min($this->fromValue, $this->toValue);
	}

	public function maxValue() : float{
		return max($this->fromValue, $this->toValue);
	}
}
