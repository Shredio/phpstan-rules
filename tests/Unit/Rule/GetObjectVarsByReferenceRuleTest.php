<?php declare(strict_types = 1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shredio\PhpstanRules\Rule\GetObjectVarsByReferenceRule;
use Tests\Common\ObjectHelper;

final class GetObjectVarsByReferenceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GetObjectVarsByReferenceRule(ObjectHelper::class, 'toArrayByReference');
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/GetObjectVarsByReferenceRuleCases.php'], [
			[
				'Method Tests\Common\ObjectHelper::toArrayByReference() expects the third argument to be a constant array, but got array<string, mixed>.',
				15,
			]
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
