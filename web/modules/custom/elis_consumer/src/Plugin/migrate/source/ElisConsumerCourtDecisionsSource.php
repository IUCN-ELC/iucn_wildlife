<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use Drupal\migrate\Row;


/**
 * Migrate court decision from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_court_decisions"
 * )
 */
class ElisConsumerCourtDecisionsSource extends ElisConsumerDefaultSource {

  protected function getElisServiceUrl() {
    return 'http://www2.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE';
  }

  protected function getFilesDestination() {
    return 'court_decisions';
  }

  public function __toString() {
    return 'Migrate court decisions from ELIS having `projectInformation` = `WILD`';
  }

  public function fields() {
    return array(
      'id' => 'Remote primary key',
      'isisMfn' => 'Isis number',
      'dateOfEntry' => 'Date of entry',
      'dateOfModification' => 'Date of modification',
      'titleOfText' => 'Title of text',
      'titleOfTextShort' => 'Title of text short',
      'titleOfText_original' => 'Title of text in english',
      'titleOfText_languages' => 'Languages of titleOfText field',
      'titleOfTextOther' => 'Title of text in another language',
      'titleOfTextShortOther' => 'Short title of text in another language',
      'country' => 'Country',
      'subject' => 'Subject',
      'languageOfDocument' => 'Language',
      'courtName' => 'Court name',
      'dateOfText' => 'Date of text',
      'referenceNumber' => 'Reference number',
      'numberOfPages' => 'Number of pages',
      'availableIn' => 'Available in',
      'linkToFullText' => 'Link to the full text',
      'linkToFullText_languages' => 'Languages of linkToFullText field',
      'linkToFullTextOther' => 'Link to full text in another language',
      'internetReference' => 'Link to website of court decision origin',
      'relatedWebSite' => 'Related Internet website',
      'keyword' => 'Keywords',
      'abstract' => 'Abstract',
      'abstract_languages' => 'Languages of abstract field',
      'typeOfText' => 'Type of text',
      'referenceToNationalLegislation' => 'Reference to legislation',
      'referenceToTreaties' => 'Reference to other treaties',
      'referenceToCourtDecision' => 'Reference to court decisions',
      'subdivision' => 'Court subdivision',
      'justices' => 'Name of justices',
      'territorialSubdivision' => 'Geopolitical Territory',
      'linkToAbstract' => 'Link to decision abstract text',
      'statusOfDecision' => 'Status of court decision',
      'referenceToEULegislation' => 'Refecence to European Union legislation',
      'seatOfCourt' => 'Seat of court',
      'courtJurisdiction' => 'Jurisdiction of the court',
      'instance' => 'Instance',
      'officialPublication' => 'Publication',
      'region' => 'Geopolitical region',
      'referenceToFaolex' => 'Reference to FAOLEX legislation',
      'wildlifeSpecies' => 'Species',
      'wildlifePenalty' => 'Penaly of the case',
      'wildlifeValue' => 'Financial value of the case',
      'wildlifeTransnational' => 'Transnational (Y/N)',
      'wildlifeDecision' => 'Decision of the court',
      'wildlifeCharges' => 'Charges against accused person(s)',
      'referenceToNationalLegislationNotes' => 'Cited legislations',
      'courtCase' => 'Type of case',
      'source' => 'Source',
    );
  }

  public function prepareRow(Row $row) {
    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $linkToAbstract = str_replace('server2.php/', '', $row->getSourceProperty('linkToAbstract'));
    $row->setSourceProperty('linkToAbstract', $linkToAbstract);
    $this->fixDateFields($row, [
      'dateOfEntry',
      'dateOfModification',
      'dateOfText',
      'referenceToFaolexDate',
    ]);
    return parent::prepareRow($row);
  }
}
