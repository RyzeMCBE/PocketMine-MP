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

/**
 * Quarter turns around the Y axis, stored as 0 to 3.
 */
final class Rotation{

	public const NONE = 0;
	public const ROTATE_90 = 1;
	public const ROTATE_180 = 2;
	public const ROTATE_270 = 3;

	private function __construct(){
	}

	public static function add(int $base, int $other) : int{
		return ($base + $other) & 3;
	}

	public static function inverse(int $rotation) : int{
		return match($rotation){
			self::ROTATE_90 => self::ROTATE_270,
			self::ROTATE_270 => self::ROTATE_90,
			default => $rotation
		};
	}
}
