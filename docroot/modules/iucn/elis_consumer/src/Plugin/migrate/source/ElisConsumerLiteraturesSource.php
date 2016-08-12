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

  protected function getElisServicePath() {
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
      'country' => 'Country',
      'subject' => 'Subject',
      'languageOfDocument' => 'Language',
      'linkToFullText' => 'Link to the full text',
      'linkToFullText_languages' => 'Languages of linkToFullText field',
      'linkToFullTextOther' => 'Link to full text in another language',
      'typeOfText' => 'Type of text',
      'paperTitleOfText' => 'Paper title of text',
      'serialTitle' => 'Serial title',
      'callNo' => 'Call number',
      'publPlace' => 'Publication place',
      'publisher' => 'Publisher',
      'scope' => 'Scope',
    );
  }

  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $this->fixDateFields($row, [
      'dateOfEntry',
      'dateOfModification',
      'dateOfText',
    ]);
    return TRUE;
  }
}
