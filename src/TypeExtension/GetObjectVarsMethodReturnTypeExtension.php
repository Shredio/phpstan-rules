<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final readonly class GetObjectVarsMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	/**
	 * @param class-string $className
	 */
	public function __construct(
		private string $className,
		private string $methodName,
	)
	{
	}

	public function getClass(): string
	{
		return $this->className;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === $this->methodName;
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		Scope $scope,
	): ?Type
	{
		$objectArg = $methodCall->getArgs()[0] ?? null;
		if ($objectArg === null) {
			return null;
		}

		$objectType = $scope->getType($objectArg->value);
		if (!$objectType->isObject()->yes()) {
			return null;
		}

		$reflectionClass = $objectType->getObjectClassReflections()[0] ?? null;
		if ($reflectionClass === null) {
			return null;
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($reflectionClass->getNativeReflection()->getProperties() as $property) {
			if (!$property->isPublic() || $property->isStatic()) {
				continue;
			}

			$builder->setOffsetValueType(new ConstantStringType($property->name), $reflectionClass->getProperty($property->name, $scope)->getReadableType());
		}

		return $builder->getArray();
	}

}
