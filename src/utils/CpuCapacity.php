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
use function ceil;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function in_array;
use function max;
use function min;
use function preg_match;
use function str_contains;
use function trim;

/** @internal Startup-only estimate of logical CPUs available to this process, including container limits. */
final class CpuCapacity{

	/** @param Closure(string) : ?string|null $read */
	public static function detect(int $logicalCores, ?Closure $read = null) : int{
		$read ??= static function(string $path) : ?string{
			$value = @file_get_contents($path);
			return $value === false ? null : $value;
		};
		$available = max(1, $logicalCores);
		$status = $read('/proc/self/status') ?? '';
		if(preg_match('/^Cpus_allowed_list:\s*(.+)$/m', $status, $matches) === 1){
			$available = min($available, self::countCpuList(trim($matches[1])) ?? $available);
		}
		$paths = ['/sys/fs/cgroup' => true, '/sys/fs/cgroup/cpu' => true, '/sys/fs/cgroup/cpu,cpuacct' => true, '/sys/fs/cgroup/cpuset' => true];
		foreach(explode("\n", $read('/proc/self/cgroup') ?? '', 128) as $line){
			$parts = explode(':', $line, 3);
			if(count($parts) !== 3 || str_contains($parts[2], '..')){
				continue;
			}
			$roots = $parts[1] === '' ? ['/sys/fs/cgroup'] : [];
			foreach(explode(',', $parts[1], 16) as $controller){
				if(in_array($controller, ['cpu', 'cpuacct', 'cpuset'], true)){
					$roots[] = '/sys/fs/cgroup/' . $controller;
					$roots[] = '/sys/fs/cgroup/' . $parts[1];
				}
			}
			foreach($roots as $root){
				$relative = trim($parts[2], '/');
				while($relative !== '' && $relative !== '.'){
					$paths[$root . '/' . $relative] = true;
					$relative = dirname($relative);
				}
			}
		}
		foreach(Utils::stringifyKeys($paths) as $path => $_){
			$quota = $read($path . '/cpu.max');
			if($quota !== null && preg_match('/^(\d+)\s+(\d+)\s*$/D', trim($quota), $matches) === 1){
				$available = self::limitQuota($available, (int) $matches[1], (int) $matches[2]);
			}
			$v1Quota = trim($read($path . '/cpu.cfs_quota_us') ?? '');
			$v1Period = trim($read($path . '/cpu.cfs_period_us') ?? '');
			if(preg_match('/^\d+$/D', $v1Quota) === 1 && preg_match('/^\d+$/D', $v1Period) === 1){
				$available = self::limitQuota($available, (int) $v1Quota, (int) $v1Period);
			}
			foreach(['cpuset.cpus.effective', 'cpuset.effective_cpus', 'cpuset.cpus'] as $name){
				$available = min($available, self::countCpuList(trim($read($path . '/' . $name) ?? '')) ?? $available);
			}
		}
		return max(1, $available);
	}

	private static function limitQuota(int $available, int $quota, int $period) : int{
		return $quota > 0 && $period > 0 ? min($available, max(1, (int) ceil($quota / $period))) : $available;
	}

	/** Kernel CPU lists contain disjoint, ascending ranges; never allocate one element per CPU. */
	public static function countCpuList(string $list) : ?int{
		if($list === ''){ return null; }
		$count = 0;
		$previous = -1;
		foreach(explode(',', $list, 4096) as $range){
			if(preg_match('/^(\d+)(?:-(\d+))?$/D', $range, $matches) !== 1){ return null; }
			$first = (int) $matches[1];
			$last = isset($matches[2]) ? (int) $matches[2] : $first;
			if($first <= $previous || $last < $first || $last > 1048575){ return null; }
			$count += $last - $first + 1;
			$previous = $last;
		}
		return $count;
	}
}
