#!/usr/bin/php
<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/
/*
 * File Contributed By: Open Source Consulting, S.A.   San Jose, Costa Rica.
 * http://osc.co.cr
 */

if ( PHP_SAPI != 'cli' ) {
	echo "This script can only be called from the Command Line.\n";
	exit;
}


// creates the locale directories for use with gettext
// and also initializes each with a messages.po file.
// Must be run from the i18n tools directory
//

$depth = '../..';

$locales = array(

//	'af_ZA',
//	'am_ET',
	'ar_EG',
//	'as_IN',
//	'az_AZ',
//	'be_BY',
//	'bg_BG',
//	'bn_IN',
//	'bo_CN',
//	'br_FR',
//	'bs_BA',
//	'ca_ES',
//	'ce_RU',
//	'co_FR',
//	'cs_CZ',
//	'cy_GB',
	'da_DK',
	'de_DE',
//	'dz_BT',
//	'el_GR',
	'en_US',
	'es_ES',
//	'et_EE',
//	'fa_IR',
//	'fi_FI',
//	'fj_FJ',
//	'fo_FO',
	'fr_FR',
	'fr_CA',
//	'ga_IE',
//	'gd_GB',
//	'gu_IN',
//	'he_IL',
//	'hi_IN',
//	'hr_HR',
	'hu_HU',
//	'hy_AM',
	'id_ID',
// 'is_IS',
	'it_IT',
//	'ja_JP',
//	'jv_ID',
//	'ka_GE',
//	'kk_KZ',
//	'kl_GL',
//	'km_KH',
//	'kn_IN',
//	'ko_KR',
//	'kok_IN',
//	'lo_LA',
//	'lt_LT',
//	'lv_LV',
//	'mg_MG',
//	'mk_MK',
//	'ml_IN',
//	'mn_MN',
//	'mr_IN',
//	'ms_MY',
//	'mt_MT',
//	'my_MM',
//	'mni_IN',
//	'na_NR',
//	'nb_NO',
//	'ne_NP',
//	'nl_NL',
//	'nn_NO',
//	'no_NO',
//	'oc_FR',
//	'or_IN',
//	'pa_IN',
//	'pl_PL',
//	'ps_AF',
	'pt_PT',
	'pt_BR',
//	'rm_CH',
//	'rn_BI',
//	'ro_RO',
//	'ru_RU',
//	'sa_IN',
//	'sc_IT',
//	'sg_CF',
//	'si_LK',
//	'sk_SK',
//	'sl_SI',
//	'so_SO',
//	'sq_AL',
//	'sr_YU',
//	'sv_SE',
//	'te_IN',
//	'tg_TJ',
//	'th_TH',
//	'tk_TM',
//	'tl_PH',
//	'to_TO',
//	'tr_TR',
//	'uk_UA',
//	'ur_PK',
//	'uz_UZ',
//	'vi_VN',
//	'wa_BE',
//	'wen_DE',
//	'lp_SG',
	'zh_ZH',
	'yi_US',
);

$dir = $depth . '/interface/locale';
chdir( $dir );

foreach( $locales as $locale ) {
	if ( !is_dir( './' . $locale ) ) {
		$cmd = "mkdir $locale && mkdir $locale/LC_MESSAGES && msginit --no-translator -l $locale -o $locale/LC_MESSAGES/messages.po -i messages.pot";
		shell_exec( $cmd );
	}
}
?>
