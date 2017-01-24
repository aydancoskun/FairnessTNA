<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
//
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Piotr Klaban <makler@man.torun.pl>                          |
// +----------------------------------------------------------------------+
//
// $Id: lang.pl.php,v 1.4 2005/09/18 19:52:22 makler Exp $
//
// Numbers_Words class extension to spell numbers in Polish.
//

/**
 * Class for translating numbers into Polish.
 *
 * @author Piotr Klaban
 * @package Numbers_Words
 */

/**
 * Include needed files
 */
require_once("Numbers/Words.php");

/**
 * Class for translating numbers into Polish.
 *
 * @author Piotr Klaban
 * @package Numbers_Words
 */
class Numbers_Words_pl extends Numbers_Words
{

    // {{{ properties

    /**
     * Locale name
     * @var string
     * @access public
     */
    public $locale = 'pl';

    /**
     * Language name in English
     * @var string
     * @access public
     */
    public $lang = 'Polish';

    /**
     * Native language name
     * @var string
     * @access public
     */
    public $lang_native = 'polski';

    /**
     * The word for the minus sign
     * @var string
     * @access private
     */
    public $_minus = 'minus'; // minus sign

    /**
     * The sufixes for exponents (singular and plural)
     * Names based on:
     * mathematical tables, my memory, and also:
     * http://ux1.math.us.edu.pl/~szyjewski/FAQ/liczby/iony.htm
     * @var array
     * @access private
     */
    public $_exponent = array(
        // pot�ga dziesi�tki => liczba pojedyncza, podw�jna, mnoga
        0 => array('', '', ''),
        3 => array('tysi�c', 'tysi�ce', 'tysi�cy'),
        6 => array('milion', 'miliony', 'milion�w'),
        9 => array('miliard', 'miliardy', 'miliard�w'),
        12 => array('bilion', 'biliony', 'bilion�w'),
        15 => array('biliard', 'biliardy', 'biliard�w'),
        18 => array('trylion', 'tryliony', 'trylion�w'),
        21 => array('tryliard', 'tryliardy', 'tryliard�w'),
        24 => array('kwadrylion', 'kwadryliony', 'kwadrylion�w'),
        27 => array('kwadryliard', 'kwadryliardy', 'kwadryliard�w'),
        30 => array('kwintylion', 'kwintyliony', 'kwintylion�w'),
        33 => array('kwintyliiard', 'kwintyliardy', 'kwintyliard�w'),
        36 => array('sekstylion', 'sekstyliony', 'sekstylion�w'),
        39 => array('sekstyliard', 'sekstyliardy', 'sekstyliard�w'),
        42 => array('septylion', 'septyliony', 'septylion�w'),
        45 => array('septyliard', 'septyliardy', 'septyliard�w'),
        48 => array('oktylion', 'oktyliony', 'oktylion�w'),
        51 => array('oktyliard', 'oktyliardy', 'oktyliard�w'),
        54 => array('nonylion', 'nonyliony', 'nonylion�w'),
        57 => array('nonyliard', 'nonyliardy', 'nonyliard�w'),
        60 => array('decylion', 'decyliony', 'decylion�w'),
        63 => array('decyliard', 'decyliardy', 'decyliard�w'),
        100 => array('centylion', 'centyliony', 'centylion�w'),
        103 => array('centyliard', 'centyliardy', 'centyliard�w'),
        120 => array('wicylion', 'wicylion', 'wicylion'),
        123 => array('wicyliard', 'wicyliardy', 'wicyliard�w'),
        180 => array('trycylion', 'trycylion', 'trycylion'),
        183 => array('trycyliard', 'trycyliardy', 'trycyliard�w'),
        240 => array('kwadragilion', 'kwadragilion', 'kwadragilion'),
        243 => array('kwadragiliard', 'kwadragiliardy', 'kwadragiliard�w'),
        300 => array('kwinkwagilion', 'kwinkwagilion', 'kwinkwagilion'),
        303 => array('kwinkwagiliard', 'kwinkwagiliardy', 'kwinkwagiliard�w'),
        360 => array('seskwilion', 'seskwilion', 'seskwilion'),
        363 => array('seskwiliard', 'seskwiliardy', 'seskwiliard�w'),
        420 => array('septagilion', 'septagilion', 'septagilion'),
        423 => array('septagiliard', 'septagiliardy', 'septagiliard�w'),
        480 => array('oktogilion', 'oktogilion', 'oktogilion'),
        483 => array('oktogiliard', 'oktogiliardy', 'oktogiliard�w'),
        540 => array('nonagilion', 'nonagilion', 'nonagilion'),
        543 => array('nonagiliard', 'nonagiliardy', 'nonagiliard�w'),
        600 => array('centylion', 'centyliony', 'centylion�w'),
        603 => array('centyliard', 'centyliardy', 'centyliard�w'),
        6000018 => array('milinilitrylion', 'milinilitryliony', 'milinilitrylion�w')
    );

