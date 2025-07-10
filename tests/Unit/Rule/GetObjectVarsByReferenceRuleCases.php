<?php declare(strict_types = 1);

namespace Tests\Unit\Rule;

use Tests\Common\ObjectHelper;

final readonly class GetObjectVarsByReferenceRuleCases
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function dynamicArray(array $values = []): void
	{
		ObjectHelper::toArrayByReference(new class {}, self::class, $values);
	}

	// Valid cases

	public function withoutValues(): void
	{
		ObjectHelper::toArrayByReference(new class {}, self::class);
	}

	public function constantArrayInCall(): void
	{
		ObjectHelper::toArrayByReference(new class {}, self::class, [
			'key1' => 'value1',
			'key2' => 'value2',
		]);
	}

	public function constantArrayOutsideOfCall(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
		];

		ObjectHelper::toArrayByReference(new class {}, self::class, $values);
	}

	public function constantArrayOutsideOfCallWithConditions(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
		];

		if (mt_rand(1, 2) === 1) {
			$values['key3'] = 'value3';
		}

		ObjectHelper::toArrayByReference(new class {}, self::class, $values);
	}

	/**
	 * @param array{ key: string } $values
	 */
	public function constantArrayInParameter(array $values): void
	{
		ObjectHelper::toArrayByReference(new class {}, self::class, $values);
	}

}
