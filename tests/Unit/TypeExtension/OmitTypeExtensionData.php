<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Classes\ComplexPost;
use function PHPStan\Testing\assertType;

final readonly class OmitTypeExtensionData
{

	/**
	 * @param Omit<ComplexPost, 'id'> $value
	 */
	public function testOmitOneProperty(array $value): void
	{
		assertType('array{title: string, union: int|string|null, privateSet: string, virtual: string}', $value);
	}

	/**
	 * @param Omit<ComplexPost, 'id'|'title'> $value
	 */
	public function testOmitTwoProperties(array $value): void
	{
		assertType('array{union: int|string|null, privateSet: string, virtual: string}', $value);
	}

	/**
	 * @param Omit<'id'|'title', 'title'> $value
	 */
	public function testOmitStringConstant(string $value): void
	{
		assertType("'id'", $value);
	}

}