    /**
     * The array containing the digits (indexed by the digits themselves).
     * @var array
     * @access private
     */
    public $_digits = array(
        0 => 'zero', 'jeden', 'dwa', 'trzy', 'cztery',
        'pi��', 'sze��', 'siedem', 'osiem', 'dziewi��'
    );

    /**
     * The word separator
     * @var string
     * @access private
     */
    public $_sep = ' ';

    /**
     * The currency names (based on the below links,
     * informations from central bank websites and on encyclopedias)
     *
     * @var array
     * @link http://www.xe.com/iso4217.htm Currency codes
     * @link http://www.republika.pl/geographia/peuropy.htm Europe review
     * @link http://pieniadz.hoga.pl/waluty_objasnienia.asp Currency service
     * @access private
     */
    public $_currency_names = array(
        'ALL' => array(array('lek', 'leki', 'lek�w'), array('quindarka', 'quindarki', 'quindarek')),
        'AUD' => array(array('dolar australijski', 'dolary australijskie', 'dolar�w australijskich'), array('cent', 'centy', 'cent�w')),
        'BAM' => array(array('marka', 'marki', 'marek'), array('fenig', 'fenigi', 'fenig�w')),
        'BGN' => array(array('lew', 'lewy', 'lew'), array('stotinka', 'stotinki', 'stotinek')),
        'BRL' => array(array('real', 'reale', 'real�w'), array('centavos', 'centavos', 'centavos')),
        'BYR' => array(array('rubel', 'ruble', 'rubli'), array('kopiejka', 'kopiejki', 'kopiejek')),
        'CAD' => array(array('dolar kanadyjski', 'dolary kanadyjskie', 'dolar�w kanadyjskich'), array('cent', 'centy', 'cent�w')),
        'CHF' => array(array('frank szwajcarski', 'franki szwajcarskie', 'frank�w szwajcarskich'), array('rapp', 'rappy', 'rapp�w')),
        'CYP' => array(array('funt cypryjski', 'funty cypryjskie', 'funt�w cypryjskich'), array('cent', 'centy', 'cent�w')),
        'CZK' => array(array('korona czeska', 'korony czeskie', 'koron czeskich'), array('halerz', 'halerze', 'halerzy')),
        'DKK' => array(array('korona du�ska', 'korony du�skie', 'koron du�skich'), array('ore', 'ore', 'ore')),
        'EEK' => array(array('korona esto�ska', 'korony esto�skie', 'koron esto�skich'), array('senti', 'senti', 'senti')),
        'EUR' => array(array('euro', 'euro', 'euro'), array('eurocent', 'eurocenty', 'eurocent�w')),
        'GBP' => array(array('funt szterling', 'funty szterlingi', 'funt�w szterling�w'), array('pens', 'pensy', 'pens�w')),
        'HKD' => array(array('dolar Hongkongu', 'dolary Hongkongu', 'dolar�w Hongkongu'), array('cent', 'centy', 'cent�w')),
        'HRK' => array(array('kuna', 'kuny', 'kun'), array('lipa', 'lipy', 'lip')),
        'HUF' => array(array('forint', 'forinty', 'forint�w'), array('filler', 'fillery', 'filler�w')),
        'ILS' => array(array('nowy szekel', 'nowe szekele', 'nowych szekeli'), array('agora', 'agory', 'agorot')),
        'ISK' => array(array('korona islandzka', 'korony islandzkie', 'koron islandzkich'), array('aurar', 'aurar', 'aurar')),
        'JPY' => array(array('jen', 'jeny', 'jen�w'), array('sen', 'seny', 'sen�w')),
        'LTL' => array(array('lit', 'lity', 'lit�w'), array('cent', 'centy', 'cent�w')),
        'LVL' => array(array('�at', '�aty', '�at�w'), array('sentim', 'sentimy', 'sentim�w')),
        'MKD' => array(array('denar', 'denary', 'denar�w'), array('deni', 'deni', 'deni')),
        'MTL' => array(array('lira malta�ska', 'liry malta�skie', 'lir malta�skich'), array('centym', 'centymy', 'centym�w')),
        'NOK' => array(array('korona norweska', 'korony norweskie', 'koron norweskich'), array('oere', 'oere', 'oere')),
        'PLN' => array(array('z�oty', 'z�ote', 'z�otych'), array('grosz', 'grosze', 'groszy')),
        'ROL' => array(array('lej', 'leje', 'lei'), array('bani', 'bani', 'bani')),
        'RUB' => array(array('rubel', 'ruble', 'rubli'), array('kopiejka', 'kopiejki', 'kopiejek')),
        'SEK' => array(array('korona szwedzka', 'korony szwedzkie', 'koron szweckich'), array('oere', 'oere', 'oere')),
        'SIT' => array(array('tolar', 'tolary', 'tolar�w'), array('stotinia', 'stotinie', 'stotini')),
        'SKK' => array(array('korona s�owacka', 'korony s�owackie', 'koron s�owackich'), array('halerz', 'halerze', 'halerzy')),
        'TRL' => array(array('lira turecka', 'liry tureckie', 'lir tureckich'), array('kurusza', 'kurysze', 'kuruszy')),
        'UAH' => array(array('hrywna', 'hrywna', 'hrywna'), array('cent', 'centy', 'cent�w')),
        'USD' => array(array('dolar', 'dolary', 'dolar�w'), array('cent', 'centy', 'cent�w')),
        'YUM' => array(array('dinar', 'dinary', 'dinar�w'), array('para', 'para', 'para')),
        'ZAR' => array(array('rand', 'randy', 'rand�w'), array('cent', 'centy', 'cent�w'))
    );

