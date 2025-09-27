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
	 * @param array<non-empty-string, mixed> $values
	 * @return array<non-empty-string, mixed>
	 */
	public static function toArrayByReference(object $object, string $reference, array $values = []): array
	{
		return array_merge(get_object_vars($object), $values);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @param array<non-empty-string, mixed> $values
	 * @return T
	 */
	public static function objectToClass(object $object, string $className, array $values = []): object
	{
		return new $className(...self::toArrayByReference($object, $className, $values));
	}

}
