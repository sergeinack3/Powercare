<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7\Events\QBP;

use Ox\Mediboard\Patients\CPatient;

/**
 * Class CHL7v2EventPDQK22
 * K22 - Find Candidates response
 */
class CHL7v2EventQBPZV2 extends CHL7v2EventQBP implements CHL7EventQBPK22 {

  /** @var string */
  public $code = "ZV2";

  /**
   * Construct
   *
   * @return CHL7v2EventQBPZV2
   */
  function __construct() {
    parent::__construct();

    $this->profil = "PDQ";
  }

  /**
   * Build ZV2 event
   *
   * @param CPatient $patient Person
   *
   * @see parent::build()
   *
   * @return void
   */
  function build($patient) {
  }
}