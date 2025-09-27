<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\Helper;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;

final readonly class PhpStanReflectionHelper
{

	/**
	 * @return iterable<int, ParameterReflection>
	 */
	public function getParametersFromMethod(ExtendedMethodReflection $methodReflection): iterable
	{
		foreach ($methodReflection->getVariants() as $variant) {
			foreach ($variant->getParameters() as $parameter) {
				yield $parameter;
			}
		}
	}

	/**
	 * @return array<non-empty-string, Type>
	 */
	public function getStringKeyArrayOfTypesFromType(Type $type): array
	{
		if (!$type->isArray()->yes()) {
			return [];
		}

		$values = [];
		foreach ($type->getConstantArrays() as $constantArray) {
			$valueTypes = $constantArray->getValueTypes();
			foreach ($constantArray->getKeyTypes() as $i => $keyType) {
				$key = $keyType->getValue();
				if (!is_string($key) || $key === '') {
					continue;
				}
				if (!isset($valueTypes[$i])) {
					continue;
				}

				$values[$key] = $valueTypes[$i];
			}
		}
		return $values;
	}

	/**
	 * @param array<string, bool>|null $pick
	 * @return iterable<string, Type>
	 */
	public function getTypeOfReadablePropertiesFromReflection(ClassReflection $reflection, ?array $pick = null): iterable
	{
		foreach ($reflection->getNativeReflection()->getProperties() as $property) {
			if (!$property->isPublic() || $property->isStatic()) {
				continue;
			}
			if ($pick !== null && !isset($pick[$property->name])) {
				continue;
			}

			$type = $reflection->getProperty($property->name, new OutOfClassScope())->getReadableType();

			yield $property->name => $type;
		}
	}

}
