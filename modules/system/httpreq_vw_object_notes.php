<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\System\CNote;

CCanDo::check();
$object_class = CView::get("object_class", "str");
$object_id    = CView::get("object_id", "ref class|$object_class");
$object_guid  = CView::get("object_guid", "str default|" . "$object_class-$object_id");

$object = CMbObject::loadFromGuid($object_guid);

if (!$object || !$object->_id) {
    CAppUI::notFound($object_guid);
}
CView::checkin();
CView::enableSlave();

$object->needsRead();

$object->loadRefsNotes(PERM_READ);

/** @var CNote $_note */
foreach ($object->_ref_notes as $_note) {
    $_note->loadRefUser()->loadRefFunction();
}

// Cr�ation du template
$smarty = new CSmartyDP();
$smarty->assign("notes", $object->_ref_notes);
$smarty->assign("object", $object);
$smarty->display("vw_object_notes.tpl");
