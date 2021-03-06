<?php
class ReportingRepo{

	public $predefinedBranches;
	public $predefinedReferBy;
	public $predefinedServices;

	public $predefinedBranchesAND;
	public $predefinedBranchesOR;
	public $predefinedReferByAND;
	public $predefinedReferByOR;
	public $predefinedServicesAND;
	public $predefinedServicesByOR;

	function __construct()
	{
		$this->predefinedBranches = array('Escandon', 'San Angel', 'San Jeronimo',);
		$this->predefinedReferBy = array('Google', 'Recomendación', 'Youtube', 'Bing', 'Otro - especificar:','Publicidad exterior');
		$this->predefinedServices = array('Web Cams', 'Estimulacion', 'Maternales', 'Lactantes', 'Express 2', 'Guarderia', 'Ingles');
	}

	public function getPredefinedValues($type, $operator)
	{
		$str = '';
		if($type == 'branch_office')
		{
			$arr = $this->predefinedBranches;
		}
		else if($type == 'refer_by')
		{
			$arr = $this->predefinedReferBy;
		}
		else if($type == 'service')
		{
			$arr = $this->predefinedServices;
		}

		foreach ($arr as $key => $value) 
		{
			$str .= $type." = '".$value."' ".$operator.' ';
		}

		$str = trim($str, ' || ');
		$str = trim($str, ' AND ');
		return $str;
	}

	public function getReporting($request)
	{
		$this->predefinedBranchesAND = $this->getPredefinedValues('branch_office', 'AND');
		$this->predefinedBranchesOR = $this->getPredefinedValues('branch_office', '||');
		$this->predefinedReferByAND = $this->getPredefinedValues('refer_by', 'AND');
		$this->predefinedReferByOR = $this->getPredefinedValues('refer_by', '||');
		$this->predefinedServicesAND = $this->getPredefinedValues('service', 'AND');
		$this->predefinedServicesOR = $this->getPredefinedValues('service', '||');

		$finalData = array();
		$yearsArr = range(2011	, date('Y'));
		if(!empty($yearsArr))
		{
			foreach ($yearsArr as $key => $year) 
			{
				$day = new DateTime('first day of this month');
				$monthStart =  date('m-d', strtotime($day->format('Y-m-d')));
				$curDay = date('m-d');
				$monthStart = $year.'-'.$monthStart;
				$curDay = $year.'-'.$curDay;

				$sql = "SELECT  DATE(date_created) date, COUNT(DISTINCT id) downloads FROM queries where YEAR(date_created)='".$year."' AND DATE(date_created) between '".$monthStart."' AND  '".$curDay."' GROUP   BY  DATE(date_created) ";
				$sth = $GLOBALS['pdo']->query($sql);
				$sth->setFetchMode(PDO::FETCH_ASSOC);
				$finalData[$key]['name'] = $year;
				while($row = $sth->fetch()) 
				{
					$datePart = date('m-d', strtotime($row['date']));
					$datePart = strtotime('2012-' . $datePart) * 1000;
					$finalData[$key]['data'][] = array($datePart, (int) $row['downloads'], $row['date']);
				}
			}
		}
	
		// current month
		$day = new DateTime('first day of this month');
		$toLowerDate =  date('Y-m-d', strtotime($day->format('Y-m-d')));
		$toUpperDate =  date('Y-m-d');
		
		$curStartMonth = $this->getFormattedDate($toLowerDate);
		$curTodayMonth = $this->getFormattedDate($toUpperDate);

		$day = new DateTime('first day of last month');
		$fromLowerDate =  date('Y-m-d', strtotime($day->format('Y-m-d')));
		$fromUpperDate =  date('Y-m-d', strtotime("-1 month"));

		$lastMonthStart = $this->getFormattedDate($fromLowerDate);
		$lastMonthToday = $this->getFormattedDate($fromUpperDate);

		// $fromLowerDate = '2012-01-01';
		// $fromUpperDate = '2012-12-01';
		// $toLowerDate = '2013-01-01';
		// $toUpperDate = '2014-12-01';

		$curMonthcomparison = $this->getDownloadComparison($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate);
		$monthlyBranchComparison = $this->getDownloadComparisonByType($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate, 'branch_office');
		$monthlyReferbyComparison = $this->getDownloadComparisonByType($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate, 'refer_by');
		$monthlyBranchReferrals = $this->getComparisonByBranchReferrals($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate);

		$firstDayOfYear =  date('Y').'-01-01';
		$otherBranches = $this->getOtherByType($firstDayOfYear, date('Y-m-d'), 'branch_office');
		$otherReferBy = $this->getOtherByType($firstDayOfYear, date('Y-m-d'), 'refer_by');
		$otherServices = $this->getOtherByType($firstDayOfYear, date('Y-m-d'), 'service');//$toLowerDate
		$monthlyBrnachServicesComparison = $this->getComparisonByBranchServices($firstDayOfYear, date('Y-m-d'), '', '');


		// current month
		$day = new DateTime('first day of this month');
		$toLowerDate =  date('Y-m-d', strtotime($day->format('Y-m-d')));
		$toUpperDate =  date('Y-m-d');

		// current year
 		$fromUpperDate =  date('Y-m-d', strtotime("-1 year"));
		$day = new DateTime($fromUpperDate);
		$day =  $day->modify('first day of this month');
		$fromLowerDate = $day->format('Y-m-d');
		$lastYearStart = $this->getFormattedDate($fromLowerDate);
		$lastYearToday = $this->getFormattedDate($fromUpperDate);

		$yearcomparison = $this->getDownloadComparison($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate);
		$yearlyBranchComparison = $this->getDownloadComparisonByType($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate, 'branch_office');
		$yearlyReferbyComparison = $this->getDownloadComparisonByType($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate, 'refer_by');
		$yearlyBranchReferrals = $this->getComparisonByBranchReferrals($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate);


		return array('chart' => $finalData,
					 'cur_month' => $curMonthcomparison, 
					 'last_year' => $yearcomparison, 
					 'cur_month_branch' => $monthlyBranchComparison,
					 'cur_year_branch' => $yearlyBranchComparison,					 
					 'cur_month_referby' => $monthlyReferbyComparison,
					 'cur_year_referby' => $yearlyReferbyComparison,
					 'cur_from' => $curStartMonth,
					 'cur_to' => $curTodayMonth,
					 'last_from' => $lastMonthStart,
					 'last_to' => $lastMonthToday,
					 'last_year_from' => $lastYearStart,
					 'last_year_to' => $lastYearToday,
					 'other_branches' => $otherBranches,
					 'other_refer_by' => $otherReferBy,
					 'other_services' => $otherServices,
					 'services_comparison' => $monthlyBrnachServicesComparison,
					 'monthly_brnach_referrals' => $monthlyBranchReferrals,
					 'yearly_brnach_referrals' => $yearlyBranchReferrals,
					 );
		// print_r($yearsArr);
	}

