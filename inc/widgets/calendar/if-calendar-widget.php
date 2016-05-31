<?php 
/*
 * Plugin Name: IF Calendar
 * Description: Displays a mini ajax calendar
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
  
class If_Calendar_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'ifcalendar',
			'description' => __("Displays an ajaxified mini calendar",'iftheme'),
		);
    parent::__construct( 
        'ifcalendar', 
        __('IF Calendar', 'iftheme'), 
        $widget_ops
    );
	}
	
	function form ($instance) {
		// prints the form on the widgets page
		$defaults = array('title'=> '');
		$instance = wp_parse_args( (array) $instance, $defaults ); 
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','iftheme')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20" />
		</p>
		<p>
			<?php __("This widget displays a calendar. No configuration options", 'iftheme'); 
  			//@TODO: configuration for links, months setlocale... 
			?>
		</p>
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function widget ($args,$instance) {
	// used when the sidebar calls in the widget
		extract($args);
    $default_title = '';
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? $default_title : $instance['title'], $instance, $this->id_base);
		
		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title; 
  ?>
  <?php //calendar code
    // Get current year, month and day
    list($iNowYear, $iNowMonth, $iNowDay) = explode('-', date('Y-m-d'));
    
    // Get current year and month depending on possible GET parameters
    if (isset($_GET['month'])) {
      list($iMonth, $iYear) = explode('-', $_GET['month']);
      $iMonth = (int)$iMonth;
      $iYear = (int)$iYear;
    } else {
      list($iMonth, $iYear) = explode('-', date('n-Y'));
    }
    
    // Get name and number of days of specified month
    $iTimestamp = mktime(0, 0, 0, $iMonth, $iNowDay, $iYear);
    list($sMonthName, $iDaysInMonth) = explode('-', date('F-t', $iTimestamp));
    
    // Get previous year and month
    $iPrevYear = $iYear;
    $iPrevMonth = $iMonth - 1;
    if ($iPrevMonth <= 0) {
        $iPrevYear--;
        $iPrevMonth = 12; // set to December
    }
    
    // Get next year and month
    $iNextYear = $iYear;
    $iNextMonth = $iMonth + 1;
    if ($iNextMonth > 12) {
        $iNextYear++;
        $iNextMonth = 1;
    }
    
    // Get number of days of previous month
    $iPrevDaysInMonth = (int)date('t', mktime(0, 0, 0, $iPrevMonth, $iNowDay, $iPrevYear));
    
    // Get numeric representation of the day of the week of the first day of specified (current) month
    $iFirstDayDow = (int)date('w', mktime(0, 0, 0, $iMonth, 1, $iYear));
    
    // On what day the previous month begins
    $iPrevShowFrom = $iPrevDaysInMonth - $iFirstDayDow + 1;
    
    // If previous month
    $bPreviousMonth = ($iFirstDayDow > 0);
    
    // Initial day
    $iCurrentDay = ($bPreviousMonth) ? $iPrevShowFrom : 1;
    
    $bNextMonth = false;
    $sCalTblRows = '';
    
    // Generate rows for the calendar
    for ($i = 0; $i < 6; $i++) { // 6-weeks range
        $sCalTblRows .= '<tr>';
        for ($j = 0; $j < 7; $j++) { // 7 days a week
    
            $sClass = '';
            if ($iNowYear == $iYear && $iNowMonth == $iMonth && $iNowDay == $iCurrentDay && !$bPreviousMonth && !$bNextMonth) {
                $sClass = 'today';
            } elseif (!$bPreviousMonth && !$bNextMonth) {
                $sClass = 'current';
            }
            $sCalTblRows .= '<td class="'.$sClass.'"><a href="javascript: void(0)">'.$iCurrentDay.'</a></td>';
    
            // Next day
            $iCurrentDay++;
            if ($bPreviousMonth && $iCurrentDay > $iPrevDaysInMonth) {
                $bPreviousMonth = false;
                $iCurrentDay = 1;
            }
            if (!$bPreviousMonth && !$bNextMonth && $iCurrentDay > $iDaysInMonth) {
                $bNextMonth = true;
                $iCurrentDay = 1;
            }
        }
        $sCalTblRows .= '</tr>';
    }
    
    // Prepare replacement keys and generate the calendar
    $aKeys = array(
        '__prev_month__' => "{$iPrevMonth}-{$iPrevYear}",
        '__next_month__' => "{$iNextMonth}-{$iNextYear}",
        '__cal_caption__' => $sMonthName . ', ' . $iYear,
        '__cal_rows__' => $sCalTblRows,
    );
    $sCalendarItself = strtr(file_get_contents('templates/calendar.html'), $aKeys);
    
    // AJAX requests - return the calendar
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && isset($_GET['month'])) {
        header('Content-Type: text/html; charset=utf-8');
        echo $sCalendarItself;
        exit;
    }
    
    $aVariables = array(
        '__calendar__' => $sCalendarItself,
    );
    echo strtr(file_get_contents('templates/index.html'), $aVariables);
  ?>

  <?php echo $after_widget;
	}
}//end If_Calendar_Widget

function if_calendar_load_widgets() {
	register_widget('If_Calendar_Widget');
}

add_action('widgets_init', 'if_calendar_load_widgets');
?>
