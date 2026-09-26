<?php

$_page['key'] = 'reservations_account_arhiv';

class reservation_account_arhive_admin {

	function start () {
		$this->r = getEngine ('Engines', false, true);

		switch ($_GET['action']) {
			case 'saveConfig':
				return $this->viewArhive ();
				break;
			case 'viewaccounts':
				return $this->viewAccounts ();
				break;
			default:
				return $this->getArhive ();
		}
	}

	function viewArhive () {
		if (isset ($_POST['max_forward_reservation_days_count']) && isset ($_POST['min_rejection_days_count']) && isset ($_POST['admin_email'])  && isset ($_POST['notify_email']) && isset ($_POST['email_subject_prefix'])) {
			$this->r->setConfig ((int)$_POST['max_forward_reservation_days_count'], (int)$_POST['min_rejection_days_count'], $_POST['admin_email'], $_POST['notify_email'], $_POST['email_subject_prefix'], (int)isset($_POST['order_notify']));
			$this->message = 'Die Daten wurden aktualisiert';
		}
		return $this->getValuesList ();
	}

	//список отчетов
	function viewAccounts() {
		$output = array ();
		
		if(isset($_GET['client_id']))
			$client_id = (int)$_GET['client_id'];
		else
			return $this->getArhive ();
		
		if(isset($_GET['year']))
			$year = (int)$_GET['year'];
		
		if($years = $this->r->account->getListYearByClient ($client_id))
		{
			$out .= '<p align="center">';
			$s = '';
			foreach($years as $y)
			{
				if(!isset($year)) $year = $y['lyear'];
				if($year == $y['lyear'])
					$out .= $s.$y['lyear'];
				else	
					$out .= $s.'<a href="'.$_SERVER['PHP_SELF'].'?action=viewaccounts&client_id='.$client_id.'&year='.$y['lyear'].'">'.$y['lyear'] . '</a>';
				$s = ' | ';
			}
			$out .= '</p>';
		}
				
		if($accounts=$this->r->account->getAccountsByClient($client_id, $year))
		{
			$out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
			$out .= '<tr>';
			$out .= '<th>Datum</th>'. "\n";
			$out .= '<th>Rechnungen</th></tr>' . "\n";

			foreach($accounts as $a)
			{
				//строка таблицы
					$out .= '<tr>';
					$out .= '<td class="light">'.date('d.m.Y', strtotime($a['date_finish'])).'</td>';
					$out .= '<td class="light"><a href="reservations_account.php?year='.date('Y', strtotime($a['date_finish'])).'&month='.date('m', strtotime($a['date_finish'])).'&client_id='.$client_id.'" target="_blank">Rechnung №' . sprintf("%06d", $a['account_id']) . '</a></td>';
					$out .= '</tr>' . "\n";
			}
			$out .= "</table>\n<br>\n";
		
		}
		else
		{
		$out .= "<p align=\"center\">Keine Rechnungen vorhanden!</p>\n<br>\n";
		}
		$out .= "<p align=\"center\"><a href=\"".$_SERVER['PHP_SELF']."\">Zur&uuml;ck</a></p>\n<br>\n";
		$output[] = $out;

		return $output;
	}


	//список клиентов
	function getArhive() {
		$output = array ();

		if($clients=$this->r->account->getClientsWithAccount())
		{
			$out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
			$out .= '<tr>';
			$out .= '<th>Kundenliste</th></tr>' . "\n";

			foreach($clients as $c)
			{
				//строка таблицы
					$out .= '<tr>';
					$out .= '<td class="light"><a href="'.$_SERVER['PHP_SELF'].'?action=viewaccounts&client_id='.$c['client_id'].'">' . $c['name'] . ' ' . $c['surname'] . '</a></td>';
					$out .= '</tr>' . "\n";
			}
			$out .= "</table>\n<br>\n";
		}
		else
		{
		$out .= "<p align=\"center\">Keine Rechnungen vorhanden!</p>\n<br>\n";
		}
		$output[] = $out;

		return $output;
	}
}

$a = new reservation_account_arhive_admin;
$_page['content'] = $a->start ();

