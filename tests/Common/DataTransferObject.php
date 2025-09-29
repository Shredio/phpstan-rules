<?php declare(strict_types = 1);

namespace Tests\Common;

abstract class DataTransferObject
{

	/**
	 * @param array<non-empty-string, mixed> $values
	 */
	public function cloneWith(array $values): static
	{
		return new static(...array_merge(get_object_vars($this), $values));
	}

	/**
	 * @param array{ target?: class-string, values?: array<non-empty-string, mixed>, unpack: bool } $options
	 * @return array<string, mixed>
	 */
	public function toArray(array $options = []): array
	{
		return get_object_vars($this);
	}

}