	public function getComparisonByBranchServices($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate)
	{
		$previousMonthBranches = array();
		$currentMonthBranches = array();
		$sql = "SELECT distinct branch_office from queries as q where DATE(q.date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."'  AND (".str_replace('branch_office', 'q.branch_office', $this->predefinedBranchesOR).") group by q.branch_office";
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		while ($row = $sth->fetch()) {
			$sql2 = "SELECT count(s.service) as service_count, s.service from queries as q, services as s where q.id = s. query_id AND q.branch_office = '".$row['branch_office']."' AND DATE(q.date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."' AND (".str_replace('service', 's.service', $this->predefinedServicesOR).") group by s.service";
			$sth2 = $GLOBALS['pdo']->query($sql2);
			$sth2->setFetchMode(PDO::FETCH_ASSOC);
			while ($row2 = $sth2->fetch()) 
			{
				if(!isset($previousMonthBranches[$row['branch_office']]))
					$previousMonthBranches[$row['branch_office']] = array();
				$previousMonthBranches[$row['branch_office']][$row2['service']] = $row2['service_count'];
			}
		}


		$finalData = array();
		if(!empty($previousMonthBranches))
		{
			$key = 1;
			foreach ($previousMonthBranches as $branch => $services) {
				$finalData[$branch]  = array();
				if(!empty($services))
				{
					foreach ($services as $service => $counts) 
					{
						$finalData[$branch][] = array('name' => utf8_encode($service), 'y' => (int) $counts);
					}
				}
				
				++$key;

			}
		}

		return $finalData;
	}

