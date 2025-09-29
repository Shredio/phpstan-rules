<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use LogicException;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Shredio\PhpStanHelpers\Exception\EmptyTypeException;
use Shredio\PhpStanHelpers\Exception\InvalidTypeException;
use Shredio\PhpStanHelpers\Exception\NonConstantTypeException;
use Shredio\PhpStanHelpers\PhpStanReflectionHelper;

/**
 * @phpstan-type OptionsType array{ target: ?ClassReflection, pick: array<string, bool>|null, values: array<non-empty-string, Type>, recursive: bool }
 */
final readonly class ObjectVarsMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
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

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === $this->methodName;
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope,
	): ?Type
	{
		$declaringClassName = $methodReflection->getDeclaringClass()->getName();
		if ($declaringClassName !== $this->className) {
			return null; // method is overridden in a child class, use type-hint from there
		}

		// options
		$optionsArg = $methodCall->getArgs()[0] ?? null;
		$optionsType = $optionsArg === null ? null : $scope->getType($optionsArg->value);

		$calledOnType = $scope->getType($methodCall->var);
		$classReflections = $calledOnType->getObjectClassReflections();
		if (count($classReflections) !== 1) {
			throw new LogicException('Only one class reflection is supported.');
		}

		$classReflection = $calledOnType->getObjectClassReflections()[0];

		return $this->createType($optionsType, $classReflection);
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $this->isMethodSupported($methodReflection);
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		Scope $scope,
	): ?Type
	{
		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			return null;
		}
		if ($methodReflection->getDeclaringClass()->getName() !== $this->className) { // covers parent:: call
			return null;
		}

		$optionsArg = $methodCall->getArgs()[0] ?? null;
		$optionsType = $optionsArg === null ? null : $scope->getType($optionsArg->value);

		return $this->createType($optionsType, $classReflection);
	}

	/**
	 * @param ClassReflection $classReflection
	 * @param OptionsType $options
	 * @return Type
	 */
	private function _createType(ClassReflection $classReflection, array $options): Type
	{
		$values = $options['values'];
		$options['values'] = [];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->reflectionHelper->getReadablePropertiesFromReflection($classReflection, $options['pick']) as $propertyName => $reflectionProperty) {
			if (isset($values[$propertyName])) {
				continue;
			}

			$readableType = $reflectionProperty->getReadableType();
			if ($options['recursive'] && (new ObjectType($this->className))->isSuperTypeOf($readableType)->yes()) {
				$reflections = $readableType->getObjectClassReflections();
				if (count($reflections) === 1) {
					$readableType = $this->_createType($reflections[0], $options);
				}
			}
			$builder->setOffsetValueType(new ConstantStringType($propertyName), $readableType);
		}

		foreach ($values as $key => $type) {
			$builder->setOffsetValueType(new ConstantStringType($key), $type);
		}

		return $builder->getArray();
	}

	private function createType(?Type $optionsType, ClassReflection $classReflection): Type
	{
		$options = $this->buildOptionsFromType($optionsType);
		if ($options === null) {
			return new NeverType(); // invalid options
		}

		return $this->_createType($classReflection, $options);
	}

	/**
	 * @return OptionsType|null
	 */
	private function buildOptionsFromType(?Type $optionsType): ?array
	{
		try {
			if ($optionsType === null) {
				$options = [];
			} else {
				$options = $this->reflectionHelper->getNonEmptyStringKeyWithTypeFromConstantArray($optionsType);
			}
		} catch (InvalidTypeException|NonConstantTypeException) {
			return null; // not a constant array
		}

		// options.target
		try {
			if (!isset($options['target'])) {
				$targetReflection = null;
			} else {
				$targetReflection = $this->reflectionHelper->getClassReflectionFromClassString($options['target']);
			}
		} catch (InvalidTypeException|EmptyTypeException) {
			return null;
		}

		if ($targetReflection !== null) {
			$pick = [];
			if ($targetReflection->hasConstructor()) {
				foreach ($this->reflectionHelper->getParametersFromMethod($targetReflection->getConstructor()) as $name => $_) {
					$pick[$name] = true;
				}
			}
		} else {
			$pick = null;
		}

		// options.values
		try {
			if (!isset($options['values'])) {
				$values = [];
			} else {
				$values = $this->reflectionHelper->getNonEmptyStringKeyWithTypeFromConstantArray($options['values']);
			}
		} catch (InvalidTypeException|NonConstantTypeException) {
			return null; // not a constant array
		}

		// options.recursive
		try {
			if (!isset($options['recursive'])) {
				$recursive = false;
			} else {
				$recursive = $this->reflectionHelper->getTrueOrFalseFromConstantBoolean($options['recursive']);
			}
		} catch (InvalidTypeException|NonConstantTypeException) {
			return null; // not a constant boolean
		}

		return [
			'target' => $targetReflection,
			'pick' => $pick,
			'values' => $values,
			'recursive' => $recursive,
		];
	}

}
