<?php

declare( strict_types = 1 );

namespace DNB\GND\Tests\Unit\Adapters\DataAccess;

use DNB\GND\Adapters\DataAccess\GndConverter\ItemBuilder;
use DNB\GND\Adapters\DataAccess\GndConverter\ProductionValueBuilder;
use DNB\GND\Adapters\DataAccess\GndConverterItemSource;
use DNB\GND\Adapters\Presentation\GndDokuSerializer;
use DNB\GND\Domain\Doku\GndField;
use DNB\GND\Domain\Doku\GndSubfield;
use DNB\GND\Domain\ItemSource;
use DNB\GND\Tests\TestDoubles\StubDataTypeLookup;
use PHPUnit\Framework\TestCase;
use SplFileObject;

/**
 * @covers \DNB\GND\Adapters\Presentation\GndDokuSerializer
 * @covers \DNB\GND\Domain\Doku\GndField
 * @covers \DNB\GND\Domain\Doku\GndSubfield
 */
class GndDokuSerializerTest extends TestCase {

	public function testSerialization(): void {
		$field = new GndField();
		$field->id = 'P58';

		$field->label = 'Person – preferred name';
		$field->description = 'GND data field for the preferred name of a person or a family';
		$field->aliases = [
			"PICA+ 028A",
			"PICA3 100",
			"MARC 21 100"
		];

		$field->definition = "Das Feld enthält den bevorzugten Namen einer Person bzw. einer Familie";

		$field->isRepeatable = false;

		$field->subfields = [
			new GndSubfield(
				"P41",
				"Persönlicher Name",
				"Hinweis zur Erfassung von Personen, die nur unter einem Nachnamen bekannt sind",
				[
					'PICA+' => '$P',
					'MARC 21' => '$a',
					'PICA3' => '$P',
				],
				[]
			)
		];

		$field->codings = [
			'PICA+' => '028A',
			'MARC 21' => '100',
			'PICA3' => '100',
		];

		$field->rulesOfUse = [
			'Der bevorzugte Name einer Person setzt sich...',
			'Das Unterfeld für unterscheidende Zusätze...',
		];

		$field->examples = [
			'Q626' => 'Sarah Hartmann'
		];

		$expectedSerialization = <<<'EOD'
{
	"fields": {
		"P58": {
			"label": "Person – preferred name",
			"description": "GND data field for the preferred name of a person or a family",
			"aliases": [
				"PICA+ 028A",
				"PICA3 100",
				"MARC 21 100"
			],

			"definition": "Das Feld enthält den bevorzugten Namen einer Person bzw. einer Familie",
			"repeatable": false,

			"subfields": {
				"P41": {
					"label": "Persönlicher Name",
					"description": "Hinweis zur Erfassung von Personen, die nur unter einem Nachnamen bekannt sind",
					"codings": {
						"PICA+": "$P",
						"MARC 21": "$a",
						"PICA3": "$P"
					},
					"allowedValues": {}
				}
			},

			"codings": {
				"PICA+": "028A",
				"MARC 21": "100",
				"PICA3": "100"
			},

			"rulesOfUse": [
				"Der bevorzugte Name einer Person setzt sich...",
				"Das Unterfeld für unterscheidende Zusätze..."
			],

			"examples": {
				"Q626": {
					"label": "Sarah Hartmann"
				}
			},

			"viewLink": "https://doku.wikibase.wiki/wiki/datafield?property=P58",
			"editLink": "https://doku.wikibase.wiki/wiki/Property:P58"
		}
	}
}
EOD;

		$this->assertFieldIsSerializedTo( $expectedSerialization, $field );
	}

	private function assertFieldIsSerializedTo( string $expectedSerialization, GndField $field ): void {
		$this->assertEquals(
			json_decode( $expectedSerialization, true ),
			( new GndDokuSerializer() )->fieldsToArrays( $field )
		);
	}

}
