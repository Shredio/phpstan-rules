<?php declare(strict_types = 1);

namespace Tests\Unit\Rule\CloneWith;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shredio\PhpstanRules\Helper\PhpStanReflectionHelper;
use Shredio\PhpstanRules\Rule\CloneWithRule;
use Tests\Common\DataTransferObject;

final class CloneWithRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CloneWithRule(
			DataTransferObject::class,
			'cloneWith',
			new PhpStanReflectionHelper(),
			$this->createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/CloneWithRuleCases.php'], [
			['Unknown property $extra on class Tests\Unit\Rule\CloneWith\Article, cannot be cloned.', 13],
			['Property Tests\Unit\Rule\CloneWith\Article::$id (int) does not accept string, cannot be cloned.', 21],
			['Unknown property $external on class Tests\Unit\Rule\CloneWith\Article, cannot be cloned.', 29],
		]);
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/phpstan.neon'];
	}

}
