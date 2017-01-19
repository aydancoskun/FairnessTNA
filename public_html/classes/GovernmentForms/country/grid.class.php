<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


include_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'GovernmentForms_Base.class.php' );

/**
 * @package GovernmentForms
 */
class GovernmentForms_grid extends GovernmentForms_Base {

	public $pdf_template_pages = 1;

	public $grid_width = 20;
	public $grid_height = 10;

	public function getFilterFunction( $name ) {
		return FALSE;
	}

	function getTemplate() {
		return $this->pdf_template;
	}
	function setTemplate( $value ) {
		$this->pdf_template = $value;
		return TRUE;
	}

	function getTemplatePages() {
		return $this->pdf_template_pages;
	}
	function setTemplatePages( $value ) {
		$this->pdf_template_pages = $value;
		return TRUE;
	}

	function _outputPDF() {
		//Initialize PDF with template.
		$pdf = $this->getPDFObject();


		if ( $this->getShowBackground() == TRUE AND $this->getTemplate() != '' ) {
			$pdf->setSourceFile( $this->getTemplate() );

			for( $i=1; $i <= $this->getTemplatePages(); $i++ ) {
				$this->template_index[$i] = $pdf->ImportPage($i);
			}
		}

		$pdf->AddPage();

		if ( isset($this->template_index[1]) ) {
			$pdf->useTemplate( $this->template_index[1], $this->getTemplateOffsets('x'), $this->getTemplateOffsets('y') );
		}


		$pdf->SetFont( $this->default_font, '', 4 );

		//Red
		//$pdf->SetTextColor( 255, 0, 0 );
		//$pdf->setDrawColor( 255, 0, 0 );

		//Blue
		$pdf->SetTextColor( 0, 0, 255 );
		$pdf->setDrawColor( 0, 0, 255 );

		//Draw grid.
		$continue = TRUE;
		$i=0;

		$x=0;
		$y=0;
		$page = 1;
		while( $continue AND $i < 1000000 ) {
			$pdf->setXY( $x, $y );
			$pdf->Cell( $this->grid_width, $this->grid_height, $x . 'x' . $y , 1, 0, 'L', 0 );

			$x = $x + $this->grid_width;
			if ( $x > $pdf->getPageWidth() ) {
				$x = 0;
				$y = $y + $this->grid_height;
			}

			if ( $y > $pdf->getPageHeight() AND $page < $this->getTemplatePages() ) {
				$page++;

				$pdf->AddPage();
				$pdf->useTemplate( $this->template_index[$page], $this->getTemplateOffsets('x'), $this->getTemplateOffsets('y') );

				$x = 0;
				$y = 0;
			} elseif ( $y > $pdf->getPageHeight() AND $page == $this->getTemplatePages() ) {
				$continue = FALSE;
				break;
			}

			$i++;
		}

		return TRUE;
	}
}
?>