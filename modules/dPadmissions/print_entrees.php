<?php
/**
 * @package Mediboard\Admissions
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();

$date            = CView::get("date", "date default|now");
$type            = CView::get("type", "str");
$services_ids    = CView::get("services_ids", "str", true);
$sejours_ids     = CView::get("sejours_ids", "str", true);
$period          = CView::get("period", "str");
$group_by        = CView::get("group_by_prat", "bool default|1");
$order_by        = CView::get("order_by", "str");
$enabled_service = CView::get("active_filter_services", "bool default|0", true);

CView::checkin();

$type_pref = array();
$sejour    = new CSejour();

// Liste des types d'admission possibles
$list_type_admission = $sejour->_specs["_type_admission"]->_list;

if (is_array($services_ids)) {
  CMbArray::removeValue("", $services_ids);
}

if (is_array($sejours_ids)) {
  CMbArray::removeValue("", $sejours_ids);

  // recupere les pr�f�rences des differents types de s�jours selectionn�s par l'utilisateur
  foreach ($sejours_ids as $key) {
    if ($key != 0) {
      $type_pref[] = $list_type_admission[$key];
    }
  }
}

if (count($type_pref) == 1) {
  $type = $type_pref[0];
}

$date_min = $date;
$date_max = CMbDT::date("+ 1 DAY", $date);
$group = CGroups::loadCurrent();

if ($period) {
  $hour = CAppUI::gconf("dPadmissions General hour_matin_soir");
  if ($period == "matin") {
    // Matin
    $date_max = CMbDT::dateTime($hour, $date);
  }
  else {
    // Soir
    $date_min = CMbDT::dateTime($hour, $date);
  }
}

$where = array(
  "sejour.entree"   => "BETWEEN '$date_min' AND '$date_max'",
  "sejour.annule"   => "= '0'",
  "sejour.group_id" => "= '$group->_id'"
);

// Filtre sur les types d'admission
if (count($type_pref)) {
  $where["sejour.type"] = CSQLDataSource::prepareIn($type_pref);
}
else {
  $where["sejour.type"] = CSQLDataSource::prepareNotIn(array_merge(CSejour::getTypesSejoursUrgence(), ["seances"]));
}

$ljoin = array(
  "users"    => "users.user_id = sejour.praticien_id",
  "patients" => "patients.patient_id = sejour.patient_id"
);

// Filtre sur les services
if (count($services_ids) && $enabled_service) {
  $ljoin["affectation"]        = "affectation.sejour_id = sejour.sejour_id AND affectation.entree = sejour.entree";
  $where["affectation.service_id"] = CSQLDataSource::prepareIn($services_ids);
}

switch ($order_by) {
  case "patient_name":
    $order = "patients.nom ASC, sejour.entree";
    break;

  case "entree_prevue":
    $order = "sejour.entree_prevue ASC";
    break;

  case "entree_reelle":
    $order = "sejour.entree_reelle ASC";
    break;

  default:
    $order = "users.user_last_name, users.user_first_name, sejour.entree";
}

/** @var CSejour[] $sejours */
$sejours = $sejour->loadList($where, $order, null, null, $ljoin);
CStoredObject::massLoadFwdRef($sejours, "praticien_id");
CStoredObject::massLoadFwdRef($sejours, "patient_id");
CStoredObject::massLoadFwdRef($sejours, "prestation_id");
$affectations = CStoredObject::massLoadBackRefs($sejours, "affectations", "sortie DESC");
CAffectation::massUpdateView($affectations);

// Chargement optimis� des prestations
CSejour::massLoadPrestationSouhaitees($sejours);

CSejour::massLoadNDA($sejours);

$listByPrat = array();
foreach ($sejours as $sejour) {
  $sejour->loadRefPraticien();
  $sejour->loadRefsAffectations();
  $sejour->loadRefPatient();
  $sejour->loadRefPrestation();
  $sejour->_ref_first_affectation->loadRefLit();
  $sejour->_ref_first_affectation->_ref_lit->loadCompleteView();

  $curr_prat = $sejour->praticien_id;
  if (!isset($listByPrat[$curr_prat])) {
    $listByPrat[$curr_prat]["praticien"] = $sejour->_ref_praticien;
  }
  $listByPrat[$curr_prat]["sejours"][] = $sejour;
}

// Cr�ation du template
$smarty = new CSmartyDP();
$smarty->assign("sejours"   , $sejours);
$smarty->assign("date"      , $date);
$smarty->assign("type"      , $type);
$smarty->assign("listByPrat", $listByPrat);
$smarty->assign("group_by"  , $group_by);
$smarty->assign("total"     , count($sejours));

$smarty->display("print_entrees.tpl");
