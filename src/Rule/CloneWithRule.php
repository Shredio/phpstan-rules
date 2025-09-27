<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\Rule;

use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use Shredio\PhpstanRules\Helper\PhpStanReflectionHelper;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final readonly class CloneWithRule implements Rule
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

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$args = $node->getArgs();
		if (!isset($args[0])) {
			return [];
		}

		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$methodName = $node->name->toString();
		if ($methodName !== $this->methodName) {
			return [];
		}

		$type = $scope->getType($node->var);
		if (!$type->isObject()->yes()) {
			return [];
		}

		$classReflections = $type->getObjectClassReflections();
		if (count($classReflections) !== 1) {
			throw new LogicException('Multiple class reflections found, cannot determine the exact class.');
		}

		$classReflection = $classReflections[0];
		if (!$classReflection->isSubclassOfClass($this->reflectionProvider->getClass($this->className))) {
			return [];
		}

		$arrayType = $scope->getType($args[0]->value);
		if (!$arrayType->isConstantArray()->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'First argument of %s::%s() must be a constant array, but got %s.',
					$classReflection->getName(),
					$this->methodName,
					$arrayType->describe(VerbosityLevel::precise()),
				))
					->identifier('shredio.cloneWith.argumentType')
					->build(),
			];
		}

		if (!$classReflection->hasConstructor()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'The class %s does not have a constructor, so it cannot be used with %s::%s().',
					$classReflection->getName(),
					$classReflection->getName(),
					$this->methodName,
				))
					->identifier('shredio.cloneWith.missingConstructor')
					->build(),
			];
		}

		$fieldsToClone = $this->reflectionHelper->getStringKeyArrayOfTypesFromType($arrayType);
		$errors = [];
		foreach ($this->reflectionHelper->getParametersFromMethod($classReflection->getConstructor()) as $parameter) {
			$parameterName = $parameter->getName();
			if (!isset($fieldsToClone[$parameterName])) {
				continue;
			}

			$parameterType = $parameter->getType();
			if (!$parameterType->isSuperTypeOf($fieldsToClone[$parameterName])->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s (%s) does not accept %s, cannot be cloned.',
					$classReflection->getName(),
					$parameterName,
					$parameterType->describe(VerbosityLevel::typeOnly()),
					$fieldsToClone[$parameterName]->describe(VerbosityLevel::typeOnly()),
				))
					->identifier('shredio.cloneWith.propertyType')
					->build();
			}

			unset($fieldsToClone[$parameterName]);
 		}

		foreach ($fieldsToClone as $fieldName => $_) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Unknown property $%s on class %s, cannot be cloned.',
				$fieldName,
				$classReflection->getName(),
			))
				->identifier('shredio.cloneWith.unknownProperty')
				->build();
		}

		return $errors;
	}

}
