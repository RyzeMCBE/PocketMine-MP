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

namespace pocketmine\network\mcpe\convert;

use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function intdiv;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function str_repeat;
use function substr;
use const JSON_THROW_ON_ERROR;

class LegacySkinAdapter implements SkinAdapter{
	/** @var \WeakMap<Skin, SkinData> */
	private \WeakMap $networkSkins;

	public function __construct(){
		$this->networkSkins = new \WeakMap();
	}

	public function toSkinData(Skin $skin) : SkinData{
		if(isset($this->networkSkins[$skin])){
			return $this->networkSkins[$skin];
		}
		$capeData = $skin->getCapeData();
		$capeImage = $capeData === "" ? new SkinImage(0, 0, "") : new SkinImage(32, 64, $capeData);
		$geometryName = $skin->getGeometryName();
		if($geometryName === ""){
			$geometryName = "geometry.humanoid.custom";
		}
		return new SkinData(
			$skin->getSkinId(),
			"", //TODO: playfab ID
			json_encode(["geometry" => ["default" => $geometryName]], JSON_THROW_ON_ERROR),
			SkinImage::fromLegacy($skin->getSkinData()), [],
			$capeImage,
			$skin->getGeometryData() !== "" ? $skin->getGeometryData() : SkinData::GEOMETRY_DATA_NONE
		);
	}

	private static function normalizeSkinImage(SkinImage $image) : string{
		$width = $image->getWidth();
		$height = $image->getHeight();
		$data = $image->getData();
		if($width !== $height || ($width !== 254 && $width !== 256)){
			return $data;
		}
		$normalized = "";
		for($y = 0; $y < 128; ++$y){
			$sourceY = intdiv($y * $height, 128);
			for($x = 0; $x < 128; ++$x){
				$sourceX = intdiv($x * $width, 128);
				$normalized .= substr($data, ($sourceY * $width + $sourceX) * 4, 4);
			}
		}
		return $normalized;
	}

	public function fromSkinData(SkinData $data) : Skin{
		if($data->isPersona()){
			try{
				$skin = new Skin("Standard_Custom", self::normalizeSkinImage($data->getSkinImage()), "", "geometry.humanoid.custom", SkinData::GEOMETRY_DATA_NONE);
			}catch(InvalidSkinException){
				$skin = new Skin("Standard_Custom", str_repeat("\x7f\x7f\x7f\xff", 64 * 64), "", "geometry.humanoid.custom", SkinData::GEOMETRY_DATA_NONE);
			}
			$this->networkSkins[$skin] = $data;
			return $skin;
		}

		$capeData = $data->isPersonaCapeOnClassic() ? "" : $data->getCapeImage()->getData();

		$resourcePatch = json_decode($data->getResourcePatch(), true);
		if(is_array($resourcePatch) && isset($resourcePatch["geometry"]["default"]) && is_string($resourcePatch["geometry"]["default"])){
			$geometryName = $resourcePatch["geometry"]["default"];
		}else{
			throw new InvalidSkinException("Missing geometry name field");
		}

		$skin = new Skin($data->getSkinId(), self::normalizeSkinImage($data->getSkinImage()), $capeData, $geometryName, $data->getGeometryData());
		$this->networkSkins[$skin] = $data;
		return $skin;
	}
}
