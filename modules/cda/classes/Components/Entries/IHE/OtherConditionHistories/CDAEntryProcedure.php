<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Components\Entries\IHE\OtherConditionHistories;

use Ox\Interop\Cda\CCDAClasseCda;
use Ox\Interop\Cda\CCDAFactory;
use Ox\Interop\Cda\Components\Entries\CDAEntryAct;
use Ox\Interop\Cda\Rim\CCDARIMAct;
use Ox\Interop\Cda\Structure\CCDAPOCD_MT000040_Procedure;

/**
 * Class CDAEntryProcedure
 *
 * @package Ox\Interop\Cda\Components\Entries\IHE\OtherConditionHistories
 */
class CDAEntryProcedure extends CDAEntryAct
{
    /** @var string */
    public const TEMPLATE_ID = '1.3.6.1.4.1.19376.1.5.3.1.4.19';

    /**
     * CDAEntryProcedure constructor.
     *
     * @param CCDAFactory $factory
     */
    public function __construct(CCDAFactory $factory)
    {
        parent::__construct($factory);

        //Conformity si acte pr�vu
        $this->addTemplateIds('2.16.840.1.113883.10.20.1.25');

        $this->entry_content = new CCDAPOCD_MT000040_Procedure();
    }

    /**
     * @param CCDARIMAct $entry_content
     */
    protected function buildContent(CCDAClasseCda $entry_content): void
    {
        // not implemented
    }
}
