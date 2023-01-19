<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\Console;

use Exception;
use Ox\Core\CMbDT;
use Ox\Core\CMbPath;
use Ox\Core\FileUtil\CCSVFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;


/**
 * A class that convert the DRC base files (in CSV) to an SQL format
 */
class CDRCToSQLConverter extends Command {

  /** @var array An array that links the files names to the SQL tables */
  protected static $files_to_tables = array(
    'ref_rc.txt'                  => array(
      'table'   => 'consultation_results',
      'fields'  => array(
        'result_id'           => 'int',
        'title'               => 'string',
        'nature'              => 'string',
        'sex'                 => 'string',
        'episode_type'        => 'string',
        'state'               => 'string',
        'version'             => 'int',
        'symptom'             => 'string',
        'syndrome'            => 'string',
        'disease'             => 'string',
        'certified_diagnosis' => 'string',
        'unpathological'      => 'string',
        'details'             => 'string',
        'dur_prob_epis'       => 'int',
        'age_min'             => 'int',
        'age_max'             => 'int',
        'cim10_code'          => 'string',
        'cisp_code'           => 'string'
      )
    ),
    'ref_definition.txt'          => array(
      'table'   => 'criteria',
      'fields'   => array(
        'criterion_id'    => 'int',
        'result_id'       => 'int',
        'version'         => 'int',
        'order'           => 'int',
        'title_id'        => 'int',
        'spacing_id'      => 'int',
        'ponderation_id'  => 'int',
        'libelle'         => 'string',
        'parent_id'       => 'int',
        'validity'        => 'string'
      )
    ),
    'ref_rcitem.txt'              => array(
      'table'   => 'criteria_titles',
      'fields'  => array(
        'title_id'  => 'int',
        'title'     => 'string'
      )
    ),
    'ref_retrait.txt'             => array(
      'table'   => 'spacings',
      'fields'  => array(
        'spacing_id'  => 'int',
        'level'       => 'string',
        'text'        => 'string',
        'spaces'      => 'int'
      )
    ),
    'ref_ponder.txt'              => array(
      'table'   => 'ponderations',
      'fields'  => array(
        'ponderation_id'  => 'int',
        'text'            => 'string',
        'comment'         => 'string'
      )
    ),
    'ref_classrc.txt'             => array(
      'table'   => 'result_classes',
      'fields'  => array(
        'class_id'    => 'int',
        'text'        => 'string',
        'chapter'     => 'string',
        'libelle'     => 'string',
        'beginning'   => 'string',
        'end'         => 'string'
      )
    ),
    'ref_l_rc_class.txt'          => array(
      'table'   => 'results_to_classes',
      'fields'  => array(
        'result_id' => 'int',
        'class_id'  => 'int'
      )
    ),
    'ref_dic.txt'                 => array(
      'table'   => 'critical_diagnoses',
      'fields'  => array(
        'diagnosis_id'  => 'int',
        'libelle'       => 'string',
        'criticality'   => 'int',
        'group'         => 'int'
      )
    ),
    'ref_l_rc_dic.txt'            => array(
      'table'   => 'results_to_diagnoses',
      'fields'  => array(
        'result_id'     => 'int',
        'diagnosis_id'  => 'int'
      )
    ),
    'ref_l_rc_voir_aussi_rc.txt'  => array(
      'table'   => 'siblings',
      'fields'  => array(
        'result_id'   => 'int',
        'sibling_id'  => 'int'
      )
    ),
    'ref_l_rc_cim10.txt'          => array(
      'table'   => 'transcodings',
      'fields'  => array(
        'transcoding_id'  => 'int',
        'result_id'       => 'int',
        'code_cim_1'      => 'string',
        'libelle_cim_1'   => 'string',
        'code_cim_2'      => 'string',
        'libelle_cim_2'   => 'string',
        'code_cisp'       => 'string',
        'libelle_cisp'    => 'string',
        'subtitle'        => 'string'
      )
    ),
    'ref_combi_criteres_cim.txt'  => array(
      'table'   => 'transcoding_criteria',
      'fields'  => array(
        'transcoding_criterion_id'  => 'int',
        'transcoding_id'            => 'int',
        'criterion_id'              => 'int',
        'condition'                 => 'string'
      )
    ),
    'ref_rc_synonymes.txt'        => array(
      'table'   => 'synonyms',
      'fields'  => array(
        'synonym_id'  => 'int',
        'result_id'   => 'int',
        'libelle'     => 'string'
      )
    ),
    'ref_versionning_rc.txt'      => array(
      'table'   => 'versionings',
      'fields'  => array(
        'version_id'  => 'int',
        'old_result_id'   => 'int',
        'version'     => 'int',
        'result_id'       => 'int'
      )
    )
  );

