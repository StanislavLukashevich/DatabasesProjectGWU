<?php
	require_once('../includes/utils.php');

	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	$keys = array("employer", "startDate", "endDate", "position", "description");

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$query = "SELECT employer, startDate, endDate, position, description FROM experience ";
		$query .= "WHERE applicationID = " . $_SESSION["appID"];
		$data = try_query($dbc, $query, NULL);
		$output = array();
		while ($row = mysqli_fetch_row($data)) {
			array_push($output, array_combine($keys, $row));
		}
		echo json_encode($output);
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST)) {
		// create application if not already exists
		require_once('createApplication.php');
		if (isset($_POST["delete"])) {
			$query = "DELETE FROM experience WHERE applicationID = " . $_SESSION["appID"];
			$query .= " AND employer = '" . $_POST["employer"] . "'";
			$query .= " AND position = '" . $_POST["position"] . "'";
			try_query($dbc, $query, "Deleted degree from database.");
		} else {
			$query = "INSERT INTO experience (employer, startDate, endDate, position, description, applicationID) VALUES ( ";
			$_POST["startDate"] = date("Y-m-d", strtotime($_POST["startDate"]));
			$_POST["endDate"] = date("Y-m-d", strtotime($_POST["endDate"]));
			foreach ($keys as $key) {
				$query .= "'" . $_POST[$key] . "', ";
			}
			$query .= $_SESSION["appID"] . ")";
			try_insert($dbc, $query, "Added work experience to database.");
		}
	}
	// TODO - permit degree deletion
?>