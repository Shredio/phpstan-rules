<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use LogicException;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Shredio\PhpstanRules\Helper\PhpStanReflectionHelper;

final readonly class ObjectVarsMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{

	/**
	 * @param class-string $className
	 */
	public function __construct(
		private string $className,
		private string $methodName,
		private PhpStanReflectionHelper $reflectionHelper,
		private ReflectionProvider $reflectionProvider,
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

	private function createType(?Type $optionsType, ClassReflection $classReflection): Type
	{
		$options = $this->getOptions($optionsType);

		// options.reference
		$target = $this->getClassStringFrom($options['target'] ?? null);
		$targetReflection = $this->getReflectionClassFrom($target);
		$pick = $this->getConstructorParameters($targetReflection);

		// options.values
		$values = $this->getTypesFromConstantArray($options['values'] ?? null) ?? [];
		if ($values === false) {
			return new NeverType();
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->reflectionHelper->getTypeOfReadablePropertiesFromReflection($classReflection, $pick) as $propertyName => $propertyType) {
			if (isset($values[$propertyName])) {
				continue;
			}

			$builder->setOffsetValueType(new ConstantStringType($propertyName), $propertyType);
		}

		foreach ($values as $key => $type) {
			$builder->setOffsetValueType(new ConstantStringType($key), $type);
		}

		return $builder->getArray();
	}

	/**
	 * @return array<string, true>|null
	 */
	private function getConstructorParameters(?ClassReflection $reflection): ?array
	{
		if ($reflection === null) {
			return null;
		}

		$constructor = $reflection->getNativeReflection()->getConstructor();
		if ($constructor === null || !$constructor->isPublic()) {
			return [];
		}

		$parameters = [];
		foreach ($constructor->getParameters() as $parameter) {
			$parameters[$parameter->getName()] = true;
		}

		return $parameters;
	}

	/**
	 * @param class-string|null $className
	 */
	private function getReflectionClassFrom(?string $className): ?ClassReflection
	{
		if ($className === null) {
			return null;
		}
		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		return $this->reflectionProvider->getClass($className);
	}

	/**
	 * @return class-string|null
	 */
	private function getClassStringFrom(?Type $type): ?string
	{
		if ($type === null || !$type->isClassString()->yes()) {
			return null;
		}

		foreach ($type->getConstantStrings() as $constantString) {
			$value = $constantString->getValue();
			if ($value !== '') {
				/** @var class-string */
				return $value;
			}
		}

		return null;
	}

	/**
	 * @param Type|null $optionsType
	 * @return array<non-empty-string, Type>
	 */
	private function getOptions(?Type $optionsType): array
	{
		if ($optionsType === null) {
			return [];
		}

		return $this->reflectionHelper->getStringKeyArrayOfTypesFromType($optionsType);
	}

	/**
	 * @param Type|null $type
	 * @return array<non-empty-string, Type>|false|null
	 */
	private function getTypesFromConstantArray(?Type $type): array|false|null
	{
		if ($type === null) {
			return null;
		}
		if (!$type->isConstantArray()->yes()) {
			return false;
		}

		$types = [];
		$unionTypes = [];
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

				if (isset($types[$key])) {
					if (!is_array($types[$key])) {
						$types[$key] = [$types[$key]];
						$unionTypes[] = $key;
					}

					$types[$key][] = $valueTypes[$i];
				} else {
					$types[$key] = $valueTypes[$i];
				}
			}
		}

		foreach ($unionTypes as $key) {
			$types[$key] = TypeCombinator::union(...$types[$key]); // @phpstan-ignore argument.unpackNonIterable
		}

		return $types; // @phpstan-ignore return.type
	}

}
