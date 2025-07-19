<?php
/**
 * Teachers admin management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Hamdy_Admin_Teachers {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_hamdy_save_teacher', array($this, 'ajax_save_teacher'));
        add_action('wp_ajax_hamdy_delete_teacher', array($this, 'ajax_delete_teacher'));
        add_action('wp_ajax_hamdy_get_teacher', array($this, 'ajax_get_teacher'));
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
        $teachers = Hamdy_Teacher::get_all();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Teachers Management', 'hamdy-plugin'); ?>
                <a href="<?php echo admin_url('admin.php?page=hamdy-teachers&action=add'); ?>" class="page-title-action">
                    <?php _e('Add New Teacher', 'hamdy-plugin'); ?>
                </a>
            </h1>
            
            <?php if (empty($teachers)): ?>
                <div class="hamdy-empty-state">
                    <h2><?php _e('No teachers found', 'hamdy-plugin'); ?></h2>
                    <p><?php _e('Add your first teacher to get started with the booking system.', 'hamdy-plugin'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=hamdy-teachers&action=add'); ?>" class="button button-primary">
                        <?php _e('Add New Teacher', 'hamdy-plugin'); ?>
                    </a>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Photo', 'hamdy-plugin'); ?></th>
                            <th><?php _e('Name', 'hamdy-plugin'); ?></th>
                            <th><?php _e('Gender', 'hamdy-plugin'); ?></th>
                            <th><?php _e('Age Group', 'hamdy-plugin'); ?></th>
                            <th><?php _e('Status', 'hamdy-plugin'); ?></th>
                            <th><?php _e('Actions', 'hamdy-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td>
                                    <?php if ($teacher->photo): ?>
                                        <img src="<?php echo esc_url($teacher->photo); ?>" alt="<?php echo esc_attr($teacher->name); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="hamdy-avatar-placeholder" style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                            <?php echo strtoupper(substr($teacher->name, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($teacher->name); ?></strong></td>
                                <td><?php echo ucfirst($teacher->gender); ?></td>
                                <td><?php echo ucfirst($teacher->age_group); ?></td>
                                <td>
                                    <span class="hamdy-status hamdy-status-<?php echo $teacher->status; ?>">
                                        <?php echo ucfirst($teacher->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=hamdy-teachers&action=edit&teacher_id=' . $teacher->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'hamdy-plugin'); ?>
                                    </a>
                                    <button class="button button-small button-link-delete hamdy-delete-teacher" data-teacher-id="<?php echo $teacher->id; ?>">
                                        <?php _e('Delete', 'hamdy-plugin'); ?>
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
            <h1><?php _e('Add New Teacher', 'hamdy-plugin'); ?></h1>
            
            <form method="post" class="hamdy-teacher-form" enctype="multipart/form-data">
                <?php wp_nonce_field('hamdy_save_teacher', 'hamdy_teacher_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="teacher_name"><?php _e('Name', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="teacher_name" name="teacher_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_photo"><?php _e('Photo', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="teacher_photo" name="teacher_photo" class="regular-text" placeholder="<?php _e('Photo URL', 'hamdy-plugin'); ?>">
                            <p class="description"><?php _e('Enter the URL of the teacher\'s photo or upload via Media Library.', 'hamdy-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_gender"><?php _e('Gender', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_gender" name="teacher_gender" required>
                                <option value=""><?php _e('Select Gender', 'hamdy-plugin'); ?></option>
                                <option value="man"><?php _e('Man', 'hamdy-plugin'); ?></option>
                                <option value="woman"><?php _e('Woman', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_age_group"><?php _e('Age Group', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_age_group" name="teacher_age_group" required>
                                <option value=""><?php _e('Select Age Group', 'hamdy-plugin'); ?></option>
                                <option value="adults"><?php _e('Adults', 'hamdy-plugin'); ?></option>
                                <option value="children"><?php _e('Children', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Availability', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <div class="hamdy-availability-grid">
                                <?php $this->display_availability_grid(); ?>
                            </div>
                            <p class="description"><?php _e('Select the time slots when this teacher is available.', 'hamdy-plugin'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_status"><?php _e('Status', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="teacher_status" name="teacher_status">
                                <option value="active"><?php _e('Active', 'hamdy-plugin'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_teacher" class="button-primary" value="<?php _e('Add Teacher', 'hamdy-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=hamdy-teachers'); ?>" class="button">
                        <?php _e('Cancel', 'hamdy-plugin'); ?>
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
        $teacher = Hamdy_Teacher::get_by_id($teacher_id);
        
        if (!$teacher) {
            echo '<div class="notice notice-error"><p>' . __('Teacher not found.', 'hamdy-plugin') . '</p></div>';
            return;
        }
        
        $availability = json_decode($teacher->availability, true) ?: array();
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Teacher', 'hamdy-plugin'); ?></h1>
            
            <form method="post" class="hamdy-teacher-form" enctype="multipart/form-data">
                <?php wp_nonce_field('hamdy_save_teacher', 'hamdy_teacher_nonce'); ?>
                <input type="hidden" name="teacher_id" value="<?php echo $teacher->id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="teacher_name"><?php _e('Name', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="teacher_name" name="teacher_name" class="regular-text" value="<?php echo esc_attr($teacher->name); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_photo"><?php _e('Photo', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="teacher_photo" name="teacher_photo" class="regular-text" value="<?php echo esc_attr($teacher->photo); ?>" placeholder="<?php _e('Photo URL', 'hamdy-plugin'); ?>">
                            <?php if ($teacher->photo): ?>
                                <div class="hamdy-current-photo" style="margin-top: 10px;">
                                    <img src="<?php echo esc_url($teacher->photo); ?>" alt="<?php echo esc_attr($teacher->name); ?>" style="max-width: 100px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_gender"><?php _e('Gender', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_gender" name="teacher_gender" required>
                                <option value=""><?php _e('Select Gender', 'hamdy-plugin'); ?></option>
                                <option value="man" <?php selected($teacher->gender, 'man'); ?>><?php _e('Man', 'hamdy-plugin'); ?></option>
                                <option value="woman" <?php selected($teacher->gender, 'woman'); ?>><?php _e('Woman', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_age_group"><?php _e('Age Group', 'hamdy-plugin'); ?> *</label>
                        </th>
                        <td>
                            <select id="teacher_age_group" name="teacher_age_group" required>
                                <option value=""><?php _e('Select Age Group', 'hamdy-plugin'); ?></option>
                                <option value="adults" <?php selected($teacher->age_group, 'adults'); ?>><?php _e('Adults', 'hamdy-plugin'); ?></option>
                                <option value="children" <?php selected($teacher->age_group, 'children'); ?>><?php _e('Children', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Availability', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <div class="hamdy-availability-grid">
                                <?php $this->display_availability_grid($availability); ?>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="teacher_status"><?php _e('Status', 'hamdy-plugin'); ?></label>
                        </th>
                        <td>
                            <select id="teacher_status" name="teacher_status">
                                <option value="active" <?php selected($teacher->status, 'active'); ?>><?php _e('Active', 'hamdy-plugin'); ?></option>
                                <option value="inactive" <?php selected($teacher->status, 'inactive'); ?>><?php _e('Inactive', 'hamdy-plugin'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_teacher" class="button-primary" value="<?php _e('Update Teacher', 'hamdy-plugin'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=hamdy-teachers'); ?>" class="button">
                        <?php _e('Cancel', 'hamdy-plugin'); ?>
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
            'sunday' => __('Sunday', 'hamdy-plugin'),
            'monday' => __('Monday', 'hamdy-plugin'),
            'tuesday' => __('Tuesday', 'hamdy-plugin'),
            'wednesday' => __('Wednesday', 'hamdy-plugin'),
            'thursday' => __('Thursday', 'hamdy-plugin'),
            'friday' => __('Friday', 'hamdy-plugin'),
            'saturday' => __('Saturday', 'hamdy-plugin')
        );
        
        echo '<div class="hamdy-availability-days">';
        
        foreach ($days as $day_key => $day_name) {
            echo '<div class="hamdy-day-column">';
            echo '<h4>' . $day_name . '</h4>';
            
            // Generate 24 hour slots (00:00 to 23:00)
            for ($hour = 0; $hour < 24; $hour++) {
                $time_slot = sprintf('%02d:00', $hour);
                $is_checked = isset($availability[$day_key]) && in_array($time_slot, $availability[$day_key]);
                
                echo '<label class="hamdy-time-slot">';
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
        if (!isset($_POST['save_teacher']) || !wp_verify_nonce($_POST['hamdy_teacher_nonce'], 'hamdy_save_teacher')) {
            return;
        }
        
        $teacher_data = array(
            'name' => sanitize_text_field($_POST['teacher_name']),
            'photo' => sanitize_url($_POST['teacher_photo']),
            'gender' => sanitize_text_field($_POST['teacher_gender']),
            'age_group' => sanitize_text_field($_POST['teacher_age_group']),
            'availability' => isset($_POST['availability']) ? $_POST['availability'] : array(),
            'status' => sanitize_text_field($_POST['teacher_status'])
        );
        
        if (isset($_POST['teacher_id']) && !empty($_POST['teacher_id'])) {
            // Update existing teacher
            $result = Hamdy_Teacher::update(intval($_POST['teacher_id']), $teacher_data);
            $message = $result ? __('Teacher updated successfully.', 'hamdy-plugin') : __('Failed to update teacher.', 'hamdy-plugin');
        } else {
            // Create new teacher
            $result = Hamdy_Teacher::create($teacher_data);
            $message = $result ? __('Teacher created successfully.', 'hamdy-plugin') : __('Failed to create teacher.', 'hamdy-plugin');
        }
        
        $notice_class = $result ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . $message . '</p></div>';
        
        if ($result) {
            echo '<script>setTimeout(function(){ window.location.href = "' . admin_url('admin.php?page=hamdy-teachers') . '"; }, 1500);</script>';
        }
    }
    
    /**
     * AJAX: Save teacher
     */
    public function ajax_save_teacher() {
        check_ajax_referer('hamdy_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'hamdy-plugin'));
        }
        
        // Handle AJAX save logic here
        wp_send_json_success(array('message' => __('Teacher saved successfully.', 'hamdy-plugin')));
    }
    
    /**
     * AJAX: Delete teacher
     */
    public function ajax_delete_teacher() {
        check_ajax_referer('hamdy_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'hamdy-plugin'));
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        $result = Hamdy_Teacher::delete($teacher_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Teacher deleted successfully.', 'hamdy-plugin')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete teacher.', 'hamdy-plugin')));
        }
    }
    
    /**
     * AJAX: Get teacher
     */
    public function ajax_get_teacher() {
        check_ajax_referer('hamdy_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'hamdy-plugin'));
        }
        
        $teacher_id = intval($_POST['teacher_id']);
        $teacher = Hamdy_Teacher::get_by_id($teacher_id);
        
        if ($teacher) {
            wp_send_json_success($teacher);
        } else {
            wp_send_json_error(array('message' => __('Teacher not found.', 'hamdy-plugin')));
        }
    }
}