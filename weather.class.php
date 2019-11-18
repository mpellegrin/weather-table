<?php
require_once('ics-parser/class.iCalReader.php');

class Weather {

	public function __construct() {
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
	}

	/**
	 * Query the CalDAV calendar to get the list of all events
	 * @param  $calendar_url  the CalDAV URL
	 * @param  $dav_user  the calDAV user for HTTP basic authentification
	 * @param  $dav_password  the CalDAV password for HTTP basic authentification
	 */
	public function query_calendar($calendar_url, $dav_user, $dav_password, $start_time = null, $end_time = null) {
		if ($start_time === null) {
			$start_time = time() - 60*60*24*30;
		}
		if ($end_time === null) {
				$end_time = time() + 60*60*24*100;
		}

		// Get events

		$headers = array(
				'Content-Type: application/xml; charset=utf-8',
				'Depth: 1',
				'Prefer: return-minimal'
			);

		// Prepare request body
		$doc  = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$query = $doc->createElement('c:calendar-query');
		$query->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:c', 'urn:ietf:params:xml:ns:caldav');
		$query->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d', 'DAV:');

		$prop = $doc->createElement('d:prop');
		$prop->appendChild($doc->createElement('d:getetag'));
		$prop->appendChild($doc->createElement('c:calendar-data'));
		$query->appendChild($prop);

		$filter = $doc->createElement('c:filter');

		$filter_calendar = $doc->createElement('c:comp-filter');
		$filter_calendar->setAttribute('name', 'VCALENDAR');

		$filter_event = $doc->createElement('c:comp-filter');
		$filter_event->setAttribute('name', 'VEVENT');
		$filter_event->setAttribute('name', 'VEVENT');

		$time_range = $doc->createElement('c:time-range');
		$time_range->setAttribute('start', date('Ymd\THis', $start_time));
		$time_range->setAttribute('end',   date('Ymd\THis', $end_time));

		$filter_event->appendChild($time_range);
		$filter_calendar->appendChild($filter_event);
		$filter->appendChild($filter_calendar);
		$query->appendChild($filter);

		$doc->appendChild($query);
		$body = $doc->saveXML();

		//echo '<pre>' . htmlspecialchars($body) . '</pre>';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $calendar_url);
		curl_setopt($ch, CURLOPT_USERPWD, $dav_user . ':' . $dav_password);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'REPORT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

		$response = curl_exec($ch);
		if (curl_error($ch)) {
			//echo curl_error($ch);
			exit();
		}
		curl_close($ch);

		return $response;
	}

	/**
	 * Parse and sort XML events with ICS parser
	 * @param  response  the XML response from CalDAV
	 */
	public function parse_events($response) {

		$xml = simplexml_load_string($response);
		$data = $xml->xpath('//cal:calendar-data');

		//echo '<pre>' . htmlspecialchars($data[0]) . '</pre>';

		// Parse events
		$calendar_events = array();
		foreach ($data as $vcalendars) {
			$ical = new ICal();
			$vcalendars = $vcalendars->__tostring();

			$lines = explode("\n", $vcalendars);
			$ical->initLines($lines);
			$events = $ical->events();

			foreach ($events as $event) {
				$start = $ical->iCalDateToUnixTimestamp($event['DTSTART']);
				$end = $ical->iCalDateToUnixTimestamp($event['DTEND']);
				$summary = $event['SUMMARY'];
				$calendar_events[] = array(
					'start' => $start,
					'end' => $end,
					'summary' => $summary,
				);
			}
		}
		usort($calendar_events, 'Weather::_sort_events');
		return $calendar_events;
	}

