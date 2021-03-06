<?php
 	/*
	 * Script:    DataTables server-side script for PHP and MySQL
	 * Copyright: 2010 - Allan Jardine
	 * License:   GPL v2 or BSD (3-point)
	 
	 * modify by Nicolas Boyer
	 */
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Easy set variables
	 */
	
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 
	 
	 */
	
	$aColumns = array( "DATEDIFF(date_futureInt,CURDATE())",'date_futureInt', 'nomDes' , 'numInstru');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "numInstru";
	
	/* DB table to use */
	$sTable = "instrument_vth iv, instrument i LEFT OUTER JOIN designation de ON i.idDes_designation=de.idDes";
	
	
	
	require("../../conf/connexion_param.php");
	
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".mysqli_real_escape_string($bdd, $_GET['iDisplayStart'] ).", ".
			mysqli_real_escape_string($bdd, $_GET['iDisplayLength'] );
	}
	
	
	/*
	 * Ordering
	 */
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  ";
		$îSorting=intval( $_GET['iSortingCols'] );
		for ( $i=0 ; $i<$îSorting ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
				 	".mysqli_real_escape_string($bdd, $_GET['sSortDir_'.$i] ) .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}
	}
	
	
	/* 
	 * Filtering
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here, but concerned about efficiency
	 * on very large tables, and MySQL's regex functionality is very limited
	 */
	$nbcol=count($aColumns);
	$sWhere = "";
	if ( $_GET['sSearch'] != "" )
	{
		$sWhere = "WHERE (";
		
		for ( $i=0 ; $i<$nbcol; $i++ )
		{
			$sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($bdd, $_GET['sSearch'] )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	/* Individual column filtering */
	for ( $i=0 ; $i<$nbcol ; $i++ )
	{
		if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
			$sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($bdd,$_GET['sSearch_'.$i])."%' ";
		}
	}
	
	//condition where en plus
	if($sWhere != "")
		$condWhere="and ";
	else
		$condWhere="where ";
	$condWhere.="i.trescalId is null 
	and	iv.numInstru_instrument=i.numInstru
	and i.idStatut_statut=2
	and (i.idEtat_etat =1 )"; //or i.idEtat_etat is NULL
	/*and DATEDIFF(date_futureInt,CURDATE()) >-10*/
	
	/*
	 * SQL queries
	 * Get data to display
	 */
	 /*
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
		FROM   $sTable
		$sWhere
		$sOrder
		$sLimit
	";
	$rResult = mysqli_query( $bdd,$sQuery );
	*/
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
		from $sTable
		$sWhere
		$condWhere
		$sOrder
		$sLimit
	
	";
	$rResult = mysqli_query( $bdd,$sQuery );
	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS()
	";
	$rResultFilterTotal = mysqli_query( $bdd,$sQuery) ;
	$aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
	$iFilteredTotal = $aResultFilterTotal[0];
	
	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iFilteredTotal, //afin d'enlever un affichage supérflu, on lui donne la meme valeur que iTotalDisplayRecords
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	while ( $aRow = mysqli_fetch_array( $rResult ) )
	{
		$row = array();
		for ( $i=0 ; $i<$nbcol ; $i++ )
		{	
			//formatage des dates
			if(($i==1) && $aRow[ $aColumns[$i] ]!="")
				$aRow[ $aColumns[$i] ]=date('d/m/Y',strtotime($aRow[ $aColumns[$i] ]));
			
			$row[] = $aRow[ $aColumns[$i] ];
		}
		$output['aaData'][] = $row;
	}
	

	
	echo json_encode( $output );
