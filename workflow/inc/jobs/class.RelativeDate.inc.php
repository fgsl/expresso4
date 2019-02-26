<?php
/**************************************************************************\
* eGroupWare                                                               *
* http://www.egroupware.org                                                *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

require_once 'class.JobDate.inc.php';

/**
 * Classe para datas relativas ao final do m�s
 * @author Sidnei Augusto Drovetto Junior - drovetto@gmail.com
 * @version 1.0
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 * @package Workflow
 * @subpackage Job
 */
class RelativeDate extends JobDate
{
	/**
	 * @var int $dayOffset O n�mero de dias restantes para o final do m�s (quando o Job deve ser executado)
	 * @access private
	 */
	private $dayOffset;

	/**
	 * Construtor da classe RelativeDate
	 * @param object $startDate A data a partir da qual o Job � v�lido
	 * @param array $interval O intervalo de execu��o do Job
	 * @param int $dayOffset O n�mero de dias restantes para o final do m�s
	 * @return object Objeto da classe RelativeDate
	 * @access public
	 */
	public function RelativeDate($startDate, $interval, $dayOffset = null)
	{
		parent::JobDate($startDate, $interval);
		if (!is_null($dayOffset))
			$this->setOffset($dayOffset);
	}

	/**
	 * Verifica se um Job ser� executado na data fornecida
	 * @param object $checkDate A data que ser� verificada
	 * @return bool True caso o Job deva ser executado e false caso contr�rio
	 * @access public
	 */
	public function checkMatchesInterval($checkDate)
	{
		if ($checkDate->format('G:i') !== $this->startDate->format('G:i'))
			return false;

		$start = ($this->startDate->format('Y') * 12) + $this->startDate->format('n');
		$check = ($checkDate->format('Y') * 12) + $checkDate->format('n');
		if ((($check - $start) % $this->interval['value']) !== 0)
			return false;

		$model = new \DateTime($checkDate->format('Y-n-1 G:i:00'));
		$model->modify('+1 month');
		$model->modify("-{$this->dayOffset} day");

		return (($checkDate->format('Y-n-j G:i:00') == $model->format('Y-n-j G:i:00')) && ($checkDate->format('U') >= $this->startDate->format('U')));
	}

	/**
	 * Define a quantidade de dias restantes para o final do m�s (que � quando o Job deve ser executado)
	 * @param int $dayOffset O n�mero de dias restantes para o final do m�s
	 * @return void
	 * @access public
	 */
	public function setOffset($dayOffset)
	{
		$this->dayOffset = $dayOffset;
	}
}
?>
