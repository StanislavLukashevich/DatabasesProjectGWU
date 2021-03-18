<?php
session_start();

    include ('connectvars.php'); 
    // Connect to the database
    $dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    $uid = mysqli_real_escape_string($dbc, trim($_SESSION['id']));
    $crn = mysqli_real_escape_string($dbc, trim($_GET['crn']));
    $grade = 'IP';

    $cno =  $_GET['cno'];

	$enroll_course_t = "SELECT start_time, day, semester, year FROM schedule WHERE crn='".$_GET['crn']."'";
	$enroll_course_t = mysqli_fetch_array (mysqli_query ($dbc, $enroll_course_t));

	$s = $enroll_course_t['semester'];
	$y = $enroll_course_t['year'];
	$day = $enroll_course_t['day'];
	$enroll_course_t = $enroll_course_t['start_time'];

	$courses = "SELECT * FROM schedule, courses_taken 
				WHERE start_time='$enroll_course_t' and student_id='$uid'
					and schedule.crn=courses_taken.crn 
					and semester='$s' and year='$y' and day='$day'";
	$courses = mysqli_query ($dbc, $courses);
	
	$time_conflict = 0;

	if ($courses && mysqli_num_rows ($courses) > 0) {
		$time_conflict = 1;	
	}

    $class_registering_for_query = "SELECT * FROM schedule, catalog WHERE crn=$crn AND course_id=c_id";
    // die(mysqli_query($dbc, $class_registering_for_query));
    $class_registering_for_result = mysqli_query($dbc, $class_registering_for_query);
    $crn_course_registering_for = trim($_GET['crn']);

    // $class_registering_for_data = mysqli_fetch_array($class_registering_for_result);
    if ($class_registering_for_result && mysqli_num_rows($class_registering_for_result) > 0) {
        while ($class_data = mysqli_fetch_assoc($class_registering_for_result)) {
			// Get crn, day, semester, start_time, and end_time for each course the student is currently enrolled in
			$course_id = $class_data["course_id"];
			$day = $class_data["day"];
			$term = $class_data["semester"];
        }
    }

        // Check for pre-requisite conflits
        $prereq1_query = "SELECT prereq1 FROM prereqs WHERE course_Id='$course_id'";
        $prereq1_data = mysqli_fetch_assoc(mysqli_query($dbc, $prereq1_query));
        $prereq1 = $prereq1_data['prereq1'];


        $prereq2_query = "SELECT prereq2 FROM prereqs WHERE course_Id='$course_id'";
        $prereq2_data = mysqli_fetch_assoc(mysqli_query($dbc, $prereq2_query));
        $prereq2 = $prereq2_data['prereq2'];

        $prereq_crns=array();

        // conflict flags
        $prereq1_conflict = 1;
        $prereq2_conflict = 1;

        if (empty($prereq1)) {
            $prereq1_conflict = 0;

        }
        if (empty($prereq2)) {
            $prereq2_conflict = 0;
        }

        $has_prereq1 = 0;
        $has_prereq2 = 0;

        // Get crn from pre-req course numbers (c_no)

        $courses_taken_query = "SELECT * FROM courses_taken 
								WHERE student_id=$uid and grade!='IP'";
        $courses_taken_data = mysqli_query($dbc, $courses_taken_query);

        $crns_taken = array();
        if ($courses_taken_data && mysqli_num_rows($courses_taken_data) > 0) {
            while ($row = mysqli_fetch_assoc($courses_taken_data)) {
                array_push($crns_taken, $row['crn']);
            }
        }

        // die(implode($crns_taken));
                if (!empty($prereq1)) {
                    list($dept1, $c_no1) = explode(" ", $prereq1);
                    $prereq1_cno = $c_no1;

                    $cid_query = "SELECT crn FROM schedule s, catalog c WHERE c.c_no='$prereq1_cno' AND s.course_id=c.c_id";
                    $crn_prereq1_data = mysqli_fetch_assoc(mysqli_query($dbc, $cid_query));
                    $crn_prereq1 = $crn_prereq1_data['crn'];

                    if (  in_array($crn_prereq1, $crns_taken) ) {
                        $prereq1_conflict = 0;
                    }
                }

                if (!empty($prereq2)) {
                    list($dep2, $c_no2) = explode(" ", $prereq2);
                    $prereq2_cno = $c_no2;

                    $cid_query = "SELECT crn FROM schedule s, catalog c WHERE c.c_no='$prereq2_cno' AND s.course_id=c.c_id";
                    $crn_prereq2_data = mysqli_fetch_assoc(mysqli_query($dbc, $cid_query));
                    $crn_prereq2 = $crn_prereq2_data['crn'];

                    // array_push($prereq_crns, $crn_prereq2);
                    // die("CRN Prereq2: " .$crn_prereq2 . " " . "CRN: " . $row['crn']);

                    if ( in_array($crn_prereq2, $crns_taken) ) {
                        $prereq2_conflict = 0;
                    }
                }

	// Check if there is an advising hold on student's account
	$query = "SELECT COUNT(*) AS approved FROM formone WHERE universityid = $uid AND department = 'APPROVED';";
	$advising_hold = mysqli_fetch_array(mysqli_query($dbc, $query))["approved"];

    if ($advising_hold > 0 && $prereq1_conflict == 0 && $prereq2_conflict == 0 && $time_conflict == 0) {

        $enroll = "INSERT courses_taken (student_id, crn, grade) VALUES ($uid, $crn, '$grade')";
        mysqli_query($dbc, $enroll);
        header("Location: course.php?cno=$cno");

    } else {
		if ($time_conflict) {
			$error = "time";
		} else if ($advising_hold == 0) {
			$error = "advising_hold";
		} else {
			$error = "prereq";
		}
		echo $error;
        header("Location: course.php?cno=$cno&conflict=$error");
    }

?>
