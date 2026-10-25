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

namespace pocketmine\world;

use PHPUnit\Framework\TestCase;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\WorldData;
use pocketmine\world\format\io\WritableWorldProvider;
use function iterator_to_array;

final class IncrementalWorldSaveTest extends TestCase{

	/** @return array{World, WritableWorldProvider&\PHPUnit\Framework\MockObject\MockObject, WorldData&\PHPUnit\Framework\MockObject\MockObject} */
	private function world() : array{
		$world = $this->getMockBuilder(World::class)->disableOriginalConstructor()->onlyMethods(['getAutoSave', 'getFolderName'])->getMock();
		$world->method('getAutoSave')->willReturn(true);
		$world->method('getFolderName')->willReturn('save-test');
		$provider = $this->createMock(WritableWorldProvider::class);
		$data = $this->createMock(WorldData::class);
		$provider->method('getWorldData')->willReturn($data);
		(new \ReflectionProperty(World::class, 'provider'))->setValue($world, $provider);
		(new \ReflectionProperty(World::class, 'time'))->setValue($world, 123);
		$world->timings = new WorldTimings($world);
		return [$world, $provider, $data];
	}

	public function testAutosaveWritesCurrentChunkVersionAndSkipsUnloadedTerrain() : void{
		[$world, $provider, $data] = $this->world();
		$a = World::chunkHash(0, 0);
		$b = World::chunkHash(1, 0);
		(new \ReflectionProperty(World::class, 'chunks'))->setValue($world, [$a => new Chunk([], true), $b => new Chunk([], true)]);
		$tasks = iterator_to_array($world->createIncrementalSaveTasks());
		self::assertCount(4, $tasks);
		$replacement = new Chunk([], false);
		(new \ReflectionProperty(World::class, 'chunks'))->setValue($world, [$a => $replacement]);
		$provider->expects(self::once())->method('saveChunk')->willReturnCallback(static function(int $x, int $z, $data) : void{
			self::assertSame([0, 0], [$x, $z]);
			self::assertFalse($data->isPopulated(), 'Must not write the old snapshot after replacement');
		});
		$data->expects(self::once())->method('save');
		foreach($tasks as $task){ $task(); }
	}

	public function testFailedWriteRetainsDirtyFlagsAndWorldUnloadMakesTasksHarmless() : void{
		[$world, $provider, $data] = $this->world();
		$chunk = new Chunk([], true);
		(new \ReflectionProperty(World::class, 'chunks'))->setValue($world, [World::chunkHash(0, 0) => $chunk]);
		$tasks = iterator_to_array($world->createIncrementalSaveTasks());
		$dirty = $chunk->getTerrainDirtyFlags();
		self::assertNotSame(0, $dirty);
		$provider->expects(self::once())->method('saveChunk')->willThrowException(new \RuntimeException('disk failure'));
		try{ $tasks[1](); self::fail('Write must fail'); }catch(\RuntimeException){}
		self::assertSame($dirty, $chunk->getTerrainDirtyFlags());
		(new \ReflectionProperty(World::class, 'unloaded'))->setValue($world, true);
		$data->expects(self::never())->method('save');
		foreach($tasks as $task){ $task(); }
	}
}
