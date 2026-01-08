<?php
/**
 * Plugin Name: Atomic SEO Architect
 * Plugin URI: https://github.com/ogichanchan/atomic-seo-architect
 * Description: A unique PHP-only WordPress utility. A atomic style seo plugin acting as a architect. Focused on simplicity and efficiency.
 * Version: 1.0.0
 * Author: ogichanchan
 * Author URI: https://github.com/ogichanchan
 * License: GPLv2 or later
 * Text Domain: atomic-seo-architect
 */

// Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Atomic_SEO_Architect class
 * Manages all plugin functionality within a single PHP file.
 */
class Atomic_SEO_Architect {

    /**
     * Constructor.
     * Initializes the plugin by setting up hooks for admin and frontend.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_post_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_post_meta' ) );
        add_action( 'wp_head', array( $this, 'output_seo_meta_tags' ), 1 ); // High priority to output early
        add_filter( 'document_title_parts', array( $this, 'filter_document_title_parts' ) );
    }

    /**
     * Adds the plugin's administration menu page under 'Settings'.
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__( 'Atomic SEO Architect Settings', 'atomic-seo-architect' ),
            esc_html__( 'Atomic SEO Architect', 'atomic-seo-architect' ),
            'manage_options',
            'atomic-seo-architect',
            array( $this, 'settings_page_content' )
        );
    }

    /**
     * Renders the plugin's settings page content.
     * Includes inline CSS for basic styling, adhering to the "PHP ONLY" rule.
     */
    public function settings_page_content() {
        // Inline CSS for a consistent and clean look within the admin area.
        echo '<style type="text/css">';
        echo '
            .atomic-seo-architect-settings-wrapper {
                max-width: 960px;
                margin: 30px auto;
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
            }
            .atomic-seo-architect-settings-wrapper h1 {
                margin-top: 0;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            .atomic-seo-architect-settings-wrapper form {
                margin-top: 20px;
            }
            .form-table th {
                width: 250px;
                padding-right: 20px;
            }
            .form-table td input[type="text"], .form-table td textarea {
                width: 100%;
                max-width: 450px;
            }
            .atomic-seo-architect-settings-wrapper .button-primary {
                margin-top: 20px;
            }
        ';
        echo '</style>';
        ?>
        <div class="wrap atomic-seo-architect-settings-wrapper">
            <h1><?php esc_html_e( 'Atomic SEO Architect Settings', 'atomic-seo-architect' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'atomic-seo-architect-group' );
                do_settings_sections( 'atomic-seo-architect' );
                submit_button( esc_html__( 'Save Changes', 'atomic-seo-architect' ) );
                ?>
            </form>
            <p><?php esc_html_e( 'Need help or have questions? Visit the plugin\'s GitHub repository:', 'atomic-seo-architect' ); ?> <a href="https://github.com/ogichanchan/atomic-seo-architect" target="_blank">github.com/ogichanchan/atomic-seo-architect</a></p>
        </div>
        <?php
    }

    /**
     * Registers the plugin's settings, sections, and fields.
     */
    public function register_settings() {
        // Register a setting group to store all options as a single array.
        register_setting(
            'atomic-seo-architect-group', // Option group
            'atomic_seo_architect_options', // Option name (stores all settings as an array)
            array( $this, 'sanitize_settings' ) // Sanitize callback
        );

        // Add a settings section for general SEO configurations.
        add_settings_section(
            'atomic_seo_architect_general_section', // ID
            esc_html__( 'General SEO Settings', 'atomic-seo-architect' ), // Title
            array( $this, 'general_section_callback' ), // Callback to render section description
            'atomic-seo-architect' // Page slug
        );

        // Add individual fields to the general settings section.
        add_settings_field(
            'general_title_prefix',
            esc_html__( 'Default Title Prefix', 'atomic-seo-architect' ),
            array( $this, 'general_title_prefix_callback' ),
            'atomic-seo-architect',
            'atomic_seo_architect_general_section',
            array( 'label_for' => 'atomic_seo_architect_general_title_prefix' ) // Associate label with input
        );

        add_settings_field(
            'general_title_suffix',
            esc_html__( 'Default Title Suffix', 'atomic-seo-architect' ),
            array( $this, 'general_title_suffix_callback' ),
            'atomic-seo-architect',
            'atomic_seo_architect_general_section',
            array( 'label_for' => 'atomic_seo_architect_general_title_suffix' )
        );

        add_settings_field(
            'general_description',
            esc_html__( 'Default Meta Description', 'atomic-seo-architect' ),
            array( $this, 'general_description_callback' ),
            'atomic-seo-architect',
            'atomic_seo_architect_general_section',
            array( 'label_for' => 'atomic_seo_architect_general_description' )
        );

        add_settings_field(
            'general_robots_noindex',
            esc_html__( 'Globally Noindex', 'atomic-seo-architect' ),
            array( $this, 'general_robots_noindex_callback' ),
            'atomic-seo-architect',
            'atomic_seo_architect_general_section',
            array( 'label_for' => 'atomic_seo_architect_general_robots_noindex' )
        );

        add_settings_field(
            'general_robots_nofollow',
            esc_html__( 'Globally Nofollow', 'atomic-seo-architect' ),
            array( $this, 'general_robots_nofollow_callback' ),
            'atomic-seo-architect',
            'atomic_seo_architect_general_section',
            array( 'label_for' => 'atomic_seo_architect_general_robots_nofollow' )
        );
    }

    /**
     * Sanitize callback for plugin settings.
     * Ensures all input data is clean and safe for database storage.
     *
     * @param array $input The raw input from the settings form.
     * @return array The sanitized input.
     */
    public function sanitize_settings( $input ) {
        $new_input = array();

        if ( isset( $input['general_title_prefix'] ) ) {
            $new_input['general_title_prefix'] = sanitize_text_field( $input['general_title_prefix'] );
        }
        if ( isset( $input['general_title_suffix'] ) ) {
            $new_input['general_title_suffix'] = sanitize_text_field( $input['general_title_suffix'] );
        }
        if ( isset( $input['general_description'] ) ) {
            $new_input['general_description'] = sanitize_textarea_field( $input['general_description'] );
        }
        // Checkbox values should be explicitly set to 1 or 0.
        $new_input['general_robots_noindex'] = isset( $input['general_robots_noindex'] ) ? 1 : 0;
        $new_input['general_robots_nofollow'] = isset( $input['general_robots_nofollow'] ) ? 1 : 0;

        return $new_input;
    }

    /**
     * Callback function for the general settings section.
     * Outputs a descriptive paragraph for the section.
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__( 'These settings apply site-wide, but can be overridden on individual posts/pages.', 'atomic-seo-architect' ) . '</p>';
    }

    /**
     * Callback for the Default Title Prefix setting field.
     * Renders the input field for the global SEO title prefix.
     */
    public function general_title_prefix_callback() {
        $options = get_option( 'atomic_seo_architect_options' );
        $value   = isset( $options['general_title_prefix'] ) ? $options['general_title_prefix'] : '';
        echo '<input type="text" id="atomic_seo_architect_general_title_prefix" name="atomic_seo_architect_options[general_title_prefix]" value="' . esc_attr( $value ) . '" />';
        echo '<p class="description">' . esc_html__( 'Text to prepend to your SEO title (e.g., "Brand Name | ").', 'atomic-seo-architect' ) . '</p>';
    }

    /**
     * Callback for the Default Title Suffix setting field.
     * Renders the input field for the global SEO title suffix.
     */
    public function general_title_suffix_callback() {
        $options = get_option( 'atomic_seo_architect_options' );
        $value   = isset( $options['general_title_suffix'] ) ? $options['general_title_suffix'] : '';
        echo '<input type="text" id="atomic_seo_architect_general_title_suffix" name="atomic_seo_architect_options[general_title_suffix]" value="' . esc_attr( $value ) . '" />';
        echo '<p class="description">' . esc_html__( 'Text to append to your SEO title (e.g., " | Official Website").', 'atomic-seo-architect' ) . '</p>';
    }

    /**
     * Callback for the Default Meta Description setting field.
     * Renders the textarea for the global default meta description.
     */
    public function general_description_callback() {
        $options = get_option( 'atomic_seo_architect_options' );
        $value   = isset( $options['general_description'] ) ? $options['general_description'] : '';
        echo '<textarea id="atomic_seo_architect_general_description" name="atomic_seo_architect_options[general_description]" rows="3">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'This description will be used as the default for pages without a specific meta description.', 'atomic-seo-architect' ) . '</p>';
    }

    /**
     * Callback for the Globally Noindex setting field.
     * Renders a checkbox to globally prevent indexing.
     */
    public function general_robots_noindex_callback() {
        $options = get_option( 'atomic_seo_architect_options' );
        $checked = isset( $options['general_robots_noindex'] ) ? (bool) $options['general_robots_noindex'] : false;
        echo '<input type="checkbox" id="atomic_seo_architect_general_robots_noindex" name="atomic_seo_architect_options[general_robots_noindex]" value="1" ' . checked( 1, $checked, false ) . ' />';
        echo '<label for="atomic_seo_architect_general_robots_noindex">' . esc_html__( 'Prevent search engines from indexing the entire site (use with caution).', 'atomic-seo-architect' ) . '</label>';
    }

    /**
     * Callback for the Globally Nofollow setting field.
     * Renders a checkbox to globally prevent following links.
     */
    public function general_robots_nofollow_callback() {
        $options = get_option( 'atomic_seo_architect_options' );
        $checked = isset( $options['general_robots_nofollow'] ) ? (bool) $options['general_robots_nofollow'] : false;
        echo '<input type="checkbox" id="atomic_seo_architect_general_robots_nofollow" name="atomic_seo_architect_options[general_robots_nofollow]" value="1" ' . checked( 1, $checked, false ) . ' />';
        echo '<label for="atomic_seo_architect_general_robots_nofollow">' . esc_html__( 'Prevent search engines from following links on the entire site (use with caution).', 'atomic-seo-architect' ) . '</label>';
    }

    /**
     * Adds a meta box to post and page edit screens.
     */
    public function add_post_meta_box() {
        $screens = array( 'post', 'page' ); // Add meta box to 'post' and 'page' post types.
        foreach ( $screens as $screen ) {
            add_meta_box(
                'atomic_seo_architect_meta_box', // Unique ID for the meta box.
                esc_html__( 'Atomic SEO Settings', 'atomic-seo-architect' ), // Title of the meta box.
                array( $this, 'render_post_meta_box' ), // Callback function to render its content.
                $screen, // The screen on which to show the box.
                'normal', // The context within the screen (e.g., 'normal', 'side', 'advanced').
                'high' // The priority within the context.
            );
        }
    }

    /**
     * Renders the content of the post meta box.
     * Includes inline CSS for basic styling, adhering to the "PHP ONLY" rule.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_post_meta_box( $post ) {
        // Inline CSS for the meta box fields.
        echo '<style type="text/css">';
        echo '
            .atomic-seo-architect-metabox-field {
                margin-bottom: 15px;
            }
            .atomic-seo-architect-metabox-field label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .atomic-seo-architect-metabox-field input[type="text"],
            .atomic-seo-architect-metabox-field textarea {
                width: 100%;
                max-width: 600px;
                padding: 8px;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .atomic-seo-architect-metabox-field .description {
                font-style: italic;
                color: #666;
                margin-top: 5px;
            }
            .atomic-seo-architect-metabox-field input[type="checkbox"] {
                margin-right: 5px;
            }
        ';
        echo '</style>';

        // Use a nonce for security verification.
        wp_nonce_field( 'atomic_seo_architect_save_meta', 'atomic_seo_architect_meta_nonce' );

        // Retrieve existing meta data for the post.
        $title_override       = get_post_meta( $post->ID, '_atomic_seo_architect_title', true );
        $description_override = get_post_meta( $post->ID, '_atomic_seo_architect_description', true );
        $robots_noindex       = get_post_meta( $post->ID, '_atomic_seo_architect_robots_noindex', true );
        $robots_nofollow      = get_post_meta( $post->ID, '_atomic_seo_architect_robots_nofollow', true );
        $canonical_url        = get_post_meta( $post->ID, '_atomic_seo_architect_canonical_url', true );
        ?>

        <div class="atomic-seo-architect-metabox-field">
            <label for="atomic_seo_architect_title"><?php esc_html_e( 'SEO Title', 'atomic-seo-architect' ); ?></label>
            <input type="text" id="atomic_seo_architect_title" name="atomic_seo_architect_title" value="<?php echo esc_attr( $title_override ); ?>" placeholder="<?php esc_attr_e( 'Leave blank to use default (Post Title + Global Settings)', 'atomic-seo-architect' ); ?>" />
            <p class="description"><?php esc_html_e( 'Custom title tag for this specific content. Overrides global and default WordPress title.', 'atomic-seo-architect' ); ?></p>
        </div>

        <div class="atomic-seo-architect-metabox-field">
            <label for="atomic_seo_architect_description"><?php esc_html_e( 'Meta Description', 'atomic-seo-architect' ); ?></label>
            <textarea id="atomic_seo_architect_description" name="atomic_seo_architect_description" rows="3" placeholder="<?php esc_attr_e( 'Leave blank to use global default.', 'atomic-seo-architect' ); ?>"><?php echo esc_textarea( $description_override ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Custom meta description for this specific content. Aim for 150-160 characters.', 'atomic-seo-architect' ); ?></p>
        </div>

        <div class="atomic-seo-architect-metabox-field">
            <label><?php esc_html_e( 'Robots Meta', 'atomic-seo-architect' ); ?></label><br/>
            <input type="checkbox" id="atomic_seo_architect_robots_noindex" name="atomic_seo_architect_robots_noindex" value="1" <?php checked( '1', $robots_noindex ); ?> />
            <label for="atomic_seo_architect_robots_noindex"><?php esc_html_e( 'Noindex (Prevent search engines from indexing this page)', 'atomic-seo-architect' ); ?></label><br/>
            <input type="checkbox" id="atomic_seo_architect_robots_nofollow" name="atomic_seo_architect_robots_nofollow" value="1" <?php checked( '1', $robots_nofollow ); ?> />
            <label for="atomic_seo_architect_robots_nofollow"><?php esc_html_e( 'Nofollow (Prevent search engines from following links on this page)', 'atomic-seo-architect' ); ?></label>
            <p class="description"><?php esc_html_e( 'These settings override global robots settings for this specific page.', 'atomic-seo-architect' ); ?></p>
        </div>

        <div class="atomic-seo-architect-metabox-field">
            <label for="atomic_seo_architect_canonical_url"><?php esc_html_e( 'Canonical URL', 'atomic-seo-architect' ); ?></label>
            <input type="text" id="atomic_seo_architect_canonical_url" name="atomic_seo_architect_canonical_url" value="<?php echo esc_attr( $canonical_url ); ?>" placeholder="<?php esc_attr_e( 'Leave blank for default permalink.', 'atomic-seo-architect' ); ?>" />
            <p class="description"><?php esc_html_e( 'Specify the preferred URL for this content to avoid duplicate content issues.', 'atomic-seo-architect' ); ?></p>
        </div>
        <?php
    }

    /**
     * Saves meta box data when a post is saved.
     * Includes nonce verification and capability checks for security.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_post_meta( $post_id ) {
        // Check if our nonce is set and verified.
        if ( ! isset( $_POST['atomic_seo_architect_meta_nonce'] ) || ! wp_verify_nonce( $_POST['atomic_seo_architect_meta_nonce'], 'atomic_seo_architect_save_meta' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Define the meta keys and their corresponding $_POST keys.
        $fields = array(
            '_atomic_seo_architect_title'          => 'atomic_seo_architect_title',
            '_atomic_seo_architect_description'    => 'atomic_seo_architect_description',
            '_atomic_seo_architect_robots_noindex' => 'atomic_seo_architect_robots_noindex',
            '_atomic_seo_architect_robots_nofollow' => 'atomic_seo_architect_robots_nofollow',
            '_atomic_seo_architect_canonical_url'  => 'atomic_seo_architect_canonical_url',
        );

        // Loop through the fields and save/update post meta.
        foreach ( $fields as $meta_key => $post_key ) {
            $value = '';
            if ( isset( $_POST[ $post_key ] ) ) {
                // Sanitize values based on their expected type.
                if ( '_atomic_seo_architect_description' === $meta_key ) {
                    $value = sanitize_textarea_field( $_POST[ $post_key ] );
                } elseif ( '_atomic_seo_architect_canonical_url' === $meta_key ) {
                    $value = esc_url_raw( $_POST[ $post_key ] );
                } elseif ( '_atomic_seo_architect_robots_noindex' === $meta_key || '_atomic_seo_architect_robots_nofollow' === $meta_key ) {
                    $value = (int) $_POST[ $post_key ]; // Checkbox values are 1 or 0.
                } else {
                    $value = sanitize_text_field( $_POST[ $post_key ] );
                }
            }

            // Update or delete meta based on whether a value is provided.
            if ( ! empty( $value ) ) {
                update_post_meta( $post_id, $meta_key, $value );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    /**
     * Filters the document title parts to apply custom SEO title.
     * This function is hooked into `document_title_parts` to correctly modify the <title> tag.
     *
     * @param array $title Current title parts (e.g., 'title', 'page', 'tagline', 'site').
     * @return array Modified title parts.
     */
    public function filter_document_title_parts( $title ) {
        $options = get_option( 'atomic_seo_architect_options' );
        $post_id = get_queried_object_id(); // Get the ID of the current post, page, or category.

        $post_title_override = '';
        if ( is_singular() && $post_id ) {
            $post_title_override = get_post_meta( $post_id, '_atomic_seo_architect_title', true );
        }

        $prefix = isset( $options['general_title_prefix'] ) ? $options['general_title_prefix'] : '';
        $suffix = isset( $options['general_title_suffix'] ) ? $options['general_title_suffix'] : '';

        // If a specific title is set for the post, use it directly.
        if ( ! empty( $post_title_override ) ) {
            $title['title'] = $post_title_override;
        }

        // Apply global prefix/suffix if a title part exists (either default WP or our override).
        if ( ! empty( $title['title'] ) ) {
            if ( ! empty( $prefix ) ) {
                $title['title'] = $prefix . $title['title'];
            }
            if ( ! empty( $suffix ) ) {
                $title['title'] .= $suffix;
            }
        }

        // If a custom title (override or modified with prefix/suffix) is used,
        // it often means we don't want the default site title or tagline to be added by WordPress.
        if ( ! empty( $post_title_override ) || ( ! empty( $prefix ) || ! empty( $suffix ) && ! empty( $title['title'] ) ) ) {
            if ( isset( $title['site'] ) ) {
                unset( $title['site'] );
            }
            if ( isset( $title['tagline'] ) ) {
                unset( $title['tagline'] );
            }
        }
        
        return $title;
    }


    /**
     * Outputs SEO meta tags in the document head (meta description, robots, canonical).
     * This function is hooked into `wp_head`.
     */
    public function output_seo_meta_tags() {
        global $post; // Access the global post object for current post context.
        $options = get_option( 'atomic_seo_architect_options' ); // Retrieve global settings.
        $output  = ''; // Initialize an empty string to accumulate meta tags.

        // --- Meta Description ---
        $meta_description = '';
        if ( is_singular() && isset( $post->ID ) ) {
            $post_description = get_post_meta( $post->ID, '_atomic_seo_architect_description', true );
            if ( ! empty( $post_description ) ) {
                $meta_description = $post_description;
            }
        }
        // If no post-specific description, use the global default.
        if ( empty( $meta_description ) && isset( $options['general_description'] ) ) {
            $meta_description = $options['general_description'];
        }

        // Output the meta description tag if a description is available.
        if ( ! empty( $meta_description ) ) {
            $output .= '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $meta_description ) ) . '" />' . "\n";
        }

        // --- Robots Meta ---
        $robots_noindex_global  = isset( $options['general_robots_noindex'] ) ? (bool) $options['general_robots_noindex'] : false;
        $robots_nofollow_global = isset( $options['general_robots_nofollow'] ) ? (bool) $options['general_robots_nofollow'] : false;

        $robots_index   = 'index';
        $robots_follow  = 'follow';

        // Check for post-specific overrides for singular content.
        if ( is_singular() && isset( $post->ID ) ) {
            $post_noindex  = get_post_meta( $post->ID, '_atomic_seo_architect_robots_noindex', true );
            $post_nofollow = get_post_meta( $post->ID, '_atomic_seo_architect_robots_nofollow', true );

            // Prioritize post-specific settings.
            if ( '1' === $post_noindex ) {
                $robots_index = 'noindex';
            } elseif ( $robots_noindex_global ) { // Fallback to global setting if no post-specific override.
                $robots_index = 'noindex';
            }

            if ( '1' === $post_nofollow ) {
                $robots_follow = 'nofollow';
            } elseif ( $robots_nofollow_global ) {
                $robots_follow = 'nofollow';
            }
        } else { // For archives, homepage, etc., only global settings apply.
            if ( $robots_noindex_global ) {
                $robots_index = 'noindex';
            }
            if ( $robots_nofollow_global ) {
                $robots_follow = 'nofollow';
            }
        }
        
        // Output the robots meta tag only if it's explicitly set to noindex or nofollow,
        // to avoid redundant 'index,follow' tags.
        if ( 'noindex' === $robots_index || 'nofollow' === $robots_follow ) {
            $output .= '<meta name="robots" content="' . $robots_index . ',' . $robots_follow . '" />' . "\n";
        }


        // --- Canonical URL ---
        $canonical_url = '';
        if ( is_singular() && isset( $post->ID ) ) {
            $post_canonical = get_post_meta( $post->ID, '_atomic_seo_architect_canonical_url', true );
            if ( ! empty( $post_canonical ) ) {
                $canonical_url = $post_canonical; // Use post-specific canonical.
            }
        }
        
        // If no post-specific canonical, generate default WordPress canonical URL.
        if ( empty( $canonical_url ) ) {
            if ( is_singular() && isset( $post->ID ) ) {
                $canonical_url = get_permalink( $post );
            } elseif ( is_front_page() ) {
                $canonical_url = home_url( '/' );
            } elseif ( is_category() || is_tag() || is_tax() ) {
                $queried_object = get_queried_object();
                if ( $queried_object instanceof WP_Term ) {
                    $canonical_url = get_term_link( $queried_object->term_id, $queried_object->taxonomy );
                }
            }
            // Other archives (author, date) might need specific handling if desired.
            // For simplicity, we stick to singular, front page, and basic taxonomies.
        }

        // Output the canonical link tag if a URL is available.
        if ( ! empty( $canonical_url ) ) {
            $output .= '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
        }

        // Echo all generated meta tags to the document head.
        echo $output;
    }
}

// Instantiate the plugin class to activate all its functionalities.
new Atomic_SEO_Architect();