	public function getComparisonByBranchReferrals($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate)
	{
		$allBranches = $this->predefinedBranches;
		$previousMonthBranches = array();
		$currentMonthBranches = array();
		$sql = "SELECT distinct branch_office from queries as q where DATE(q.date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."'  AND (".$this->predefinedBranchesOR.") group by q.branch_office";
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		while ($row = $sth->fetch()) {
			$sql2 = "SELECT count(q.refer_by) as refer_by_count, q.refer_by from queries as q where q.branch_office = '".$row['branch_office']."' AND DATE(q.date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."' AND (".$this->predefinedReferByOR.") group by q.refer_by";
			$sth2 = $GLOBALS['pdo']->query($sql2);
			$sth2->setFetchMode(PDO::FETCH_ASSOC);
			while ($row2 = $sth2->fetch()) 
			{
				if(!isset($previousMonthBranches[$row['branch_office']]))
					$previousMonthBranches[$row['branch_office']] = array();
				$previousMonthBranches[$row['branch_office']][strtolower($row2['refer_by'])] = $row2['refer_by_count'];
			}
		}


		$sql = "SELECT distinct branch_office from queries as q where DATE(q.date_created) between '".$toLowerDate."' AND  '".$toUpperDate."'  AND (".$this->predefinedBranchesOR.") group by q.branch_office";
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		while ($row = $sth->fetch()) {
			$sql2 = "SELECT count(q.refer_by) as refer_by_count, q.refer_by from queries as q where q.branch_office = '".$row['branch_office']."' AND DATE(q.date_created) between '".$toLowerDate."' AND  '".$toUpperDate."' AND (".$this->predefinedReferByOR.") group by q.refer_by";
			$sth2 = $GLOBALS['pdo']->query($sql2);
			$sth2->setFetchMode(PDO::FETCH_ASSOC);
			while ($row2 = $sth2->fetch()) 
			{
				if(!isset($currentMonthBranches[$row['branch_office']]))
					$currentMonthBranches[$row['branch_office']] = array();
				$currentMonthBranches[$row['branch_office']][strtolower($row2['refer_by'])] = $row2['refer_by_count'];
			}
		}

		foreach ($allBranches as $key => $singleBranch) 
		{
			if(!isset($previousMonthBranches[$singleBranch]))
				$previousMonthBranches[$singleBranch] = array();
			if(!isset($currentMonthBranches[$singleBranch]))
				$currentMonthBranches[$singleBranch] = array();			
		}



		$uniqueNetworks = array();
		if(!empty($currentMonthBranches))
		{
			foreach ($currentMonthBranches as $singleBranch => $currentMonthBranch) {
				if(!empty($currentMonthBranch))
				{
					foreach ($currentMonthBranch as $referBy => $downloads) {
						if(!in_array($referBy, $uniqueNetworks))
							$uniqueNetworks[] = strtolower($referBy);
					}
				}
			}
		}
		if(!empty($previousMonthBranches))
		{
			foreach ($previousMonthBranches as $singleBranch => $currentMonthBranch) {
				if(!empty($currentMonthBranch))
				{
					foreach ($currentMonthBranch as $referBy => $downloads) {
						if(!in_array(strtolower($referBy), $uniqueNetworks))
							$uniqueNetworks[] = $referBy;
					}
				}
			}
		}

		if(!empty($uniqueNetworks))
		{
			foreach ($uniqueNetworks as $key => $uniqueNetwork) 
			{
				foreach ($allBranches as $key => $singleBranch) 
				{
					if(!isset($previousMonthBranches[$singleBranch][$uniqueNetwork]))
						$previousMonthBranches[$singleBranch][$uniqueNetwork] = 0;

					if(!isset($currentMonthBranches[$singleBranch][$uniqueNetwork]))
						$currentMonthBranches[$singleBranch][$uniqueNetwork] = 0;					
				}
			}
		}
		
		$finalData = array();

		if(!empty($previousMonthBranches))
		{
			foreach ($previousMonthBranches as $branch => $networks) 
			{
				foreach ($networks as $network => $download) {
					$curDownload = $currentMonthBranches[$branch][$network];
					$percentage = $this->getPercentage($curDownload, $download);
					if(!isset($finalData[$network]))
						$finalData[$network] = array('name' => $network, 'data' => array());

					$finalData[$network]['data'][] = $percentage;
				}
			}
		}

		$inc = 0;
		$dataArr = array();
		foreach ($finalData as $key => $data) {
			$dataArr[$inc] = $data;
			$inc++;
		}

		return array('data' => $dataArr, 'branches' => $allBranches);
	}

	public function getOtherByType($fromDate, $toDate, $type)
	{
		$finalData = array();
		if($type == 'branch_office')
			$sql = "SELECT ".$type.", COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$fromDate."'  AND  '".$toDate."' AND (branch_office != 'Escandon' AND branch_office != 'San Jeronimo' AND branch_office != 'San Angel') group by ".$type;
		else if($type == 'refer_by')
			$sql = "SELECT ".$type.", COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$fromDate."'  AND  '".$toDate."' AND (refer_by != 'Google' AND refer_by != 'Recomendación' AND refer_by != 'Youtube' AND refer_by != 'Bing' AND refer_by != 'Facebook' AND refer_by != 'Publicidad exterior' AND refer_by != 'Otro - especificar:') group by ".$type;
		else if($type == 'service')
		{
			$sql = "Select s.* from services as s, queries as q where (s.service != 'Estimulación Temprana' && s.service != 'Inglés' && s.service != 'Guarderia' && s.service != 'Guarderia Express' && s.service != 'Lactantes' && s.service != 'Maternal') AND q.id = s.query_id AND DATE(date_created) between '".$fromDate."'  AND  '".$toDate."'   group by s.service ";
		}

		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);

