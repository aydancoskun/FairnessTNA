<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
  ********************************************************************************/
/*
 * $Revision: 1246 $
 * $Id: barcode_img.php 1246 2007-09-14 23:47:42Z ipso $
 * $Date: 2007-09-14 16:47:42 -0700 (Fri, 14 Sep 2007) $
 */
//require_once("Image/Barcode.php");
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'Image_Barcode'. DIRECTORY_SEPARATOR .'Barcode.php');
$num = isset($_REQUEST['num']) ? $_REQUEST['num'] : '1010101';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'code128';
//$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Code39';
$imgtype = isset($_REQUEST['imgtype']) ? $_REQUEST['imgtype'] : 'png';

Image_Barcode::draw($num, $type, $imgtype);
?>