    /**
     * The default currency name
     * @var string
     * @access public
     */
    public $def_currency = 'PLN'; // Polish zloty

    // }}}
    // {{{ toWords()

    /**
     * Converts a currency value to its word representation
     * (with monetary units) in Polish language
     *
     * @param  integer $int_curr An international currency symbol
     *                 as defined by the ISO 4217 standard (three characters)
     * @param  integer $decimal A money total amount without fraction part (e.g. amount of dollars)
     * @param  integer $fraction Fractional part of the money amount (e.g. amount of cents)
     *                 Optional. Defaults to false.
     * @param  integer $convert_fraction Convert fraction to words (left as numeric if set to false).
     *                 Optional. Defaults to true.
     *
     * @return string  The corresponding word representation for the currency
     *
     * @access public
     * @author Piotr Klaban <makler@man.torun.pl>
     * @since  Numbers_Words 0.4
     */
    public function toCurrencyWords($int_curr, $decimal, $fraction = false, $convert_fraction = true)
    {
        $int_curr = strtoupper($int_curr);
        if (!isset($this->_currency_names[$int_curr])) {
            $int_curr = $this->def_currency;
        }
        $curr_names = $this->_currency_names[$int_curr];
        $ret = trim($this->toWords($decimal));
        $lev = $this->_get_numlevel($decimal);
        $ret .= $this->_sep . $curr_names[0][$lev];

        if ($fraction !== false) {
            if ($convert_fraction) {
                $ret .= $this->_sep . trim($this->toWords($fraction));
            } else {
                $ret .= $this->_sep . $fraction;
            }
            $lev = $this->_get_numlevel($fraction);
            $ret .= $this->_sep . $curr_names[1][$lev];
        }
        return $ret;
    }
    // }}}
    // {{{ toCurrencyWords()

