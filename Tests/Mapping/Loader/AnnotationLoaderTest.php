<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\Component\Serializer\Tests\Mapping\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Seferov\Component\Serializer\Mapping\ClassMetadata;
use Seferov\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Seferov\Component\Serializer\Tests\Mapping\TestClassMetadataFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AnnotationLoader
     */
    private $loader;

    protected function setUp()
    {
        $this->loader = new AnnotationLoader(new AnnotationReader());
    }

    public function testInterface()
    {
        $this->assertInstanceOf('Seferov\Component\Serializer\Mapping\Loader\LoaderInterface', $this->loader);
    }

    public function testLoadClassMetadataReturnsTrueIfSuccessful()
    {
        $classMetadata = new ClassMetadata('Seferov\Component\Serializer\Tests\Fixtures\GroupDummy');

        $this->assertTrue($this->loader->loadClassMetadata($classMetadata));
    }

    public function testLoadClassMetadata()
    {
        $classMetadata = new ClassMetadata('Seferov\Component\Serializer\Tests\Fixtures\GroupDummy');
        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(), $classMetadata);
    }

    public function testLoadClassMetadataAndMerge()
    {
        $classMetadata = new ClassMetadata('Seferov\Component\Serializer\Tests\Fixtures\GroupDummy');
        $parentClassMetadata = new ClassMetadata('Seferov\Component\Serializer\Tests\Fixtures\GroupDummyParent');

        $this->loader->loadClassMetadata($parentClassMetadata);
        $classMetadata->merge($parentClassMetadata);

        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(true), $classMetadata);
    }
}