		while ($row = $sth->fetch()) 
		{
			// if($type == 'services')
			// {
			// 	echo "<pre>";
			// 	print_r($row);			
			// }

			if(isset($row[$type]))
			{
				if(!empty(trim($row[$type])))
					$finalData[] = utf8_encode($row[$type]);				
			}

		}



		return $finalData;

	}

	public function getFormattedDate($date)
	{
		return date('d F Y', strtotime($date));
	}

	public function getDownloadComparison($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate)
	{
		$sql = "SELECT COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."'";
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$lastMonthDownload = $sth->fetch();

		$sql = "SELECT COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$toLowerDate."' AND  '".$toUpperDate."'";
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$curMonthDownload = $sth->fetch();

		$currentMonthstring = date('F-Y', strtotime($toUpperDate));
		$lastMonthstring = date('F-Y', strtotime($fromUpperDate));


		if(!isset($lastMonthDownload['downloads']))
			$lastMonthDownload['downloads'] = 0;
		if(!isset($curMonthDownload['downloads']))
			$curMonthDownload['downloads'] = 0;

		$percentage = $this->getPercentage($curMonthDownload['downloads'], $lastMonthDownload['downloads']);

		return array('current_downloads' => $curMonthDownload['downloads'],
		 			 'last_downloads' => $lastMonthDownload['downloads'],
		 			 'percentage' => $percentage,
		 			 'current' => 'Total downloads in '.$currentMonthstring,
		 			 'last' => 'Total downloads in '.$lastMonthstring,
		 			 );
	}

	public function getDownloadComparisonByType($fromLowerDate, $fromUpperDate, $toLowerDate, $toUpperDate, $type)
	{
		if($type == 'branch_office')
			$predefinedValues = $this->predefinedBranchesOR;
		else if($type == 'refer_by')
			$predefinedValues = $this->predefinedReferByOR;


		$finalData = array();
		$finalBranches1 = array();
		$finalBranches2 = array();		
		$sql = "SELECT ".$type.", COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$fromLowerDate."' AND  '".$fromUpperDate."' AND ($predefinedValues) group by ".$type;
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		while($row = $sth->fetch())
		{
			$finalBranches1[$row[$type]] = $row['downloads'];
		}


		$sql = "SELECT ".$type.", COUNT(DISTINCT id) downloads FROM queries where DATE(date_created) between '".$toLowerDate."' AND  '".$toUpperDate."' AND ($predefinedValues) AND ($predefinedValues)  group by ".$type;
		$sth = $GLOBALS['pdo']->query($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		while($row = $sth->fetch())
		{
			$finalBranches2[$row[$type]] = $row['downloads'];
		}

		$currentMonthstring = date('F-Y', strtotime($fromUpperDate));
		$lastMonthstring = date('F-Y', strtotime($toUpperDate));

		if(!empty($finalBranches1))
		{
			foreach ($finalBranches1 as $singleBranch => $downloads) {
				if(!array_key_exists($singleBranch, $finalBranches2))
				{
					$finalBranches2[$singleBranch] = 0;
				}
			}
		}

		if(!empty($finalBranches2))
		{
			foreach ($finalBranches2 as $singleBranch => $downloads) {
				if(!array_key_exists($singleBranch, $finalBranches1))
				{
					$finalBranches1[$singleBranch] = 0;
				}
			}
		}

		if(!empty($finalBranches2))
		{
			foreach ($finalBranches2 as $singleBranch => $curDownloads) {
				$lastDownlaods = $finalBranches1[$singleBranch];
				$percentage = $this->getPercentage($curDownloads, $lastDownlaods);
				$finalData[] = array('name' => utf8_encode($singleBranch) . ' '.$percentage.'%', 'data' => array( (int) $lastDownlaods, (int) $curDownloads), 'real_name' => utf8_encode($singleBranch), 'downlaods' => $curDownloads);
			}
		}

		return $finalData;
	}
	public function getPercentage($curDownloads, $lastDownloads)
	{
		$percentage = '-';
		if($curDownloads == 0 && $lastDownloads == 0)
			$percentage = '-';
		else
		{
			if($curDownloads == 0 || $lastDownloads == 0)
				$percentage = '-';
			else
			{
				$percentage = (($curDownloads - $lastDownloads) / $lastDownloads) * 100;
			}
			//$curDownloads > $lastDownloads
			// if($curDownloads > $lastDownloads)
			// {
			// 	if($lastDownloads > 0)
			// 		$percentage = ($curDownloads / $lastDownloads) * 100;
			// 	else
			// 		$percentage = '-';
			// }
			// else
			// {
			// 	if($lastDownloads > 0 && $curDownloads > 0)
			// 		$percentage = (($curDownloads / $lastDownloads) - 1) * 100;
			// 	else
			// 		$percentage = '-';
			// }
		}	
		if($percentage != '-')
		$percentage = round($percentage);	
		return $percentage;	
	}
}
