<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Shredio\PhpstanRules\Helper\PhpStanReflectionHelper;

final class PropsOfTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{

	private TypeNodeResolver $typeNodeResolver;

	public function __construct(
		private readonly PhpStanReflectionHelper $reflectionHelper,
	)
	{
	}

	public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
	{
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if (!$typeNode instanceof GenericTypeNode) {
			return null;
		}

		$typeName = $typeNode->type;
		if ($typeName->name !== 'PropsOf') {
			return null;
		}

		$arguments = $typeNode->genericTypes;
		if (count($arguments) !== 1) {
			return null;
		}

		$objectType = $this->typeNodeResolver->resolve($arguments[0], $nameScope);
		if (!$objectType->isObject()->yes()) {
			return null;
		}

		$types = [];
		foreach ($objectType->getObjectClassReflections() as $reflectionClass) {
			$properties = $this->reflectionHelper->getTypeOfReadablePropertiesFromReflection($reflectionClass);
			foreach ($properties as $propertyName => $_) {
				$types[] = new ConstantStringType($propertyName);
			}
		}

		return TypeCombinator::union(...$types);
	}

}
