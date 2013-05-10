<?php 

/**
* Class Analytics.
* 
* It's a sort of useful stats and numbers generator about Wysija usage.
* It also handles the MixPanel integration.
*/
class WJ_Analytics {
  
  // Array: store all analytics data to be sent to JS.
  private $analytics_data = array(
    'monthly_emails_sent' => '',
    'lists_with_more_than_25' => '',
    'confirmed_subscribers' => '',
    'unconfirmed_subscribers' => '',
    'standard_newsletters' => '',
    'auto_newsletters' => '',
    'wordpress_version' => '',
    'plugin_version' => '',
    'license_type' => '',
    'sending_method' => '',
    'smtp_hostname' => '',
    'activation_email_status' => '',
    'average_open_rate' => '',
    'average_click_rate' => '',
    'industry' => '',
    'wordpress_language' => '',
    'rtl' => ''
  );

  function __construct() {

  }

  /**
   * Send data to Mixpanel by enqueuing the analytics JS file.
   * @return
   */
  public function send() {

    // Enqueue analytics Javascript.
    wp_enqueue_script('analytics', WYSIJA_URL.'js/analytics.js',array(),WYSIJA::get_version());
    // Make analytics data available in JS. 
    wp_localize_script('analytics', 'analytics_data', $this->analytics_data);

  }

  /**
   * Generate fresh data and store it in the $analytics_data Class property.
   * @return
   */
  public function generate_data() {

    $this->analytics_data['monthly_emails_sent'] = $this->get_monthly_emails_sent();
    $this->analytics_data['lists_with_more_than_25'] = $this->get_lists_with_more_than_25();
    $this->analytics_data['confirmed_subscribers'] = $this->get_confirmed_subscribers();
    $this->analytics_data['unconfirmed_subscribers'] = $this->get_unconfirmed_subscribers();
    $this->analytics_data['standard_newsletters'] = $this->get_standard_newsletters();
    $this->analytics_data['auto_newsletters'] = $this->get_auto_newsletters();
    $this->analytics_data['wordpress_version'] = get_bloginfo('version');
    $this->analytics_data['plugin_version'] = WYSIJA::get_version();
    $this->analytics_data['license_type'] = $this->get_license_type();
    $this->analytics_data['sending_method'] = $this->get_sending_method();
    $this->analytics_data['smtp_hostname'] = $this->get_smtp_hostname();
    $this->analytics_data['activation_email_status'] = $this->get_activation_email_status();
    $this->analytics_data['average_open_rate'] = $this->get_average_open_rate();
    $this->analytics_data['average_click_rate'] = $this->get_average_click_rate();
    $this->analytics_data['industry'] = $this->get_industry();
    $this->analytics_data['wordpress_language'] = get_bloginfo('language');
    $this->analytics_data['rtl'] = $this->get_rtl();

  }

  /**
   * Calculate Emails sent in the last 30 days.
   * @return Int
   */
  private function get_monthly_emails_sent() {

    $model_email_user_stat =& WYSIJA::get('email_user_stat','model');
    $query = 'SELECT COUNT(*) as total_emails
              FROM ' . '[wysija]' . $model_email_user_stat->table_name. ' 
              WHERE DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= sent_at';
    $result = $model_email_user_stat->query('get_res', $query);

    return $result[0]['total_emails'];

  }

  /**
   * Calculate lists with more than 25 subscribers.
   * @return Int
   */
  private function get_lists_with_more_than_25() {

    $model_user_list =& WYSIJA::get('user_list','model');
    $query = 'SELECT list_id, COUNT(*) as count
              FROM ' . '[wysija]' . $model_user_list->table_name. ' 
              GROUP BY list_id
              HAVING COUNT(*) >= 25';
    $result = $model_user_list->query('get_res', $query);
    $lists_count = count($result);

    return $lists_count;

  }

  /**
   * Calculate confirmed subscribers.
   * @return Int
   */
  private function get_confirmed_subscribers() {

    $model_user =& WYSIJA::get('user','model');
    $query = 'SELECT COUNT(*) as confirmed_subscribers
              FROM ' . '[wysija]' . $model_user->table_name. ' 
              WHERE  status = 1';
    $result = $model_user->query('get_res', $query);

    return $result[0]['confirmed_subscribers'];

  }

