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

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\network\mcpe\cache\CraftingDataCache;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\handler\SessionStartPacketHandler;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\timings\Timings;
use function count;
use function defined;
use function file_get_contents;
use function str_repeat;

final class Bedrock12652CompatibilityTest extends TestCase{
	public function testCraftingAndBrewingCacheSurvivesTheLoginPacketRoundTrip() : void{
		Timings::init();
		$manager = CraftingManagerFromDataHelper::make(BedrockDataFiles::RECIPES);
		$packet = CraftingDataCache::getInstance()->getCache($manager);
		self::assertNotEmpty($packet->recipesWithTypeIds);
		self::assertNotEmpty($packet->potionTypeRecipes);
		self::assertNotEmpty($packet->potionContainerRecipes);
		self::assertCount(count($manager->getPotionTypeRecipes()), $packet->potionTypeRecipes);
		self::assertCount(count($manager->getPotionContainerChangeRecipes()), $packet->potionContainerRecipes);
		$out = new ByteBufferWriter();
		$packet->encode($out);
		$decoded = new CraftingDataPacket();
		$reader = new ByteBufferReader($out->getData());
		$decoded->decode($reader);
		self::assertSame(0, $reader->getUnreadLength());
		self::assertCount(count($packet->recipesWithTypeIds), $decoded->recipesWithTypeIds);
		self::assertEquals($packet->potionTypeRecipes, $decoded->potionTypeRecipes);
		self::assertEquals($packet->potionContainerRecipes, $decoded->potionContainerRecipes);
	}

	public function testOriginalSingleProtocolEntryPointsArePreserved() : void{
		self::assertSame(2193, (new \ReflectionClass(ProtocolInfo::class))->getConstant('CURRENT_PROTOCOL'));
		self::assertSame('1.26.52', (new \ReflectionClass(ProtocolInfo::class))->getConstant('MINECRAFT_VERSION_NETWORK'));
		self::assertFalse(defined(ProtocolInfo::class . '::ACCEPTED_PROTOCOL'));
		foreach(['encode', 'decode'] as $name){
			self::assertSame(1, (new \ReflectionMethod(Packet::class, $name))->getNumberOfParameters());
		}
		$handler = (new \ReflectionClass(SessionStartPacketHandler::class))->newInstanceWithoutConstructor();
		$method = new \ReflectionMethod(SessionStartPacketHandler::class, 'isCompatibleProtocol');
		self::assertTrue($method->invoke($handler, 2193));
		self::assertFalse($method->invoke($handler, 1001));
		self::assertFalse($method->invoke($handler, 975));
	}

	public function testCompleteNetworkPaletteRetainsItsSignedHashes() : void{
		$dictionary = TypeConverter::getInstance()->getBlockTranslator()->getBlockStateDictionary();
		self::assertCount(22091, $dictionary->getStates());
		foreach($dictionary->getStates() as $hash => $entry){
			self::assertSame($hash, $dictionary->lookupStateIdFromData($entry->generateStateData()));
			self::assertEquals($entry->generateStateData(), $dictionary->generateDataFromStateId($hash));
		}
		self::assertSame(-2144268767, TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::STONE()->getStateId()));
	}

	public function testMismatchedPaletteFilesAreRejected() : void{
		$this->expectException(\InvalidArgumentException::class);
		BlockStateDictionary::loadFromString((string) file_get_contents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT), '[]', []);
	}

	public function testExistingBlockItemsCanReturnFromTheNetwork() : void{
		$converter = TypeConverter::getInstance();
		foreach([VanillaBlocks::STONE(), VanillaBlocks::CHEST(), VanillaBlocks::OAK_DOOR(), VanillaBlocks::OAK_STAIRS(), VanillaBlocks::OAK_FENCE()] as $block){
			$item = $block->asItem()->setCount(3);
			$item->setCustomName('network item');
			$network = $converter->coreItemStackToNet($item);
			$out = new ByteBufferWriter();
			CommonTypes::putItemStackWrapper($out, new ItemStackWrapper(1, $network));
			$wireItem = CommonTypes::getItemStackWrapper(new ByteBufferReader($out->getData()))->getItemStack();
			self::assertSame($network->getBlockRuntimeId(), $wireItem->getBlockRuntimeId());
			$decoded = $converter->netItemStackToCore($wireItem);
			self::assertTrue($item->equalsExact($decoded), $item->getName());
		}
	}

	public function testSkinMetadataSurvivesTheOriginalAdapterInterface() : void{
		$adapter = new LegacySkinAdapter();
		foreach([false, true] as $persona){
			foreach([64, 128, 256] as $size){
				$data = new SkinData('skin', '', '{"geometry":{"default":"geometry.humanoid.custom"}}', new SkinImage($size, $size, str_repeat("\xff", $size * $size * 4)), persona: $persona, trustedSkinFlag: 'true', profileHash: 'profile-12652');
				$skin = $adapter->fromSkinData($data);
				$converted = $adapter->toSkinData($skin);
				self::assertSame($data, $converted);
				self::assertSame('profile-12652', $converted->getProfileHash());
				self::assertSame('true', $converted->getTrustedSkinFlag());
			}
		}
	}

	public function testBiomeDefinitionsUseTheCurrentWireFormat() : void{
		$packet = StaticPacketCache::getInstance()->getBiomeDefs();
		$first = new ByteBufferWriter();
		$packet->encode($first);
		$decoded = new BiomeDefinitionListPacket();
		$reader = new ByteBufferReader($first->getData());
		$decoded->decode($reader);
		self::assertSame(0, $reader->getUnreadLength());
		self::assertNotEmpty($decoded->getDefinitionData());
		self::assertNotEmpty($decoded->buildDefinitionsFromData());
		$second = new ByteBufferWriter();
		$decoded->encode($second);
		self::assertSame($first->getData(), $second->getData());
	}
}