	/**
	 * Returns HTML table for current weather
	 * @param  $calendar_events  the parsed events
	 * @param  $now  the timestamp of today, a day for the first row (week)
	 * @param  $rows  the nomber of rows (weeks) to display in the table
	 */
	public function get_html_table($calendar_events, $now = null, $rows = 10) {

		if ($now === null) {
			$now = mktime(0,0,0);
		}

		ob_start();

		$calendar_start = $now - (date('N')-1)*24*60*60;
		$day_color = array();

		foreach ($calendar_events as $event) {

			if ($event['end'] < $calendar_start) {
				continue;
			}

			$current_day = mktime(0,0,0, date('n', $event['start']), date('j', $event['start']), date('Y', $event['start']));
			$first_loop = true;
			for ($d = $current_day; ($first_loop || $d < $event['end']); $d+=(60*60*24)) {
				if (!$this->color2hex($event['summary'])) {
					$first_loop = false;
					continue;
				}
				if (isset($day_color[$d]) && is_array($day_color[$d])) {
					array_unshift($day_color[$d], $this->color2hex($event['summary']));
				} else {
					$day_color[$d][0] = $this->color2hex($event['summary']);
				}
				$first_loop = false;
			}
		}
		//var_dump($day_color);

		$week_day = date('N', $calendar_start);
		echo '<table class="forecast">';

		echo '<tr><th></th><th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><tH>Dim</th></tr>';

		for ($row = 0; $row < $rows; $row++) {
			echo '<tr>';

			echo '<td class="week">' . (date('W', $calendar_start) + $row) . '</td>';
			for ($col = 0; $col < 7; $col++) {

				if ($row == 0 && $col < $week_day-1) {
					echo '<td></td>';
					continue;
				}

				$current_day = $calendar_start + ($row)*(7*60*60*24) + ($col-($week_day-1))*(60*60*24);
				$current_day = mktime(0,0,0, date('n', $current_day), date('j', $current_day), date('Y', $current_day)); // Working around daylight saving time
				if (isset($day_color[$current_day]) && isset($day_color[$current_day][0])) {
					$color = $day_color[$current_day][0];
					echo '<td class="' . $color . '">';
				} else {
					echo '<td>';
				}
				//echo '<pre>' . $current_day . '</pre>';
				//echo date('r', $current_day);
				echo date('j', $current_day) . ' ' . date('M', $current_day) . ' ' . date('Y', $current_day);
				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';

		$html = ob_get_clean();
		ob_end_flush();
		return $html;
	}


	/**
	 * Returns weather level for the day
	 * @param  $calendar_events  the parsed events
	 * @param  $now  the timestamp of a day
	 */
	public function get_level($calendar_events, $now = null, $rows = 10) {

		if ($now === null) {
			$now = mktime(0,0,0);
		}

		$day_color = array();

		foreach ($calendar_events as $event) {

			if ($event['end'] < time()) {
				continue;
			}

			$current_day = mktime(0,0,0, date('n', $event['start']), date('j', $event['start']), date('Y', $event['start']));
			$first_loop = true;
			for ($d = $current_day; ($first_loop || $d < $event['end']); $d+=(60*60*24)) {
				//var_dump(date('r', $current_day) . ' ' . date('r', $d) . ' ' . date('r', $event['start']) . ' ' . date('r', $event['end']));
				if (!$this->color2hex($event['summary'])) {
					$first_loop = false;
					continue;
				}
				if (isset($day_color[$d]) && is_array($day_color[$d])) {
					array_unshift($day_color[$d], $this->color2hex($event['summary']));
				} else {
					$day_color[$d][0] = $this->color2hex($event['summary']);
				}
				$first_loop = false;
			}
		}
		//var_dump($day_color);

		$current_day = mktime(0,0,0, date('n', $now), date('j', $now), date('Y', $now)); // Working around daylight saving time
		if (isset($day_color[$current_day]) && isset($day_color[$current_day][0])) {
			$color = $day_color[$current_day][0];
		}
		return $color;
	}

	public static function _sort_events($a, $b) {
		return ($a['start'] > $b['start']);
	}

	private function color2hex($color) {
		$color = strtolower($color);

		switch($color) {
			case 'green':
			case 'vert':
			return 'green';
			break;

			case 'orange':
			return 'orange';
			break;

			case 'red':
			case 'rouge':
			return 'red';
			break;

			case 'black':
			case 'noir':
			return 'black';
			break;

			case 'blue':
			case 'bleu':
			return 'blue';
			break;
		}
	}

}