  /**
   * Calculate unconfirmed subscribers.
   * @return Int
   */
  public function get_unconfirmed_subscribers() {

    $model_user =& WYSIJA::get('user','model');
    $query = 'SELECT COUNT(*) as unconfirmed_subscribers
              FROM ' . '[wysija]' . $model_user->table_name. ' 
              WHERE  status = 0';
    $result = $model_user->query('get_res', $query);

    return $result[0]['unconfirmed_subscribers'];

  }

  /**
   * Calculate standard newsletters total.
   * @return Int
   */
  private function get_standard_newsletters() {

    $model_email =& WYSIJA::get('email','model');
    $query = 'SELECT COUNT(*) as standard_newsletters
              FROM ' . '[wysija]' . $model_email->table_name. ' 
              WHERE type = 1
              AND status = 2';
    $result = $model_email->query('get_res', $query);

    return $result[0]['standard_newsletters'];

  }

  /**
   * Calculate auto newsletters total.
   * @return Int
   */
  private function get_auto_newsletters() {

    $model_email =& WYSIJA::get('email','model');
    $query = 'SELECT COUNT(*) as auto_newsletters
              FROM ' . '[wysija]' . $model_email->table_name. ' 
              WHERE  type = 2';
    $result = $model_email->query('get_res', $query);

    return $result[0]['auto_newsletters'];

  }

  /**
   * Check license type in use.
   * @return String Free | Premium
   */
  private function get_license_type() {

    $model_config =& WYSIJA::get('config','model');
    $is_premium = $model_config->getValue('premium_key');

    if ($is_premium) {
      $license_type = 'Premium';
    } else {
      $license_type = 'Free';
    }

    return $license_type;

  }

  /**
   * Get sending method in use.
   * @return String
   */
  private function get_sending_method() {

    $model_config =& WYSIJA::get('config','model');
    return $model_config->getValue('sending_method');

  }

  /**
   * Get smtp hostname in use.
   * @return String
   */
  private function get_smtp_hostname() {

    $model_config =& WYSIJA::get('config','model');
    return $model_config->getValue('smtp_host');

  }

  /**
   * Get activation email status.
   * @return String On | Off
   */
  private function get_activation_email_status() {

    $model_config =& WYSIJA::get('config','model');
    $activation_email_status = $model_config->getValue('confirm_dbleoptin');

    if ($activation_email_status === 1) {
      $result = 'On';
    } else {
      $result = 'Off';
    }

    return $result;

  }

  /**
   * Calculate average open rate.
   * @return Int
   */
  private function get_average_open_rate() {

    $model_email_user_stat =& WYSIJA::get('email_user_stat','model');
    $query = 'SELECT COUNT(*) as opened_emails
              FROM ' . '[wysija]' . $model_email_user_stat->table_name. ' 
              WHERE status = 1';
    $result = $model_email_user_stat->query('get_res', $query);

    $opened_emails = $result[0]['opened_emails'];
    $total_emails = $this->get_total_emails_sent();

    $average_open_rate = round(($opened_emails * 100) / $total_emails);

    return $average_open_rate;

  }

  /**
   * Calculate average click rate.
   * @return String opened/total
   */
  private function get_average_click_rate() {

    $model_email_user_stat =& WYSIJA::get('email_user_stat','model');
    $query = 'SELECT COUNT(*) as clicked_emails
              FROM ' . '[wysija]' . $model_email_user_stat->table_name. ' 
              WHERE status = 2';
    $result = $model_email_user_stat->query('get_res', $query);

    $clicked_emails = $result[0]['clicked_emails'];
    $total_emails = $this->get_total_emails_sent();

    $average_click_rate = round(($clicked_emails * 100) / $total_emails);

    return $average_click_rate;

  }


  /**
   * Get all emails sent.
   * @return Int
   */
  private function get_total_emails_sent() {
        
      $model_email_user_stat =& WYSIJA::get('email_user_stat','model');
      $query = 'SELECT COUNT(*) as all_emails
                FROM ' . '[wysija]' . $model_email_user_stat->table_name. '';
      $result = $model_email_user_stat->query('get_res', $query);

      return $result[0]['all_emails'];

  }

  /**
   * Get Industry specified in the settings page.
   * @return String
   */
  private function get_industry() {

    $model_config =& WYSIJA::get('config','model');

    return $model_config->getValue('industry');

  }

  /**
   * Get if is using right to left language.
   * @return String
   */
  private function get_rtl() {

    if (is_rtl()) {
      $is_rtl = 'Yes';
    } else {
      $is_rtl = 'No';
    }

    return $is_rtl;

  }

}