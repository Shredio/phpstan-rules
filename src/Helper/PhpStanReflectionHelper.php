<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\Helper;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;

final readonly class PhpStanReflectionHelper
{

	/**
	 * @return iterable<string, Type>
	 */
	public function getTypeOfReadablePropertiesFromReflection(ClassReflection $reflection): iterable
	{
		foreach ($reflection->getNativeReflection()->getProperties() as $property) {
			if (!$property->isPublic() || $property->isStatic()) {
				continue;
			}

			$type = $reflection->getProperty($property->name, new OutOfClassScope())->getReadableType();

			yield $property->name => $type;
		}
	}

}
