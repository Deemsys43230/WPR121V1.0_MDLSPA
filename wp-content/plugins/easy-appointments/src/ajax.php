<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Ajax communication
 *
 * TODO switch to rest API
 *
 */
class EAAjax
{

    /**
     * DB utils
     *
     * @var EADBModels
     **/
    protected $models;

    /**
     * @var EAOptions
     */
    protected $options;

    /**
     * @var EAMail
     */
    protected $mail;

    /**
     * Type of data request
     *
     * @var string
     **/
    protected $type;

    /**
     * @var EALogic
     */
    protected $logic;

    /**
     * @var EAReport
     */
    protected $report;

    /**
     * @var
     */
    private $data;

    /**
     * @param EADBModels $models
     * @param EAOptions $options
     * @param EAMail $mail
     * @param EALogic $logic
     * @param EAReport $report
     */
    function __construct($models, $options, $mail, $logic, $report)
    {
        $this->models = $models;
        $this->options = $options;
        $this->mail = $mail;
        $this->logic = $logic;
        $this->report = $report;
    }

    /**
     * Register ajax points
     */
    public function init()
    {
        // Frontend ajax calls
        add_action('wp_ajax_nopriv_ea_next_step', array($this, 'ajax_front_end'));
        add_action('wp_ajax_ea_next_step', array($this, 'ajax_front_end'));

        add_action('wp_ajax_nopriv_ea_current_date', array($this, 'ajax_current_date'));
        add_action('wp_ajax_ea_current_date', array($this, 'ajax_current_date'));

        add_action('wp_ajax_nopriv_ea_date_selected', array($this, 'ajax_date_selected'));
        add_action('wp_ajax_ea_date_selected', array($this, 'ajax_date_selected'));

        add_action('wp_ajax_ea_res_appointment', array($this, 'ajax_res_appointment'));
        add_action('wp_ajax_nopriv_ea_res_appointment', array($this, 'ajax_res_appointment'));

        add_action('wp_ajax_ea_final_appointment', array($this, 'ajax_final_appointment'));
        add_action('wp_ajax_nopriv_ea_final_appointment', array($this, 'ajax_final_appointment'));

        add_action('wp_ajax_ea_cancel_appointment', array($this, 'ajax_cancel_appointment'));
        add_action('wp_ajax_nopriv_ea_cancel_appointment', array($this, 'ajax_cancel_appointment'));

        add_action('wp_ajax_ea_month_status', array($this, 'ajax_month_status'));
        add_action('wp_ajax_nopriv_ea_month_status', array($this, 'ajax_month_status'));
        // end frontend

        // admin ajax section
        if (is_admin()) {
            add_action('wp_ajax_ea_errors', array($this, 'ajax_errors'));

            add_action('wp_ajax_ea_test_wp_mail', array($this, 'ajax_test_mail'));

            // Appointments
            add_action('wp_ajax_ea_appointments', array($this, 'ajax_appointments'));

            // Appointment
            add_action('wp_ajax_ea_appointment', array($this, 'ajax_appointment'));

            // Services
            add_action('wp_ajax_ea_services', array($this, 'ajax_services'));

            // Service
            add_action('wp_ajax_ea_service', array($this, 'ajax_service'));

            // Locations
            add_action('wp_ajax_ea_locations', array($this, 'ajax_locations'));

            // Location
            add_action('wp_ajax_ea_location', array($this, 'ajax_location'));

            // Worker
            add_action('wp_ajax_ea_worker', array($this, 'ajax_worker'));

            // Workers
            add_action('wp_ajax_ea_workers', array($this, 'ajax_workers'));

            // Connection
            add_action('wp_ajax_ea_connection', array($this, 'ajax_connection'));

            // Connections
            add_action('wp_ajax_ea_connections', array($this, 'ajax_connections'));

            // Open times
            add_action('wp_ajax_ea_open_times', array($this, 'ajax_open_times'));

            // Setting
            add_action('wp_ajax_ea_setting', array($this, 'ajax_setting'));

            // Settings
            add_action('wp_ajax_ea_settings', array($this, 'ajax_settings'));

            // Report
            add_action('wp_ajax_ea_report', array($this, 'ajax_report'));

            // Custom fields
            add_action('wp_ajax_ea_fields', array($this, 'ajax_fields'));
            add_action('wp_ajax_ea_field', array($this, 'ajax_field'));
            add_action('wp_ajax_ea_export', array($this, 'ajax_export'));
        }
    }

