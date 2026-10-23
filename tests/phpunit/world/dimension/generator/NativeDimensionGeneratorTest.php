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

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\dimension\generator\end\EndGenerator;
use pocketmine\world\dimension\generator\nether\NetherGenerator;
use pocketmine\world\dimension\generator\structure\template\BlockStateTransform;
use pocketmine\world\dimension\generator\structure\template\StateBlocks;
use pocketmine\world\dimension\generator\structure\template\StructureTemplates;
use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;
use function in_array;
use const pocketmine\RESOURCE_PATH;

final class NativeDimensionGeneratorTest extends TestCase{
	public function testExpiredPharResourcePathsUseTheCurrentServerResources() : void{
		$preset = GeneratorOptions::encode(__DIR__ . '/expired-phar-cache/resources');
		self::assertSame(RESOURCE_PATH, GeneratorOptions::resourceFolder($preset));
		self::assertSame(RESOURCE_PATH, GeneratorOptions::resourceFolder(GeneratorOptions::encode(RESOURCE_PATH)));
	}

	public function testStructureTemplatesAndBlockStatesResolve() : void{
		$template = StructureTemplates::get(RESOURCE_PATH, 'bastion/units/air_base');
		self::assertNotNull($template);
		self::assertGreaterThan(0, $template->getVolume());
		$stone = StateBlocks::toBlock(BlockStateData::current('minecraft:stone', []));
		self::assertNotNull($stone);
		self::assertSame(VanillaBlocks::STONE()->getStateId(), $stone->getStateId());
	}

	public function testFourStructureRotationsPreserveTypedBlockProperties() : void{
		$original = BlockStateData::current('minecraft:oak_stairs', [
			'upside_down_bit' => new ByteTag(1),
			'weirdo_direction' => new IntTag(0),
			'minecraft:corner' => new StringTag('inner_left')
		]);
		$rotated = $original;
		for($i = 0; $i < 4; ++$i){
			$rotated = BlockStateTransform::clockwise90($rotated);
		}
		self::assertTrue($original->equals($rotated));
	}

	public function testEndGeneratorCreatesMainIslandTerrain() : void{
		$manager = new SimpleChunkManager(0, 256);
		$chunk = new Chunk([], false);
		$manager->setChunk(0, 0, $chunk);

		$generator = new EndGenerator(123456789, "");
		$generator->generateChunk($manager, 0, 0);

		self::assertSame(BiomeIds::THE_END, $chunk->getBiomeId(8, 63, 8));

		$endStone = VanillaBlocks::END_STONE()->getStateId();
		$blocks = 0;
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				for($y = 0; $y < 128; ++$y){
					if($chunk->getBlockStateId($x, $y, $z) === $endStone){
						++$blocks;
					}
				}
			}
		}
		self::assertGreaterThan(0, $blocks, "The End origin chunk must contain end stone from the main island");
	}

	public function testNetherGeneratorCreatesBoundedTerrainAndBiome() : void{
		$manager = new SimpleChunkManager(0, 128);
		$chunk = new Chunk([], false);
		$manager->setChunk(0, 0, $chunk);

		$generator = new NetherGenerator(123456789, "");
		$generator->generateChunk($manager, 0, 0);

		$bedrock = VanillaBlocks::BEDROCK()->getStateId();
		self::assertSame($bedrock, $chunk->getBlockStateId(0, 0, 0));
		self::assertSame($bedrock, $chunk->getBlockStateId(0, 127, 0));

		$biome = $chunk->getBiomeId(8, 63, 8);
		self::assertTrue(in_array($biome, [
			BiomeIds::HELL,
			BiomeIds::SOULSAND_VALLEY,
			BiomeIds::CRIMSON_FOREST,
			BiomeIds::WARPED_FOREST,
			BiomeIds::BASALT_DELTAS
		], true), "Generated Nether chunks must use a Nether biome");
	}
}