    /**
     * Converts a number to its word representation
     * in Polish language
     *
     * @param  integer $num An integer between -infinity and infinity inclusive :)
     *                        that need to be converted to words
     * @param  integer $power The power of ten for the rest of the number to the right.
     *                        Optional, defaults to 0.
     * @param  integer $powsuffix The power name to be added to the end of the return string.
     *                        Used internally. Optional, defaults to ''.
     *
     * @return string  The corresponding word representation
     *
     * @access public
     * @author Piotr Klaban <makler@man.torun.pl>
     * @since  PHP 4.2.3
     */
    public function toWords($num, $power = 0, $powsuffix = '')
    {
        $ret = '';

        // add a minus sign
        if (substr($num, 0, 1) == '-') {
            $ret = $this->_sep . $this->_minus;
            $num = substr($num, 1);
        }

        // strip excessive zero signs and spaces
        $num = trim($num);
        $num = preg_replace('/^0+/', '', $num);

        if (strlen($num) > 3) {
            $maxp = strlen($num) - 1;
            $curp = $maxp;
            for ($p = $maxp; $p > 0; --$p) { // power

                // check for highest power
                if (isset($this->_exponent[$p])) {
                    // send substr from $curp to $p
                    $snum = substr($num, $maxp - $curp, $curp - $p + 1);
                    $snum = preg_replace('/^0+/', '', $snum);
                    if ($snum !== '') {
                        $cursuffix = $this->_exponent[$power][count($this->_exponent[$power]) - 1];
                        if ($powsuffix != '') {
                            $cursuffix .= $this->_sep . $powsuffix;
                        }
                        $ret .= $this->toWords($snum, $p, $cursuffix);
                    }
                    $curp = $p - 1;
                    continue;
                }
            }
            $num = substr($num, $maxp - $curp, $curp - $p + 1);
            if ($num == 0) {
                return $ret;
            }
        } elseif ($num == 0 || $num == '') {
            return $this->_sep . $this->_digits[0];
        }

        $h = $t = $d = 0;

        switch (strlen($num)) {
            case 3:
                $h = (int)substr($num, -3, 1);

            case 2:
                $t = (int)substr($num, -2, 1);

            case 1:
                $d = (int)substr($num, -1, 1);
                break;

            case 0:
                return;
                break;
        }

        switch ($h) {
            case 9:
                $ret .= $this->_sep . 'dziewi��set';
                break;

            case 8:
                $ret .= $this->_sep . 'osiemset';
                break;

            case 7:
                $ret .= $this->_sep . 'siedemset';
                break;

            case 6:
                $ret .= $this->_sep . 'sze��set';
                break;

            case 5:
                $ret .= $this->_sep . 'pi��set';
                break;

            case 4:
                $ret .= $this->_sep . 'czterysta';
                break;

            case 3:
                $ret .= $this->_sep . 'trzysta';
                break;

            case 2:
                $ret .= $this->_sep . 'dwie�cie';
                break;

            case 1:
                $ret .= $this->_sep . 'sto';
                break;
        }

        switch ($t) {
            case 9:
            case 8:
            case 7:
            case 6:
            case 5:
                $ret .= $this->_sep . $this->_digits[$t] . 'dziesi�t';
                break;

            case 4:
                $ret .= $this->_sep . 'czterdzie�ci';
                break;

            case 3:
                $ret .= $this->_sep . 'trzydzie�ci';
                break;

            case 2:
                $ret .= $this->_sep . 'dwadzie�cia';
                break;

            case 1:
                switch ($d) {
                    case 0:
                        $ret .= $this->_sep . 'dziesi��';
                        break;

                    case 1:
                        $ret .= $this->_sep . 'jedena�cie';
                        break;

                    case 2:
                    case 3:
                    case 7:
                    case 8:
                        $ret .= $this->_sep . $this->_digits[$d] . 'na�cie';
                        break;

                    case 4:
                        $ret .= $this->_sep . 'czterna�cie';
                        break;

                    case 5:
                        $ret .= $this->_sep . 'pi�tna�cie';
                        break;

                    case 6:
                        $ret .= $this->_sep . 'szesna�cie';
                        break;

                    case 9:
                        $ret .= $this->_sep . 'dziewi�tna�cie';
                        break;
                }
                break;
        }

        if ($t != 1 && $d > 0) {
            $ret .= $this->_sep . $this->_digits[$d];
        }

        if ($t == 1) {
            $d = 0;
        }

        if (($h + $t) > 0 && $d == 1) {
            $d = 0;
        }

        if ($power > 0) {
            if (isset($this->_exponent[$power])) {
                $lev = $this->_exponent[$power];
            }

            if (!isset($lev) || !is_array($lev)) {
                return null;
            }

            switch ($d) {
                case 1:
                    $suf = $lev[0];
                    break;
                case 2:
                case 3:
                case 4:
                    $suf = $lev[1];
                    break;
                case 0:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    $suf = $lev[2];
                    break;
            }
            $ret .= $this->_sep . $suf;
        }

        if ($powsuffix != '') {
            $ret .= $this->_sep . $powsuffix;
        }

        return $ret;
    }
    // }}}
    // {{{ _get_numlevel()

    /**
     * Returns grammatical "level" of the number - this is necessary
     * for choosing the right suffix for exponents and currency names.
     *
     * @param  integer $num An integer between -infinity and infinity inclusive
     *                        that need to be converted to words
     *
     * @return integer  The grammatical "level" of the number.
     *
     * @access private
     * @author Piotr Klaban <makler@man.torun.pl>
     * @since  Numbers_Words 0.4
     */
    public function _get_numlevel($num)
    {
        $num = (int)substr($num, -3);
        $h = $t = $d = $lev = 0;

        switch (strlen($num)) {
            case 3:
                $h = (int)substr($num, -3, 1);

            case 2:
                $t = (int)substr($num, -2, 1);

            case 1:
                $d = (int)substr($num, -1, 1);
                break;

            case 0:
                return $lev;
                break;
        }
        if ($t == 1) {
            $d = 0;
        }

        if (($h + $t) > 0 && $d == 1) {
            $d = 0;
        }

        switch ($d) {
            case 1:
                $lev = 0;
                break;
            case 2:
            case 3:
            case 4:
                $lev = 1;
                break;
            default:
                $lev = 2;
        }
        return $lev;
    }
    // }}}
}
