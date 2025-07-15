<?php declare(strict_types = 1);

namespace Tests\Classes;

final readonly class SimpleArticle
{

	/**
	 * @param list<string> $tags
	 */
	public function __construct(
		public int $id,
		public string $title,
		public string $content,
		public ?string $image,
		public \DateTimeImmutable $createdAt,
		public bool $isPublished = false,
		public array $tags = [],
	)
	{
	}

}
