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

namespace pocketmine\utils;

use Closure;
use Generator;
use function hrtime;

/** @internal Cooperative main-thread jobs. The budget is checked between indivisible operations. */
final class IncrementalTaskQueue{

	/** @var \SplQueue<Generator<int, Closure() : void, void, void>> */
	private \SplQueue $jobs;
	/** @var Closure() : int */
	private Closure $clock;
	private int $completedTasks = 0;

	public function getCompletedTasks() : int{ return $this->completedTasks; }

	/** @param Closure() : int|null $clock */
	public function __construct(?Closure $clock = null){
		$this->jobs = new \SplQueue();
		$this->clock = $clock ?? static fn() : int => hrtime(true);
	}

	/** @param Generator<int, Closure() : void, void, void> $job */
	public function add(Generator $job) : void{
		$this->jobs->enqueue($job);
	}

	public function hasWork() : bool{
		return !$this->jobs->isEmpty();
	}

	public function process(int $budgetNanoseconds, int $maximumTasks) : int{
		$deadline = ($this->clock)() + $budgetNanoseconds;
		$processed = 0;
		while(!$this->jobs->isEmpty() && $processed < $maximumTasks && ($processed === 0 || ($this->clock)() < $deadline)){
			$job = $this->jobs->bottom();
			if(!$job->valid()){
				$this->jobs->dequeue();
				continue;
			}
			// Advance only after success; failed writes remain available for retry.
			($job->current())();
			++$this->completedTasks;
			++$processed;
			$job->next();
			$this->jobs->dequeue();
			if($job->valid()){
				$this->jobs->enqueue($job);
			}
		}
		return $processed;
	}
}
