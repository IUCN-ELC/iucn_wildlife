<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerLiteraturesSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use Drupal\migrate\Row;


/**
 * Migrate literatures from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_literatures"
 * )
 */
class ElisConsumerLiteraturesSource extends ElisConsumerDefaultSource {

  protected function getElisServiceUrl() {
    return 'http://www2.ecolex.org/elis_isis3w.php?database=libcat&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE';
  }

  protected function getFilesDestination() {
    return 'literatures';
  }

  public function __toString() {
    return 'Migrate literatures from ELIS having `projectInformation` = `WILD`';
  }

  public function fields() {
    return array(
      'id' => 'Remote primary key',
      'country' => 'Country',
      'isisMfn' => 'Isis number',
      'dateOfEntry' => 'Date of entry',
      'dateOfModification' => 'Date of modification',
      'dateOfText' => 'Date of text',
      'titleOfText' => 'Title of text',
      'titleOfTextShort' => 'Title of text short',
      'titleOfText_original' => 'Title of text in english',
      'titleOfText_languages' => 'Languages of titleOfText field',
      'titleOfTextOther' => 'Title of text in another language',
      'titleOfTextShortOther' => 'Short title of text in another language',
      'subject' => 'Subject',
      'languageOfDocument' => 'Language',
      'typeOfText' => 'Type of text',
      'linkToFullText' => 'Link to the full text',
      'linkToFullText_languages' => 'Languages of linkToFullText field',
      'linkToFullTextOther' => 'Link to full text in another language',
      'publPlace' => 'Publication place',
      'publisher' => 'Publisher',
      'scope' => 'Scope',
      'paperTitleOfText' => 'Paper title of text',
      'serialTitle' => 'Serial title',
      'callNo' => 'Call number',
      'authorA' => 'Author',
      'basin' => 'Basins',
      'collation' => 'Collation',
      'confDate' => 'Conference date',
      'confName' => 'Conference name',
      'confNo' => 'Conference number',
      'confPlace' => 'Conference place',
      'contributor' => 'Contributor',
      'corpAuthorA' => 'Corp. author',
      'displayRegion' => 'Display region',
      'edition' => 'Edition',
      'frequency' => 'Frequency',
      'holdings' => 'Holdings',
      'internetReference' => 'Internet reference',
      'isbn' => 'ISBN number',
      'issn' => 'ISSN number',
      'keyword' => 'Keywords',
      'location' => 'Location',
      'notes' => 'Notes',
      'referenceToCourtDecision' => 'Reference to court decision',
      'referenceToFaolex' => 'Reference to FAOLEX',
      'referenceToLiterature' => 'Reference to literature',
      'referenceToTreaties' => 'Reference to treaty',
      'region' => 'Region',
      'serialStatus' => 'Serial status',
      'territorialSubdivision' => 'Territorial subdivision',
      'volumeNo' => 'Volume number',
      'dateOfTextSer' => 'Date of text Ser',
    );
  }

  public function prepareRow(Row $row) {
    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $this->fixDateFields($row, [
      'dateOfEntry',
      'dateOfModification',
      'dateOfText',
      'dateOfTextSer',
    ]);
    return parent::prepareRow($row);
  }
}
