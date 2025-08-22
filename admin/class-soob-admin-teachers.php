<?php
/**
 * Teachers admin management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Admin_Teachers {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_soob_save_teacher', array($this, 'ajax_save_teacher'));
        add_action('wp_ajax_soob_delete_teacher', array($this, 'ajax_delete_teacher'));
        add_action('wp_ajax_soob_get_teacher', array($this, 'ajax_get_teacher'));
    }
    
    /**
     * Enqueue scripts and styles for teachers page
     */
    public function enqueue_scripts() {
        // Enqueue admin teachers styles if needed
        wp_enqueue_style('soob-admin-teachers', SOOB_PLUGIN_URL . 'assets/css/admin-teachers.css', array(), SOOB_PLUGIN_VERSION);
        
        // Enqueue admin teachers JavaScript if needed
        wp_enqueue_script('soob-admin-teachers', SOOB_PLUGIN_URL . 'assets/js/admin-teachers.js', array('jquery'), SOOB_PLUGIN_VERSION, true);
        
        // Localize script for AJAX with unique variable name
        wp_localize_script('soob-admin-teachers', 'soob_teachers_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('soob_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this teacher?', 'soob-plugin'),
                'loading' => __('Loading...', 'soob-plugin'),
                'saved' => __('Teacher saved successfully!', 'soob-plugin'),
                'error' => __('An error occurred. Please try again.', 'soob-plugin'),
            )
        ));
    }
    
    /**
     * Display teachers page
     */
    public function display_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
        
        switch ($action) {
            case 'add':
                $this->display_add_teacher_form();
                break;
            case 'edit':
                $this->display_edit_teacher_form($teacher_id);
                break;
            default:
                $this->display_teachers_list();
                break;
        }
    }
    
    /**
     * Display teachers list
     */
    private function display_teachers_list() {
        $teachers = SOOB_Teacher::get_all();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Teachers Management', 'soob-plugin'); ?>
                <a href="<?php echo admin_url('admin.php?page=soob-teachers&action=add'); ?>" class="page-title-action">
                    <?php _e('Add New Teacher', 'soob-plugin'); ?>
                </a>
            </h1>
            
            <?php if (empty($teachers)): ?>
                <div class="soob-empty-state">
                    <h2><?php _e('No teachers found', 'soob-plugin'); ?></h2>
                    <p><?php _e('Add your first teacher to get started with the booking system.', 'soob-plugin'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=soob-teachers&action=add'); ?>" class="button button-primary">
                        <?php _e('Add New Teacher', 'soob-plugin'); ?>
                    </a>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Photo', 'soob-plugin'); ?></th>
                            <th><?php _e('Name', 'soob-plugin'); ?></th>
                            <th><?php _e('Gender', 'soob-plugin'); ?></th>
                            <th><?php _e('Status', 'soob-plugin'); ?></th>
                            <th><?php _e('Actions', 'soob-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td>
                                    <?php if ($teacher->photo): ?>
                                        <img src="<?php echo esc_url($teacher->photo); ?>" alt="<?php echo esc_attr($teacher->name); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="soob-avatar-placeholder" style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                            <?php echo strtoupper(substr($teacher->name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($teacher->name); ?></strong></td>
                                <td><?php echo ucfirst($teacher->gender); ?></td>
                                <td>
                                    <span class="soob-status soob-status-<?php echo $teacher->status; ?>">
                                        <?php echo ucfirst($teacher->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=soob-teachers&action=edit&teacher_id=' . $teacher->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'soob-plugin'); ?>
                                    </a>
                                    <button class="button button-small button-link-delete soob-delete-teacher" data-teacher-id="<?php echo $teacher->id; ?>">
                                        <?php _e('Delete', 'soob-plugin'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Display add teacher form
     */
    private function display_add_teacher_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Teacher', 'soob-plugin'); ?></h1>
            
            <form method="post" class="soob-teacher-form" enctype="multipart/form-data">
                <?php wp_nonce_field('soob_save_teacher', 'soob_teacher_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="teacher_name"><?php _e('Name', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="teacher_name" name="teacher_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_photo"><?php _e('Photo', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="teacher_photo" name="teacher_photo" class="regular-text" placeholder="<?php _e('Photo URL', 'soob-plugin'); ?>">
                            <p class="description"><?php _e('Enter the URL of the teacher\'s photo or upload via Media Library.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_gender"><?php _e('Gender', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_gender" name="teacher_gender" required>
                                <option value=""><?php _e('Select Gender', 'soob-plugin'); ?></option>
                                <option value="male"><?php _e('Male', 'soob-plugin'); ?></option>
                                <option value="female"><?php _e('Female', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_timezone"><?php _e('Timezone', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_timezone" name="teacher_timezone" required>
                                <option value=""><?php _e('Select Timezone', 'soob-plugin'); ?></option>
                                <?php
                                $woocommerce = new SOOB_WooCommerce();
                                $timezones = $woocommerce->get_timezone_options();
                                foreach ($timezones as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the timezone for this teacher\'s availability schedule.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Availability', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <div class="soob-availability-grid">
                                <?php $this->display_availability_grid(); ?>
                            </div>
                            <p class="description"><?php _e('Select the time slots when this teacher is available. Times are in the selected timezone above.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_status"><?php _e('Status', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="teacher_status" name="teacher_status">
                                <option value="active"><?php _e('Active', 'soob-plugin'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_teacher" class="button-primary" value="<?php _e('Add Teacher', 'soob-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=soob-teachers'); ?>" class="button">
                        <?php _e('Cancel', 'soob-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
        
        $this->handle_form_submission();
    }
    
    /**
     * Display edit teacher form
     */
    private function display_edit_teacher_form($teacher_id) {
        $teacher = SOOB_Teacher::get_by_id($teacher_id);
        
        if (!$teacher) {
            echo '<div class="notice notice-error"><p>' . __('Teacher not found.', 'soob-plugin') . '</p></div>';
            return;
        }
        
        $raw_availability = json_decode($teacher->availability, true) ?: array();
        
        // Convert UTC availability to display timezone (default to admin's browser timezone)
        $display_timezone = 'UTC'; // Will be updated by JavaScript auto-detection
        $availability = $this->convert_availability_from_utc($raw_availability, $display_timezone);
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Teacher', 'soob-plugin'); ?></h1>
            
            <form method="post" class="soob-teacher-form" enctype="multipart/form-data">
                <?php wp_nonce_field('soob_save_teacher', 'soob_teacher_nonce'); ?>
                <input type="hidden" name="teacher_id" value="<?php echo $teacher->id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="teacher_name"><?php _e('Name', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="teacher_name" name="teacher_name" class="regular-text" value="<?php echo esc_attr($teacher->name); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_photo"><?php _e('Photo', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="teacher_photo" name="teacher_photo" class="regular-text" value="<?php echo esc_attr($teacher->photo); ?>" placeholder="<?php _e('Photo URL', 'soob-plugin'); ?>">
                            <?php if ($teacher->photo): ?>
                                <div class="soob-current-photo" style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($teacher->photo); ?>" alt="<?php echo esc_attr($teacher->name); ?>" style="max-width: 100px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_gender"><?php _e('Gender', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_gender" name="teacher_gender" required>
                                <option value=""><?php _e('Select Gender', 'soob-plugin'); ?></option>
                                <option value="male" <?php selected($teacher->gender, 'male'); ?>><?php _e('Male', 'soob-plugin'); ?></option>
                                <option value="female" <?php selected($teacher->gender, 'female'); ?>><?php _e('Female', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_timezone"><?php _e('Timezone', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_timezone" name="teacher_timezone" required>
                                <option value=""><?php _e('Select Timezone', 'soob-plugin'); ?></option>
                                <?php
                                $woocommerce = new SOOB_WooCommerce();
                                $timezones = $woocommerce->get_timezone_options();
                                foreach ($timezones as $value => $label) {
                                    $selected = ($teacher->timezone === $value) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the timezone for this teacher\'s availability schedule.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Availability', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <div class="soob-availability-grid">
                                <?php $this->display_availability_grid($availability); ?>
                            </div>
                            <p class="description"><?php _e('Select the time slots when this teacher is available. Times are in the selected timezone above.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_status"><?php _e('Status', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="teacher_status" name="teacher_status">
                                <option value="active" <?php selected($teacher->status, 'active'); ?>><?php _e('Active', 'soob-plugin'); ?></option>
                                <option value="inactive" <?php selected($teacher->status, 'inactive'); ?>><?php _e('Inactive', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_teacher" class="button-primary" value="<?php _e('Update Teacher', 'soob-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=soob-teachers'); ?>" class="button">
                        <?php _e('Cancel', 'soob-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
        
        $this->handle_form_submission();
    }
    
    /**
     * Display availability grid
     */
    private function display_availability_grid($availability = array()) {
        $days = array(
            'sunday' => __('Sunday', 'soob-plugin'),
            'monday' => __('Monday', 'soob-plugin'),
            'tuesday' => __('Tuesday', 'soob-plugin'),
            'wednesday' => __('Wednesday', 'soob-plugin'),
            'thursday' => __('Thursday', 'soob-plugin'),
            'friday' => __('Friday', 'soob-plugin'),
            'saturday' => __('Saturday', 'soob-plugin')
        );
        
        echo '<div class="soob-availability-days">';
        
        foreach ($days as $day_key => $day_name) {
            echo '<div class="soob-day-column">';
            echo '<h4>' . $day_name . '</h4>';
            
            // Generate 24 hour slots (00:00 to 23:00)
            for ($hour = 0; $hour < 24; $hour++) {
                $time_slot = sprintf('%02d:00', $hour);
                $is_checked = isset($availability[$day_key]) && in_array($time_slot, $availability[$day_key]);
                
                echo '<label class="soob-time-slot">';
                echo '<input type="checkbox" name="availability[' . $day_key . '][]" value="' . $time_slot . '"' . ($is_checked ? ' checked' : '') . '>';
                echo '<span>' . $time_slot . '</span>';
                echo '</label>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Handle form submission
     */
    private function handle_form_submission() {
        if (!isset($_POST['save_teacher']) || !wp_verify_nonce($_POST['soob_teacher_nonce'], 'soob_save_teacher')) {
            return;
        }
        
        // Convert availability times from admin's selected timezone to UTC before saving
        $admin_timezone = sanitize_text_field($_POST['teacher_timezone']);
        $availability = isset($_POST['availability']) ? $_POST['availability'] : array();
        $utc_availability = $this->convert_availability_to_utc($availability, $admin_timezone);
        
        $teacher_data = array(
            'name' => sanitize_text_field($_POST['teacher_name']),
            'photo' => sanitize_url($_POST['teacher_photo']),
            'gender' => sanitize_text_field($_POST['teacher_gender']),
            'availability' => $utc_availability,
            'status' => sanitize_text_field($_POST['teacher_status'])
        );
        
        if (isset($_POST['teacher_id']) && !empty($_POST['teacher_id'])) {
            // Update existing teacher
            $result = SOOB_Teacher::update(intval($_POST['teacher_id']), $teacher_data);
            $message = $result ? __('Teacher updated successfully.', 'soob-plugin') : __('Failed to update teacher.', 'soob-plugin');
        } else {
            // Create new teacher
            $result = SOOB_Teacher::create($teacher_data);
            $message = $result ? __('Teacher created successfully.', 'soob-plugin') : __('Failed to create teacher.', 'soob-plugin');
        }
        
        $notice_class = $result ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . $message . '</p></div>';
        
        if ($result) {
            echo '<script>setTimeout(function(){ window.location.href = "' . admin_url('admin.php?page=soob-teachers') . '"; }, 1500);</script>';
        }
    }
    
    /**
     * AJAX: Save teacher
     */
    public function ajax_save_teacher() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        // Handle AJAX save logic here
        wp_send_json_success(array('message' => __('Teacher saved successfully.', 'soob-plugin')));
    }
    
    /**
     * AJAX: Delete teacher
     */
    public function ajax_delete_teacher() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        $result = SOOB_Teacher::delete($teacher_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Teacher deleted successfully.', 'soob-plugin')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete teacher.', 'soob-plugin')));
        }
    }
    
    /**
     * AJAX: Get teacher
     */
    public function ajax_get_teacher() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        $teacher = SOOB_Teacher::get_by_id($teacher_id);
        
        if ($teacher) {
            wp_send_json_success($teacher);
        } else {
            wp_send_json_error(array('message' => __('Teacher not found.', 'soob-plugin')));
        }
    }
    
    /**
     * Convert availability times from admin's timezone to UTC
     */
    private function convert_availability_to_utc($availability, $admin_timezone) {
        if (empty($availability) || empty($admin_timezone)) {
            return $availability;
        }
        
        $utc_availability = array();
        
        try {
            $admin_tz = new DateTimeZone($admin_timezone);
            $utc_tz = new DateTimeZone('UTC');
            
            foreach ($availability as $day => $slots) {
                $utc_availability[$day] = array();
                
                foreach ($slots as $slot) {
                    // Create datetime in admin's timezone
                    $datetime = new DateTime($slot, $admin_tz);
                    
                    // Convert to UTC
                    $datetime->setTimezone($utc_tz);
                    
                    $utc_availability[$day][] = $datetime->format('H:i:s');
                }
            }
        } catch (Exception $e) {
            // If conversion fails, return original availability
            error_log('Timezone conversion error: ' . $e->getMessage());
            return $availability;
        }
        
        return $utc_availability;
    }
    
    /**
     * Convert availability times from UTC to display timezone
     */
    private function convert_availability_from_utc($availability, $display_timezone) {
        if (empty($availability) || empty($display_timezone)) {
            return $availability;
        }
        
        $display_availability = array();
        
        try {
            $utc_tz = new DateTimeZone('UTC');
            $display_tz = new DateTimeZone($display_timezone);
            
            foreach ($availability as $day => $slots) {
                $display_availability[$day] = array();
                
                foreach ($slots as $slot) {
                    // Create datetime in UTC
                    $datetime = new DateTime($slot, $utc_tz);
                    
                    // Convert to display timezone
                    $datetime->setTimezone($display_tz);
                    
                    $display_availability[$day][] = $datetime->format('H:i');
                }
            }
        } catch (Exception $e) {
            // If conversion fails, return original availability
            error_log('Timezone conversion error: ' . $e->getMessage());
            return $availability;
        }
        
        return $display_availability;
    }
}