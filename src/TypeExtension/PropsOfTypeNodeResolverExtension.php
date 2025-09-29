<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\TypeExtension;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Shredio\PhpStanHelpers\Exception\CannotCombinePickWithOmitException;
use Shredio\PhpStanHelpers\Exception\EmptyTypeException;
use Shredio\PhpStanHelpers\Exception\InvalidTypeException;
use Shredio\PhpStanHelpers\Exception\NonConstantTypeException;
use Shredio\PhpStanHelpers\Helper\PropertyPicker;
use Shredio\PhpStanHelpers\PhpStanReflectionHelper;

final class PropsOfTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{

	private TypeNodeResolver $typeNodeResolver;

	/** @var array<class-string, bool> */
	private static array $stack = [];

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
		if ($typeName->name !== 'props-of') {
			return null;
		}

		$arguments = $typeNode->genericTypes;
		$count = count($arguments);
		if ($count === 0 || $count > 2) {
			return null;
		}

		$objectType = $this->typeNodeResolver->resolve($arguments[0], $nameScope);
		if (!$objectType->isObject()->yes()) {
			return null;
		}

		$omitType = isset($arguments[1]) ? $this->typeNodeResolver->resolve($arguments[1], $nameScope) : null;
		if ($omitType === null) {
			$picker = PropertyPicker::empty();
		} else {
			try {
				$picker = new PropertyPicker(omit: $this->reflectionHelper->getNonEmptyStringsFromStringType($omitType));
			} catch (InvalidTypeException|NonConstantTypeException|EmptyTypeException|CannotCombinePickWithOmitException) {
				$picker = PropertyPicker::empty();
			}
		}

		$types = [];
		foreach ($objectType->getObjectClassReflections() as $reflectionClass) {
			if (isset(self::$stack[$reflectionClass->getName()])) {
				return new ErrorType();
			}

			self::$stack[$reflectionClass->getName()] = true;
			$properties = $this->reflectionHelper->getReadablePropertiesFromReflection($reflectionClass, $picker);
			foreach ($properties as $propertyName => $type) {
				$types[] = new ConstantStringType($propertyName);
			}

			unset(self::$stack[$reflectionClass->getName()]);
		}

		return TypeCombinator::union(...$types);
	}

}
