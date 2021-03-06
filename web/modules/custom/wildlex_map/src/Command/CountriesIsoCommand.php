<?php

namespace Drupal\wildlex_map\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CountriesIsoCommand.
 *
 * @DrupalCommand (
 *     extension="wildlex_map",
 *     extensionType="module"
 * )
 */
class CountriesIsoCommand extends Command {

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new CountriesIsoCommand object.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    parent::__construct();
  }
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('wildlex_map:countries_iso')
      ->setDescription("Command for prepopulating ISO field for countries taxonomy.");
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('Executing');
    $datamaps_countries = [
      'Aruba'=>'ABW',
      'Afghanistan'=>'AFG',
      'Angola'=>'AGO',
      'Anguilla'=>'AIA',
      'Albania'=>'ALB',
      'Åland Islands'=>'ALA',
      'Andorra'=>'AND',
      'United Arab Emirates'=>'ARE',
      'Argentina'=>'ARG',
      'Armenia'=>'ARM',
      'American Samoa'=>'ASM',
      'Antarctica'=>'ATA',
      'French Southern Territories'=>'ATF',
      'Antigua and Barbuda'=>'ATG',
      'Australia'=>'AUS',
      'Austria'=>'AUT',
      'Azerbaijan'=>'AZE',
      'Burundi'=>'BDI',
      'Belgium'=>'BEL',
      'Benin'=>'BEN',
      'Burkina Faso'=>'BFA',
      'Bangladesh'=>'BGD',
      'Bulgaria'=>'BGR',
      'Bahrain'=>'BHR',
      'Bahamas'=>'BHS',
      'Bosnia and Herzegovina'=>'BIH',
      'Saint Barthélemy'=>'BLM',
      'Belarus'=>'BLR',
      'Belize'=>'BLZ',
      'Bermuda'=>'BMU',
      'Bolivia, Plurinational State of'=>'BOL',
      'Brazil'=>'BRA',
      'Barbados'=>'BRB',
      'Brunei Darussalam'=>'BRN',
      'Bhutan'=>'BTN',
      'Botswana'=>'BWA',
      'Central African Republic'=>'CAF',
      'Canada'=>'CAN',
      'Switzerland'=>'CHE',
      'Chile'=>'CHL',
      'China'=>'CHN',
      'Côte d\'Ivoire'=>'CIV',
      'Cameroon'=>'CMR',
      'Congo, the Democratic Republic of the'=>'COD',
      'Congo'=>'COG',
      'Cook Islands'=>'COK',
      'Colombia'=>'COL',
      'Comoros'=>'COM',
      'Cape Verde'=>'CPV',
      'Costa Rica'=>'CRI',
      'Cuba'=>'CUB',
      'Curaçao'=>'CUW',
      'Cayman Islands'=>'CYM',
      'Northern Cyprus'=>'null',
      'Cyprus'=>'CYP',
      'Czech Republic'=>'CZE',
      'Germany'=>'DEU',
      'Djibouti'=>'DJI',
      'Dominica'=>'DMA',
      'Denmark'=>'DNK',
      'Dominican Republic'=>'DOM',
      'Algeria'=>'DZA',
      'Ecuador'=>'ECU',
      'Egypt'=>'EGY',
      'Eritrea'=>'ERI',
      'Spain'=>'ESP',
      'Estonia'=>'EST',
      'Ethiopia'=>'ETH',
      'Finland'=>'FIN',
      'Fiji'=>'FJI',
      'Falkland Islands (Malvinas)'=>'FLK',
      'France'=>'FRA',
      'French Guiana'=>'GUF',
      'Faroe Islands'=>'FRO',
      'Micronesia, Federated States of'=>'FSM',
      'Gabon'=>'GAB',
      'United Kingdom'=>'GBR',
      'Georgia'=>'GEO',
      'Guernsey'=>'GGY',
      'Ghana'=>'GHA',
      'Guinea'=>'GIN',
      'Gambia'=>'GMB',
      'Guinea-Bissau'=>'GNB',
      'Equatorial Guinea'=>'GNQ',
      'Greece'=>'GRC',
      'Grenada'=>'GRD',
      'Greenland'=>'GRL',
      'Guatemala'=>'GTM',
      'Guam'=>'GUM',
      'Guyana'=>'GUY',
      'Hong Kong'=>'HKG',
      'Heard Island and McDonald Islands'=>'HMD',
      'Honduras'=>'HND',
      'Croatia'=>'HRV',
      'Haiti'=>'HTI',
      'Hungary'=>'HUN',
      'Indonesia'=>'IDN',
      'Isle of Man'=>'IMN',
      'India'=>'IND',
      'Cocos (Keeling) Islands'=>'CCK',
      'Christmas Island'=>'CXR',
      'British Indian Ocean Territory'=>'IOT',
      'Ireland'=>'IRL',
      'Iran, Islamic Republic of'=>'IRN',
      'Iraq'=>'IRQ',
      'Iceland'=>'ISL',
      'Israel'=>'ISR',
      'Italy'=>'ITA',
      'Jamaica'=>'JAM',
      'Jersey'=>'JEY',
      'Jordan'=>'JOR',
      'Japan'=>'JPN',
      'Kazakhstan'=>'KAZ',
      'Kenya'=>'KEN',
      'Kyrgyzstan'=>'KGZ',
      'Cambodia'=>'KHM',
      'Kiribati'=>'KIR',
      'Saint Kitts and Nevis'=>'KNA',
      'Korea, Republic of'=>'KOR',
      'Kosovo'=>'null',
      'Kuwait'=>'KWT',
      'Lao People\'s Democratic Republic'=>'LAO',
      'Lebanon'=>'LBN',
      'Liberia'=>'LBR',
      'Libya'=>'LBY',
      'Saint Lucia'=>'LCA',
      'Liechtenstein'=>'LIE',
      'Sri Lanka'=>'LKA',
      'Lesotho'=>'LSO',
      'Lithuania'=>'LTU',
      'Luxembourg'=>'LUX',
      'Latvia'=>'LVA',
      'Macao'=>'MAC',
      'Saint Martin (French part)'=>'MAF',
      'Morocco'=>'MAR',
      'Monaco'=>'MCO',
      'Moldova, Republic of'=>'MDA',
      'Madagascar'=>'MDG',
      'Maldives'=>'MDV',
      'Mexico'=>'MEX',
      'Marshall Islands'=>'MHL',
      'Macedonia'=>'MKD',
      'Mali'=>'MLI',
      'Malta'=>'MLT',
      'Myanmar'=>'MMR',
      'Montenegro'=>'MNE',
      'Mongolia'=>'MNG',
      'Northern Mariana Islands'=>'MNP',
      'Mozambique'=>'MOZ',
      'Mauritania'=>'MRT',
      'Montserrat'=>'MSR',
      'Mauritius'=>'MUS',
      'Malawi'=>'MWI',
      'Malaysia'=>'MYS',
      'Namibia'=>'NAM',
      'New Caledonia'=>'NCL',
      'Niger'=>'NER',
      'Norfolk Island'=>'NFK',
      'Nigeria'=>'NGA',
      'Nicaragua'=>'NIC',
      'Niue'=>'NIU',
      'Netherlands'=>'NLD',
      'Norway'=>'NOR',
      'Nepal'=>'NPL',
      'Nauru'=>'NRU',
      'New Zealand'=>'NZL',
      'Oman'=>'OMN',
      'Pakistan'=>'PAK',
      'Panama'=>'PAN',
      'Pitcairn'=>'PCN',
      'Peru'=>'PER',
      'Philippines'=>'PHL',
      'Palau'=>'PLW',
      'Papua New Guinea'=>'PNG',
      'Poland'=>'POL',
      'Puerto Rico'=>'PRI',
      'Korea, Democratic People\'s Republic of'=>'PRK',
      'Portugal'=>'PRT',
      'Paraguay'=>'PRY',
      'Palestinian Territories'=>'PSE',
      'French Polynesia'=>'PYF',
      'Qatar'=>'QAT',
      'Romania'=>'ROU',
      'Russian Federation'=>'RUS',
      'Rwanda'=>'RWA',
      'Western Sahara'=>'ESH',
      'Saudi Arabia'=>'SAU',
      'Sudan'=>'SDN',
      'South Sudan'=>'SSD',
      'Senegal'=>'SEN',
      'Singapore'=>'SGP',
      'South Georgia and the South Sandwich Islands'=>'SGS',
      'Saint Helena, Ascension and Tristan da Cunha'=>'SHN',
      'Solomon Islands'=>'SLB',
      'Sierra Leone'=>'SLE',
      'El Salvador'=>'SLV',
      'San Marino'=>'SMR',
      'Somaliland'=>'null',
      'Somalia'=>'SOM',
      'Saint Pierre and Miquelon'=>'SPM',
      'Serbia'=>'SRB',
      'Sao Tome and Principe'=>'STP',
      'Suriname'=>'SUR',
      'Slovakia'=>'SVK',
      'Slovenia'=>'SVN',
      'Sweden'=>'SWE',
      'Swaziland'=>'SWZ',
      'Sint Maarten (Dutch part)'=>'SXM',
      'Seychelles'=>'SYC',
      'Syrian Arab Republic'=>'SYR',
      'Turks and Caicos Islands'=>'TCA',
      'Chad'=>'TCD',
      'Togo'=>'TGO',
      'Thailand'=>'THA',
      'Tajikistan'=>'TJK',
      'Turkmenistan'=>'TKM',
      'Timor-Leste'=>'TLS',
      'Tonga'=>'TON',
      'Trinidad and Tobago'=>'TTO',
      'Tunisia'=>'TUN',
      'Turkey'=>'TUR',
      'Taiwan'=>'TWN',
      'Tanzania, United Republic of'=>'TZA',
      'Uganda'=>'UGA',
      'Ukraine'=>'UKR',
      'Uruguay'=>'URY',
      'United States'=>'USA',
      'Uzbekistan'=>'UZB',
      'Saint Vincent and the Grenadines'=>'VCT',
      'Venezuela, Bolivarian Republic of'=>'VEN',
      'Virgin Islands, British'=>'VGB',
      'Virgin Islands, U.S.'=>'VIR',
      'Viet Nam'=>'VNM',
      'Vanuatu'=>'VUT',
      'Wallis and Futuna'=>'WLF',
      'Samoa'=>'WSM',
      'Yemen'=>'YEM',
      'South Africa'=>'ZAF',
      'Zambia'=>'ZMB',
      'Zimbabwe'=>'ZWE',
      'Svalbard and Jan Mayen'=>'SJM',
      'Bonaire, Sint Eustatius and Saba'=>'BES',
      'Mayotte'=>'MYT',
      'Martinique'=>'MTQ',
      'Réunion'=>'REU',
      'Holy See (Vatican City State)'=>'VAT',
      'Tokelau'=>'TKL',
      'Tuvalu'=>'TUV',
      'Bouvet Island'=>'BVT',
      'Gibraltar'=>'GIB',
      'Guadeloupe'=>'GLP',
      'United States Minor Outlying Islands'=>'UMI',
    ];


    $exceptions = [
      'Bolivia' => 'BOL',
      'Bonaire, Saint Eustatius And Saba' => 'BES',
      'Brunei' => 'BRN',
      'Cabo Verde' => 'CPV',
      'Democratic Republic of the Congo' => 'COD',
      'Eswatini, Kingdom of' => 'SWZ',
      'European Union',
      'French Southern and Antarctic Lands' => 'ATA',
      'Heard Island And McDonald Islands' => 'HMD',
      'International',
      'Iran' => 'IRN',
      'Lao, People\'s Dem. Rep.' => 'LAO',
      'Laos' => 'LAO',
      'Micronesia' => 'FSM',
      'Moldova' => 'MDA',
      'North Korea' => 'PRK',
      'Palestinian Territory, Occupied' => 'PSE',
      'Pitcairn Islands' => 'PCN',
      'Russia' => 'RUS',
      'Saint Martin' => 'MAF',
      'Sint Maarten' => 'SXM',
      'South Korea' => 'KOR',
      'Syria' => 'SYR',
      'Tanzania' => 'TZA',
      'Tanzania, Un. Rep. of' => 'TZA',
      'The Gambia' => 'GMB',
      'United States of America' => 'USA',
      'United States Virgin Islands' => 'VIR',
      'Vatican City State' => 'VAT',
      'Venezuela' => 'VEN',
      'Virgin Islands' => 'VGB',
    ];

    $vid = 'countries';
    $terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($vid);
    $not_found = NULL;

    if(!$terms) {
      $this->getIo()->info('Zero (0) terms found.');
      return;
    }
    foreach ($terms as $term) {
      if (!isset($datamaps_countries[$term->name])) {
        if(!isset($exceptions[$term->name])) {
          $not_found++;
          $this->getIo()->error("Could not find: " . $term->name);
        } else {
          $update_term = Term::load($term->tid);
          $update_term->field_iso->setValue($exceptions[$term->name]);
          $update_term->save();
        }
      } else {
        $update_term = Term::load($term->tid);
        $update_term->field_iso->setValue($datamaps_countries[$term->name]);
        $update_term->save();
        $this->getIo()->info($term->name . " : " . $datamaps_countries[$term->name]);
      }
    }
    if($not_found) {
      $this->getIo()->warning("Not found items:" . $not_found);
    } else {
      $this->getIo()->success("ALL OK");
    }
    $this->getIo()->success("Completed.");
  }
}
