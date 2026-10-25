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

namespace pocketmine\world\dimension\generator\math;

use function pack;
use function unpack;

/**
 * Single precision (IEEE 754 binary32) rounding. Every single precision
 * operation is computed in double precision then rounded with of(), which
 * gives the correctly rounded binary32 result for +, -, *, / and sqrt.
 */
final class Float32{

	private function __construct(){
	}

	public static function of(float $value) : float{
		$unpacked = unpack("g", pack("g", $value));
		return $unpacked === false ? $value : (float) $unpacked[1];
	}
}
