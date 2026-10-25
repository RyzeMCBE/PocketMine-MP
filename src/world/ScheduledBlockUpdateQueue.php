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

use pocketmine\math\Vector3;
use pocketmine\utils\ReversePriorityQueue;

/** @internal One current deadline per block; superseded heap entries are discarded lazily. */
final class ScheduledBlockUpdateQueue{

	/** @var ReversePriorityQueue<int, array{int, int, Vector3}> */
	private ReversePriorityQueue $queue;
	/** @var array<int, array{int, int}> block hash => [deadline, identity] */
	private array $scheduled = [];
	private int $sequence = 0;

	public function __construct(){
		$this->queue = new ReversePriorityQueue();
		$this->queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
	}

	public function schedule(Vector3 $position, int $deadline) : void{
		$hash = World::blockHash((int) $position->x, (int) $position->y, (int) $position->z);
		if(isset($this->scheduled[$hash]) && $this->scheduled[$hash][0] <= $deadline){
			return;
		}
		$identity = ++$this->sequence;
		$this->scheduled[$hash] = [$deadline, $identity];
		$this->queue->insert([$hash, $identity, new Vector3((int) $position->x, (int) $position->y, (int) $position->z)], $deadline);
	}

	public function fetchReady(int $currentTick) : ?Vector3{
		while(!$this->queue->isEmpty()){
			/** @var array{priority: int, data: array{int, int, Vector3}} $entry */
			$entry = $this->queue->current();
			if($entry['priority'] > $currentTick){ return null; }
			/** @var array{priority: int, data: array{int, int, Vector3}} $entry */
			$entry = $this->queue->extract();
			[$hash, $identity, $position] = $entry['data'];
			if(($this->scheduled[$hash][1] ?? null) !== $identity){
				continue;
			}
			unset($this->scheduled[$hash]);
			return $position;
		}
		return null;
	}
}
