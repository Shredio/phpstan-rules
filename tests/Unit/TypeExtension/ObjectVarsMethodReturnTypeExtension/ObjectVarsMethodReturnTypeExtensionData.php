<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension\ObjectVarsMethodReturnTypeExtension;

use Tests\Common\DataTransferObject;
use function PHPStan\Testing\assertType;

final readonly class ObjectVarsMethodReturnTypeExtensionData
{

	public function testAnonymous(): void
	{
		$object = new class extends DataTransferObject {
			public int $id = 1;
			/**
			 * @var non-empty-string
			 */
			public string $title = 'Test Article';
			public string|int|null $union = 'Union Value';
			private string $private = 'Private Value';
			protected string $protected = 'Protected Value';

			public static string $staticProperty = 'Static Value';

			private(set) string $privateSet = 'Private Set Value';

			public string $virtual {
				get => 'Virtual Value';
			}
		};

		assertType('array{id: int, title: non-empty-string, union: int|string|null, privateSet: string, virtual: string}', $object->toArray());
	}

	public function testTargetNoMatchingSource(): void
	{
		assertType('array{}', (new Article())->toArray([
			'target' => Foo::class,
		]));
	}

	public function testValues(): void
	{
		assertType('array{id: int, content: string, title: 12, extra: \'value\'}', (new Article())->toArray([
			'values' => [
				'title' => 12,
				'extra' => 'value',
			],
		]));
	}

	public function testTargetMatchingSource(): void
	{
		assertType('array{id: int, content: string}', (new Article())->toArray([
			'target' => Post::class,
		]));
	}

	public function testInside(): void
	{
		new class extends DataTransferObject {

			public string $name = 'Test Name';

			public function call(): void
			{
				assertType('array{name: string}', $this->toArray());
			}
		};
	}

	public function testOverride(): void
	{
		$object = new class extends DataTransferObject {
			public int $id = 1;
			public string $name = 'Test Name';

			/**
			 * @return array{ other: string }
			 */
			public function toArray(array $options = []): array
			{
				return [];
			}
		};

		assertType('array{other: string}', $object->toArray());
	}

	public function testOverrideWithParentCall(): void
	{
		$object = new class extends DataTransferObject {
			public int $id = 1;
			public string $name = 'Test Name';

			/**
			 * @return array{ other: string }
			 */
			public function toArray(array $options = []): array
			{
				assertType('array{id: int, name: string}', parent::toArray());
				return [];
			}
		};
	}

}

class Article extends DataTransferObject {

	public function __construct(
		public int $id = 1,
		public string $title = 'Test Article',
		public string $content = 'Content of the article.'
	)
	{
	}

}

class Post {

	public function __construct(
		public int $id = 1,
		public string $content = 'Content of the post.'
	)
	{
	}

}

class Foo extends DataTransferObject {
	public string $name;
}
