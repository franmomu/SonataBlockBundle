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

use PHPUnit\Framework\TestCase;
use Sonata\BlockBundle\Block\BlockLoaderChain;
use Sonata\BlockBundle\Block\BlockLoaderInterface;
use Sonata\BlockBundle\Model\BlockInterface;

final class BlockLoaderChainTest extends TestCase
{
    public function testBlockNotFoundException(): void
    {
        $this->expectException(\Sonata\BlockBundle\Exception\BlockNotFoundException::class);

        $loader = new BlockLoaderChain([]);
        $loader->load('foo');
    }

    public function testLoaderWithSupportedLoader(): void
    {
        $block = $this->createMock(BlockInterface::class);

        $loader = $this->createMock(BlockLoaderInterface::class);
        $loader->expects($this->once())->method('support')->willReturn(true);
        $loader->expects($this->once())->method('load')->willReturn($block);

        $loaderChain = new BlockLoaderChain([$loader]);

        $this->assertTrue($loaderChain->support('foo'));

        $this->assertSame($block, $loaderChain->load('foo'));
    }

    public function testLoaderWithUnSupportedLoader(): void
    {
        $this->expectException(\Sonata\BlockBundle\Exception\BlockNotFoundException::class);

        $loader = $this->createMock(BlockLoaderInterface::class);
        $loader->expects($this->once())->method('support')->willReturn(false);
        $loader->expects($this->never())->method('load');

        $loaderChain = new BlockLoaderChain([$loader]);

        $this->assertTrue($loaderChain->support('foo'));

        $loaderChain->load('foo');
    }
}
