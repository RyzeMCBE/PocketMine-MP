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

/**
 * Adds a constant to, or multiplies by a constant, another function.
 */
final class MulOrAdd implements DensityFunction{

	public const ADD = 0;
	public const MUL = 1;

	public function __construct(
		private int $type,
		private DensityFunction $input,
		private float $minValue,
		private float $maxValue,
		private float $argument
	){}

	public function compute(int $x, int $y, int $z) : float{
		$value = $this->input->compute($x, $y, $z);
		return $this->type === self::MUL ? $value * $this->argument : $value + $this->argument;
	}

	public function minValue() : float{
		return $this->minValue;
	}

	public function maxValue() : float{
		return $this->maxValue;
	}
}
