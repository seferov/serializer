<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\Component\Serializer\Tests;

use Seferov\Component\Serializer\Serializer;
use Seferov\Component\Serializer\Encoder\JsonEncoder;
use Seferov\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Seferov\Component\Serializer\Normalizer\CustomNormalizer;
use Seferov\Component\Serializer\Tests\Fixtures\TraversableDummy;
use Seferov\Component\Serializer\Tests\Fixtures\NormalizableTraversableDummy;
use Seferov\Component\Serializer\Tests\Normalizer\TestNormalizer;
use Seferov\Component\Serializer\Tests\Normalizer\TestDenormalizer;

class SerializerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $serializer = new Serializer();

        $this->assertInstanceOf('Seferov\Component\Serializer\SerializerInterface', $serializer);
        $this->assertInstanceOf('Seferov\Component\Serializer\Normalizer\NormalizerInterface', $serializer);
        $this->assertInstanceOf('Seferov\Component\Serializer\Normalizer\DenormalizerInterface', $serializer);
        $this->assertInstanceOf('Seferov\Component\Serializer\Encoder\EncoderInterface', $serializer);
        $this->assertInstanceOf('Seferov\Component\Serializer\Encoder\DecoderInterface', $serializer);
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testNormalizeNoMatch()
    {
        $serializer = new Serializer(array($this->getMock('Seferov\Component\Serializer\Normalizer\CustomNormalizer')));
        $serializer->normalize(new \stdClass(), 'xml');
    }

    public function testNormalizeTraversable()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $result = $serializer->serialize(new TraversableDummy(), 'json');
        $this->assertEquals('{"foo":"foo","bar":"bar"}', $result);
    }

    public function testNormalizeGivesPriorityToInterfaceOverTraversable()
    {
        $serializer = new Serializer(array(new CustomNormalizer()), array('json' => new JsonEncoder()));
        $result = $serializer->serialize(new NormalizableTraversableDummy(), 'json');
        $this->assertEquals('{"foo":"normalizedFoo","bar":"normalizedBar"}', $result);
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testNormalizeOnDenormalizer()
    {
        $serializer = new Serializer(array(new TestDenormalizer()), array());
        $this->assertTrue($serializer->normalize(new \stdClass(), 'json'));
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDenormalizeNoMatch()
    {
        $serializer = new Serializer(array($this->getMock('Seferov\Component\Serializer\Normalizer\CustomNormalizer')));
        $serializer->denormalize('foo', 'stdClass');
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDenormalizeOnNormalizer()
    {
        $serializer = new Serializer(array(new TestNormalizer()), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $this->assertTrue($serializer->denormalize(json_encode($data), 'stdClass', 'json'));
    }

    public function testCustomNormalizerCanNormalizeCollectionsAndScalar()
    {
        $serializer = new Serializer(array(new TestNormalizer()), array());
        $this->assertNull($serializer->normalize(array('a', 'b')));
        $this->assertNull($serializer->normalize(new \ArrayObject(array('c', 'd'))));
        $this->assertNull($serializer->normalize(array()));
        $this->assertNull($serializer->normalize('test'));
    }

    public function testNormalizeWithSupportOnData()
    {
        $normalizer1 = $this->getMock('Seferov\Component\Serializer\Normalizer\NormalizerInterface');
        $normalizer1->method('supportsNormalization')
            ->willReturnCallback(function ($data, $format) {
                return isset($data->test);
            });
        $normalizer1->method('normalize')->willReturn('test1');

        $normalizer2 = $this->getMock('Seferov\Component\Serializer\Normalizer\NormalizerInterface');
        $normalizer2->method('supportsNormalization')
            ->willReturn(true);
        $normalizer2->method('normalize')->willReturn('test2');

        $serializer = new Serializer(array($normalizer1, $normalizer2));

        $data = new \stdClass();
        $data->test = true;
        $this->assertEquals('test1', $serializer->normalize($data));

        $this->assertEquals('test2', $serializer->normalize(new \stdClass()));
    }

    public function testDenormalizeWithSupportOnData()
    {
        $denormalizer1 = $this->getMock('Seferov\Component\Serializer\Normalizer\DenormalizerInterface');
        $denormalizer1->method('supportsDenormalization')
            ->willReturnCallback(function ($data, $type, $format) {
                return isset($data['test1']);
            });
        $denormalizer1->method('denormalize')->willReturn('test1');

        $denormalizer2 = $this->getMock('Seferov\Component\Serializer\Normalizer\DenormalizerInterface');
        $denormalizer2->method('supportsDenormalization')
            ->willReturn(true);
        $denormalizer2->method('denormalize')->willReturn('test2');

        $serializer = new Serializer(array($denormalizer1, $denormalizer2));

        $this->assertEquals('test1', $serializer->denormalize(array('test1' => true), 'test'));

        $this->assertEquals('test2', $serializer->denormalize(array(), 'test'));
    }

    public function testSerialize()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $result = $serializer->serialize(Model::fromArray($data), 'json');
        $this->assertEquals(json_encode($data), $result);
    }

    public function testSerializeScalar()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $result = $serializer->serialize('foo', 'json');
        $this->assertEquals('"foo"', $result);
    }

    public function testSerializeArrayOfScalars()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $data = array('foo', array(5, 3));
        $result = $serializer->serialize($data, 'json');
        $this->assertEquals(json_encode($data), $result);
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testSerializeNoEncoder()
    {
        $serializer = new Serializer(array(), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->serialize($data, 'json');
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\LogicException
     */
    public function testSerializeNoNormalizer()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->serialize(Model::fromArray($data), 'json');
    }

    public function testDeserialize()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $result = $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
        $this->assertEquals($data, $result->toArray());
    }

    public function testDeserializeUseCache()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
        $data = array('title' => 'bar', 'numbers' => array(2, 8));
        $result = $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
        $this->assertEquals($data, $result->toArray());
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\LogicException
     */
    public function testDeserializeNoNormalizer()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDeserializeWrongNormalizer()
    {
        $serializer = new Serializer(array(new CustomNormalizer()), array('json' => new JsonEncoder()));
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
    }

    /**
     * @expectedException \Seferov\Component\Serializer\Exception\UnexpectedValueException
     */
    public function testDeserializeNoEncoder()
    {
        $serializer = new Serializer(array(), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $serializer->deserialize(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json');
    }

    public function testDeserializeSupported()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $this->assertTrue($serializer->supportsDenormalization(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json'));
    }

    public function testDeserializeNotSupported()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $this->assertFalse($serializer->supportsDenormalization(json_encode($data), 'stdClass', 'json'));
    }

    public function testDeserializeNotSupportedMissing()
    {
        $serializer = new Serializer(array(), array());
        $data = array('title' => 'foo', 'numbers' => array(5, 3));
        $this->assertFalse($serializer->supportsDenormalization(json_encode($data), '\Seferov\Component\Serializer\Tests\Model', 'json'));
    }

    public function testEncode()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $data = array('foo', array(5, 3));
        $result = $serializer->encode($data, 'json');
        $this->assertEquals(json_encode($data), $result);
    }

    public function testDecode()
    {
        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));
        $data = array('foo', array(5, 3));
        $result = $serializer->decode(json_encode($data), 'json');
        $this->assertEquals($data, $result);
    }
}

class Model
{
    private $title;
    private $numbers;

    public static function fromArray($array)
    {
        $model = new self();
        if (isset($array['title'])) {
            $model->setTitle($array['title']);
        }
        if (isset($array['numbers'])) {
            $model->setNumbers($array['numbers']);
        }

        return $model;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getNumbers()
    {
        return $this->numbers;
    }

    public function setNumbers($numbers)
    {
        $this->numbers = $numbers;
    }

    public function toArray()
    {
        return array('title' => $this->title, 'numbers' => $this->numbers);
    }
}
