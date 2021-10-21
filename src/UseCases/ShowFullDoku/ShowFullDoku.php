<?php

declare( strict_types = 1 );

namespace DNB\GND\UseCases\ShowFullDoku;

use DataValues\BooleanValue;
use DataValues\StringValue;
use DNB\GND\Domain\Doku\GndField;
use DNB\GND\Domain\PropertyCollectionLookup;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;

class ShowFullDoku {

	private const ELEMENT_OF_PROPERTY = 'P2';
	private const GND_FIELD_ITEM = 'Q2';

	private const CODINGS_PROPERTY = 'P4';
	private const CODING_TYPE_PROPERTY = 'P3';
	private const CODING_MAP = [
		'Q1317' => 'PICA+',
		'Q1316' => 'PICA3',
		'Q1320' => 'MARC 21',
	];

	private const DEFINITION_PROPERTY = 'P1';
	private const REPEATABLE_PROPERTY = 'P12';
	private const SUBFIELDS_PROPERTY = 'P15';
	private const VALIDATION_PROPERTY = 'P9';
	private const RULES_OF_USE_PROPERTY = 'P10';
	private const EXAMPLES_PROPERTY = 'P11';

	private FullDokuPresenter $presenter;
	private PropertyCollectionLookup $propertyLookup;
	private ItemLookup $itemLookup;

	public function __construct( FullDokuPresenter $presenter, PropertyCollectionLookup $propertyLookup, ItemLookup $itemLookup ) {
		$this->presenter = $presenter;
		$this->propertyLookup = $propertyLookup;
		$this->itemLookup = $itemLookup;
	}

	public function showFullDoku( string $languageCode ): void {
		/**
		 * @var GndField[]
		 */
		$gndFields = [];

		$properties = $this->propertyLookup->getProperties();

		foreach ( $properties->asArray() as $property ) {
			$field = $this->propertyToGndField( $property, $languageCode );

			if ( $field !== null ) {
				$gndFields[] = $field;
			}
		}

		$this->presenter->present( $gndFields );
	}

	private function propertyToGndField( Property $property, string $languageCode ): ?GndField {
		if ( !$this->isElementOfGndField( $property ) ) {
			return null;
		}

		$field = new GndField();

		$field->id = $property->getId()->serialize();
		$field->label = $property->getLabels()->toTextArray()[$languageCode] ?? '';
		$field->description = $property->getDescriptions()->toTextArray()[$languageCode] ?? '';
		$field->aliases = $property->getAliasGroups()->toTextArray()[$languageCode] ?? [];

		$field->definition = $this->getPropertyStringValue( $property, self::DEFINITION_PROPERTY ) ?? '';
		$field->codings = $this->getCodingsFromProperty( $property );
		$field->isRepeatable = $this->getIsRepeatableFromProperty( $property );

		return $field;
	}

	private function isElementOfGndField( Property $property ): bool {
		$elementOfStatements = $property->getStatements()->getByPropertyId( new PropertyId( self::ELEMENT_OF_PROPERTY ) )->toArray();

		if ( !array_key_exists( 0, $elementOfStatements ) ) {
			return false;
		}

		return $elementOfStatements[0]->getMainSnak()->equals(
			new PropertyValueSnak(
				new PropertyId( self::ELEMENT_OF_PROPERTY ),
				new EntityIdValue( new ItemId( self::GND_FIELD_ITEM ) )
			)
		);
	}

	private function getPropertyStringValue( Property $property, string $propertyId ): ?string {
		$snaks = $property->getStatements()->getByPropertyId( new PropertyId( $propertyId ) )->getMainSnaks();

		if ( array_key_exists( 0, $snaks ) ) {
			if ( $snaks[0] instanceof PropertyValueSnak ) {
				$value = $snaks[0]->getDataValue();

				if ( $value instanceof StringValue ) {
					return $value->getValue();
				}
			}
		}

		return null;
	}

	private function getCodingsFromProperty( Property $property ): array {
		$codings = [];

		$codingStatements = $property->getStatements()->getByPropertyId( new PropertyId( self::CODINGS_PROPERTY ) );

		foreach ( $codingStatements as $statement ) {
			foreach ( self::CODING_MAP as $codingItemId => $keyName ) {
				if ( $statement->getQualifiers()->hasSnak( $this->newCodingType( $codingItemId ) ) ) {
					$codings[$keyName] = $statement->getMainSnak()->getDataValue()->getValue();
				}
			}
		}

		return $codings;
	}

	private function newCodingType( string $typeItemId ): PropertyValueSnak {
		return new PropertyValueSnak(
			new PropertyId( self::CODING_TYPE_PROPERTY ),
			new EntityIdValue( new ItemId( $typeItemId ) )
		);
	}

	private function getIsRepeatableFromProperty( Property $property ): bool {
		$snaks = $property->getStatements()->getByPropertyId( new PropertyId( self::REPEATABLE_PROPERTY ) )->getMainSnaks();

		if ( array_key_exists( 0, $snaks ) ) {
			if ( $snaks[0] instanceof PropertyValueSnak ) {
				$value = $snaks[0]->getDataValue();

				if ( $value instanceof BooleanValue ) {
					return $value->getValue();
				}
			}
		}

		return false;
	}

}
