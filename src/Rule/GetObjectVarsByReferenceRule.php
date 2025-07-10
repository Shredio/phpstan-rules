<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
final readonly class GetObjectVarsByReferenceRule implements Rule
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

	public function getNodeType(): string
	{
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$args = $node->getArgs();
		$valuesArg = $args[2] ?? null;

		if ($valuesArg === null) {
			return [];
		}

		$calledOnClass = $node->class->name;
		if ($calledOnClass !== $this->className) {
			return [];
		}

		$methodNameNode = $node->name;
		if (!$methodNameNode instanceof Node\Identifier || $methodNameNode->name !== $this->methodName) {
			return [];
		}

		$valuesType = $scope->getType($valuesArg->value);
		if (!$valuesType->isConstantArray()->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Method %s::%s() expects the third argument to be a constant array, but got %s.',
					$this->className,
					$this->methodName,
					$valuesType->describe(VerbosityLevel::typeOnly()),
				))
					->identifier('shredio.getObjectVarsByReference')
					->build()
			];
		}

		return [];
	}

}
