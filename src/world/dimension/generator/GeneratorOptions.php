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

use pocketmine\world\dimension\generator\structure\template\StructureTemplates;
use function file_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function rtrim;
use const JSON_THROW_ON_ERROR;
use const pocketmine\RESOURCE_PATH;

/**
 * Structure resource settings shared with generator workers. Stored PHAR paths
 * may refer to an expired decompression cache after a server restart.
 */
final class GeneratorOptions{

	public static function encode(string $resourceFolder) : string{
		return json_encode(["resources" => $resourceFolder], JSON_THROW_ON_ERROR);
	}

	public static function resourceFolder(string $preset) : string{
		$decoded = json_decode($preset, true);
		if(is_array($decoded) && is_string($decoded["resources"] ?? null)){
			$folder = $decoded["resources"];
			return file_exists(rtrim($folder, "/\\") . "/" . StructureTemplates::FILE) ? $folder : RESOURCE_PATH;
		}
		return "";
	}
}
