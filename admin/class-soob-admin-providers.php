<?php
/**
 * Providers admin management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SOOB_Admin_Providers {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_soob_save_provider', array($this, 'ajax_save_provider'));
        add_action('wp_ajax_soob_delete_provider', array($this, 'ajax_delete_provider'));
        add_action('wp_ajax_soob_get_provider', array($this, 'ajax_get_provider'));
    }
    
    /**
     * Enqueue scripts and styles for providers page
     */
    public function enqueue_scripts() {
        // Enqueue admin providers styles
        wp_enqueue_style('soob-admin-providers', SOOB_PLUGIN_URL . 'assets/css/admin-providers.css', array(), SOOB_PLUGIN_VERSION);

        // Do NOT enqueue JS here. Script is registered and enqueued with proper dependencies in SOOB_Admin.
        // We only localize data onto the already registered handle.
        wp_localize_script('soob-admin-providers', 'soob_providers_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('soob_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this provider?', 'soob-plugin'),
                'loading' => __('Loading...', 'soob-plugin'),
                'saved' => __('Provider saved successfully!', 'soob-plugin'),
                'error' => __('An error occurred. Please try again.', 'soob-plugin'),
            )
        ));
    }
    
    /**
     * Display providers page
     */
    public function display_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
        
        switch ($action) {
            case 'add':
                $this->display_add_provider_form();
                break;
            case 'edit':
                $this->display_edit_provider_form($provider_id);
                break;
            default:
                $this->display_providers_list();
                break;
        }
    }
    
    /**
     * Display providers list
     */
    private function display_providers_list() {
        // Changed query to include all statuses instead of only active providers
        $providers = SOOB_Provider::get_all('');
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Providers Management', 'soob-plugin'); ?>
                <a href="<?php echo admin_url('admin.php?page=soob-providers&action=add'); ?>" class="page-title-action">
                    <?php _e('Add New Provider', 'soob-plugin'); ?>
                </a>
            </h1>
            
            <?php if (empty($providers)): ?>
                <div class="soob-empty-state">
                    <h2><?php _e('No providers found', 'soob-plugin'); ?></h2>
                    <p><?php _e('Add your first provider to get started with the booking system.', 'soob-plugin'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=soob-providers&action=add'); ?>" class="button button-primary">
                        <?php _e('Add New Provider', 'soob-plugin'); ?>
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
                        <?php foreach ($providers as $provider): ?>
                            <tr>
                                <td>
                                    <?php if ($provider->photo): ?>
                                        <img src="<?php echo esc_url($provider->photo); ?>" alt="<?php echo esc_attr($provider->name); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="soob-avatar-placeholder" style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                            <?php echo strtoupper(substr($provider->name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($provider->name); ?></strong></td>
                                <td><?php echo ucfirst($provider->gender); ?></td>
                                <td>
                                    <?php
                                    // Map internal status values to exact "Active" or "Inactive" strings
                                    $status_display = '';
                                    if ($provider->status === 'active' || $provider->status === '1' || $provider->status == 1) {
                                        $status_display = __('Active', 'soob-plugin'); // Rendered and escaped the status value with internationalization
                                    } else {
                                        $status_display = __('Inactive', 'soob-plugin'); // Rendered and escaped the status value with internationalization
                                    }
                                    ?>
                                    <span class="soob-status soob-status-<?php echo esc_attr($provider->status); ?>">
                                        <?php echo esc_html($status_display); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=soob-providers&action=edit&provider_id=' . $provider->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'soob-plugin'); ?>
                                    </a>
                                    <button class="button button-small button-link-delete soob-delete-provider" data-provider-id="<?php echo $provider->id; ?>">
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
     * Display add provider form
     */
    private function display_add_provider_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Provider', 'soob-plugin'); ?></h1>
            
            <form method="post" class="soob-provider-form" enctype="multipart/form-data">
                <?php wp_nonce_field('soob_save_provider', 'soob_provider_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="provider_name"><?php _e('Name', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="provider_name" name="provider_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_photo"><?php _e('Photo', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="provider_photo" name="provider_photo" class="regular-text" placeholder="<?php _e('Photo URL', 'soob-plugin'); ?>">
                            <p class="description"><?php _e('Enter the URL of the provider\'s photo or upload via Media Library.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_gender"><?php _e('Gender', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="provider_gender" name="provider_gender" required>
                                <option value=""><?php _e('Select Gender', 'soob-plugin'); ?></option>
                                <option value="male"><?php _e('Male', 'soob-plugin'); ?></option>
                                <option value="female"><?php _e('Female', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_timezone"><?php _e('Timezone', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="provider_timezone" name="provider_timezone" required>
                                <option value=""><?php _e('Select Timezone', 'soob-plugin'); ?></option>
                                <?php
                                // Build timezone list from IANA identifiers, sorted by current UTC offset (cached daily)
                                $selected_tz = isset($_POST['provider_timezone']) ? sanitize_text_field($_POST['provider_timezone']) : '';
                                $zoneOffsets = $this->get_sorted_timezones_today();
                                foreach ($zoneOffsets as $row) {
                                    $seconds = (int) $row['offset'];
                                    $sign = $seconds >= 0 ? '+' : '-';
                                    $abs = abs($seconds);
                                    $hours = floor($abs / 3600);
                                    $minutes = floor(($abs % 3600) / 60);

                                    if ($seconds === 0) {
                                        $offset_label = 'UTC';
                                    } else {
                                        $offset_label = $minutes
                                            ? sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes)
                                            : sprintf('UTC%s%d', $sign, $hours);
                                    }

                                    $val = $row['id'];
                                    $sel = selected($selected_tz, $val, false);
                                    echo '<option value="' . esc_attr($val) . '" ' . $sel . '>(' . esc_html($offset_label) . ') ' . esc_html($val) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the provider timezone. This affects the provider\'s availability schedule.', 'soob-plugin'); ?></p>
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
                            <p class="description"><?php _e('Select the time slots when this provider is available. Times are in the selected timezone above.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_status"><?php _e('Status', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="provider_status" name="provider_status">
                                <option value="active"><?php _e('Active', 'soob-plugin'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_provider" class="button-primary" value="<?php _e('Add Provider', 'soob-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=soob-providers'); ?>" class="button">
                        <?php _e('Cancel', 'soob-plugin'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
        
        $this->handle_form_submission();
    }
    
    /**
     * Display edit provider form
     */
    private function display_edit_provider_form($provider_id) {
        $provider = SOOB_Provider::get_by_id($provider_id);
        
        if (!$provider) {
            echo '<div class="notice notice-error"><p>' . __('Provider not found.', 'soob-plugin') . '</p></div>';
            return;
        }
        
        $raw_availability = json_decode($provider->availability, true) ?: array();
        
        // Auto-fetch provider timezone with fallbacks: provider -> user_meta -> site -> UTC
        // This ensures proper timezone is loaded when opening provider page
        $display_timezone = SOOB_Provider::get_provider_timezone($provider_id);
        $availability = $this->convert_availability_from_utc($raw_availability, $display_timezone);
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Provider', 'soob-plugin'); ?></h1>
            
            <form method="post" class="soob-provider-form" enctype="multipart/form-data">
                <?php wp_nonce_field('soob_save_provider', 'soob_provider_nonce'); ?>
                <input type="hidden" name="provider_id" value="<?php echo $provider->id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="provider_name"><?php _e('Name', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="provider_name" name="provider_name" class="regular-text" value="<?php echo esc_attr($provider->name); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_photo"><?php _e('Photo', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="provider_photo" name="provider_photo" class="regular-text" value="<?php echo esc_attr($provider->photo); ?>" placeholder="<?php _e('Photo URL', 'soob-plugin'); ?>">
                            <?php if ($provider->photo): ?>
                                <div class="soob-current-photo" style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($provider->photo); ?>" alt="<?php echo esc_attr($provider->name); ?>" style="max-width: 100px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_gender"><?php _e('Gender', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="provider_gender" name="provider_gender" required>
                                <option value=""><?php _e('Select Gender', 'soob-plugin'); ?></option>
                                <option value="male" <?php selected($provider->gender, 'male'); ?>><?php _e('Male', 'soob-plugin'); ?></option>
                                <option value="female" <?php selected($provider->gender, 'female'); ?>><?php _e('Female', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_timezone"><?php _e('Timezone', 'soob-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="provider_timezone" name="provider_timezone" required>
                                <option value=""><?php _e('Select Timezone', 'soob-plugin'); ?></option>
                                <?php
                                // Build timezone list from IANA identifiers, sorted by current UTC offset (cached daily)
                                $current_timezone = SOOB_Provider::get_provider_timezone($provider_id);
                                $selected_tz = isset($_POST['provider_timezone'])
                                    ? sanitize_text_field($_POST['provider_timezone'])
                                    : (!empty($provider->timezone) ? $provider->timezone : $current_timezone);

                                $zoneOffsets = $this->get_sorted_timezones_today();

                                foreach ($zoneOffsets as $row) {
                                    $seconds = (int) $row['offset'];
                                    $sign = $seconds >= 0 ? '+' : '-';
                                    $abs = abs($seconds);
                                    $hours = floor($abs / 3600);
                                    $minutes = floor(($abs % 3600) / 60);

                                    if ($seconds === 0) {
                                        $offset_label = 'UTC';
                                    } else {
                                        $offset_label = $minutes
                                            ? sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes)
                                            : sprintf('UTC%s%d', $sign, $hours);
                                    }

                                    $val = $row['id'];
                                    $sel = selected($selected_tz, $val, false);
                                    echo '<option value="' . esc_attr($val) . '" ' . $sel . '>(' . esc_html($offset_label) . ') ' . esc_html($val) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Select the provider timezone. This affects the availability schedule display.', 'soob-plugin'); ?></p>
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
                            <p class="description"><?php _e('Select the time slots when this provider is available. Times are in the selected timezone above.', 'soob-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="provider_status"><?php _e('Status', 'soob-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="provider_status" name="provider_status">
                                <option value="active" <?php selected($provider->status, 'active'); ?>><?php _e('Active', 'soob-plugin'); ?></option>
                                <option value="inactive" <?php selected($provider->status, 'inactive'); ?>><?php _e('Inactive', 'soob-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_provider" class="button-primary" value="<?php _e('Update Provider', 'soob-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=soob-providers'); ?>" class="button">
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
        if (!isset($_POST['save_provider']) || !wp_verify_nonce($_POST['soob_provider_nonce'], 'soob_save_provider')) {
            return;
        }

        // Capability check for admin actions
        if (!current_user_can('manage_woocommerce')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Insufficient permissions.', 'soob-plugin') . '</p></div>';
            return;
        }
        
        // Get and validate timezone from form submission
        $provider_timezone = sanitize_text_field($_POST['provider_timezone']);

        // Validate timezone strictly against IANA list
        $iana_list = timezone_identifiers_list();
        if (!in_array($provider_timezone, $iana_list, true)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Invalid timezone selected. Please choose a valid option.', 'soob-plugin') . '</p></div>';
            return;
        }
        
        // Convert availability times from admin's selected timezone to UTC before saving
        $availability = isset($_POST['availability']) ? $_POST['availability'] : array();
        $utc_availability = $this->convert_availability_to_utc($availability, $provider_timezone);
        
        $provider_data = array(
            'name' => sanitize_text_field($_POST['provider_name']),
            'photo' => sanitize_url($_POST['provider_photo']),
            'gender' => sanitize_text_field($_POST['provider_gender']),
            'timezone' => $provider_timezone, // Include timezone in provider data
            'availability' => $utc_availability,
            'status' => sanitize_text_field($_POST['provider_status'])
        );
        
        if (isset($_POST['provider_id']) && !empty($_POST['provider_id'])) {
            // Update existing provider
            $result = SOOB_Provider::update(intval($_POST['provider_id']), $provider_data);
            $message = $result ? __('Provider updated successfully.', 'soob-plugin') : __('Failed to update provider.', 'soob-plugin');
        } else {
            // Create new provider
            $result = SOOB_Provider::create($provider_data);
            $message = $result ? __('Provider created successfully.', 'soob-plugin') : __('Failed to create provider.', 'soob-plugin');
        }
        
        $notice_class = $result ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . $message . '</p></div>';
        
        if ($result) {
            echo '<script>setTimeout(function(){ window.location.href = "' . admin_url('admin.php?page=soob-providers') . '"; }, 1500);</script>';
        }
    }
    
    /**
     * AJAX: Save provider
     */
    public function ajax_save_provider() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        // Handle AJAX save logic here
        wp_send_json_success(array('message' => __('Provider saved successfully.', 'soob-plugin')));
    }
    
    /**
     * AJAX: Delete provider
     */
    public function ajax_delete_provider() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $provider_id = intval($_POST['provider_id']);
        $result = SOOB_Provider::delete($provider_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Provider deleted successfully.', 'soob-plugin')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete provider.', 'soob-plugin')));
        }
    }
    
    /**
     * AJAX: Get provider
     */
    public function ajax_get_provider() {
        check_ajax_referer('soob_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'soob-plugin'));
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = SOOB_Provider::get_by_id($provider_id);
        
        if ($provider) {
            wp_send_json_success($provider);
        } else {
            wp_send_json_error(array('message' => __('Provider not found.', 'soob-plugin')));
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

    /**
     * Get sorted timezone offsets list for today, cached via transient (12 hours).
     * @return array Array of ['id' => IANA, 'offset' => int seconds], sorted by offset asc then id asc.
     */
    private function get_sorted_timezones_today() {
        // get/set transient 'soob_tz_offsets_{Ymd}'
        $key = 'soob_tz_offsets_' . gmdate('Ymd');
        $data = get_transient($key);
        if ($data !== false && is_array($data)) {
            return $data;
        }

        $zones = timezone_identifiers_list();
        $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
        $zoneOffsets = array();

        foreach ($zones as $z) {
            try {
                $tzObj = new DateTimeZone($z);
                $offset = $tzObj->getOffset($nowUtc);
                $zoneOffsets[] = array('id' => $z, 'offset' => $offset);
            } catch (Exception $e) {
                // Skip invalid zone if any
            }
        }

        usort($zoneOffsets, function($a, $b) {
            if ($a['offset'] === $b['offset']) {
                return strcmp($a['id'], $b['id']);
            }
            return $a['offset'] - $b['offset'];
        });

        set_transient($key, $zoneOffsets, 12 * HOUR_IN_SECONDS);
        return $zoneOffsets;
    }
}