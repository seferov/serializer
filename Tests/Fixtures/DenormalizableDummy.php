<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\Component\Serializer\Tests\Fixtures;

use Seferov\Component\Serializer\Normalizer\DenormalizableInterface;
use Seferov\Component\Serializer\Normalizer\DenormalizerInterface;

class DenormalizableDummy implements DenormalizableInterface
{
    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = array())
    {
    }
}
