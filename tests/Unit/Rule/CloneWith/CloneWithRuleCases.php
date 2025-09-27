<?php declare(strict_types = 1);

namespace Tests\Unit\Rule\CloneWith;

use Tests\Common\DataTransferObject;

final readonly class CloneWithRuleCases
{

	public function extraField(): void
	{
		$article = new Article();
		$article->cloneWith([
			'extra' => 'This property does not exist in Article',
		]);
	}

	public function invalidType(): void
	{
		$article = new Article();
		$article->cloneWith([
			'id' => 'string instead of int',
		]);
	}

	public function external(): void
	{
		$article = new Article();
		$article->cloneWith([
			'external' => false,
		]);
	}

	public function valid(): void
	{
		$article = new Article();
		$article->cloneWith([
			'title' => 'Updated Title',
			'isPublished' => true,
			'tags' => ['php', 'stan'],
		]);
	}

}

class Article extends DataTransferObject {

	public bool $external = true;

	public function __construct(
		public int $id = 1,
		public string $title = 'Test Article',
		public string $content = 'This is the content of the article.',
		public ?string $image = null,
		public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
		public bool $isPublished = false,
		public array $tags = [],
	)
	{
	}

}

class PostExtraRequiredProperty {

	public function __construct(
		public int $id,
		public string $content,
		public \DateTimeImmutable $createdAt,
		public bool $isPublished = false,
		public string $extra,
	)
	{
	}
}

class Post {

	public function __construct(
		public int $id,
		public string $content,
		public \DateTimeImmutable $createdAt,
		public bool $isPublished = false,
	)
	{
	}
}
