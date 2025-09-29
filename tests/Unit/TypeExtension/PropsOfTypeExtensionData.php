<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Classes\AccessObject;
use Tests\Classes\ComplexPost;
use Tests\Classes\SimpleArticle;
use function PHPStan\Testing\assertType;

final readonly class PropsOfTypeExtensionData
{

	/**
	 * @param props-of<SimpleArticle> $value
	 */
	public function testSimpleObject(string $value): void
	{
		assertType("'content'|'createdAt'|'id'|'image'|'isPublished'|'tags'|'title'", $value);
	}

	/**
	 * @param props-of<AccessObject> $value
	 */
	public function testAccess(string $value): void
	{
		assertType("'hookBoth'|'hookGet'|'privateSet'|'protectedSet'|'readonlyPublic'|'regularPublic'", $value);
	}

	/**
	 * @param props-of<ComplexPost> $value
	 */
	public function testComplexObject(string $value): void
	{
		assertType("'id'|'privateSet'|'title'|'union'|'virtual'", $value);
	}

	/**
	 * @param props-of<'invalid'> $value
	 */
	public function testInvalidType(string $value): void
	{
		assertType('string', $value);
	}

}
