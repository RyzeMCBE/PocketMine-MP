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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class DimensionSettingsTest extends TestCase{

	public function testDefaultsKeepExistingWorldFolders() : void{
		$settings = DimensionSettings::fromArray([]);
		self::assertSame([DimensionIds::NETHER => "world_nether", DimensionIds::THE_END => "world_the_end"], $settings->getWorldNames());
		self::assertSame(600, $settings->portalLoadTimeoutTicks);
		self::assertFalse($settings->transitionDiagnostics);
		self::assertSame(120, $settings->transitionTimeoutSeconds);
		self::assertSame([100, 49, 0], [$settings->endX, $settings->endY, $settings->endZ]);
		self::assertFalse($settings->endUseWorldSpawn);
		self::assertNull($settings->getSourceDimension(DimensionIds::NETHER));
	}

	public function testExplicitStorageDimensions() : void{
		$settings = DimensionSettings::fromArray(["nether" => ["source-dimension" => "nether"], "end" => ["source-dimension" => "overworld"]]);
		self::assertSame(1, $settings->getSourceDimension(DimensionIds::NETHER));
		self::assertSame(0, $settings->getSourceDimension(DimensionIds::THE_END));
	}

	public function testCustomWorldAndDisabledDimension() : void{
		$settings = DimensionSettings::fromArray([
			"nether" => ["enabled" => false],
			"end" => ["world" => "custom-end", "arrival" => ["use-world-spawn" => true, "create-platform" => false]]
		]);
		self::assertSame([DimensionIds::THE_END => "custom-end"], $settings->getWorldNames());
		self::assertTrue($settings->endUseWorldSpawn);
		self::assertFalse($settings->endCreatePlatform);
	}

	/** @return iterable<string, array{array<mixed>}> */
	public static function invalidSettings() : iterable{
		yield "path traversal" => [["nether" => ["world" => "../world"]]];
		yield "absolute path" => [["nether" => ["world" => "/world"]]];
		yield "same world" => [["nether" => ["world" => "same"], "end" => ["world" => "same"]]];
		yield "string boolean" => [["end" => ["enabled" => "false"]]];
		yield "wrong section" => [["end" => true]];
		yield "unknown source" => [["end" => ["source-dimension" => "unknown"]]];
		yield "numeric source" => [["nether" => ["source-dimension" => 1]]];
		yield "wrong arrival section" => [["end" => ["arrival" => "spawn"]]];
		yield "low End arrival" => [["end" => ["arrival" => ["y" => 0]]]];
		yield "high End arrival" => [["end" => ["arrival" => ["y" => 254]]]];
		yield "unbounded coordinate" => [["end" => ["arrival" => ["x" => 30000000]]]];
		yield "fractional coordinate" => [["end" => ["arrival" => ["x" => 1.5]]]];
		yield "short transition timeout" => [["portal" => ["transition-timeout-seconds" => 9]]];
		yield "unbounded transition timeout" => [["portal" => ["transition-timeout-seconds" => 601]]];
		yield "invalid transition diagnostics" => [["portal" => ["transition-diagnostics" => "true"]]];
		yield "short timeout" => [["portal" => ["load-timeout-ticks" => 0]]];
		yield "unbounded timeout" => [["portal" => ["load-timeout-ticks" => 12001]]];
	}

	/** @param mixed[] $config */
	#[DataProvider("invalidSettings")]
	public function testInvalidSettingsAreRejected(array $config) : void{
		$this->expectException(InvalidArgumentException::class);
		DimensionSettings::fromArray($config);
	}

	public function testDimensionCannotReplaceDefaultOverworld() : void{
		$this->expectException(InvalidArgumentException::class);
		DimensionSettings::fromArray([])->validateOverworld("world_nether");
	}
}
