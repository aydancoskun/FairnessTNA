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
 * $Revision: 2216 $
 * $Id: DatabaseConfig.php 2216 2008-10-31 20:10:18Z ipso $
 * $Date: 2008-10-31 13:10:18 -0700 (Fri, 31 Oct 2008) $
 */
$disable_database_connection = TRUE;
require_once('../../includes/global.inc.php');
//Debug::setVerbosity(11);

$authenticate = FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext($title = '3. Database Configuration')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'data',
												'external_installer',
												) ) );

$install_obj = new Install();
if ( $install_obj->isInstallMode() == FALSE ) {
	Redirect::Page( URLBuilder::getURL(NULL, 'install.php') );
}

//Convert enterprisedb type to postgresql8
if ( isset($data['type']) AND $data['type'] == 'enterprisedb' ) {
	$data['final_type'] = 'postgres8';

	//Check to see if a port was specified or not, if not, default to: 5444
	if ( strpos($data['host'], ':') === FALSE ) {
		$data['final_host'] = $data['host'].':5444';
	} else {
		$data['final_host'] = $data['host'];
	}
} else {
	if ( isset($data['type']) ) {
		$data['final_type'] = $data['type'];
	}
	if ( isset($data['host']) ) {
		$data['final_host'] = $data['host'];
	}
}

$database_engine = TRUE;

if (!$action == 'install_schema' ){
	$action = Misc::findSubmitButton();
}

$action = strtolower($action);
switch ($action) {
	case 'back':
		Debug::Text('Back', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL(NULL, 'Requirements.php') );
		break;
	case 'next':
		Debug::Text('Next', __FILE__, __LINE__, __METHOD__,10);
		//Debug::setVerbosity(11);

		if ( isset($data) AND isset($data['priv_user']) AND isset($data['priv_password'])
			AND $data['priv_user'] != '' AND $data['priv_password'] != '' ) {
			$tmp_user_name = $data['priv_user'];
			$tmp_password = $data['priv_password'];
		} elseif ( isset($data) ) {
			$tmp_user_name = $data['user'];
			$tmp_password = $data['password'];
		}

		$test_db_connection = $install_obj->setNewDatabaseConnection($data['final_type'], $data['final_host'], $tmp_user_name, $tmp_password,'');
		$install_obj->setDatabaseDriver( $data['final_type'] );
		if ( $install_obj->checkDatabaseExists($data['database_name']) == FALSE ) {
			Debug::Text('Creating Database', __FILE__, __LINE__, __METHOD__,10);
			$install_obj->createDatabase( $data['database_name'] );
		}

		//Make sure InnoDB engine exists on MySQL
		if ( $install_obj->getDatabaseType() != 'mysql' OR ( $install_obj->getDatabaseType() == 'mysql' AND $install_obj->checkDatabaseEngine() == TRUE ) ) {
			//Check again to make sure database exists.
			$db_connection = $install_obj->setNewDatabaseConnection($data['final_type'], $data['final_host'], $tmp_user_name, $tmp_password,$data['database_name']);
			if ( $install_obj->checkDatabaseExists($data['database_name']) == TRUE ) {
				//Create SQL
				Debug::Text('yDatabase does exist...', __FILE__, __LINE__, __METHOD__,10);

				$data['type'] = $data['final_type'];
				$data['host'] = $data['final_host'];

				$install_obj->writeConfigFile( $data );

				//Redirect::Page( URLBuilder::getURL( array('action' => 'install_schema'), 'DatabaseSchema.php') );
				Redirect::Page( URLBuilder::getURL( array('external_installer' => $external_installer), 'DatabaseSchema.php') );

				break;
			} else {
				Debug::Text('zDatabase does not exist.', __FILE__, __LINE__, __METHOD__,10);
			}
		} else {
			$database_engine = FALSE;
			Debug::Text('MySQL does not support InnoDB storage engine!', __FILE__, __LINE__, __METHOD__,10);
		}
	default:
		if ( $action == 'test_connection' ) {

			//Test regular user
			//This used to connect to the template1 database, but it seems newer versions of PostgreSQL
			//default to disallow connect privs.
			$test_connection = $install_obj->setNewDatabaseConnection($data['final_type'], $data['final_host'], $data['user'], $data['password'], $data['database_name']);
			if ( $test_connection == TRUE ) {
				$database_exists = $install_obj->checkDatabaseExists($data['database_name']);
			}

			//Test priv user.
			if ( $data['priv_user'] != '' AND $data['priv_password'] != '' ) {
				Debug::Text('Testing connection as priv user', __FILE__, __LINE__, __METHOD__,10);
				$test_priv_connection = $install_obj->setNewDatabaseConnection($data['final_type'], $data['final_host'], $data['priv_user'], $data['priv_password'], '');
			} else {
				$test_priv_connection = TRUE;
			}
		} else {
			$test_connection = NULL;
			$test_priv_connection = NULL;
		}

		$data['test_connection'] = $test_connection;
		$data['test_priv_connection'] = $test_priv_connection;
		$data['database_engine'] = $database_engine;

		//Get DB settings from INI file.
		if ( $action != 'test_connection' ) {
			$data = array(
						'type' => $config_vars['database']['type'],
						'host' => $config_vars['database']['host'],
						'database_name' => $config_vars['database']['database_name'],
						'user' => $config_vars['database']['user'],
						'password' => $config_vars['database']['password'],
						'test_connection' => $test_connection,
						'test_priv_connection' => $test_priv_connection,
						'database_engine' => $database_engine,
						);
		}

		$data['type_options'] = $install_obj->getDatabaseTypeArray();

		$smarty->assign_by_ref('data', $data);
		break;
}

if ( !isset($data['priv_user']) ) {
	$data['priv_user'] = NULL;
}

$smarty->assign_by_ref('install_obj', $install_obj);
$smarty->assign_by_ref('external_installer', $external_installer);
$smarty->display('install/DatabaseConfig.tpl');
?>
