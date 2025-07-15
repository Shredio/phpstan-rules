<?php declare(strict_types = 1);

namespace Tests\Classes;

final class ComplexPost
{

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

	public function __construct(
		public string $title,
	)
	{
	}

}
