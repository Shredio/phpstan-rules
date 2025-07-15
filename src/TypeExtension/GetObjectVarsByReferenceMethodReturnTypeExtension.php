<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use LogicException;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Shredio\PhpstanRules\Helper\PhpStanReflectionHelper;

final readonly class GetObjectVarsByReferenceMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	/**
	 * @param class-string $className
	 */
	public function __construct(
		private string $className,
		private string $methodName,
		private PhpStanReflectionHelper $reflectionHelper,
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
		$referenceArg = $methodCall->getArgs()[1] ?? null;
		$valuesArg = $methodCall->getArgs()[2] ?? null;
		if ($objectArg === null || $referenceArg === null) {
			return null;
		}

		$objectType = $scope->getType($objectArg->value);
		$referenceType = $scope->getType($referenceArg->value);
		$valuesType = $valuesArg === null ? null : $scope->getType($valuesArg->value);
		if (!$objectType->isObject()->yes() || !$referenceType->isClassString()->yes()) {
			return null;
		}

		if ($valuesType !== null && !$valuesType->isConstantArray()->yes()) {
			return null;
		}

		$reflectionClass = $this->getReflectionClassFromType($objectType);
		if ($reflectionClass === null) {
			return null;
		}
		$reflectionReferenceClass = $this->getReflectionClassFromType($referenceType->getClassStringObjectType());
		if ($reflectionReferenceClass === null) {
			return null;
		}

		$constructor = $reflectionReferenceClass->getNativeReflection()->getConstructor();
		if ($constructor === null || !$constructor->isPublic()) {
			return ConstantArrayTypeBuilder::createEmpty()->getArray();
		}

		$parameters = [];
		foreach ($constructor->getParameters() as $parameter) {
			$parameters[$parameter->getName()] = true;
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->reflectionHelper->getTypeOfReadablePropertiesFromReflection($reflectionClass) as $propertyName => $propertyType) {
			if (!isset($parameters[$propertyName])) {
				continue;
			}

			$builder->setOffsetValueType(new ConstantStringType($propertyName), $propertyType);
		}

		$arrayToReturn = $builder->getArray();

		if (!$arrayToReturn->isConstantArray()->yes()) {
			return new NeverType();
		}

		$constantArrays = $arrayToReturn->getConstantArrays();
		if (count($constantArrays) !== 1) {
			return new NeverType();
		}

		$arrayToReturn = $constantArrays[0];
		if ($valuesType !== null) {
			$arrayToReturn = $this->addConstantArrayValuesToBuilder($arrayToReturn, $valuesType);
		}

		return $arrayToReturn;
	}

	private function getReflectionClassFromType(Type $type): ?ClassReflection
	{
		return $type->getObjectClassReflections()[0] ?? null;
	}

	private function addConstantArrayValuesToBuilder(ConstantArrayType $arrayToReturn, Type $type): Type
	{
		$types = [];

		foreach ($type->getConstantArrays() as $constantArray) {
			$builder = ConstantArrayTypeBuilder::createFromConstantArray($arrayToReturn);
			$keyTypes = $constantArray->getKeyTypes();
			$valueTypes = $constantArray->getValueTypes();
			$optionalKeys = $constantArray->getOptionalKeys();

			foreach ($keyTypes as $i => $keyType) {
				$valueType = $valueTypes[$i] ?? throw new LogicException(sprintf(
					'Key "%s" does not have a corresponding value type in constant array.',
					$keyType->getValue(),
				));
				$isOptional = in_array($i, $optionalKeys, true);

				$builder->setOffsetValueType($keyType, $valueType, $isOptional);
			}

			$types[] = $builder->getArray();
		}

		if (count($types) === 0) {
			return $arrayToReturn;
		}

		if (count($types) === 1) {
			return $types[0];
		}

		return TypeCombinator::union(...$types);
	}

}