  /** @var OutputInterface */
  protected $output;

  /** @var InputInterface */
  protected $input;

  /** @var string The path of the DRC database archive or directory */
  protected $input_base_path;

  /** @var string The path of the archive containing the base file in SQL */
  protected $output_base_path;

  /** @var string The path of the SQL import file */
  protected $import_file;

  /**
   * @inheritdoc
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $style = new OutputFormatterStyle('blue', null, array('bold'));
    $output->getFormatter()->setStyle('b', $style);

    $style = new OutputFormatterStyle(null, 'red', array('bold'));
    $output->getFormatter()->setStyle('error', $style);
  }

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('ox-convert:drc')
      ->setDescription('Convert the DRC database to MySQL dump')
      ->setHelp('DRC basefile to SQL converter')
      ->addOption(
        'input',
        'i',
        InputOption::VALUE_REQUIRED,
        'DRC database archive or directory path'
      )
      ->addOption(
        'output',
        'o',
        InputOption::VALUE_OPTIONAL,
        'The output archive path',
        __DIR__ . '/../../../modules/dPcim10/base/drc.tar.gz'
      );
  }

  /**
   * @throws Exception
   *
   * @return void
   */
  protected function getParams() {
    $this->input_base_path = $this->input->getOption('input');
    $this->output_base_path = $this->input->getOption('output');

    if ((!is_file($this->input_base_path) && !is_dir($this->input_base_path)) || !is_readable($this->input_base_path)) {
      $type = is_file($this->input_base_path) ? 'file' : 'dir';
      throw new Exception("Cannot read {$type} {$this->input_base_path}");
    }

    if ((!is_file($this->output_base_path) && !is_dir($this->output_base_path)) || !is_readable($this->output_base_path)) {
      $type = is_file($this->output_base_path) ? 'file' : 'dir';
      throw new Exception("Cannot read {$type} {$this->output_base_path}");
    }
  }

  /**
   * Output timed text
   *
   * @param string $text Text to print
   *
   * @return void
   */
  protected function out($text) {
    $this->output->writeln(CMbDT::strftime("[%Y-%m-%d %H:%M:%S]") . " - $text");
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->input  = $input;
    $this->output = $output;

    $this->getParams();

    set_error_handler(array($this, 'handleFileSystemError'));
    if (is_file($this->input_base_path)) {
      set_error_handler(array($this, 'handleFileSystemError'));
      $path = __DIR__ . '/../../../tmp/cim10/drc_import';

      $this->out('Extracting the files from the SFMG\'s archive');

      if (!CMbPath::extract($this->input_base_path, $path)) {
        throw new Exception('Unable to extract the SFMG\'s DRC archive');
      }

      $this->out('SFMG\'s DRC archive extracted');

      $this->input_base_path = $path;
    }

    if (is_file($this->output_base_path)) {
      $this->out('Extracting the Mediboard DRC database archive.');

      $path = __DIR__ . '/../../../tmp/cim10/drc';
      if (!CMbPath::extract($this->output_base_path, $path)) {
        throw new Exception('Unable to extract the DRC archive');
      }
      $this->out('Mediboard DRC database archive extracted');

      $this->output_base_path = $path;
    }

    if (is_dir($this->output_base_path) && substr($this->output_base_path, -1, 1) == '/') {
      $this->output_base_path = substr($this->output_base_path, 0, -1);
    }

    $this->import_file = "{$this->output_base_path}/data.sql";

    if (file_exists($this->import_file)) {
      file_put_contents($this->import_file, '');
    }

    $files = CMbPath::getFiles($this->input_base_path);
    foreach ($files as $file_path) {
      $file_name = pathinfo($file_path, PATHINFO_BASENAME);
      $extension = pathinfo($file_path, PATHINFO_EXTENSION);

      if ($extension !== 'txt') {
        continue;
      }

      if (!array_key_exists(strtolower($file_name), self::$files_to_tables)) {
        continue;
      }

      $this->out("Converting data from file {$file_name}");
      $this->importFile($file_name, $file_path);
      $this->out("File {$file_name} converted");
    }

    $this->out('Compressing the final archive');

    $this->createArchive();

    $this->out('Extraction complete');

    return self::SUCCESS;
  }

