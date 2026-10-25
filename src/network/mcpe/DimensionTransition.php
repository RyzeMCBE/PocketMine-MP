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

namespace pocketmine\network\mcpe;

use function count;

/** Per-session transition observations. A transport receipt is not a rendering acknowledgement. */
final class DimensionTransition{

	/** @var array<int, true> */
	private array $receivedChunks = [];
	private ?float $serverAckAt = null;
	private ?float $clientAckAt = null;
	private ?float $screenStartedAt = null;
	private ?float $screenStoppedAt = null;
	private ?string $result = null;

	/** @param array<int, true> $arrivalChunks */
	public function __construct(
		private int $id,
		private ?int $loadingScreenId,
		private ?string $sourceWorld,
		private string $targetWorld,
		private int $dimension,
		private float $startedAt,
		private array $arrivalChunks,
		private bool $diagnostics
	){}

	public function getId() : int{ return $this->id; }
	public function getLoadingScreenId() : ?int{ return $this->loadingScreenId; }
	public function getSourceWorld() : ?string{ return $this->sourceWorld; }
	public function getTargetWorld() : string{ return $this->targetWorld; }
	public function getDimension() : int{ return $this->dimension; }
	public function getStartedAt() : float{ return $this->startedAt; }
	public function getServerAckAt() : ?float{ return $this->serverAckAt; }
	public function getClientAckAt() : ?float{ return $this->clientAckAt; }
	public function getScreenStartedAt() : ?float{ return $this->screenStartedAt; }
	public function getScreenStoppedAt() : ?float{ return $this->screenStoppedAt; }
	public function getResult() : ?string{ return $this->result; }
	public function hasDiagnostics() : bool{ return $this->diagnostics; }
	public function getReceivedChunkCount() : int{ return count($this->receivedChunks); }
	public function containsChunk(int $hash) : bool{ return isset($this->arrivalChunks[$hash]); }

	/** @internal */
	public function recordReceipt(int $hash) : void{
		if($this->containsChunk($hash)){
			$this->receivedChunks[$hash] = true;
		}
	}

	/** @internal */
	public function recordServerAck(float $now) : void{ $this->serverAckAt ??= $now; }
	/** @internal Untagged ACKs are observations, not proof of which screen was rendered. */
	public function recordClientAck(float $now) : void{ $this->clientAckAt ??= $now; }

	/** @internal Only matching tagged notifications may complete a modern transition. */
	public function recordScreen(bool $started, ?int $screenId, float $now) : bool{
		if($this->loadingScreenId === null || $screenId !== $this->loadingScreenId){
			return false;
		}
		if($started){
			$this->screenStartedAt ??= $now;
		}else{
			$this->screenStoppedAt ??= $now;
		}
		return true;
	}

	/** @internal */
	public function finish(string $result) : void{ $this->result = $result; }
}
