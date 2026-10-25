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

namespace pocketmine\world\dimension\portal;

use PHPUnit\Framework\TestCase;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

final class NetherPortalSearchTest extends TestCase{

	/** @var array<int, Chunk> */
	private array $chunks = [];
	private int $loads = 0;
	/** @var World&\PHPUnit\Framework\MockObject\MockObject */
	private World $world;

	protected function setUp() : void{
		$this->world = $this->createMock(World::class);
		$this->world->method('getMinY')->willReturn(-64);
		$this->world->method('getMaxY')->willReturn(320);
		$this->world->method('loadChunk')->willReturnCallback(function(int $x, int $z) : ?Chunk{
			++$this->loads;
			return $this->chunks[World::chunkHash($x, $z)] ?? null;
		});
	}

	public function testSearchIsIncrementalAndKeepsHorizontalThenVerticalOrdering() : void{
		$chunk = new Chunk([], true);
		$portalId = VanillaBlocks::NETHER_PORTAL()->getStateId();
		$chunk->setBlockStateId(0, 80, 0, $portalId);
		$chunk->setBlockStateId(1, 60, 0, $portalId);
		$this->chunks[World::chunkHash(0, 0)] = $chunk;
		$search = new NetherPortalSearch($this->world, DimensionIds::NETHER, 0, 60, 0);
		self::assertSame(0, $this->loads);
		for($step = 0; $step < 8; ++$step){
			self::assertFalse($search->step());
			self::assertSame($step + 1, $this->loads);
		}
		self::assertTrue($search->step());
		self::assertEquals(new Vector3(0, 80, 0), $search->getResult());
		self::assertTrue($search->hasCachedCoverage());
		self::assertEquals(new Vector3(0, 80, 0), NetherPortalLocator::findNearest($this->world, DimensionIds::NETHER, 0, 60, 0));
		self::assertSame(9, $this->loads, 'Second query must reuse cached chunk candidates');
	}

	public function testChangesAndNewlyLoadedTerrainInvalidateCachedCandidates() : void{
		$index = new NetherPortalIndex($this->world);
		self::assertSame([], $index->getPortals(0, 0, DimensionIds::NETHER));
		$chunk = $this->chunks[World::chunkHash(0, 0)] = new Chunk([], true);
		$chunk->setBlockStateId(2, 15, 3, VanillaBlocks::NETHER_PORTAL()->getStateId());
		$chunk->setBlockStateId(2, 16, 3, VanillaBlocks::NETHER_PORTAL()->getStateId());
		$index->onChunkLoaded(0, 0, $chunk);
		self::assertEquals([new Vector3(2, 15, 3)], $index->getPortals(0, 0, DimensionIds::NETHER));
		$chunk->setBlockStateId(2, 15, 3, VanillaBlocks::AIR()->getStateId());
		$index->onBlockChanged(new Vector3(2, 15, 3));
		self::assertEquals([new Vector3(2, 16, 3)], $index->getPortals(0, 0, DimensionIds::NETHER));
		self::assertSame(3, $this->loads);
	}

	public function testIndexEvictsListenersAndNegativeCacheWithinCapacity() : void{
		$this->world->expects(self::exactly(2))->method('unregisterChunkListener');
		$index = new NetherPortalIndex($this->world, 2);
		foreach([0, 1, 2, 0] as $x){ $index->getPortals($x, 0, DimensionIds::NETHER); }
		self::assertSame(4, $this->loads);
		self::assertFalse($index->hasCachedChunk(1, 0, DimensionIds::NETHER));
	}
}
