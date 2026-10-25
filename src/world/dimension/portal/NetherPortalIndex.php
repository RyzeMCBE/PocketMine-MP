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

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\dimension\Dimensions;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use WeakMap;
use WeakReference;
use function array_key_first;
use function count;
use function in_array;
use function max;
use function min;

/** @internal Bounded, invalidated portal locations. Chunk listeners do not keep terrain loaded. */
final class NetherPortalIndex implements ChunkListener{

	/** @var WeakMap<World, self>|null */
	private static ?WeakMap $instances = null;
	/** @var WeakReference<World> */
	private WeakReference $world;
	/** @var array<int, array{int, int}> */
	private array $listening = [];
	/** @var array<int, array{dimension: int, portals: list<Vector3>}> */
	private array $cache = [];
	private int $revision = 0;

	public function getRevision() : int{ return $this->revision; }
	public function hasCachedChunk(int $x, int $z, int $dimension) : bool{
		return ($this->cache[World::chunkHash($x, $z)]['dimension'] ?? null) === $dimension;
	}

	public static function getInstance(World $world) : self{
		self::$instances ??= new WeakMap();
		return self::$instances[$world] ??= new self($world);
	}

	public function __construct(World $world, private int $capacity = 2048){
		if($capacity < 1){ throw new \InvalidArgumentException('Portal index capacity must be positive'); }
		$this->world = WeakReference::create($world);
		$world->addOnUnloadCallback(fn() => $this->close());
	}

	/** @return list<Vector3> */
	public function getPortals(int $chunkX, int $chunkZ, int $dimension) : array{
		$world = $this->world->get() ?? throw new \LogicException('Portal world was destroyed');
		$hash = World::chunkHash($chunkX, $chunkZ);
		if(isset($this->listening[$hash])){
			unset($this->listening[$hash]); // refresh LRU order
		}else{
			$world->registerChunkListener($this, $chunkX, $chunkZ);
		}
		$this->listening[$hash] = [$chunkX, $chunkZ];
		if(count($this->listening) > $this->capacity){
			++$this->revision;
			$oldest = array_key_first($this->listening);
			[$oldX, $oldZ] = $this->listening[$oldest];
			$world->unregisterChunkListener($this, $oldX, $oldZ);
			unset($this->listening[$oldest], $this->cache[$oldest]);
		}
		if(($this->cache[$hash]['dimension'] ?? null) !== $dimension){
			$chunk = $world->loadChunk($chunkX, $chunkZ);
			$this->cache[$hash] = [
				'dimension' => $dimension,
				'portals' => $chunk === null ? [] : self::scanChunk($chunk, $chunkX, $chunkZ, max($world->getMinY(), Dimensions::getMinY($dimension)), min($world->getMaxY(), Dimensions::getMaxY($dimension)))
			];
		}
		return $this->cache[$hash]['portals'];
	}

	/** @return list<Vector3> */
	public static function scanChunk(Chunk $chunk, int $chunkX, int $chunkZ, int $minY, int $maxY) : array{
		$ids = [VanillaBlocks::NETHER_PORTAL()->setAxis(Axis::X)->getStateId(), VanillaBlocks::NETHER_PORTAL()->setAxis(Axis::Z)->getStateId()];
		$portals = [];
		for($subY = $minY >> 4; $subY <= ($maxY - 1) >> 4; ++$subY){
			$subChunk = $chunk->getSubChunk($subY);
			$layers = $subChunk->getBlockLayers();
			$hasPortal = false;
			foreach(isset($layers[0]) ? $layers[0]->getPalette() : [] as $stateId){
				if(in_array($stateId, $ids, true)){ $hasPortal = true; break; }
			}
			if(!$hasPortal){ continue; }
			for($x = 0; $x < 16; ++$x){
				for($z = 0; $z < 16; ++$z){
					for($y = 0; $y < 16; ++$y){
						$worldY = ($subY << 4) + $y;
						if($worldY < $minY || $worldY >= $maxY || !in_array($subChunk->getBlockStateId($x, $y, $z), $ids, true)){ continue; }
						if($worldY > $minY && in_array($chunk->getBlockStateId($x, $worldY - 1, $z), $ids, true)){ continue; }
						$portals[] = new Vector3(($chunkX << 4) + $x, $worldY, ($chunkZ << 4) + $z);
					}
				}
			}
		}
		return $portals;
	}

	public function onBlockChanged(Vector3 $block) : void{
		++$this->revision;
		unset($this->cache[World::chunkHash($block->getFloorX() >> 4, $block->getFloorZ() >> 4)]);
	}
	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void{ ++$this->revision; unset($this->cache[World::chunkHash($chunkX, $chunkZ)]); }
	public function onChunkLoaded(int $chunkX, int $chunkZ, Chunk $chunk) : void{ $this->onChunkChanged($chunkX, $chunkZ, $chunk); }
	public function onChunkPopulated(int $chunkX, int $chunkZ, Chunk $chunk) : void{ $this->onChunkChanged($chunkX, $chunkZ, $chunk); }
	public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk) : void{
		++$this->revision;
		$hash = World::chunkHash($chunkX, $chunkZ);
		unset($this->cache[$hash], $this->listening[$hash]);
		$this->world->get()?->unregisterChunkListener($this, $chunkX, $chunkZ);
	}

	private function close() : void{
		$world = $this->world->get();
		if($world !== null){
			foreach($this->listening as [$x, $z]){ $world->unregisterChunkListener($this, $x, $z); }
			if(self::$instances !== null){ unset(self::$instances[$world]); }
		}
		$this->listening = $this->cache = [];
	}
}
