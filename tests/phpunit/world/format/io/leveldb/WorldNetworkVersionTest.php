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

namespace pocketmine\world\format\io\leveldb;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Binary;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\data\BedrockWorldData;
use pocketmine\world\format\io\exception\UnsupportedWorldFormatException;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use Symfony\Component\Filesystem\Filesystem;
use function bin2hex;
use function file_get_contents;
use function file_put_contents;
use function random_bytes;
use function strlen;
use function sys_get_temp_dir;

final class WorldNetworkVersionTest extends TestCase{
	private string $worldPath;

	protected function setUp() : void{
		$this->worldPath = sys_get_temp_dir() . '/pmmp-world-network-' . bin2hex(random_bytes(8));
		(new Filesystem())->mkdir($this->worldPath);
	}

	protected function tearDown() : void{
		(new Filesystem())->remove($this->worldPath);
	}

	private function writeLevelData(int $networkVersion, int $storageVersion = 10) : string{
		$tag = CompoundTag::create()
			->setInt('StorageVersion', $storageVersion)
			->setInt('NetworkVersion', $networkVersion)
			->setString('LevelName', 'existing world')
			->setString('generatorName', 'flat')
			->setString('generatorOptions', '')
			->setLong('RandomSeed', 12345)
			->setInt('SpawnX', 1)
			->setInt('SpawnY', 64)
			->setInt('SpawnZ', 3);
		$payload = (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
		$path = $this->worldPath . '/level.dat';
		file_put_contents($path, Binary::writeLInt($storageVersion) . Binary::writeLInt(strlen($payload)) . $payload);
		return $path;
	}

	/** @return list<array{int}> */
	public static function supportedVersions() : array{
		return [[924], [2169]];
	}

	#[DataProvider('supportedVersions')]
	public function testExistingWorldMetadataLoadsWithoutBeingRewritten(int $networkVersion) : void{
		$path = $this->writeLevelData($networkVersion);
		$original = file_get_contents($path);
		$world = new BedrockWorldData($path);
		self::assertSame('existing world', $world->getName());
		self::assertSame(12345, $world->getSeed());
		self::assertSame($original, file_get_contents($path));
	}

	public function testNewerWorldNetworkVersionsRemainRejected() : void{
		$path = $this->writeLevelData(2170);
		$this->expectException(UnsupportedWorldFormatException::class);
		$this->expectExceptionMessage('LevelDB world protocol version 2170 is currently unsupported');
		new BedrockWorldData($path);
	}

	public function testNewerStorageFormatsRemainRejected() : void{
		$path = $this->writeLevelData(2169, 11);
		$this->expectException(UnsupportedWorldFormatException::class);
		$this->expectExceptionMessage('LevelDB world format version 11 is currently unsupported');
		new BedrockWorldData($path);
	}

	public function testWorld2169PreservesChunkDataAfterSavingAndReopening() : void{
		$path = $this->writeLevelData(2169);
		$logger = $this->createMock(\Logger::class);
		$logger->expects(self::never())->method('error');
		$blocks = [VanillaBlocks::STONE(), VanillaBlocks::CHEST(), VanillaBlocks::OAK_STAIRS(), VanillaBlocks::OAK_DOOR()];
		$subChunks = [];
		for($y = Chunk::MIN_SUBCHUNK_INDEX; $y <= Chunk::MAX_SUBCHUNK_INDEX; ++$y){
			$subChunks[$y] = new SubChunk(VanillaBlocks::AIR()->getStateId(), [], new PalettedBlockArray(BiomeIds::PLAINS));
		}
		foreach($blocks as $x => $block){
			$subChunks[0]->setBlockStateId($x, 1, 2, $block->getStateId());
		}
		$entities = [CompoundTag::create()->setString('identifier', 'minecraft:item')->setString('test', 'entity')];
		$tiles = [CompoundTag::create()->setString('id', 'Chest')->setInt('x', 1)->setInt('y', 1)->setInt('z', 2)];
		$provider = new LevelDB($this->worldPath, $logger);
		try{
			$provider->saveChunk(0, 0, new ChunkData($subChunks, true, $entities, $tiles), Chunk::DIRTY_FLAGS_ALL);
			$provider->getWorldData()->save();
		}finally{
			$provider->close();
		}
		self::assertSame(2169, (new BedrockWorldData($path))->getCompoundTag()->getInt('NetworkVersion'));
		$provider = new LevelDB($this->worldPath, $logger);
		try{
			$loaded = $provider->loadChunk(0, 0);
			self::assertNotNull($loaded);
			$data = $loaded->getData();
			self::assertTrue($data->isPopulated());
			self::assertEquals($entities, $data->getEntityNBT());
			self::assertEquals($tiles, $data->getTileNBT());
			foreach($blocks as $x => $block){
				self::assertSame($block->getStateId(), $data->getSubChunks()[0]->getBlockStateId($x, 1, 2));
			}
			self::assertSame(BiomeIds::PLAINS, $data->getSubChunks()[0]->getBiomeArray()->get(1, 1, 2));
		}finally{
			$provider->close();
		}
	}
}
