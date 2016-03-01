<?php
// This is not part of the plugin just a recopilation of all queries done to appointments table
exit();


if ('selected' == $type && !empty($_POST['app'])) {
// selected appointments
$ids = array_filter(array_map('intval', $_POST['app']));
if ($ids) $sql = "SELECT * FROM {$appointments->app_table} WHERE ID IN(" . join(',', $ids) . ") ORDER BY ID";
} else if ('type' == $type) {
$status = !empty($_POST['status']) ? $_POST['status'] : false;
if ('active' === $status) $sql = $appointments->db->prepare("SELECT * FROM {$appointments->app_table} WHERE status IN('confirmed','paid') ORDER BY ID", $status);
else if ($status) $sql = $appointments->db->prepare("SELECT * FROM {$appointments->app_table} WHERE status=%s ORDER BY ID", $status);
} else if ('all' == $type) {
$sql = "SELECT * FROM {$appointments->app_table} ORDER BY ID";
}
if (!$sql) wp_die(__('Nothing to download!','appointments'));

$apps = $appointments->db->get_results($sql, ARRAY_A);


$export = $wpdb->get_col("SELECT ID FROM {$this->app_table} WHERE status IN({$clean_stat})");

$present_events = $wpdb->get_col("SELECT DISTINCT gcal_ID FROM {$this->app_table} WHERE gcal_ID IS NOT NULL");

$wpdb->query("DELETE FROM {$this->app_table} WHERE status='reserved'");


$result = $wpdb->query( "INSERT INTO " . $this->app_table . " (created,service,worker,status,start,end,note,gcal_ID,gcal_updated)
VALUES ". implode(',',$values).  "
ON DUPLICATE KEY UPDATE start=VALUES(start), end=VALUES(end), gcal_updated=VALUES(gcal_updated)" ); // Key is autoincrement, it'll never update!



if (!empty($to_update)) {
	$result = (int)$result;
	foreach ($to_update as $upd) {
		$res2 = $wpdb->query("UPDATE {$this->app_table} SET {$upd}");
		if ($res2) $result++;
	}
	$message = sprintf( __('%s appointment record(s) affected.','appointments'), $result ). ' ';
}

if (!empty($event_ids)) {
	// Delete unlisted events for the selected worker
	$event_ids_range = '(' . join(array_filter($event_ids), ',') . ')';
	$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID NOT IN {$event_ids_range}", $worker_id));
} else { // In case we have existing reserved events that have been removed from GCal and nothing else
	$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID IS NOT NULL", $worker_id));
}


$max = $wpdb->get_var( "SELECT MAX(ID) FROM " . $this->app_table . " " );
if ( $max )
	 $wpdb->query( "ALTER TABLE " . $this->app_table ." AUTO_INCREMENT=". ($max+1). " " );


$stat = '';
foreach ( $statuses as $s ) {
	// Allow only defined stats
	if ( array_key_exists( trim( $s ), App_Template::get_status_names() ) )
		$stat .= " status='". trim( $s ) ."' OR ";
}
$stat = rtrim( $stat, "OR " );

$results = $wpdb->get_results( "SELECT * FROM " . $appointments->app_table . " WHERE (".$stat.") ORDER BY ".$appointments->sanitize_order_by( $order_by )." " );


    private function _get_booked_appointments_for_period ($service_ids, $period) {
    		$start = date('Y-m-d H:i:s', $period->get_start());
    		$end = date('Y-m-d H:i:s', $period->get_end());
    		$services = join(',', array_filter(array_map('intval', $service_ids)));

    		$sql = "SELECT COUNT(*) FROM {$this->_core->app_table} WHERE service IN ({$services}) AND end > '{$start}' AND start < '{$end}' AND status IN ('paid', 'confirmed')";
    		$cnt = (int)$this->_core->db->get_var($sql);

    		return $cnt;
    	}


      $table = $appointments->app_table;
		$where = "AND (status='pending' OR status='paid' OR status='confirmed' OR status='reserved')";

		if ($appointments->service) {
			$where .= " AND service={$appointments->service}";
		}
		if ($appointments->worker) {
			$where .= " AND worker={$appointments->worker}";
		}

		$sql = "SELECT name,user,start,end FROM {$table} WHERE UNIX_TIMESTAMP(start)>'{$interval_start}' AND UNIX_TIMESTAMP(end)<'{$interval_end}' {$where}";
		$res = $wpdb->get_results($sql);