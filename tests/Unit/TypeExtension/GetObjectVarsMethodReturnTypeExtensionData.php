<?php declare(strict_types = 1);

namespace Tests\Unit\TypeExtension;

use Tests\Common\ObjectHelper;
use function PHPStan\Testing\assertType;

final readonly class GetObjectVarsMethodReturnTypeExtensionData
{

	public function testAnonymous(): void
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

		assertType('array{id: int, title: string, union: int|string|null, privateSet: string, virtual: string}', ObjectHelper::toArray($object));
	}

	public function testClassName(): void
	{
		assertType('array{name: string}', ObjectHelper::toArray(new Foo()));
	}

	public function testInside(): void
	{
		$object = new class {

			public string $name = 'Test Name';

			public function call(): void
			{
				$vars = ObjectHelper::toArray($this);
				assertType('array{name: string}', $vars);
			}
		};

		$object->call();
	}

}

class Foo {
	public string $name;
}
