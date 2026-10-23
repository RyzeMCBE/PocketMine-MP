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

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

/**
 * The generator preset of the nether and end worlds. Generators run on worker
 * threads and cannot reach the plugin, so the plugin resource folder, where
 * the structure templates live, travels inside the preset string saved with
 * the world.
 */
final class GeneratorOptions{

	public static function encode(string $resourceFolder) : string{
		return json_encode(["resources" => $resourceFolder], JSON_THROW_ON_ERROR);
	}

	public static function resourceFolder(string $preset) : string{
		$decoded = json_decode($preset, true);
		if(is_array($decoded) && is_string($decoded["resources"] ?? null)){
			return $decoded["resources"];
		}
		return "";
	}
}