    public function ajax_front_end()
    {
      //  check_ajax_referer('ea-bootstrap-form', 'check');
        //unset($_GET['check']);

        $data = $_GET;

        $white_list = array('location', 'service', 'worker', 'next');

        foreach ($data as $key => $value) {
            if (!in_array($key, $white_list)) {
                unset($data[$key]);
            }
        }

        $result = $this->models->get_next($data);

        $this->send_ok_json_result($result);
    }

//getting current date from server
    public function ajax_current_date()
    {

     $date = new DateTime();
        $date_conversion= $date->format('Y-m-d');
       // $time_conversion=$date->format('H:i:s');
        $date_format['date']=$date_conversion;

       // $date_format['time']=$time_conversion;

        $this->send_ok_json_result($date_format,'wdwds');
    } // end here

    public function ajax_date_selected()
    {
       // check_ajax_referer('ea-bootstrap-form', 'check');

        unset($_GET['action']);
        unset($_GET['action']);
       // unset($_GET['check']);

        $block_time = (int)$this->options->get_option_value('block.time', 0);

        $slots = $this->logic->get_open_slots($_GET['location'], $_GET['service'], $_GET['worker'], $_GET['date'], null, true, $block_time);
        //custom validation start here
        // some basic validation  for empty value
      if ( empty( $_GET['worker'] ) ) {
            $translation = __('Worker value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        if ( empty( $_GET['location'] ) ) {
            $translation = __('Location value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $_GET['service'] ) ) {
            $translation = __('Service value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        if ( empty( $_GET['date'] ) ) {
            $translation = __('Date should not be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        //invalid data validation
        if ( is_numeric( $_GET['worker'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $_GET['location'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $_GET['service'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['date'])) {
            $translation = __('Invalid Date Format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        } //custom validation end here

        $this->send_ok_json_result($slots);
    }

    public function ajax_res_appointment()
    {
       // check_ajax_referer('ea-bootstrap-form', 'check');
        unset($_GET['check']);

        $table = 'ea_appointments';

        $data = $_GET;


        // PHP 5.2
        //$enum = new ReflectionClass('EAAppointmentFields');
        //$dont_remove = $enum->getConstants();
        $dont_remove = array(
            'id',
            'location',
            'service',
            'worker',
            'first-name',
            'last-name',
            'email',
            'phone',
            'date',
            'start',
            'end',
            'end_date',
            'additional-information',
            'status',
            'user',
            'created',
            'price',
            'ip',
            'session'
        );

        foreach ($data as $key => $rem) {
            if (!in_array($key, $dont_remove)) {
                unset($data[$key]);
            }
        }

        unset($data['action']);

        $block_time = (int)$this->options->get_option_value('block.time', 0);

        //custom validation start here
        // some basic validation  for empty value

        if ( empty( $data['worker'] ) ) {
            $translation = __('Worker value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        if ( empty( $data['location'] ) ) {
            $translation = __('Location value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['service'] ) ) {
            $translation = __('Service value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['date'] ) ) {
            $translation = __('Date should not be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['end_date'] ) ) {
            $translation = __('Enddate should not be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['start'] ) ) {
            $translation = __('Booking slot time should not be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['first-name'] ) ) {
            $translation = __('Firstname is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['last-name'] ) ) {
            $translation = __('Lastname is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['phone'] ) ) {
            $translation = __('Userphone number cannot be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        //invalid data validation
        if (!preg_match("/^[ A-Za-z,.]*$/", $data['first-name'])) {
            $translation = __('Enter valid first name', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^[ A-Za-z,.]*$/", $data['last-name'])) {
            $translation = __('Enter valid last name', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data['date'])) {
            $translation = __('Invalid Date Format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data['end_date'])) {
            $translation = __('Invalid Date Format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        if ( is_numeric( $data['start'] ) ) {
            $translation = __('Booking slot time invalid format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^[ A-Za-z,.]*$/", $data['name'])) {
            $translation = __('Enter valid name', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (is_numeric( $data['phone'] ) == false ) {
            $translation = __('Invalid Phone Number', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^\d{10}$/", $data['phone'])) {
            $translation = __('Phone number should be length of 10', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }//custom validation end here


        //checking the appointment booking dat is current date or not
        $current_date=date('Y-m-d');
        $appointment_booking_date=$data['date'];
        $appointment_booking_enddate=$data['end_date'];

       if($current_date > $appointment_booking_date ) {
            $translation = __('Please Select another Day', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if($current_date > $appointment_booking_enddate) {
            $translation = __('Please Select another Day', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        } //end here

        // get open slots for that day
        $open_slots = $this->logic->get_open_slots($data['location'], $data['service'], $data['worker'], $data['date'], null, true, $block_time);


        $is_free = false;
        //checking for user booking slot is there or not
        $is_noslots = false;

       foreach ($open_slots as $value) {
           if ($value['value'] == $data['start']) {
               $is_noslots = true;
               break;
           }
       }

       if (!$is_noslots ) {
            $translation = __('No slots for this time', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }//checking for user booking slot is there or not end here
        foreach ($open_slots as $value) {
            if ($value['value'] === $data['start'] && $value['count'] > 0) {
                $is_free = true;
                break;
            }
        }
        if (!$is_free) {
            $translation = __('Slot is already taken', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }

        $data['status'] = 'reservation';
        $service = $this->models->get_row('ea_services', $data['service']);

        $data['price'] = $service->price;
        $end_time = strtotime("{$data['start']} + {$service->duration} minute");

        $data['end'] = date('H:i', $end_time);

        $data['ip'] = $_SERVER['REMOTE_ADDR'];

        $data['session'] = session_id();

        $check = $this->logic->can_make_reservation($data);

        if (!$check['status']&& !is_user_logged_in()) {
            $resp = array(
                'err'     => true,
                'message' => $check['message']
            );
            $this->send_err_json_result(json_encode($resp));
        }

        $response = $this->models->replace($table, $data, true);

        if ($response == false) {
            $resp = array(
                'err'     => true,
                'message' => __('Something went wrong! Please try again.', 'easy-appointments')
            );
            $this->send_err_json_result(json_encode($resp));
        }

        $this->send_ok_json_result($response);
    }

    /**
     * Final Appointment creation from frontend part
     */
    public function ajax_final_appointment()
    {
        //check_ajax_referer('ea-bootstrap-form', 'check');
        unset($_GET['check']);

        $table = 'ea_appointments';

        $data = $_GET;

        unset($data['action']);

        $data['status'] = $this->options->get_option_value('default.status', 'pending');

        $appointment = $this->models->get_row('ea_appointments', $data['id'], ARRAY_A);

        // check IP
        if ($appointment['ip'] != $_SERVER['REMOTE_ADDR']) {
            $this->send_err_json_result('{"err":true}');
        }

        //custom validation start here
        // some basic validation  for empty value
   /*    if ( empty( $data['email'] ) ) {
            $translation = __('Emailid is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['name'] ) ) {
            $translation = __('Username is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['phone'] ) ) {
            $translation = __('Userphone number cannot be empty', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }*/
         //invalid data validation
/*        if (!preg_match("/^[ A-Za-z,.]*$/", $data['name'])) {
            $translation = __('Enter valid name', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (is_numeric( $data['phone'] ) == false ) {
            $translation = __('Invalid Phone Number', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if (!preg_match("/^\d{10}$/", $data['phone'])) {
            $translation = __('Phone number should be length of 10', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }*/

            //custom validation end here
        // check if he can update the reservation
        $check = $this->logic->can_update_reservation($appointment, $data);
        if (!$check['status']) {
            $resp = array(
                'err'     => true,
                'message' => $check['message']
            );

            $this->send_err_json_result(json_encode($resp));
        }

        $appointment['status'] = $this->options->get_option_value('default.status', 'pending');

        $response = $this->models->replace($table, $appointment, true);

        $meta = $this->models->get_all_rows('ea_meta_fields');

        foreach ($meta as $f) {
            $fields = array();
            $fields['app_id'] = $appointment['id'];
            $fields['field_id'] = $f->id;

            if (array_key_exists($f->slug, $data)) {
                // remove slashes and convert special chars
                $fields['value'] = stripslashes($data[$f->slug]);
            } else {
                $fields['value'] = '';
            }

            $response = $response && $this->models->replace('ea_fields', $fields, true, true);
        }

        if ($response == false) {
            $this->send_err_json_result('{"err":true}');
        } else {
            $this->mail->send_notification($data);

            // trigger send user email notification appointment
            do_action('ea_user_email_notification', $appointment['id']);

            // trigger new appointment
            do_action('ea_new_app', $appointment['id'], $appointment, true);

            // trigger new appointment from customer
            do_action('ea_new_app_from_customer', $appointment['id'], $appointment, true);
        }

        $response = new stdClass();
        $response->message = 'User Appointment booked Successfully.';
        $this->send_ok_json_result($response);
    }

    public function ajax_cancel_appointment()
    {
        check_ajax_referer('ea-bootstrap-form', 'check');
        unset($_GET['check']);

        $table = 'ea_appointments';

        $data = $_GET;

        unset($data['action']);

        $data['status'] = 'abandoned';

        $appointment = $this->models->get_row('ea_appointments', $data['id'], ARRAY_A);

        // Merge data
        foreach ($appointment as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
            }
        }

        $response = $this->models->replace($table, $data, true);

        if ($response == false) {
            $this->send_err_json_result('{"err":true}');
        }

        $response = new stdClass;
        $response->data = true;

        $this->send_ok_json_result($response);
    }

    public function ajax_setting()
    {

        $this->validate_access_rights();

        $this->parse_single_model('ea_options');
    }

    public function ajax_settings()
    {
        $this->validate_access_rights();

        $data = $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {

            $response = $this->options->get_mixed_options();

            $this->send_ok_json_result($response);
        }

        $this->models->clear_options();

        // case of update
        if (array_key_exists('options', $data)) {
            foreach ($data['options'] as $option) {
                // update single option
                $response['options'][] = $this->models->replace('ea_options', $option);
            }
        }
        if (array_key_exists('fields', $data)) {
            foreach ($data['fields'] as $option) {
                // update single option
                $option['slug'] = sanitize_title($option['label']);
                $response['fields'][] = $this->models->replace('ea_meta_fields', $option);
            }
        }


        $this->send_ok_json_result($response);
    }

    /**
     * Update all settings ajax call
     */
    public function ajax_settings_upd()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_options');
        }

        $this->send_ok_json_result($response);
    }

    /**
     * Get all open time slots
     */
    public function ajax_open_times()
    {
        $data = $this->parse_input_data();

        if (!array_key_exists('app_id', $data)) {
            $data['app_id'] = null;
        }

        $block_time = (int)$this->options->get_option_value('block.time', 0);

        $slots = $this->logic->get_open_slots($data['location'], $data['service'], $data['worker'], $data['date'], $data['app_id'], true, $block_time);

        die(json_encode($slots));
    }

    public function ajax_appointments()
    {
        $data = $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_appointments($data);
        }

        die(json_encode($response));
    }

    public function ajax_appointment()
    {
        $response = $this->parse_appointment(false);

        if ($response == false) {
            $this->send_err_json_result('err');
        }

        if ($this->type != 'NEW' && $this->type != 'UPDATE') {
            $this->send_ok_json_result($response);
        }

        if (isset($this->data['_mail'])) {
            $this->mail->send_status_change_mail($response->id);
            $this->mail->send_admin_email_notification_action($response->id);
        }
        $this->send_ok_json_result($response);
    }

    /**
     * Service model
     */
    public function ajax_service()
    {
        $this->validate_access_rights();

        $this->parse_single_model('ea_services');
    }

    /**
     * Services collection
     */
    public function ajax_services()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_services');
        }

        die(json_encode($response));
    }

    /**
     * Locations collection
     */
    public function ajax_locations()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_locations');
        }

        header("Content-Type: application/json");

        die(json_encode($response));
    }

    /**
     * Single location
     */
    public function ajax_location()
    {
        $this->validate_access_rights();

        $this->parse_single_model('ea_locations');
    }

    /**
     * Workers collection
     */
    public function ajax_workers()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_staff');
        }

        header("Content-Type: application/json");

        die(json_encode($response));
    }

    /**
     * Single worker
     */
    public function ajax_worker()
    {
        $this->validate_access_rights();

        $this->parse_single_model('ea_staff');
    }

    /**
     * Workers collection
     */
    public function ajax_connections()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_connections');
        }

        header("Content-Type: application/json");

        die(json_encode($response));
    }

    /**
     * Single connection
     */
    public function ajax_connection()
    {
        $this->validate_access_rights();

        $this->parse_single_model('ea_connections');
    }

    /**
     * Get list of free days inside month
     */
    public function ajax_month_status()
    {
        //check_ajax_referer('ea-bootstrap-form', 'check');
        unset($_GET['check']);



        $data = $this->parse_input_data();
        //custom validation start here
        // some basic validation  for empty value
        if ( empty( $data['location'] ) ) {
            $translation = __('Location value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['service'] ) ) {
            $translation = __('Service value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['worker'] ) ) {
            $translation = __('Worker value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['month'] ) ) {
            $translation = __('Month value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( empty( $data['year'] ) ) {
            $translation = __('Year value is not provided', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        //invalid data validation
        if ( is_numeric( $data['location'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $data['service'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $data['worker'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $data['month'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        }
        if ( is_numeric( $data['year'] ) == false ) {
            $translation = __('Invalid data format', 'easy-appointments');
            $this->send_err_json_result('{"err": true, "message": "' . $translation . '"}');
        } //custom validation end here
        $response = $this->report->get_available_dates($data['location'], $data['service'], $data['worker'], $data['month'], $data['year']);

        $this->send_ok_json_result($response);
    }

    public function ajax_field()
    {
        $this->validate_access_rights();

        // we need to add slug
        $data = $this->parse_input_data();

        $table = 'ea_meta_fields';

        // we need to parse new and update case
        if ($this->type == 'NEW' || $this->type == 'UPDATE') {

            $data['slug'] = sanitize_title($data['label']);
            $response = $this->models->replace($table, $data, true);

            if ($response == false) {
                $this->send_err_json_result('{"err":true}');
            }

            $this->send_ok_json_result($response);
        }

        $this->parse_single_model($table);
    }

    public function ajax_fields()
    {
        $this->validate_access_rights();

        $data = $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
//            $response = $this->models->get_all_rows('ea_meta_fields', $data);
            $response = $this->models->get_all_rows('ea_meta_fields');
        }

        die(json_encode($response));
    }

    /**
     * Services collection
     */
    public function ajax_errors()
    {
        $this->validate_access_rights();

        $this->parse_input_data();

        $response = array();

        if ($this->type === 'GET') {
            $response = $this->models->get_all_rows('ea_error_logs');
        }

        die(json_encode($response));
    }

    public function ajax_test_mail()
    {
        $this->validate_access_rights();

        $address = $_POST['address'];
        $native = $_POST['native'];

        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            die(__('Invalid email address!', 'easy-appointments'));
        }

        if (!current_user_can('install_plugins')) {
            die(__('Only admin user can test mail!', 'easy-appointments'));
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');

        $send_from = $this->options->get_option_value('send.from.email', '');

        if (!empty($send_from)) {
            $headers[] = 'From: ' . $send_from;
        }

        $files = array();

        $subject = 'Test mail';

        $body = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        if ($native) {
            mail($address, $subject, $body, implode("\n", $headers));
        } else {
            wp_mail($address, $subject, $body, $headers, $files);
        }

        die(__('Request completed, please check email.', 'easy-appointments'));
    }

    /**
     * REST enter point
     */
    private function parse_input_data()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if (!empty($_REQUEST['_method'])) {
            $method = strtoupper($_REQUEST['_method']);
            unset($_REQUEST['_method']);
        }

        $data = array();

        switch ($method) {
            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                $this->type = 'NEW';
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                $this->type = 'UPDATE';
                break;

            case 'GET':
                $data = $_REQUEST;
                $this->type = 'GET';
                break;

            case 'DELETE':
                $data = $_REQUEST;
                $this->type = 'DELETE';
                break;
        }

        return $data;
    }

    /**
     * Ajax call for report data
     */
    public function ajax_report()
    {
        $this->validate_access_rights();

        $data = $this->parse_input_data();

        $type = $data['report'];

        $response = $this->report->get($type, $data);

        $this->send_ok_json_result($response);
    }

    public function ajax_export()
    {
        $this->validate_access_rights();

        $data = $this->parse_input_data();

        $workersTmp = $response = $this->models->get_all_rows('ea_staff');
        $locationsTmp = $response = $this->models->get_all_rows('ea_locations');
        $servicesTmp = $response = $this->models->get_all_rows('ea_services');

        $app_fields = array('id', 'location', 'service', 'worker', 'date', 'start', 'end', 'end_date', 'status', 'user', 'price', 'ip', 'session');
        $meta_fields_tmp = $this->models->get_all_rows('ea_meta_fields');

        $workers = array();
        $locations = array();
        $services = array();

        foreach ($workersTmp as $w) {
            $workers[$w->id] = $w->name;
        }

        foreach ($locationsTmp as $l) {
            $locations[$l->id] = $l->name;
        }

        foreach ($servicesTmp as $s) {
            $services[$s->id] = $s->name;
        }

        foreach ($meta_fields_tmp as $item) {
            $app_fields[] = $item->slug;
        }

        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=Customers_Export.csv');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        // set_time_limit(0);

        $params = array(
            'from' => $data['ea-export-from'],
            'to'   => $data['ea-export-to']
        );

        $rows = $this->models->get_all_appointments($params);

        $out = fopen('php://output', 'w');

        if (count($rows) > 0) {
            fputcsv($out, $app_fields);
        }

        foreach ($rows as $row) {
            $arr = get_object_vars($row);
            $app = array();

            foreach ($app_fields as $field) {

                // if key is not existing
                if (!array_key_exists($field, $arr)) {
                    $app[] = '';
                    continue;
                }

                if ($field == 'worker') {
                    $app[] = $workers[$arr['worker']];
                    continue;
                }

                if ($field == 'location') {
                    $app[] = $locations[$arr['location']];
                    continue;
                }

                if ($field == 'service') {
                    $app[] = $workers[$arr['service']];
                    continue;
                }

                $app[] = $arr[$field];
            }

            fputcsv($out, $app);
        }

        fclose($out);
        die;
    }

    /**
     * @param $table
     * @param bool $end
     * @return array|bool|false|int|null|object|stdClass|void
     */
    private function parse_single_model($table, $end = true)
    {
        $data = $this->parse_input_data();

        if (!$end) {
            $this->data = $data;
        }

        $response = array();

        switch ($this->type) {
            case 'GET':
                $id = (int)$_GET['id'];
                $response = $this->models->get_row($table, $id);
                break;
            case 'UPDATE':
            case 'NEW':
                $response = $this->models->replace($table, $data, true);
                break;
            case 'DELETE':
                $data = $_GET;
                $response = $this->models->delete($table, $data, true);
                break;
        }

        if ($response == false) {
            $this->send_err_json_result('{"err":true}');
        }

        if ($end) {
            $this->send_ok_json_result($response);
        } else {
            return $response;
        }
    }

    /**
     * @param bool $end
     * @return array|bool|false|int|null|object|stdClass|void
     */
    private function parse_appointment($end = true)
    {
        $data = $this->parse_input_data();

        if (!$end) {
            $this->data = $data;
        }

        $table = 'ea_appointments';
        $fields = 'ea_fields';

        $app_fields = array('id', 'location', 'service', 'worker', 'date', 'start', 'end', 'end_date', 'status', 'user', 'price');
        $app_data = array();

        foreach ($app_fields as $value) {
            if (array_key_exists($value, $data)) {
                $app_data[$value] = $data[$value];
            }
        }

        // set end data
        $service = $this->models->get_row('ea_services', $app_data['service']);
        $end_time = strtotime("{$data['start']} + {$service->duration} minute");
        $app_data['end'] = date('H:i', $end_time);


        $meta_fields = $this->models->get_all_rows('ea_meta_fields');
        $meta_data = array();

        foreach ($meta_fields as $value) {
            if (array_key_exists($value->slug, $data)) {
                $meta_data[] = array(
                    'app_id'   => null,
                    'field_id' => $value->id,
                    'value'    => $data[$value->slug]
                );
            }
        }

        $response = array();

        switch ($this->type) {
            case 'GET':
                $id = (int)$_GET['id'];
                $response = $this->models->get_row($table, $id);
                break;
            case 'UPDATE':
                $response = $this->models->replace($table, $app_data, true);

                $this->models->delete($fields, array('app_id' => $app_data['id']), true);

                foreach ($meta_data as $value) {
                    $value['app_id'] = $app_data['id'];
                    $this->models->replace($fields, $value, true, true);
                }

                // edit app
                do_action('ea_edit_app', $app_data['id']);

                break;
            case 'NEW':
                $response = $this->models->replace($table, $app_data, true);
                foreach ($meta_data as $value) {
                    $value['app_id'] = $response->id;
                    $this->models->replace($fields, $value, true, true);
                }

                // trigger new appointment
                do_action('ea_new_app', $response->id, $app_data, false);

                break;
            case 'DELETE':
                $data = $_GET;
                $response = $this->models->delete($table, $data, true);
                $this->models->delete($table, array('app_id' => $app_data['id']), true);
                break;
        }

        if ($response == false) {
            $this->send_err_json_result('{"err":true}');
        }

        if ($end) {
            $this->send_ok_json_result($response);
        } else {
            return $response;
        }
    }

    private function send_ok_json_result($result)
    {
        // $array = array('Response'=>'Success','data'=>$result);
        header("Content-Type: application/json");
         die(json_encode($result));
    }

    private function send_err_json_result($message)
    {
        header('HTTP/1.1 400 BAD REQUEST');
        die($message);
    }

    private function validate_access_rights()
    {
        if (!current_user_can( 'manage_options' )) {
            header('HTTP/1.1 403 Forbidden');
            die('You don\'t have rights for this action');
        }
    }
}
