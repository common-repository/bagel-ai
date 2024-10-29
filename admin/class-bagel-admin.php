<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Bagel
 * @subpackage Bagel/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bagel
 * @subpackage Bagel/admin
 * @author     Your Name <contact@bagel.ai>
 */
class Bagel_AI_Admin {

    const NONCE = 'bagel-update-key';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    private $asset_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->asset_version = $this->getAssetVersion();

        add_action( 'add_meta_boxes', [ $this, 'add_ai_meta_box' ] );
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

        global $hook_suffix;

        if ('post-new.php' === $hook_suffix || 'post.php' === $hook_suffix || 'toplevel_page_bagel-ai-settings' === $hook_suffix) {
            wp_enqueue_style( $this->plugin_name . 'bootstrap-css', BAGELAI_APP_HOST_URL . '/wp/css/bootstrap.css', array(), $this->asset_version, 'all' );
            wp_enqueue_style( $this->plugin_name . 'bagel-admin-css', BAGELAI_APP_HOST_URL . '/wp/css/bagel-admin.css', array(), $this->asset_version, 'all' );
        }
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bagel_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bagel_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        wp_enqueue_script( $this->plugin_name . 'bagel-admin-js', BAGELAI_APP_HOST_URL . '/wp/js/bagel-admin.js', array('jquery'), $this->asset_version, 'false' );

	}

    public static function admin_plugin_settings_link( $links ) {

        $settings_link = '<a href="'.esc_url( BAGELAI_SUBSCRIPTION_PLANS_URL ).'">'.__('Upgrade', 'Bagel').'</a>';
        array_unshift( $links, $settings_link );

        $settings_link = '<a href="'.esc_url( self::get_page_url() ).'">'.__('Settings', 'Bagel').'</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }

    public function add_settings_page()
    {
        add_menu_page(
            'Bagel.ai',
            'Bagel.ai',
            'manage_options',
            'bagel-ai-settings',
            array( $this, 'render_settings' ),
            'dashicons-edit-large',
            5
        );
    }

    public static function is_activated() {
        $settings = self::get_settings();
        return !empty($settings['api_key']);
    }

    private function getAssetVersion() {
        return $this->version . '-' . date("W");
    }

    public function add_ai_meta_box( $post ) {

        $screens = [ 'post', 'page', 'wporg_cpt' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'bagel_ai_editor',
                'Bagel.ai - AI Content Generator',
                [ $this, 'render_ai_editor' ],
                $screen,
                'advanced',
                'default'
            );
        }
    }

    private function http_get_subscription($token) {
        $response = wp_remote_get( BAGELAI_API_SUBSCRIPTIONS_URL, [
            'headers' => ['token' => $token],
        ] );

        if (empty($response['body'])) {
            return [];
        }

        $body = json_decode($response['body'], true);

        if (!empty($body['status']) && $body['status'] == 'success') {
            return $body['subscription'];
        }

        return [];
    }

    private function http_validate_token($token) {
        $http_args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'token' => $token
            ]
        ];
        $response = wp_remote_post( BAGELAI_API_VALIDATE_TOKEN_URL, $http_args );
        if ( is_wp_error( $response ) ) {
            return false;
        }

        if (empty($response['body'])) {
            return false;
        }

        $body = json_decode($response['body'], true);
        return !empty($body['status']) && ($body['status'] == 'success');
    }

    public static function get_settings() {

        global  $wpdb ;
        $table = self::get_tablename();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC limit 1"), ARRAY_A );
    }

    public static function get_tablename() {

        global  $wpdb ;
        return $wpdb->prefix . 'bagel_ai';
    }

    private function delete_api_key() {
        global  $wpdb ;
        $table = self::get_tablename();
        $wpdb->query("DELETE FROM {$table} WHERE id > 0");
    }

    private function enter_api_key() {

        $response = ['error' => null];

        if ( empty( $_POST['key'] ) ) {
            $response['error'] = 'Please enter a valid API Key';
            return $response;
        }

        $new_token = preg_replace( '/[^a-z0-9_]/i', '', $_POST['key'] );

        if ( empty( $new_token ) ) {
            $response['error'] = 'Please enter a valid API Key';
            return $response;
        }

        $settings = self::get_settings();
        $saved_token = !empty($settings['api_key']) ? $settings['api_key'] : null;

        if ($saved_token && ($new_token == $saved_token) ) {
            $response['error'] = 'API Key already exists. Please try a different one';
            return $response;
        }

        if (!$this->http_validate_token($new_token)) {
            $response['error'] = 'Please enter a valid API Key';
            return $response;
        }

        $subscription = $this->http_get_subscription($new_token);

        if (!$subscription) {
            $response['error'] = 'Failed to load subscription. Please try again';
            return $response;
        }

        global  $wpdb ;
        $table = self::get_tablename();

        $data = [
            'api_key' => $new_token
        ];

        if (!empty($settings['id'])) {

            $is_success = $wpdb->update( $table,
                $data,
                ['id' => $settings['id']],
                ['%s']
            );

        } else {
            $is_success = $wpdb->insert( $table, $data, ['%s'] );
        }

        if($is_success === false) {

            if (!empty($wpdb->last_error)) {
                $response['error'] = 'Error: ' . $wpdb->last_error;
            } else {
                $response['error'] = 'An error occurred. Please try again';
            }

            return $response;
        }

        return $response;
    }

    public function render_settings() {

    if (isset($_POST['action']) && $_POST['action'] == 'enter-key') {

        $response = $this->enter_api_key();

        $message = null;
        $is_error = false;
        if (!empty($response['error'])) {
            $is_error = true;
            $message = $response['error'];
        } else if (self::is_activated()) {
            $message = 'Your plugin is now activated! Enjoy!';
        } else {
            $is_error = true;
            $message = 'An error occurred. Please try again. contact us if the error persists';
        }
    } else if ( isset( $_POST['action'] ) && $_POST['action'] == 'delete-key' ) {
        $this->delete_api_key();
    }

        $settings = self::get_settings();

        ?>
<style>#wpcontent{background-color: rgba(250,251,253,1)}</style>
        <div id="bagelAISetttingsWrapper" style="max-width: 90%;">
            <img src="<?php echo esc_url(BAGELAI_LOGO_URL); ?>" alt="Bagel.ai" height="30" class="image mt-4 mb-2"/>
                <div class="row">
                    <?php if(self::is_activated()){?>
                    <div class="col-md-6">
                        <div class="card mt-0 bg-white mb-4">
                            <div class="card-body">
                                <h5>What can Bagel.ai do?</h5>
                                <p class="small">Bagel.ai can help you create content. It can generate a blog outline, title, full post, summarize text, expand text, create lists, suggest ideas on how to write something, and more</p>
                            </div>
                        </div>
                        <div class="card mt-0 bg-white mb-4">
                            <div class="card-body">
                                <h5>How do I use Bagel.ai?</h5>
                                <ol class="small pb-0 mb-0">
                                    <li>Go to the page to create/edit a post or page</li>
                                    <li>In the Bagel.ai meta box, enter a prompt (see examples below)</li>
                                    <li>Click generate content</li>
                                </ol>
                            </div>
                        </div>
                        <div class="card mt-0 bg-white mb-4">
                            <div class="card-body">
                                <h5>Examples of Prompts</h5>
                                <p class="alert alert-warning"><strong>TIP:</strong> Make it clear what you want either through instructions, examples, or a combination of the two.</p>
                                <ul class="list-group small p-0 m-0">
                                    <li class="list-group-item">Suggest 5 ideas for a blog post title about the benefits of exercise for people over the age of 50</li>
                                    <li class="list-group-item">Fix the grammar in this sentence: Today we're going to the park and then we're going to the store</li>
                                    <li class="list-group-item">Generate a 500 word blog post about the history of computers and how they have impacted society</li>
                                    <li class="list-group-item">Generate an outline for a blog post about the history of computers and how they have impacted society</li>
                                    <li class="list-group-item">Expand on this sentence: The best way to exercise is to begin by</li>
                                    <li class="list-group-item">Summarize this sentence for a second-grade student: Search engine optimization is the process of improving the quality and quantity of website traffic to a website or a web page from search engines. SEO targets unpaid traffic (known as "natural" or "organic" results) rather than direct traffic or paid traffic.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card mt-0 bg-white">
                            <div class="card-body">
                                <h5>Troubleshooting</h5>
                                <p class="small">If you're having trouble getting the results you want, review this checklist:</p>
                                <ol class="small pb-0 mb-0">
                                    <li>Is your prompt clear about what the intended generation should be?</li>
                                    <li>Are there enough details or examples in your prompt?</li>
                                    <li>Did you check your prompt for mistakes?</li>
                                </ol>
                            </div>
                        </div>
                    </div>
            <?php } ?>
                    <div class="col-md-6">
            <div class="card mt-0 bg-white">
                <div class="card-body">
            <div class="bagel-ai-content-wrapper">
            <?php
            if ($message) {
                if ($is_error) {
                    echo '<div class="alert alert-danger">' . esc_html($message) . '</div>';
                } else {
                    echo '<div class="alert alert-success">' . esc_html($message) . '</div>';
                }
            }

            if (self::is_activated()) {
                $this->render_member_partial($settings);

            } else {
                $this->render_guest_partial();
            }
            ?>
                <hr/>
                <?php
                $this->render_new_token_form_partial();
            $this->render_footer_partial();
            ?>
            </div>
        </div>
            </div>
                    </div>
                </div>
            </div>
        <?php
    }

    public static function get_page_url($args = []) {
        return add_query_arg( $args, menu_page_url( 'bagel-ai-settings', false ) );
    }

    private function render_footer_partial() {
        ?>
        <hr style="margin: 2rem 0"/>
        <h5>Contact Us</h5>
        <p> If you have any questions, please contact us at <a href="mailto:contact@bagel.ai">contact@bagel.ai</a></p>
            <?php
    }
    private function render_new_token_form_partial() {
            ?>

                <div id="bagelAIApiKeyNotice"><a href="javascript:void(0)" class="bagel-ai-apikey btn btn-danger" style="font-size: 16px"> Manually enter a new API key</a></div>
                <div class="card bagel-ai-enter-token mb-4" style="display: none">
                    <div class="card-body">
                    <p>Enter your API Key. If you don't have one yet, setup a free account on <a href="<?php echo esc_url(BAGELAI_SIGNUP_URL); ?>" target="_blank">Bagel.ai</a> to receive an API Key</p>
                    <form <?php echo esc_url(self::get_page_url()); ?> method="POST">
                        <input type="hidden" name="action" value="enter-key">
                        <div class="form-group mb-2">
                            <label for="bagelAIApiKeyInput">API key</label>
                            <input type="text" id="bagelAIApiKeyInput" maxlength="500" name="key" class="form-control shadow-sm" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block">Connect API key</button>
                        </div>
                    </form>
                    </div>
                </div>
    <?php
    }

    private function render_guest_partial() {
        ?>
            <h5>One more step</h5>
            <p class="small text-muted">First set up your free Bagel.ai account to received an API Key. Once you have setup your account, you will receive an API Key which you can then enter below to activate your plugin</p>
            <a href="<?php echo esc_url(BAGELAI_SIGNUP_URL); ?>" class="btn btn-primary" target="_blank">Set up your free Bagel.ai account</a>

        <?php
    }

    public function render_member_partial(array $settings) {
        ?>
        <h5>Subscription</h5>
        <p><a href="<?php echo esc_url(BAGELAI_APP_HOST_URL); ?>" target="_blank">Upgrade, downgrade, or cancel</a> subscription</p>
        <hr style="margin: 2rem 0"/>
        <p>You API Key is connected to this account</p>
        <form method="POST" action="<?php echo esc_url(self::get_page_url()); ?>">
            <input type="hidden" name="action" value="delete-key">
            <input class="btn btn-sm btn-outline-secondary" type="submit" value="Disconnect api key" onclick="return confirm('Are you sure you want to disconnect your API Key? Disconnecting your API Key will turn off the plugin. If you also want to cancel your subscription, please log into your account on Bagel.ai.')">
        </form>
        <?php

    }

    public function render_ai_editor( $post ) {

        $settings = self::get_settings();

        if (!self::is_activated()) {
            echo '<p class="alert alert-danger"><a href="'.esc_url( self::get_page_url() ).'">Configure Bagel.ai</a> and start writing better content faster</p>';
            return;
        }

        ?>
            <div class="bagel-ai-container">
                <div class='alert alert-warning bagel-ai-hide' id="bagelAINotice"></div>
                <div class="row">
                    <div id="bagel-ai-form" class="col-md-4">
                        <div id="bagelAIForm">
                            <div class="form-group mb-3">
                                <label for="bagelAIPromptInput" class="text-muted">Prompt:</label>
                                <textarea aria-describedby="bagelAIPromptInputHelp" class="form-control shadow-sm" name="prompt" id="bagelAIPromptInput" rows="3" placeholder="e.g. Generate an outline for a blog on the history of computers"></textarea>
                                <small id="bagelAIPromptInputHelp" class="form-text text-muted">Go to <a href="<?php echo esc_url( self::get_page_url() ); ?>" target="_blank">Bagel.ai's settings for examples</a> of how to write a prompt</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" id="bagelAISubmitBtn" class="btn btn-primary btn-block">Generate Content</button>
                                <button class="btn btn-primary btn-block bagel-ai-hide" id="bagelAISubmittingBtn" type="button" disabled>
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Generating...
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="bagel-ai-results" class="col-md-8">
                        <div class="form-group">
                            <label for="bagelAIResults" class="text-muted">Results:</label>
                            <textarea class="form-control shadow-sm" id="bagelAIResults" rows="3" placeholder="Your generated content will be displayed here. You can then copy and paste the portions you want into your post"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        <input type="hidden" id="bagelAICURL" value="<?php echo esc_url(BAGELAI_API_COMPLETIONS_URL); ?>">
        <input type="hidden" id="bagelAIApiToken" value="<?php echo esc_textarea($settings['api_key']); ?>">
        <style>
            .bagel-ai-hide{display:none}
        </style>
        <?php

    }
}
