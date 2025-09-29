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
	 * @param list<props-of<ComplexPost>> $value
	 */
	public function testListComplexObject(array $value): void
	{
		assertType("list<'id'|'privateSet'|'title'|'union'|'virtual'>", $value);
	}

	public function testPropsOfSelf(): void
	{
		assertType("list<'name'>", (new PropsOfSelf())->getProps());
		assertType("list<'name'>", (new PropsOfSelf())->getPropsSelf());
	}

	public function testConstructorPropsOf(): void
	{
		$instance = new ConstructorPropsOf('AAPL', 'AAPL', 'stock');

		assertType("list<'country'|'currency'|'description'|'exchange'|'industry'|'isin'|'name'|'sector'|'symbol'|'type'>", $instance->skip);
	}

	/**
	 * @param props-of<'invalid'> $value
	 */
	public function testInvalidType(string $value): void
	{
		assertType('string', $value);
	}

}

class PropsOfSelf {

	public string $name;

	/**
	 * @return list<props-of<PropsOfSelf>>
	 */
	public function getProps(): array
	{
		return [];
	}

	/**
	 * @return list<props-of<self>>
	 */
	public function getPropsSelf(): array
	{
		return [];
	}

}

class ConstructorPropsOf
{

	/**
	 * @param list<props-of<self, 'skip'>> $skip
	 */
	public function __construct(
		public readonly string $symbol,
		public readonly string $name,
		public readonly string $type,
		public readonly ?string $exchange = null,
		public readonly ?string $currency = null,
		public readonly ?string $country = null,
		public readonly ?string $isin = null,
		public readonly ?string $sector = null,
		public readonly ?string $industry = null,
		public readonly ?string $description = null,
		public readonly array $skip = [],
	)
	{
	}

}
