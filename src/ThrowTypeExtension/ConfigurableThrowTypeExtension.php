<?php declare(strict_types = 1);

namespace Shredio\PhpstanRules\ThrowTypeExtension;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\Type;

final readonly class ConfigurableThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function __construct(
		private string $className,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		if ($methodReflection->getDeclaringClass()->isSubclassOfClass($this->reflectionProvider->getClass($this->className))) {
			return true;
		}

		return false;
	}

	public function getThrowTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope,
	): ?Type
	{
		return null;
	}

}
