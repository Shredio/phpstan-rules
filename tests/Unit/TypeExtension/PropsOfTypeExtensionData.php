<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Classes\ComplexPost;
use Tests\Classes\SimpleArticle;
use function PHPStan\Testing\assertType;

final readonly class PropsOfTypeExtensionData
{

	/**
	 * @param PropsOf<SimpleArticle> $value
	 */
	public function testSimpleObject(string $value): void
	{
		assertType("'content'|'createdAt'|'id'|'image'|'isPublished'|'tags'|'title'", $value);
	}

	/**
	 * @param PropsOf<ComplexPost> $value
	 */
	public function testComplexObject(string $value): void
	{
		assertType("'id'|'privateSet'|'title'|'union'|'virtual'", $value);
	}

	/**
	 * @param PropsOf<'invalid'> $value
	 */
	public function testInvalidType(string $value): void
	{
		assertType('string', $value);
	}

}
