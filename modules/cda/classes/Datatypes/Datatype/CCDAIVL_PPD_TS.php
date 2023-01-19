<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Cda\Datatypes\Datatype;

/**
 * CCDAIVL_PPD_TS class
 * Choix entre une s�quence(low(1.1), [width(0.1)|high(0.1)]), element high(1.1), s�quence(width(1.1), high(0.1)),
 * s�quence(center(1.1), width(0.1))
 */
class CCDAIVL_PPD_TS extends CCDASXCM_PPD_TS
{

    /**
     * The low limit of the interval.
     *
     * @var CCDAIVXB_PPD_TS
     */
    public $low;
    /**
     * The difference between high and low boundary. The
     * purpose of distinguishing a width property is to
     * handle all cases of incomplete information
     * symmetrically. In any interval representation only
     * two of the three properties high, low, and width need
     * to be stated and the third can be derived.
     *
     * @var CCDAPPD_PQ
     */
    public $width;
    /**
     * The high limit of the interval.
     *
     * @var CCDAIVXB_PPD_TS
     */
    public $high;
    /**
     * The arithmetic mean of the interval (low plus high
     * divided by 2). The purpose of distinguishing the center
     * as a semantic property is for conversions of intervals
     * from and to point values.
     *
     * @var CCDAPPD_TS
     */
    public  $center;
    private $propsHigh   = "CCDAIVXB_PPD_TS xml|element max|1";
    private $propsWidth  = "CCDAPPD_PQ xml|element max|1";
    private $propsLow    = "CCDAIVXB_PPD_TS xml|element max|1";
    private $propsCenter = "CCDAPPD_TS xml|element max|1";
    private $_order      = null;

    /**
     * Getter center
     *
     * @return CCDAPPD_TS
     */
    public function getCenter()
    {
        return $this->center;
    }

    /**
     * Setter center
     *
     * @param CCDAPPD_TS $center \CCDAPPD_TS
     *
     * @return void
     */
    public function setCenter($center)
    {
        $this->setOrder("center");
        $this->center = $center;
    }

    /**
     * Affecte la s�quence choisi
     *
     * @param String $nameVar String
     *
     * @return void
     */
    function setOrder($nameVar)
    {
        if (empty($this->_order) || empty($nameVar)) {
            $this->_order = $nameVar;
        }
    }

    /**
     * Getter high
     *
     * @return CCDAIVXB_PPD_TS
     */
    public function getHigh()
    {
        return $this->high;
    }

    /**
     * Setter High
     *
     * @param CCDAIVXB_PPD_TS $high \CCDAIVXB_PPD_TS
     *
     * @return void
     */
    public function setHigh($high)
    {
        $this->setOrder("high");
        $this->high = $high;
    }

    /**
     * Getter low
     *
     * @return CCDAIVXB_PPD_TS
     */
    public function getLow()
    {
        return $this->low;
    }

    /**
     * Setter low
     *
     * @param CCDAIVXB_PPD_TS $low \CCDAIVXB_PPD_TS
     *
     * @return void
     */
    public function setLow($low)
    {
        $this->setOrder("low");
        $this->low = $low;
    }

    /**
     * Getter width
     *
     * @return CCDAPPD_PQ
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Setter width
     *
     * @param CCDAPPD_PQ $width \CCDAPPD_PQ
     *
     * @return void
     */
    public function setWidth($width)
    {
        $this->setOrder("width");
        $this->width = $width;
    }

    /**
     * Get the properties of our class as strings
     *
     * @return array
     */
    function getProps()
    {
        $props = parent::getProps();
        switch ($this->_order) {
            case "low":
                $props["low"]   = $this->propsLow;
                $props["width"] = $this->propsWidth;
                $props["high"]  = $this->propsHigh;
                break;
            case "high":
                $props["high"] = $this->propsHigh;
                break;
            case "width":
                $props["width"] = $this->propsWidth;
                $props["high"]  = $this->propsHigh;
                break;
            case "center":
                $props["center"] = $this->propsCenter;
                $props["width"]  = $this->propsWidth;
                break;
            default:
                $props["low"]    = $this->propsLow;
                $props["width"]  = $this->propsWidth;
                $props["high"]   = $this->propsHigh;
                $props["center"] = $this->propsCenter;
        }

        return $props;
    }
}
