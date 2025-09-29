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
use Shredio\PhpStanHelpers\PhpStanReflectionHelper;

final class OmitTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
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
		if ($typeName->name !== 'Omit') {
			return null;
		}

		$arguments = $typeNode->genericTypes;
		if (count($arguments) !== 2) {
			return null;
		}

		$coreType = $this->typeNodeResolver->resolve($arguments[0], $nameScope);
		$typeToOmit = $this->typeNodeResolver->resolve($arguments[1], $nameScope);

		if ($coreType->isObject()->yes()) {
			return $this->asObject($coreType, $typeToOmit);
		}

		if ($coreType->isConstantScalarValue()->yes()) {
			return $this->asConstantScalar($coreType, $typeToOmit);
		}

		return null;
	}

	private function asObject(Type $coreType, Type $typeToOmit): Type
	{
		$types = [];
		foreach ($coreType->getObjectClassReflections() as $reflectionClass) {
			$properties = $this->reflectionHelper->getReadablePropertiesFromReflection($reflectionClass);
			$newTypeBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($properties as $propertyName => $reflectionProperty) {
				$keyType = new ConstantStringType($propertyName);
				if ($typeToOmit->isSuperTypeOf($keyType)->yes()) {
					// eliminate keys that are in the Omit type
					continue;
				}

				$newTypeBuilder->setOffsetValueType($keyType, $reflectionProperty->getReadableType());
			}

			$types[] = $newTypeBuilder->getArray();
		}

		return TypeCombinator::union(...$types);
	}

	private function asConstantScalar(Type $coreType, Type $typeToOmit): Type
	{
		return TypeCombinator::remove($coreType, $typeToOmit);
	}

}
