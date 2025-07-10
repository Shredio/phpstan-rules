<?php declare(strict_types = 1);

namespace Tests\Common;

final readonly class ObjectHelper
{

	/**
	 * @return array<string, mixed>
	 */
	public static function toArray(object $object): array
	{
		return get_object_vars($object);
	}

	/**
	 * @param class-string<object> $reference
	 * @param array<string, mixed> $values
	 * @return array<string, mixed>
	 */
	public static function toArrayByReference(object $object, string $reference, array $values = []): array
	{
		return array_merge(get_object_vars($object), $values);
	}

}
