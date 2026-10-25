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

use function array_sum;
use function ceil;
use function count;
use function max;
use function sort;

/** @internal Bounded measurements; percentile sorting happens only when diagnostics are requested. */
final class RollingTimings{

	/** @var array<int, float> */
	private array $samples = [];
	private int $next = 0;

	public function __construct(private int $capacity = 1200){
		if($capacity < 1){ throw new \InvalidArgumentException('Sample capacity must be positive'); }
	}

	public function add(float $milliseconds) : void{
		$this->samples[$this->next] = max(0.0, $milliseconds);
		$this->next = ($this->next + 1) % $this->capacity;
	}

	/** @return array{count: int, mean: float, p95: float, p99: float, max: float} */
	public function summarize() : array{
		$values = $this->samples;
		sort($values);
		$count = count($values);
		return [
			'count' => $count,
			'mean' => $count > 0 ? array_sum($values) / $count : 0.0,
			'p95' => $count > 0 ? $values[(int) ceil($count * 0.95) - 1] : 0.0,
			'p99' => $count > 0 ? $values[(int) ceil($count * 0.99) - 1] : 0.0,
			'max' => $count > 0 ? $values[$count - 1] : 0.0
		];
	}
}
