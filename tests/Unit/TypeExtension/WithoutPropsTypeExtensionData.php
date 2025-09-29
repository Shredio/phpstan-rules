<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Classes\ComplexPost;
use function PHPStan\Testing\assertType;

final readonly class WithoutPropsTypeExtensionData
{

	/**
	 * @param without-props<ComplexPost, 'id'> $value
	 */
	public function testOmitOneProperty(array $value): void
	{
		assertType('array{title: string, union: int|string|null, privateSet: string, virtual: string}', $value);
	}

	/**
	 * @param without-props<ComplexPost, 'id'|'title'> $value
	 */
	public function testOmitTwoProperties(array $value): void
	{
		assertType('array{union: int|string|null, privateSet: string, virtual: string}', $value);
	}

}
