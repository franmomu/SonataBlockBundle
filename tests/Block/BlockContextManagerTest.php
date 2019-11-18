<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block;

use Doctrine\Common\Util\ClassUtils;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\BlockContextManager;
use Sonata\BlockBundle\Block\BlockLoaderInterface;
use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;

final class BlockContextManagerTest extends TestCase
{
    public function testGetWithValidData(): void
    {
        $service = $this->createMock(AbstractBlockService::class);

        $service->expects($this->once())->method('configureSettings');

        $blockLoader = $this->createMock(BlockLoaderInterface::class);

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())->method('getSettings')->willReturn([]);

        $manager = new BlockContextManager($blockLoader, $serviceManager);

        $blockContext = $manager->get($block);

        $this->assertInstanceOf(BlockContextInterface::class, $blockContext);

        $this->assertSame([
            'use_cache' => true,
            'extra_cache_keys' => [],
            'attr' => [],
            'template' => false,
            'ttl' => 0,
        ], $blockContext->getSettings());
    }

    public function testGetWithSettings(): void
    {
        $service = $this->createMock(AbstractBlockService::class);
        $service->expects($this->once())->method('configureSettings');

        $blockLoader = $this->createMock(BlockLoaderInterface::class);

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())->method('getSettings')->willReturn([]);

        $blocksCache = [
            'by_class' => [ClassUtils::getClass($block) => 'my_cache.service.id'],
        ];

        $manager = new BlockContextManager($blockLoader, $serviceManager, $blocksCache);

        $settings = ['ttl' => 1, 'template' => 'custom.html.twig'];

        $blockContext = $manager->get($block, $settings);

        $this->assertInstanceOf(BlockContextInterface::class, $blockContext);

        $this->assertSame([
            'use_cache' => true,
            'extra_cache_keys' => [
                BlockContextManager::CACHE_KEY => [
                    'template' => 'custom.html.twig',
                ],
            ],
            'attr' => [],
            'template' => 'custom.html.twig',
            'ttl' => 1,
        ], $blockContext->getSettings());
    }

    public function testWithInvalidSettings(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $service = $this->createMock(AbstractBlockService::class);
        $service->expects($this->exactly(2))->method('configureSettings');

        $blockLoader = $this->createMock(BlockLoaderInterface::class);

        $serviceManager = $this->createMock(BlockServiceManagerInterface::class);
        $serviceManager->expects($this->exactly(2))->method('get')->willReturn($service);

        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->once())->method('getSettings')->willReturn([
            'template' => [],
        ]);

        $manager = new BlockContextManager($blockLoader, $serviceManager, [], $logger);

        $blockContext = $manager->get($block);

        $this->assertInstanceOf(BlockContextInterface::class, $blockContext);
    }
}
