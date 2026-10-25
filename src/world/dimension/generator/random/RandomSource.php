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

namespace pocketmine\world\dimension\generator\random;

/**
 * A seedable source of randomness used by the terrain generators.
 *
 * Overloads are mapped as follows: nextInt() and nextInt($max) share one
 * method with an optional bound, and the two argument form nextInt(min, max)
 * is nextRangeInt($min, $max).
 */
interface RandomSource{

	/**
	 * Creates a new source seeded with the next random long of this one.
	 */
	public function fork() : RandomSource;

	/**
	 * Creates a new source seeded with the seed this one was last seeded with.
	 */
	public function identical() : RandomSource;

	/**
	 * Without bound: a random int. With a bound: see the implementation for
	 * the exact range.
	 */
	public function nextInt(?int $max = null) : int;

	/**
	 * A random int between $min and $max, see the implementation for the
	 * exact range.
	 */
	public function nextRangeInt(int $min, int $max) : int;

	public function nextBoundedInt(int $max) : int;

	public function nextLong() : int;

	public function nextBoolean() : bool;

	/**
	 * A single precision value in [0, 1).
	 */
	public function nextFloat() : float;

	/**
	 * A value in [0, 1).
	 */
	public function nextDouble() : float;

	/**
	 * A gaussian distributed value.
	 */
	public function nextGaussian() : float;

	public function setSeed(int $seed) : RandomSource;

	public function getSeed() : int;
}
