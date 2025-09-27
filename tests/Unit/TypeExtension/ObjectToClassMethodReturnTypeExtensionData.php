<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Common\ObjectHelper;
use function PHPStan\Testing\assertType;

final readonly class ObjectToClassMethodReturnTypeExtensionData
{

	public function testAnonymousComples(): void
	{
		$object = new class {
			public int $id = 1;
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

		assertType(
			'array{id: int, title: string}',
			ObjectHelper::toArrayByReference($object, Article::class),
		);
	}

	public function testValues(): void
	{
		assertType(
			'array{id: int, title: string, authorId: 42}',
			ObjectHelper::toArrayByReference(new SimpleCase(), Article::class, [
				'authorId' => 42,
			]),
		);
	}

	public function testConditionalValues(): void
	{
		$values = [];
		if (mt_rand(0, 1) === 1) {
			$values['authorId'] = 42;
		}

		assertType(
			'array{id: int, title: string, authorId?: 42}',
			ObjectHelper::toArrayByReference(new SimpleCase(), Article::class, $values),
		);
	}

	/**
	 * @param array{ extra?: string } $values
	 */
	public function testOptionalValues(array $values = []): void
	{
		assertType(
			'array{id: int, title: string, extra?: string}',
			ObjectHelper::toArrayByReference(new SimpleCase(), Article::class, $values),
		);
	}

	public function testOverrideWithValues(): void
	{
		assertType(
			'array{id: int, title: \'Title\'}',
			ObjectHelper::toArrayByReference(new SimpleCase(), Article::class, [
				'title' => 'Title',
			]),
		);
	}

	public function testValuesAsNamedArguments(): void
	{
		assertType(
			'array{id: int, title: \'Title\'}',
			ObjectHelper::toArrayByReference(new SimpleCase(), Article::class, values: [
				'title' => 'Title',
			]),
		);
	}

}

class SimpleCase {
	public int $id = 1;
	public string $title = 'Test Article';
}

class Article {

	public function __construct(
		public int $id,
		public string $title,
		public ?int $authorId = null,
	)
	{
	}

}