  /**
   * Make SQL queries from the data of the given file
   *
   * @param string $file The file's name
   * @param string $path The file's path
   *
   * @return void
   */
  protected function importFile($file, $path) {
    $table = self::$files_to_tables[strtolower($file)]['table'];
    $fields = self::$files_to_tables[strtolower($file)]['fields'];

    $fields_list = array();
    foreach ($fields as $field => $type) {
      $fields_list[] = "`{$field}`";
    }
    $fields_sql = implode(', ', $fields_list);

    $csv = new CCSVFile($path);
    $csv->delimiter = "\t";
    $csv->enclosure = '"';
    $csv->setColumnNames(array_keys($fields));

    $csv->jumpLine(1);

    $query = '';
    $total = $csv->countLines();
    $line = 1;
    $n = 1;
    while ($data = $csv->readLine(true)) {
      if ($n === 1) {
        $query .= "INSERT INTO `$table` ({$fields_sql}) VALUES\n";
      }

      $values = array();
      foreach ($fields as $field => $type) {
        if (!array_key_exists($field, $data)) {
          continue;
        }

        if ($data[$field] == '') {
          $values[] = 'NULL';
        }
        elseif ($type == 'string') {
          $values[] = "'" . addslashes($data[$field]) . "'";
        }
        else {
          $values[] = $data[$field];
        }
      }

      $query .= '  (' . implode(', ', $values) . ')';
      $n++;
      $line++;

      if ($n < 1000 && $line < $total) {
        $query .= ",\n";
      }
      else {
        $query .= ";\n";
        $n = 1;
      }
    }

    file_put_contents($this->import_file, "{$query}\n", FILE_APPEND);
  }

  /**
   * Create the archive
   *
   * @return void
   */
  protected function createArchive() {
    $where_is = (stripos(PHP_OS, 'WIN') !== false) ? 'where' : 'which';
    exec("$where_is tar", $tar);
    $path = __DIR__ . '/../../../modules/dPcim10/base';
    if ($tar) {
      $cmd = "tar -czf {$path}/drc.tar.gz -C {$this->output_base_path} ./tables.sql ./data.sql";
      exec($cmd, $result);
    }
    else {
      $zip = new ZipArchive();
      $zip->open("{$path}/drc.zip", ZipArchive::OVERWRITE);
      $zip->addFile("{$this->output_base_path}/tables.sql", 'drc/tables.sql');
      $zip->addFile($this->import_file, 'drc/data.sql');
      $zip->close();
    }

    CMbPath::remove($this->output_base_path);
  }

  /**
   * An error handler that catch the error returned by the CMbPath functions and throws an exception
   *
   * @param integer $type    The PHP error type
   * @param string  $message The error message
   *
   * @return bool
   * @throws Exception
   */
  protected function handleFileSystemError($type, $message) {
    if ($type === E_USER_WARNING) {
      throw new Exception($message);
    }
    else {
      return false;
    }
  }
}
